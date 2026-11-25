# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

SCM TaxVault: an internal Laravel 12 + Vue 3 SPA that archives SCM "programs" (supplier deals with DPP/PPN/total, invoice/PO/SJ numbers) and tracks whether each program has its three required documents: Invoice, Faktur Pajak, and Memo/DO. UI text, code comments, commit messages, and test names are in Bahasa Indonesia; match that when editing.

`README.md` is detailed but partly stale. For example, it says tests are only skeletons and that the delete-document route is missing, and both are no longer true. Trust the code over the README.

## Commands

```bash
composer run dev              # serve + queue:listen + pail logs + vite, all at once
php artisan serve             # backend only; .env SERVER_PORT=8010 (fixed to avoid clashing with other local projects)
npm run dev                   # Vite on fixed port 5190 (strictPort), HMR pinned to 127.0.0.1
npm run build                 # required to pass for any frontend change

php artisan test                                  # PHPUnit (SQLite :memory:, see phpunit.xml)
php artisan test --filter=DocumentAutoUploadTest  # single class or method
npm run test:frontend                             # node:test suite in tests/frontend
node --test tests/frontend/pdfSplit.test.js       # single frontend test file

vendor/bin/pint --test        # PHP style check (vendor/bin/pint to fix)
php artisan route:list --path=api

php artisan migrate
php artisan db:seed --class=ScmDataSeeder   # demo users + sample programs; plain db:seed does NOT run this
php artisan arsip:create-admin              # interactive Admin SCM account creation
php artisan arsip:documents-to-s3           # move locally stored program documents to S3
npm run generate:template                   # rebuild public/templates/Template_Import_Arsip_Program_SCM.*
```

`phpunit.xml` blanks `AWS_ENDPOINT` so tests never hit real S3. Tests that cover S3 or AI set a fake endpoint and use `Http::fake`. Follow the same pattern for any new outbound HTTP.

## Architecture

### Backend
- **All API routes are in `routes/web.php`** under the `api` prefix. There is no `routes/api.php`, no Sanctum, and CSRF is disabled for `api/*` in `bootstrap/app.php`. A catch-all `/{any?}` (excluding `api`) returns the `app` Blade view for the SPA.
- **No real auth on `/api`.** Role-based restrictions in `ProgramController` read the caller's role from the `X-User-Role` header (or a `user_role` input) sent by the frontend. "Staff Gudang" may only touch Memo/DO, and finance roles may not. That header is trust-the-client; it is not a security boundary. The only token-protected group is `/api/integrations/whms/*` (`EnsureWhmsIntegrationToken`, bearer token = `services.whms.token` / `WHMS_INTEGRATION_TOKEN`), which is server-to-server ingestion of Memo/DO documents from the warehouse system.
- **`ProgramController`** (~1300 lines) holds most domain logic: program CRUD with duplicate validation, manual document upload, AI auto-upload, Excel import, and raw-import history.
- **Document storage** goes through `App\Services\ProgramDocumentStorage`. It uploads to SeaweedFS (S3-compatible, custom SigV4 in `SeaweedStorageService`, not Flysystem) under `dokumen_program/{programId}/...`. If S3 is unconfigured or fails, it falls back to `public/uploads/documents`. Whether a file lives in S3 or locally is determined only by the `file_path` prefix (`isInS3`). Always read, delete, and serve files through this class (`GET /api/programs/{id}/documents/{docId}/file` streams them).
- **AI auto-matching flow** (`POST /api/documents/auto-upload`):
  1. `DocumentAiAnalysisService` sends the file to an OpenAI-compatible endpoint (`services.ai.*`, `OPENAI_COMPATIBLE_*`) and extracts the document type, numbers, parties, and amounts.
  2. `DocumentProgramMatcher` attaches the document only when it locks onto exactly one program. That requires a number key (No. SJ within the program's PO/SJ, which appears as "Referensi" on a faktur, or the same invoice/faktur number, or the same DPP plus a similar name as a weaker key), the same seller and buyer entities, and a period that doesn't conflict. Otherwise it returns `review` with candidates.
  3. Every attempt is written to `document_upload_logs` (`DocumentUploadLog`), which the History view shows. Users can skip an entry via `/api/document-logs/{id}/skip`.
- **Document type names differ between layers.** The frontend uses `invoice`, `faktur_pajak`, `mou`; the DB (`program_documents.type`) uses `invoice`, `faktur`, `memo`. The controller maps between them in several places, so keep the mapping consistent when you add code paths. One document per type per program (`updateOrCreate`).
- **Settings, role permissions, and master data are JSON files, not DB tables.** They live in `storage/app/settings.json` and `storage/app/master_data.json` (categories, brands, companies, gudang, suppliers), with defaults in the static `getDefault*()` methods of `AdminSettingController` and `MasterDataController`.
- Outbound integrations: `WhatsAppService` (WAGHub, OTP and approval notifications) and `SeaweedStorageService`. Both fail soft when their env vars are empty.
- Program IDs are strings, not auto-increment ints or UUIDs.

### Frontend (`resources/js`)
- Vue 3 + Vue Router + Tailwind 4. UI primitives in `components/ui` are shadcn-vue style (reka-ui/radix-vue, `cva`, `lib/utils.ts` `cn()`). The `@` alias points to `resources/js`.
- **`store/taxStore.js`** (~2700 lines) is a hand-rolled reactive singleton (`useTaxStore()`, no Pinia). It does all `fetch` calls to `/api`, maps backend rows into the frontend shape (`mapBackendProgram`), caches programs, users, master data, and role permissions in localStorage (`scm_taxvault_*` keys) as an offline fallback, and exports pure helpers such as `getCompleteness`, `formatRupiah`, and the PPN/PPh logic. When the API response shape changes, update `mapBackendProgram`.
- Login state is the localStorage user (`store/authSession.js`). The router guard (`resolveAuthRedirect`) requires login for non-public routes and Admin SCM for `users`, `settings`, `permissions`, and `master-data`.
- `store/documentUploadQueue.js` is a module-level queue for bulk AI auto-upload. It survives modal close and navigation but not a reload; a `beforeunload` warning guards it.
- Client-side helpers: `utils/pdfSplit.js` (pdf-lib) splits multi-page PDFs before upload, `utils/documentDb.js` caches document blobs in IndexedDB for preview, and `utils/programSearch.js` handles program-picker search. Excel import parsing happens in the browser with SheetJS; the backend receives JSON rows plus the raw file, which is stored in `raw_imports`.
- Frontend tests (`tests/frontend/*.test.js`) use plain `node:test` and import pure functions straight from `resources/js`. Keep logic you want tested in framework-free modules.

## Conventions
- Add new migrations; never edit ones that may have run on shared environments.
- When adding env vars, update `.env.example` too.
- Work branches come off `main` (`feat/…`, `fix/…`, `docs/…`).
