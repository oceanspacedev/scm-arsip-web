<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat dokumen yang masuk (unggah otomatis, manual, WHMS) beserta hasilnya
     */
    public function up(): void
    {
        Schema::create('document_upload_logs', function (Blueprint $table) {
            $table->id();
            $table->string('program_id')->nullable()->index();
            $table->string('document_id')->nullable();
            $table->string('file_name');
            $table->string('document_type', 20)->nullable(); // invoice, faktur_pajak, mou
            $table->string('source', 20); // auto, manual, whms
            $table->string('status', 20)->index(); // matched, duplicate, review, failed, skipped
            $table->string('reason')->nullable(); // alasan kecocokan, mis. "No. SJ 2609000804"
            $table->string('message')->nullable(); // keterangan untuk perlu dipilih / gagal
            $table->string('number')->nullable(); // No. Invoice / No. Faktur yang terbaca
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_upload_logs');
    }
};
