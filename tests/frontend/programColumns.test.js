import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { parseHiddenColumns, availableColumns, PROGRAM_COLUMNS } from '../../resources/js/store/programColumns.js';

describe('pilihan kolom tabel arsip program', () => {
    it('membaca kolom tersembunyi dan mengabaikan kunci yang tidak dikenal', () => {
        assert.deepEqual(parseHiddenColumns('["dpp","ppn","kolom_lama"]'), ['dpp', 'ppn']);
    });

    it('data kosong atau rusak dianggap semua kolom tampil', () => {
        for (const raw of [null, '', '{bukan json', '{"dpp":true}']) {
            assert.deepEqual(parseHiddenColumns(raw), [], String(raw));
        }
    });

    it('kolom master dan keuangan hanya tersedia untuk role yang boleh melihatnya', () => {
        const gudang = availableColumns({ canViewMaster: false, canViewFinance: false }).map(c => c.key);
        assert.deepEqual(gudang, ['date', 'doc_invoice', 'doc_faktur_pajak', 'doc_mou', 'completeness']);

        const admin = availableColumns({ canViewMaster: true, canViewFinance: true });
        assert.equal(admin.length, PROGRAM_COLUMNS.length);
    });
});
