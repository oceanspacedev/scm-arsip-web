// Kolom, filter, dan format tampilan tabel Arsip Program. Hanya tampilan: tidak mengubah data.
import { getProgramBrand, getProgramCompanyName, getProgramPoSjNumber } from '../../store/taxStore';
import { formatRupiah, parseDate } from '@/lib/format';
import { inDateRange, inSelection, someTagSelected } from '@/components/data-table/utils';

export const DOC_TYPES = [
    { type: 'invoice', short: 'IN', label: 'Invoice' },
    { type: 'faktur_pajak', short: 'FP', label: 'Faktur Pajak' },
    { type: 'mou', short: 'DO', label: 'Memo/DO' },
];

// Sama dengan pilihan "Status Kelengkapan" sebelumnya
export const DOC_STATUS_OPTIONS = [
    { value: 'lengkap', label: 'Dokumen lengkap' },
    { value: 'ada_faktur', label: 'Terverifikasi pajak' },
    { value: 'kurang_invoice', label: 'Kurang invoice' },
    { value: 'kurang_faktur', label: 'Kurang faktur pajak' },
    { value: 'kurang_mou', label: 'Kurang memo/DO' },
    { value: 'belum_ada', label: 'Belum ada dokumen' },
];

export const PAYMENT_OPTIONS = [
    { value: 'WAITING PAYMENT', label: 'Waiting Payment' },
    { value: 'cbd', label: 'CBD' },
    { value: 'tempo', label: 'Tempo' },
    { value: 'PAID', label: 'Paid' },
];

export function findDocument(program, type) {
    return (program.documents || []).find(doc => doc.document_type === type) || null;
}

export function documentCount(program) {
    return DOC_TYPES.filter(doc => findDocument(program, doc.type)).length;
}

/** Tag status dokumen, dipakai filter "Status Dokumen" */
export function docStatusTags(program) {
    const has = type => Boolean(findDocument(program, type));
    const count = documentCount(program);
    const tags = [has('faktur_pajak') ? 'ada_faktur' : 'kurang_faktur'];
    if (count === DOC_TYPES.length) tags.push('lengkap');
    if (count === 0) tags.push('belum_ada');
    if (!has('invoice')) tags.push('kurang_invoice');
    if (!has('mou')) tags.push('kurang_mou');
    return tags;
}

export function normalizePayStatus(status) {
    if (!status) return 'WAITING PAYMENT';
    const text = String(status).trim();
    const lower = text.toLowerCase();
    if (lower === 'cbd' || lower === 'cbd (cash before delivery)') return 'cbd';
    if (lower === 'tempo') return 'tempo';
    if (lower === 'paid' || lower === 'lunas' || lower === 'paid (lunas)') return 'PAID';
    if (lower === 'waiting payment') return 'WAITING PAYMENT';
    return text;
}

export function formatPaymentStatus(status) {
    const normalized = normalizePayStatus(status);
    return PAYMENT_OPTIONS.find(option => option.value === normalized)?.label || normalized;
}

export function paymentBadgeVariant(status) {
    const normalized = normalizePayStatus(status);
    if (normalized === 'PAID') return 'success';
    if (normalized === 'WAITING PAYMENT') return 'warning';
    return 'outline';
}

export const programBrand = program => program.brand || getProgramBrand(program);
export const programCompany = program => getProgramCompanyName(program);
export const programPoSj = program => getProgramPoSjNumber(program);

// Pencarian di kolom yang sama dengan pencarian Arsip Program sebelumnya
const searchCache = new WeakMap();
function searchText(program) {
    if (!searchCache.has(program)) {
        searchCache.set(program, [
            program.program_name,
            program.supplier,
            program.invoice_number,
            program.npwp,
            programCompany(program),
            program.kode_gudang,
            programPoSj(program),
            program.category,
            program.faktur_number,
            program.faktur_date,
        ].filter(Boolean).join(' ').toLowerCase());
    }
    return searchCache.get(program);
}

export function programMatchesSearch(row, _columnId, query) {
    const text = String(query || '').toLowerCase().trim();
    return !text || searchText(row.original).includes(text);
}

const sumOf = key => rows => rows.reduce((sum, program) => sum + (Number(program[key]) || 0), 0);

// Kolom tersembunyi secara bawaan; bisa dimunculkan lewat menu Kolom
export const HIDDEN_BY_DEFAULT = {
    category: false,
    company: false,
    invoice_number: false,
    faktur: false,
    payment_status: false,
};

/**
 * Kolom tabel sesuai hak akses: kolom master (PO/SJ, supplier, brand, kategori, company) dan
 * kolom keuangan (nominal, invoice, faktur, payment) hanya untuk role yang boleh melihatnya.
 */
export function buildProgramColumns({ master, finance }) {
    const money = (id, label, key, extra = {}) => ({
        id,
        accessorFn: program => Number(program[key]) || 0,
        sortDescFirst: true,
        meta: { label, align: 'right', total: sumOf(key), formatTotal: formatRupiah, ...extra },
    });

    return [
        {
            id: 'program_date',
            accessorFn: program => parseDate(program.program_date)?.getTime() || 0,
            filterFn: inDateRange,
            sortDescFirst: true,
            meta: { label: 'Tanggal', sticky: 'left' },
        },
        master && {
            id: 'po_sj',
            accessorFn: programPoSj,
            meta: { label: 'No. PO/SJ' },
        },
        {
            id: 'program_name',
            accessorFn: program => program.program_name || '',
            sortingFn: 'text',
            // Kolom utama: mengambil sisa lebar; nama panjang turun ke baris berikutnya, tidak dipotong
            meta: { label: 'Program', cellClass: 'w-full min-w-[200px] whitespace-normal' },
        },
        master && {
            id: 'supplier',
            accessorFn: program => program.supplier || '',
            filterFn: inSelection,
            sortingFn: 'text',
            meta: { label: 'Supplier', cellClass: 'min-w-[170px] max-w-[220px] whitespace-normal' },
        },
        master && {
            id: 'brand',
            accessorFn: programBrand,
            filterFn: inSelection,
            // Kolom pertama yang mengalah saat ruang sempit; isinya tetap ada di panel detail
            meta: { label: 'Brand / Gudang', headClass: '@max-[1260px]/table:hidden', cellClass: '@max-[1260px]/table:hidden' },
        },
        master && {
            id: 'category',
            accessorFn: program => program.category || '',
            filterFn: inSelection,
            meta: { label: 'Kategori' },
        },
        master && {
            id: 'company',
            accessorFn: programCompany,
            filterFn: inSelection,
            meta: { label: 'Company' },
        },
        finance && {
            id: 'invoice_number',
            accessorFn: program => program.invoice_number || '',
            meta: { label: 'No. Invoice' },
        },
        finance && {
            id: 'faktur',
            accessorFn: program => program.faktur_number || '',
            meta: { label: 'Faktur Pajak' },
        },
        finance && money('dpp', 'DPP', 'dpp'),
        // PPN (= 11% DPP) mengalah di layar sempit dengan sidebar terbuka; nilainya tetap ada di panel detail & kartu ringkasan
        finance && money('ppn', 'PPN', 'ppn', { headClass: '@max-[1150px]/table:hidden', cellClass: '@max-[1150px]/table:hidden' }),
        finance && money('total_invoice', 'Total', 'total_invoice'),
        finance && {
            id: 'payment_status',
            accessorFn: program => normalizePayStatus(program.payment_status),
            filterFn: inSelection,
            meta: { label: 'Status Payment' },
        },
        {
            id: 'documents',
            accessorFn: documentCount,
            sortDescFirst: true,
            meta: { label: 'Dokumen', align: 'center' },
        },
        {
            id: 'doc_status',
            accessorFn: docStatusTags,
            getUniqueValues: docStatusTags,
            filterFn: someTagSelected,
            enableSorting: false,
            enableHiding: false,
            meta: { label: 'Status Dokumen', filterOnly: true },
        },
        {
            id: 'actions',
            enableSorting: false,
            enableHiding: false,
            meta: { label: 'Aksi', srOnlyHeader: true, align: 'right', sticky: 'right', cellClass: 'w-12' },
        },
    ].filter(Boolean);
}

export function buildProgramFacets({ master, finance }) {
    return [
        { column: 'doc_status', title: 'Status Dokumen', options: DOC_STATUS_OPTIONS },
        finance && { column: 'payment_status', title: 'Status Payment', options: PAYMENT_OPTIONS },
        master && { column: 'category', title: 'Kategori' },
        master && { column: 'brand', title: 'Brand' },
        master && { column: 'company', title: 'Company' },
        master && { column: 'supplier', title: 'Supplier' },
    ].filter(Boolean);
}
