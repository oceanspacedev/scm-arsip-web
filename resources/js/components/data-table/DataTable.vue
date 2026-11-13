<script setup>
import { computed, onMounted, ref, useSlots, watch } from 'vue';
import { useMediaQuery, useResizeObserver } from '@vueuse/core';
import {
  FlexRender,
  getCoreRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
} from '@tanstack/vue-table';
import { SearchX } from 'lucide-vue-next';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import DataTableColumnHeader from './DataTableColumnHeader.vue';
import DataTablePagination from './DataTablePagination.vue';
import DataTableToolbar from './DataTableToolbar.vue';
import { valueUpdater } from './utils';

/*
 * Tabel data reusable (TanStack Table v8). Setiap kolom wajib punya `id`. columnDef.meta:
 * - label: judul kolom (juga di menu Kolom); srOnlyHeader: judul hanya untuk pembaca layar
 * - align: 'left' | 'right' | 'center'; headClass / cellClass: class tambahan
 * - sticky: 'left' | 'right' (hanya saat tabel bisa digulir ke samping)
 * - total(rows) & formatTotal(value): isi baris total untuk kolom nominal
 * - filterOnly: kolom hanya untuk filter, tidak pernah ditampilkan
 * Isi sel bisa diganti lewat slot `cell-<id>` ({ row, value }) dan judul lewat `header-<id>`.
 */
const props = defineProps({
  columns: { type: Array, required: true },
  data: { type: Array, required: true },
  loading: { type: Boolean, default: false },
  getRowId: { type: Function, default: undefined },
  initialSorting: { type: Array, default: () => [] },
  initialColumnVisibility: { type: Object, default: () => ({}) },
  pageSizes: { type: Array, default: () => [10, 25, 50] },
  searchPlaceholder: { type: String, default: 'Cari…' },
  // Pencarian di seluruh baris: (row, columnId, query) => boolean
  globalFilterFn: { type: Function, default: undefined },
  facets: { type: Array, default: () => [] },
  dateFilter: { type: Object, default: null },
  rowLabel: { type: String, default: 'data' },
  // Mis. 'h-16' supaya semua baris setinggi baris dua-baris (tinggi baris konsisten)
  rowClass: { type: String, default: '' },
  clickableRows: { type: Boolean, default: false },
  emptyTitle: { type: String, default: 'Tidak ada data' },
  emptyDescription: { type: String, default: 'Coba ubah kata kunci atau filter.' },
  // Pilihan kolom disimpan per browser
  storageKey: { type: String, default: '' },
});
const emit = defineEmits(['row-click', 'filtered']);
const slots = useSlots();

const filterOnlyIds = new Set(props.columns.filter(column => column.meta?.filterOnly).map(column => column.id));

function initialVisibility() {
  const visibility = { ...props.initialColumnVisibility };
  if (props.storageKey) {
    try {
      const saved = JSON.parse(localStorage.getItem(props.storageKey) || 'null');
      if (saved && typeof saved === 'object') Object.assign(visibility, saved);
    } catch {
      // Penyimpanan browser tidak tersedia: pakai bawaan
    }
  }
  filterOnlyIds.forEach(id => { visibility[id] = false; });
  return visibility;
}

const sorting = ref([...props.initialSorting]);
const columnFilters = ref([]);
const columnVisibility = ref(initialVisibility());
const globalFilter = ref('');
const pagination = ref({ pageIndex: 0, pageSize: props.pageSizes[0] });

watch(columnVisibility, value => {
  if (!props.storageKey) return;
  try {
    localStorage.setItem(props.storageKey, JSON.stringify(value));
  } catch {
    // Abaikan: pilihan kolom hanya kenyamanan
  }
}, { deep: true });

const firstColumnId = props.columns[0]?.id;

// TanStack menghitung ulang baris hanya bila referensi array data berganti, sedangkan store mengubah
// data di tempat (splice saat hapus, Object.assign saat edit). Setiap perubahan isi, termasuk di dalam
// baris, menghasilkan salinan array baru supaya tabel, urutan, filter, dan total ikut diperbarui.
const dataVersion = ref(0);
watch(() => props.data, () => { dataVersion.value++; }, { deep: true });
const tableData = computed(() => {
  void dataVersion.value;
  return [...props.data];
});

const table = useVueTable({
  get data() { return tableData.value; },
  get columns() { return props.columns; },
  getRowId: props.getRowId,
  state: {
    get sorting() { return sorting.value; },
    get columnFilters() { return columnFilters.value; },
    get columnVisibility() { return columnVisibility.value; },
    get globalFilter() { return globalFilter.value; },
    get pagination() { return pagination.value; },
  },
  onSortingChange: updater => valueUpdater(updater, sorting),
  onColumnFiltersChange: updater => valueUpdater(updater, columnFilters),
  onColumnVisibilityChange: updater => valueUpdater(updater, columnVisibility),
  onGlobalFilterChange: updater => valueUpdater(updater, globalFilter),
  onPaginationChange: updater => valueUpdater(updater, pagination),
  // Pencarian khusus memeriksa seluruh baris, jadi cukup dijalankan pada satu kolom
  ...(props.globalFilterFn
    ? { globalFilterFn: props.globalFilterFn, getColumnCanGlobalFilter: column => column.id === firstColumnId }
    : {}),
  getCoreRowModel: getCoreRowModel(),
  getFilteredRowModel: getFilteredRowModel(),
  getSortedRowModel: getSortedRowModel(),
  getPaginationRowModel: getPaginationRowModel(),
  getFacetedRowModel: getFacetedRowModel(),
  getFacetedUniqueValues: getFacetedUniqueValues(),
});

const rows = computed(() => table.getRowModel().rows);
const visibleColumns = computed(() => table.getVisibleLeafColumns());
const isFiltered = computed(() => columnFilters.value.length > 0 || Boolean(globalFilter.value));

// Baris hasil filter & urutan (semua halaman), untuk ringkasan dan ekspor di halaman induk
const filteredRows = computed(() => table.getPrePaginationRowModel().rows.map(row => row.original));
watch(filteredRows, value => emit('filtered', value), { immediate: true });

const hasTotals = computed(() => visibleColumns.value.some(column => column.columnDef.meta?.total));
function totalText(column) {
  const meta = column.columnDef.meta || {};
  if (!meta.total) return '';
  const value = meta.total(filteredRows.value);
  return meta.formatTotal ? meta.formatTotal(value) : value;
}

function reset() {
  table.resetColumnFilters();
  table.setGlobalFilter('');
}

// Di layar ≥1280px tabel tidak digulir ke samping, jadi header bisa menempel di bawah top bar.
// Bila tetap lebih lebar dari kontainer (mis. banyak kolom dimunculkan), beralih ke mode gulir.
const isWide = useMediaQuery('(min-width: 1280px)');
const containerRef = ref(null);
const tableEl = ref(null);
const tableWidth = ref(0);
const containerWidth = ref(0);
onMounted(() => {
  tableEl.value = containerRef.value?.querySelector('table') || null;
});
useResizeObserver(containerRef, entries => {
  containerWidth.value = entries[0].contentRect.width;
});
// Tabel ikut diamati: lebarnya berubah saat kolom dimunculkan/disembunyikan
useResizeObserver(tableEl, () => {
  tableWidth.value = tableEl.value?.scrollWidth || 0;
});
const scrollMode = computed(() => !isWide.value || tableWidth.value > containerWidth.value + 1);

const alignClass = align => (align === 'right' ? 'text-right' : align === 'center' ? 'text-center' : '');

function stickyClass(column, part) {
  const side = column.columnDef.meta?.sticky;
  if (!scrollMode.value || !side) return '';
  const background = part === 'head' ? 'bg-background' : part === 'foot' ? 'bg-row-hover' : 'bg-background group-hover/row:bg-row-hover';
  return cn(
    'sticky',
    side === 'left' ? 'left-0 shadow-[inset_-1px_0_0_var(--border)]' : 'right-0 shadow-[inset_1px_0_0_var(--border)]',
    part === 'head' ? 'z-20' : 'z-10',
    background,
  );
}

function headClass(column) {
  const meta = column.columnDef.meta || {};
  return cn(
    'bg-background shadow-[inset_0_-1px_0_var(--border)]',
    !scrollMode.value && 'sticky top-16 z-20',
    alignClass(meta.align),
    meta.headClass,
    stickyClass(column, 'head'),
  );
}

function cellClass(column, part = 'body') {
  const meta = column.columnDef.meta || {};
  return cn(alignClass(meta.align), meta.cellClass, stickyClass(column, part));
}

function ariaSort(column) {
  const sorted = column.getIsSorted();
  if (!column.getCanSort()) return undefined;
  return sorted === 'asc' ? 'ascending' : sorted === 'desc' ? 'descending' : 'none';
}

function onRowClick(row) {
  if (props.clickableRows) emit('row-click', row.original);
}

const skeletonWidths = ['w-24', 'w-32', 'w-40', 'w-20', 'w-28'];

defineExpose({ table, reset });
</script>

<template>
  <div class="flex flex-col gap-4">
    <DataTableToolbar
      :table="table"
      :search-placeholder="searchPlaceholder"
      :facets="facets"
      :date-filter="dateFilter"
      :is-filtered="isFiltered"
      @reset="reset"
    >
      <template v-if="slots['toolbar-actions']" #actions>
        <slot name="toolbar-actions" />
      </template>
    </DataTableToolbar>

    <div ref="containerRef" class="@container/table overflow-clip rounded-lg border bg-background">
      <Table :container-class="cn('relative w-full', scrollMode ? 'overflow-x-auto' : 'overflow-x-visible')">
        <TableHeader class="[&_tr]:border-b-0">
          <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="hover:bg-transparent">
            <TableHead
              v-for="header in headerGroup.headers"
              :key="header.id"
              :class="headClass(header.column)"
              :aria-sort="ariaSort(header.column)"
            >
              <template v-if="!header.isPlaceholder">
                <slot v-if="slots[`header-${header.column.id}`]" :name="`header-${header.column.id}`" :column="header.column" />
                <span v-else-if="header.column.columnDef.meta?.srOnlyHeader" class="sr-only">{{ header.column.columnDef.meta?.label }}</span>
                <DataTableColumnHeader
                  v-else
                  :column="header.column"
                  :title="header.column.columnDef.meta?.label ?? String(header.column.columnDef.header ?? '')"
                  :align="header.column.columnDef.meta?.align"
                />
              </template>
            </TableHead>
          </TableRow>
        </TableHeader>

        <TableBody>
          <template v-if="loading">
            <TableRow v-for="index in 5" :key="`skeleton-${index}`" :class="cn('hover:bg-transparent', rowClass)">
              <TableCell v-for="(column, columnIndex) in visibleColumns" :key="column.id" :class="cellClass(column)">
                <Skeleton :class="cn('h-4', skeletonWidths[(index + columnIndex) % skeletonWidths.length], column.columnDef.meta?.align === 'right' && 'ml-auto')" />
              </TableCell>
            </TableRow>
          </template>

          <template v-else-if="rows.length">
            <TableRow
              v-for="row in rows"
              :key="row.id"
              :class="cn('group/row', rowClass, clickableRows && 'cursor-pointer focus-visible:bg-muted/50 focus-visible:outline-none')"
              :tabindex="clickableRows ? 0 : undefined"
              @click="onRowClick(row)"
              @keydown.enter.self="onRowClick(row)"
            >
              <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" :class="cellClass(cell.column)">
                <slot
                  v-if="slots[`cell-${cell.column.id}`]"
                  :name="`cell-${cell.column.id}`"
                  :row="row.original"
                  :value="cell.getValue()"
                />
                <FlexRender v-else :render="cell.column.columnDef.cell" :props="cell.getContext()" />
              </TableCell>
            </TableRow>
          </template>

          <TableRow v-else class="hover:bg-transparent">
            <TableCell :colspan="visibleColumns.length" class="h-48 whitespace-normal">
              <div class="flex flex-col items-center justify-center gap-2 text-center">
                <SearchX class="size-8 text-muted-foreground" />
                <p class="text-sm font-medium">{{ emptyTitle }}</p>
                <p class="max-w-sm text-sm text-muted-foreground">{{ emptyDescription }}</p>
                <Button v-if="isFiltered" variant="outline" size="sm" class="mt-1" @click="reset">Reset filter</Button>
              </div>
            </TableCell>
          </TableRow>
        </TableBody>

        <TableFooter v-if="hasTotals && !loading && filteredRows.length">
          <TableRow class="hover:bg-transparent">
            <TableCell
              v-for="(column, index) in visibleColumns"
              :key="column.id"
              :class="cellClass(column, 'foot')"
            >
              <span v-if="index === 0" class="text-muted-foreground">Total</span>
              <span v-else class="tabular-nums">{{ totalText(column) }}</span>
            </TableCell>
          </TableRow>
        </TableFooter>
      </Table>
    </div>

    <DataTablePagination v-if="!loading && filteredRows.length" :table="table" :page-sizes="pageSizes" :row-label="rowLabel" />
  </div>
</template>
