import { describe, it, beforeEach, mock } from 'node:test';
import assert from 'node:assert/strict';

// taxStore membaca localStorage saat modul dimuat; program uji disiapkan sebelum import
const storage = new Map([
    ['scm_taxvault_programs_v2', JSON.stringify([
        { id: '7', program_name: 'Program Uji', documents: [{ id: 'doc-hapus', document_type: 'invoice', file_name: 'INV-2026-000022.pdf' }] },
    ])],
]);
globalThis.localStorage = {
    getItem: (key) => (storage.has(key) ? storage.get(key) : null),
    setItem: (key, value) => storage.set(key, String(value)),
    removeItem: (key) => storage.delete(key),
};

const { markDocAsDeleted, mapBackendProgram, useTaxStore } = await import('../../resources/js/store/taxStore.js');

const visibleIds = (documents) => mapBackendProgram({ id: 5, documents }).documents.map((d) => d.id);

describe('dokumen yang sudah dihapus', () => {
    beforeEach(() => storage.delete('scm_deleted_doc_identifiers_v3'));

    it('menyembunyikan dokumen yang dihapus berdasarkan ID-nya', () => {
        markDocAsDeleted('doc-lama');

        assert.deepEqual(visibleIds([{ id: 'doc-lama', type: 'invoice', file_name: 'INV-2026-000022.pdf' }]), []);
    });

    it('tetap menampilkan unggahan ulang berkas dengan nama yang sama', () => {
        markDocAsDeleted('doc-lama');

        assert.deepEqual(
            visibleIds([
                { id: 'doc-lama', type: 'invoice', file_name: 'INV-2026-000022.pdf' },
                { id: 'doc-baru', type: 'invoice', file_name: 'INV-2026-000022.pdf' },
            ]),
            ['doc-baru']
        );
    });

    it('mengabaikan daftar dari versi sebelumnya yang dokumennya masih ada di server', () => {
        storage.set('scm_deleted_doc_identifiers_v2', JSON.stringify(['doc-lama', 'INV-2026-000022.pdf']));

        assert.deepEqual(
            visibleIds([
                { id: 'doc-lama', type: 'invoice', file_name: 'INV-2026-000022.pdf' },
                { id: 'doc-baru', type: 'invoice', file_name: 'INV-2026-000022.pdf' },
            ]),
            ['doc-lama', 'doc-baru']
        );
    });

    it('mengirim permintaan hapus ke server', async () => {
        mock.timers.enable({ apis: ['setTimeout'] }); // toast tidak perlu ditunggu
        const requests = [];
        globalThis.fetch = async (url, options = {}) => {
            requests.push(`${options.method || 'GET'} ${url}`);
            if (options.method === 'DELETE') {
                return { ok: true, json: async () => ({ success: true, program: { id: '7', title: 'Program Uji', documents: [] } }) };
            }
            return { ok: false, json: async () => ({}) };
        };

        const store = useTaxStore();
        await store.deleteDocument('7', 'doc-hapus');
        mock.timers.reset();

        assert.ok(requests.includes('DELETE /api/programs/7/documents/doc-hapus'), requests.join('\n'));
        assert.deepEqual(store.getProgramById('7').documents, []);
        assert.deepEqual(JSON.parse(storage.get('scm_deleted_doc_identifiers_v3')), ['doc-hapus']);
    });
});
