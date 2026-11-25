import { reactive, computed } from 'vue';

// Kolom tabel arsip program yang bisa ditampilkan atau disembunyikan.
// Program dan Aksi selalu tampil, jadi tidak ada di daftar ini.
// group: 'master' hanya untuk role yang boleh melihat data master, 'finance' untuk data keuangan.
export const PROGRAM_COLUMNS = [
    { key: 'date', label: 'Tanggal' },
    { key: 'category', label: 'Kategori', group: 'master' },
    { key: 'brand', label: 'Brand', group: 'master' },
    { key: 'company', label: 'Company', group: 'master' },
    { key: 'supplier', label: 'Supplier', group: 'master' },
    { key: 'po_sj', label: 'No. PO/SJ', group: 'master' },
    { key: 'warehouse', label: 'Kode gudang', group: 'master' },
    { key: 'invoice_number', label: 'No. invoice', group: 'finance' },
    { key: 'faktur_number', label: 'No. faktur pajak', group: 'finance' },
    { key: 'dpp', label: 'DPP', group: 'finance' },
    { key: 'ppn', label: 'PPN', group: 'finance' },
    { key: 'total', label: 'Total', group: 'finance' },
    { key: 'payment', label: 'Pembayaran', group: 'finance' },
    { key: 'doc_invoice', label: 'Dokumen invoice' },
    { key: 'doc_faktur_pajak', label: 'Dokumen faktur pajak' },
    { key: 'doc_mou', label: 'Dokumen memo/DO' },
    { key: 'completeness', label: 'Kelengkapan' },
];

export const COLUMNS_STORAGE_KEY = 'scm_program_columns_v1';

const VALID_KEYS = new Set(PROGRAM_COLUMNS.map(c => c.key));

/** Baca daftar kolom tersembunyi dari localStorage; data rusak atau kunci lama diabaikan. */
export function parseHiddenColumns(raw) {
    if (!raw) return [];
    try {
        const parsed = JSON.parse(raw);
        return Array.isArray(parsed) ? parsed.filter(key => VALID_KEYS.has(key)) : [];
    } catch (e) {
        return [];
    }
}

/** Kolom yang boleh dilihat role ini. */
export function availableColumns({ canViewMaster, canViewFinance }) {
    return PROGRAM_COLUMNS.filter(c =>
        !c.group || (c.group === 'master' && canViewMaster) || (c.group === 'finance' && canViewFinance)
    );
}

function readStorage() {
    try {
        return parseHiddenColumns(window.localStorage.getItem(COLUMNS_STORAGE_KEY));
    } catch (e) {
        return [];
    }
}

function writeStorage(hidden) {
    try {
        window.localStorage.setItem(COLUMNS_STORAGE_KEY, JSON.stringify(hidden));
    } catch (e) {
        // Mode privat / penyimpanan diblokir: pilihan tetap berlaku sampai halaman dimuat ulang
    }
}

// Satu state untuk toolbar (pemilih kolom) dan tabel
const state = reactive({ hidden: typeof window !== 'undefined' ? readStorage() : [] });

export function useProgramColumns() {
    const hiddenSet = computed(() => new Set(state.hidden));

    function isVisible(key) {
        return !hiddenSet.value.has(key);
    }

    function setVisible(key, visible) {
        const next = new Set(state.hidden);
        if (visible) next.delete(key); else next.add(key);
        state.hidden = Array.from(next);
        writeStorage(state.hidden);
    }

    function showAll() {
        state.hidden = [];
        writeStorage(state.hidden);
    }

    return { isVisible, setVisible, showAll, hiddenKeys: computed(() => state.hidden) };
}
