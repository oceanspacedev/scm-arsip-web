import { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { matchesProgram, programMeta, programTitle } from '../../resources/js/utils/programSearch.js';

// Bentuk program di store (mapBackendProgram) dan kandidat dari server berbeda nama kolomnya
const STORE_PROGRAM = {
    id: '12',
    program_name: 'PO REALME CENTRAL JAVA',
    company_name: 'PT. COSMO TECHNOLOGY',
    supplier: 'PT. REALME MOBILE TELECOMMUNICATIONS',
    po_sj_number: '2609000804',
    invoice_no: '791/TOP/IX/26',
    faktur_number: '04002600372891514'
};
const CANDIDATE = { id: '13', title: 'PROGRAM DEALER AUG 2026 C100I', company_name: 'CV. TOP SELULAR', po_sj_number: '2609000812' };

describe('pencarian di pemilih program', () => {
    it('menemukan program lewat nama, perusahaan, No. PO/SJ, No. Invoice, atau No. Faktur', () => {
        for (const query of ['realme central', 'cosmo', '2609000804', '791/top', '04002600372891514']) {
            assert.equal(matchesProgram(STORE_PROGRAM, query), true, query);
        }
        assert.equal(matchesProgram(CANDIDATE, 'c100i top'), true, 'kandidat dari server memakai kolom title');
    });

    it('semua kata harus cocok, tanda baca pada nomor diabaikan', () => {
        assert.equal(matchesProgram(STORE_PROGRAM, 'realme jakarta'), false);
        assert.equal(matchesProgram(STORE_PROGRAM, '040.026.003'), true);
        assert.equal(matchesProgram(STORE_PROGRAM, '2004000091'), false);
        assert.equal(matchesProgram(STORE_PROGRAM, '   '), true, 'kolom cari kosong menampilkan semua');
    });

    it('label membedakan program yang namanya sama lewat No. PO/SJ', () => {
        assert.equal(programTitle(STORE_PROGRAM), 'PO REALME CENTRAL JAVA');
        assert.equal(programMeta(STORE_PROGRAM), 'PO/SJ 2609000804 · PT. COSMO TECHNOLOGY · No. Invoice 791/TOP/IX/26');
        assert.equal(programMeta(CANDIDATE), 'PO/SJ 2609000812 · CV. TOP SELULAR');
        assert.equal(programMeta({ ...STORE_PROGRAM, invoice_no: '-' }), 'PO/SJ 2609000804 · PT. COSMO TECHNOLOGY', 'nomor "-" berarti belum diisi');
    });
});
