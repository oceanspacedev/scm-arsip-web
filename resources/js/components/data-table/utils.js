// Fungsi bantu DataTable (TanStack Table v8)

/** Terapkan updater TanStack (fungsi atau nilai) ke ref Vue */
export function valueUpdater(updaterOrValue, target) {
    target.value = typeof updaterOrValue === 'function' ? updaterOrValue(target.value) : updaterOrValue;
}

/** Filter multi-select: nilai baris salah satu dari pilihan */
export function inSelection(row, columnId, selected) {
    return !selected?.length || selected.includes(row.getValue(columnId));
}

/** Filter multi-select untuk kolom yang nilainya daftar tag: salah satu tag dipilih */
export function someTagSelected(row, columnId, selected) {
    if (!selected?.length) return true;
    const tags = row.getValue(columnId) || [];
    return selected.some(tag => tags.includes(tag));
}

/** Filter rentang tanggal; nilai baris berupa timestamp (ms), filter { from: Date, to: Date } */
export function inDateRange(row, columnId, range) {
    if (!range?.from || !range?.to) return true;
    const time = row.getValue(columnId);
    if (!time) return false;
    const from = new Date(range.from.getFullYear(), range.from.getMonth(), range.from.getDate()).getTime();
    const to = new Date(range.to.getFullYear(), range.to.getMonth(), range.to.getDate(), 23, 59, 59, 999).getTime();
    return time >= from && time <= to;
}
