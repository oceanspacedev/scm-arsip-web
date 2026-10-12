<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Program;
use App\Models\ProgramDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class ProgramDocumentStorageTest extends TestCase
{
    use RefreshDatabase;

    private const S3_ENV = [
        'AWS_ENDPOINT' => 'https://s3.test',
        'AWS_ACCESS_KEY_ID' => 'test-key',
        'AWS_SECRET_ACCESS_KEY' => 'test-secret',
        'AWS_BUCKET' => 'SCM',
        'AWS_URL' => 'https://s3.test/scm',
    ];

    private array $originalEnv = [];

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        foreach (array_keys(self::S3_ENV) as $key) {
            $this->originalEnv[$key] = $_SERVER[$key] ?? null;
        }

        Program::create(['id' => '1', 'title' => 'Program Test', 'supplier' => 'PT Vendor Test']);
    }

    protected function tearDown(): void
    {
        $this->setEnv($this->originalEnv);
        parent::tearDown();
    }

    private function setEnv(array $values): void
    {
        foreach ($values as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key], $_ENV[$key]);
                putenv($key);
            } else {
                $_SERVER[$key] = $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }

    private function uploadMemo()
    {
        return $this->post('/api/programs/1/documents', [
            'document_type' => 'mou',
            'file' => UploadedFile::fake()->create('memo.pdf', 12, 'application/pdf'),
        ], [
            'Accept' => 'application/json',
            'X-User-Role' => 'Admin SCM'
        ]);
    }

    public function test_upload_is_stored_in_s3_when_configured()
    {
        $this->setEnv(self::S3_ENV);
        Http::fake(['s3.test/*' => Http::response('', 200)]);

        $res = $this->uploadMemo();

        $res->assertOk();
        $document = ProgramDocument::firstOrFail();
        $this->assertStringStartsWith('dokumen_program/1/doc_', $document->file_path);
        $this->assertFileDoesNotExist(public_path('uploads/documents/' . basename($document->file_path)));
        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === 'https://s3.test/scm/' . $document->file_path);
        $this->assertStringEndsWith("/api/programs/1/documents/{$document->id}/file", $res->json('document.file_url'));
    }

    public function test_upload_falls_back_to_local_when_s3_is_not_configured()
    {
        $this->setEnv(array_fill_keys(array_keys(self::S3_ENV), ''));

        $this->uploadMemo()->assertOk();

        $document = ProgramDocument::firstOrFail();
        $this->assertStringStartsWith('uploads/documents/', $document->file_path);
        $this->assertFileExists(public_path($document->file_path));
        Http::assertNothingSent();

        @unlink(public_path($document->file_path));
    }

    public function test_upload_falls_back_to_local_when_s3_rejects_the_file()
    {
        $this->setEnv(self::S3_ENV);
        Http::fake(['s3.test/*' => Http::response('Access Denied', 403)]);

        $this->uploadMemo()->assertOk();

        $document = ProgramDocument::firstOrFail();
        $this->assertStringStartsWith('uploads/documents/', $document->file_path);
        $this->assertFileExists(public_path($document->file_path));

        @unlink(public_path($document->file_path));
    }

    public function test_document_file_is_served_from_s3()
    {
        $this->setEnv(self::S3_ENV);
        Http::fake(['s3.test/*' => Http::response('%PDF-test', 200, ['Content-Type' => 'application/pdf'])]);
        ProgramDocument::create([
            'id' => 'doc-1',
            'program_id' => '1',
            'type' => 'memo',
            'file_name' => 'Memo Gudang.pdf',
            'file_path' => 'dokumen_program/1/doc_x.pdf',
        ]);

        $res = $this->get('/api/programs/1/documents/doc-1/file');

        $res->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('%PDF-test', $res->getContent());
        $this->assertStringStartsWith('inline', $res->headers->get('Content-Disposition'));
    }

    public function test_deleting_document_removes_the_s3_object()
    {
        $this->setEnv(self::S3_ENV);
        Http::fake(['s3.test/*' => Http::response('', 204)]);
        ProgramDocument::create([
            'id' => 'doc-1',
            'program_id' => '1',
            'type' => 'memo',
            'file_name' => 'Memo Gudang.pdf',
            'file_path' => 'dokumen_program/1/doc_x.pdf',
        ]);

        $this->deleteJson('/api/programs/1/documents/doc-1', [], ['X-User-Role' => 'Admin SCM'])->assertOk();

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && $request->url() === 'https://s3.test/scm/dokumen_program/1/doc_x.pdf');
        $this->assertDatabaseMissing('program_documents', ['id' => 'doc-1']);
    }

    public function test_program_listing_gives_each_document_a_file_url()
    {
        ProgramDocument::create(['id' => 'doc-s3', 'program_id' => '1', 'type' => 'memo', 'file_name' => 'a.pdf', 'file_path' => 'dokumen_program/1/a.pdf']);
        ProgramDocument::create(['id' => 'doc-local', 'program_id' => '1', 'type' => 'invoice', 'file_name' => 'b.pdf', 'file_path' => 'uploads/documents/b.pdf']);

        $documents = collect($this->getJson('/api/programs')->json('programs.0.documents'))->keyBy('id');

        $this->assertStringEndsWith('/api/programs/1/documents/doc-s3/file', $documents['doc-s3']['file_url']);
        $this->assertStringEndsWith('/uploads/documents/b.pdf', $documents['doc-local']['file_url']);
    }
}
