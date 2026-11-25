<template>
  <div class="space-y-3">
    <!-- Satu baris: cari, filter, kolom -->
    <div class="flex items-center gap-2">
      <div class="relative flex-1 min-w-0 sm:max-w-md">
        <Search class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" :stroke-width="1.75" />
        <input
          v-model="store.state.searchQuery"
          type="search"
          aria-label="Cari program"
          placeholder="Cari program, supplier, atau nomor"
          :class="[controlClass, 'border-slate-200 dark:border-slate-700 pl-9 pr-9']"
        />
        <button
          v-if="store.state.searchQuery"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 h-6 w-6 inline-flex items-center justify-center rounded-md text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer"
          aria-label="Hapus pencarian"
          @click="store.state.searchQuery = ''"
        >
          <X class="w-4 h-4" :stroke-width="1.75" />
        </button>
      </div>

      <button
        type="button"
        :class="[toolButtonClass, showFilters || filtersCount ? toolButtonActiveClass : toolButtonIdleClass]"
        :aria-expanded="showFilters"
        aria-controls="program-filters"
        @click="showFilters = !showFilters"
      >
        <SlidersHorizontal class="w-4 h-4 text-slate-500 dark:text-slate-400" :stroke-width="1.75" />
        <span class="hidden sm:inline">Filter</span>
        <span v-if="filtersCount" class="tabular-nums text-blue-600 dark:text-blue-400">{{ filtersCount }}</span>
      </button>

      <button
        v-if="activeFiltersCount > 0"
        type="button"
        class="h-9 px-2 text-sm text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 transition-colors cursor-pointer whitespace-nowrap"
        @click="resetAllFilters"
      >
        Reset
      </button>

      <!-- Pilih kolom tabel (desktop); pilihan diingat di browser ini -->
      <div ref="columnsMenuRef" class="relative hidden md:block ml-auto">
        <button
          type="button"
          :class="[toolButtonClass, isColumnsOpen ? toolButtonActiveClass : toolButtonIdleClass]"
          aria-haspopup="dialog"
          :aria-expanded="isColumnsOpen"
          @click="isColumnsOpen = !isColumnsOpen"
        >
          <Columns3 class="w-4 h-4 text-slate-500 dark:text-slate-400" :stroke-width="1.75" />
          <span>Kolom</span>
          <span v-if="visibleColumnCount < columnOptions.length" class="tabular-nums text-blue-600 dark:text-blue-400">{{ visibleColumnCount }}/{{ columnOptions.length }}</span>
        </button>

        <Transition
          enter-active-class="transition-[opacity,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)]"
          enter-from-class="opacity-0 -translate-y-1"
          leave-active-class="transition-opacity duration-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="isColumnsOpen"
            role="dialog"
            aria-label="Pilih kolom tabel"
            class="absolute right-0 mt-2 z-30 w-80 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-[0_8px_24px_rgba(0,0,0,0.10)] dark:shadow-[0_8px_24px_rgba(0,0,0,0.5)]"
          >
            <div class="flex items-center justify-between px-4 pt-3 pb-2">
              <p class="text-sm font-medium text-slate-900 dark:text-slate-100">Kolom yang ditampilkan</p>
              <button
                type="button"
                class="text-xs text-blue-600 dark:text-blue-400 hover:underline underline-offset-2 cursor-pointer disabled:text-slate-400 dark:disabled:text-slate-500 disabled:no-underline disabled:cursor-default"
                :disabled="visibleColumnCount === columnOptions.length"
                @click="showAllColumns"
              >
                Tampilkan semua
              </button>
            </div>
            <p class="px-4 pb-2 text-xs text-slate-500 dark:text-slate-400">Program dan tombol aksi selalu tampil</p>

            <div class="max-h-80 overflow-y-auto px-2 pb-2">
              <label
                v-for="col in columnOptions"
                :key="col.key"
                class="flex items-center gap-3 h-9 px-2 rounded-lg text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 cursor-pointer transition-colors"
              >
                <input
                  type="checkbox"
                  class="w-4 h-4 rounded accent-blue-600 cursor-pointer"
                  :checked="isColumnVisible(col.key)"
                  @change="setColumnVisible(col.key, $event.target.checked)"
                />
                <span>{{ col.label }}</span>
              </label>
            </div>
          </div>
        </Transition>
      </div>
    </div>

    <!-- Semua filter, tampil saat tombol Filter dibuka -->
    <div
      v-if="showFilters"
      id="program-filters"
      class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2"
    >
      <SearchSelect
        v-model="store.state.selectedStatus"
        aria-label="Kelengkapan dokumen"
        :options="statusOptions"
        :active="store.state.selectedStatus !== 'all'"
      />

      <SearchSelect
        v-model="store.state.selectedMonth"
        aria-label="Bulan"
        search-placeholder="Cari bulan"
        :options="monthOptions"
        :active="store.state.selectedMonth !== 'all'"
      />

      <SearchSelect
        v-if="canViewFinanceColumns"
        v-model="store.state.selectedPaymentStatus"
        aria-label="Status pembayaran"
        :options="paymentStatusOptions"
        :active="store.state.selectedPaymentStatus !== 'all'"
      />

      <template v-if="canViewMasterColumns">
        <SearchSelect
          v-model="store.state.selectedCategory"
          aria-label="Kategori"
          search-placeholder="Cari kategori"
          :options="categoryOptions"
          :active="store.state.selectedCategory !== 'Semua Kategori'"
        />

        <SearchSelect
          v-model="store.state.selectedBrand"
          aria-label="Brand"
          search-placeholder="Cari brand"
          :options="brandOptions"
          :active="store.state.selectedBrand !== 'all'"
        />

        <SearchSelect
          v-model="store.state.selectedCompany"
          aria-label="Company"
          search-placeholder="Cari company"
          :options="companyOptions"
          :active="store.state.selectedCompany !== 'all'"
        />

        <SearchSelect
          v-model="store.state.selectedSupplier"
          aria-label="Supplier"
          search-placeholder="Cari supplier"
          :options="supplierOptions"
          :active="store.state.selectedSupplier !== 'all'"
        />
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { onClickOutside, onKeyStroke } from '@vueuse/core';
import { Search, X, SlidersHorizontal, Columns3 } from 'lucide-vue-next';
import { useTaxStore } from '../../store/taxStore';
import { useProgramColumns, availableColumns } from '../../store/programColumns';
import SearchSelect from '../ui/SearchSelect.vue';

const store = useTaxStore();

// Opsi filter dalam bentuk { value, label }; opsi pertama selalu "Semua …"
const monthOptions = computed(() => store.monthsList.map(m => ({ value: m.value, label: m.value === 'all' ? 'Semua bulan' : m.label })));
const categoryOptions = computed(() => store.categoriesList.value.map(c => ({ value: c, label: c === 'Semua Kategori' ? 'Semua kategori' : c })));
const brandOptions = computed(() => store.brandsList.value.map(b => b === 'Semua Brand' ? { value: 'all', label: 'Semua brand' } : { value: b, label: b }));
const companyOptions = computed(() => store.companiesList.value.map(c => c === 'Semua Company' ? { value: 'all', label: 'Semua company' } : { value: c, label: c }));
const supplierOptions = computed(() => [{ value: 'all', label: 'Semua supplier' }, ...store.suppliersList.value.map(s => ({ value: s, label: s }))]);

const canViewMasterColumns = computed(() => store.canViewMasterColumns.value);
const canViewFinanceColumns = computed(() => store.canViewFinanceColumns.value);

const suppliersList = computed(() => store.suppliersList.value);
const categoriesList = computed(() => store.categoriesList.value);
const brandsList = computed(() => store.brandsList.value);
const companiesList = computed(() => store.companiesList.value);
const monthsList = store.monthsList;

const controlClass = 'w-full h-9 px-3 rounded-lg border bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors';
const toolButtonClass = 'h-9 px-3 inline-flex items-center justify-center gap-2 rounded-lg border text-sm cursor-pointer transition-colors whitespace-nowrap';
const toolButtonIdleClass = 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800';
const toolButtonActiveClass = 'border-slate-300 dark:border-slate-600 bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100';

// Filter yang sedang dipakai diberi garis lebih tegas supaya terlihat aktif
function borderFor(value, emptyValue) {
  return value && value !== emptyValue
    ? 'border-slate-400 dark:border-slate-500 cursor-pointer'
    : 'border-slate-200 dark:border-slate-700 cursor-pointer';
}

const statusOptions = [
  { value: 'all', label: 'Semua kelengkapan' },
  { value: 'Dokumen Lengkap', label: 'Dokumen lengkap' },
  { value: 'Terverifikasi Pajak', label: 'Terverifikasi pajak' },
  { value: 'Kurang Faktur Pajak', label: 'Kurang faktur pajak' },
  { value: 'Kurang Memo/DO', label: 'Kurang memo/DO' },
  { value: 'Kurang Invoice', label: 'Kurang invoice' },
  { value: 'Belum Ada Dokumen', label: 'Belum ada dokumen' },
];

const paymentStatusOptions = [
  { value: 'all', label: 'Semua pembayaran' },
  { value: 'WAITING PAYMENT', label: 'Waiting Payment' },
  { value: 'cbd', label: 'CBD' },
  { value: 'tempo', label: 'Tempo' },
  { value: 'PAID', label: 'Paid' },
];

// Jumlah filter di panel (tanpa pencarian)
const filtersCount = computed(() => {
  const s = store.state;
  return [
    s.selectedStatus !== 'all',
    s.selectedMonth !== 'all',
    s.selectedPaymentStatus !== 'all',
    s.selectedCategory !== 'Semua Kategori',
    s.selectedBrand !== 'all',
    s.selectedCompany !== 'all',
    s.selectedSupplier !== 'all',
  ].filter(Boolean).length;
});

const activeFiltersCount = computed(() => filtersCount.value + (store.state.searchQuery ? 1 : 0));

// Panel filter langsung terbuka bila ada filter yang sedang aktif
const showFilters = ref(filtersCount.value > 0);

function resetAllFilters() {
  store.state.searchQuery = '';
  store.state.selectedStatus = 'all';
  store.state.selectedPaymentStatus = 'all';
  store.state.selectedSupplier = 'all';
  store.state.selectedCategory = 'Semua Kategori';
  store.state.selectedBrand = 'all';
  store.state.selectedMonth = 'all';
  store.state.selectedCompany = 'all';
}

// Pemilih kolom tabel
const { isVisible: isColumnVisible, setVisible: setColumnVisible, showAll: showAllColumns } = useProgramColumns();
const columnOptions = computed(() => availableColumns({
  canViewMaster: canViewMasterColumns.value,
  canViewFinance: canViewFinanceColumns.value,
}));
const visibleColumnCount = computed(() => columnOptions.value.filter(c => isColumnVisible(c.key)).length);
const isColumnsOpen = ref(false);
const columnsMenuRef = ref(null);
onClickOutside(columnsMenuRef, () => { isColumnsOpen.value = false; });
onKeyStroke('Escape', () => { isColumnsOpen.value = false; });
</script>
