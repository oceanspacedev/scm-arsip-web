// Kolom & pencarian tabel Data Master. Hanya tampilan: data master tidak diubah.
import { formatNpwp } from '@/lib/format';

const lower = value => String(value || '').toLowerCase();

// Tombol ⋯ per baris. py-1.5 supaya tombol tidak menambah tinggi baris.
function actionsColumn(sticky = false) {
    return {
        id: 'actions',
        enableSorting: false,
        enableHiding: false,
        meta: {
            label: 'Aksi',
            srOnlyHeader: true,
            align: 'right',
            cellClass: 'w-12 py-1.5',
            ...(sticky ? { sticky: 'right' } : {}),
        },
    };
}

/** Kolom tabel per jenis data master; `nameLabel` untuk daftar nama (kategori, brand, company, status payment) */
export function buildMasterColumns(type, nameLabel = 'Nama') {
    if (type === 'warehouses') {
        return [
            { id: 'code', accessorFn: warehouse => warehouse.code || '', sortingFn: 'text', meta: { label: 'Kode', cellClass: 'tabular-nums' } },
            { id: 'name', accessorFn: warehouse => warehouse.name || '', sortingFn: 'text', meta: { label: 'Nama Gudang' } },
            { id: 'location', accessorFn: warehouse => warehouse.location || '', sortingFn: 'text', meta: { label: 'Lokasi', cellClass: 'w-full' } },
            actionsColumn(true),
        ];
    }
    if (type === 'suppliers') {
        return [
            { id: 'name', accessorFn: supplier => supplier.name || '', sortingFn: 'text', meta: { label: 'Supplier' } },
            { id: 'npwp', accessorFn: supplier => formatNpwp(supplier.npwp), sortingFn: 'text', meta: { label: 'NPWP' } },
            { id: 'phone', accessorFn: supplier => supplier.phone || '', sortingFn: 'text', meta: { label: 'Telepon', cellClass: 'w-full' } },
            actionsColumn(true),
        ];
    }
    return [
        { id: 'name', accessorFn: item => String(item ?? ''), sortingFn: 'text', meta: { label: nameLabel, cellClass: 'w-full whitespace-normal' } },
        actionsColumn(),
    ];
}

// Aturan PPN: baris { name, nonPpn } per kategori
export const PPN_COLUMNS = [
    { id: 'name', accessorFn: row => row.name, sortingFn: 'text', meta: { label: 'Kategori', cellClass: 'w-full whitespace-normal' } },
    { id: 'rate', accessorFn: row => (row.nonPpn ? 0 : 11), enableHiding: false, meta: { label: 'Tarif PPN', align: 'right', cellClass: 'py-1.5' } },
];

/**
 * Pencarian sama seperti halaman Data Master sebelumnya: nama; gudang: kode/nama/lokasi;
 * supplier: nama/NPWP/telepon. NPWP juga dicocokkan dengan format yang tampil di tabel.
 */
export function masterMatchesSearch(type) {
    return (row, _columnId, query) => {
        const q = String(query || '').toLowerCase().trim();
        if (!q) return true;
        const item = row.original;
        if (type === 'warehouses') return [item.code, item.name, item.location].some(value => lower(value).includes(q));
        if (type === 'suppliers') return [item.name, item.npwp, item.phone, formatNpwp(item.npwp)].some(value => lower(value).includes(q));
        if (type === 'ppn_rules') return lower(item.name).includes(q);
        return String(item).toLowerCase().includes(q);
    };
}
