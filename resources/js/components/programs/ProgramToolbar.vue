<template>
  <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 shadow-2xs space-y-3.5">
    <!-- Row 1: Search & Status & Bulan -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3.5">
      <!-- 1. PENCARIAN -->
      <div class="md:col-span-6 lg:col-span-6">
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-sans">
          PENCARIAN
        </label>
        <div class="relative">
          <Search class="w-4 h-4 text-slate-400 dark:text-slate-500 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="store.state.searchQuery"
            type="text"
            placeholder="Cari program, vendor, no. invoice, PO/SJ, company..."
            class="w-full pl-9 pr-8 py-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
          />
          <button
            v-if="store.state.searchQuery"
            type="button"
            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-0.5 cursor-pointer"
            @click="store.state.searchQuery = ''"
            title="Hapus pencarian"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>
      </div>

      <!-- 2. STATUS KELENGKAPAN -->
      <div class="md:col-span-3 lg:col-span-3 relative" ref="statusDropdownRef">
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-sans">
          STATUS KELENGKAPAN
        </label>
        <button
          type="button"
          class="w-full flex items-center justify-between px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          @click="toggleDropdown('status')"
        >
          <span class="truncate">{{ currentStatusLabel }}</span>
          <ChevronDown class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'status' }" />
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="openDropdown === 'status'"
          class="absolute left-0 mt-1.5 w-56 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="opt in statusOptions"
            :key="opt.value"
            type="button"
            class="w-full flex items-center justify-between px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedStatus === opt.value }"
            @click="selectStatus(opt.value)"
          >
            <span>{{ opt.label }}</span>
            <Check
              v-if="store.state.selectedStatus === opt.value"
              class="w-4 h-4 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>

      <!-- 3. BULAN -->
      <div class="md:col-span-3 lg:col-span-3 relative" ref="monthDropdownRef">
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-sans">
          BULAN
        </label>
        <button
          type="button"
          class="w-full flex items-center justify-between px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          @click="toggleDropdown('month')"
        >
          <span class="truncate">{{ currentMonthLabel }}</span>
          <ChevronDown class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'month' }" />
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="openDropdown === 'month'"
          class="absolute right-0 sm:left-0 mt-1.5 w-48 max-h-60 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="m in monthsList"
            :key="m.value"
            type="button"
            class="w-full flex items-center justify-between px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedMonth === m.value }"
            @click="selectMonth(m.value)"
          >
            <span>{{ m.label }}</span>
            <Check
              v-if="store.state.selectedMonth === m.value"
              class="w-4 h-4 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- Row 2: Kategori, Company Name, Supplier & Reset -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-12 gap-3.5 pt-1">
      <!-- 4. KATEGORI -->
      <div class="md:col-span-4 relative" ref="categoryDropdownRef">
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-sans">
          KATEGORI
        </label>
        <button
          type="button"
          class="w-full flex items-center justify-between px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          @click="toggleDropdown('category')"
        >
          <span class="truncate">{{ store.state.selectedCategory || 'Semua Kategori' }}</span>
          <ChevronDown class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'category' }" />
        </button>

        <div
          v-if="openDropdown === 'category'"
          class="absolute left-0 mt-1.5 w-60 max-h-64 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="cat in categoriesList"
            :key="cat"
            type="button"
            class="w-full flex items-center justify-between px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer truncate"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedCategory === cat }"
            @click="selectCategory(cat)"
          >
            <span class="truncate">{{ cat }}</span>
            <Check
              v-if="store.state.selectedCategory === cat"
              class="w-4 h-4 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>

      <!-- 5. COMPANY NAME -->
      <div class="md:col-span-4 relative" ref="companyDropdownRef">
        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-sans">
          COMPANY NAME
        </label>
        <button
          type="button"
          class="w-full flex items-center justify-between px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          @click="toggleDropdown('company')"
        >
          <span class="truncate">{{ currentCompanyLabel }}</span>
          <ChevronDown class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'company' }" />
        </button>

        <div
          v-if="openDropdown === 'company'"
          class="absolute left-0 mt-1.5 w-64 max-h-64 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="comp in companiesList"
            :key="comp"
            type="button"
            class="w-full flex items-center justify-between px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer truncate"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedCompany === comp || (comp === 'Semua Company' && store.state.selectedCompany === 'all') }"
            @click="selectCompany(comp)"
          >
            <span class="truncate">{{ comp }}</span>
            <Check
              v-if="store.state.selectedCompany === comp || (comp === 'Semua Company' && store.state.selectedCompany === 'all')"
              class="w-4 h-4 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>

      <!-- 6. SUPPLIER & RESET BUTTON -->
      <div class="md:col-span-4 flex items-end gap-2">
        <div class="flex-1 relative" ref="supplierDropdownRef">
          <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1.5 font-sans">
            SUPPLIER
          </label>
          <button
            type="button"
            class="w-full flex items-center justify-between px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
            @click="toggleDropdown('supplier')"
          >
            <span class="truncate">{{ currentSupplierLabel }}</span>
            <ChevronDown class="w-3.5 h-3.5 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'supplier' }" />
          </button>

          <div
            v-if="openDropdown === 'supplier'"
            class="absolute right-0 mt-1.5 w-64 max-h-64 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
          >
            <button
              type="button"
              class="w-full flex items-center justify-between px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer"
              :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedSupplier === 'all' }"
              @click="selectSupplier('all')"
            >
              <span>Semua Supplier</span>
              <Check
                v-if="store.state.selectedSupplier === 'all'"
                class="w-4 h-4 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
              />
            </button>
            <button
              v-for="sup in suppliersList"
              :key="sup"
              type="button"
              class="w-full flex items-center justify-between px-3.5 py-2 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer truncate"
              :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedSupplier === sup }"
              @click="selectSupplier(sup)"
            >
              <span class="truncate">{{ sup }}</span>
              <Check
                v-if="store.state.selectedSupplier === sup"
                class="w-4 h-4 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
              />
            </button>
          </div>
        </div>

        <button
          v-if="hasActiveFilters"
          type="button"
          class="h-[38px] px-3 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 text-xs font-semibold flex items-center gap-1 transition-colors cursor-pointer shrink-0"
          title="Reset semua filter"
          @click="resetAllFilters"
        >
          <RotateCcw class="w-3.5 h-3.5" />
          <span class="hidden xl:inline">Reset</span>
        </button>
      </div>
    </div>

    <!-- Divider -->
    <div class="border-t border-slate-100 dark:border-slate-800 my-2"></div>

    <!-- Bottom Summary Strip matching reference screenshot -->
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs text-slate-500 dark:text-slate-400">
      <div>
        Menampilkan <strong class="text-slate-900 dark:text-slate-100 font-bold">{{ filteredSummary.count }}</strong> program
      </div>
      <div>
        Total Invoice: <strong class="font-mono text-slate-900 dark:text-slate-100 font-bold ml-1">{{ formatRupiah(filteredSummary.totalInvoice) }}</strong>
      </div>
      <div>
        DPP: <strong class="font-mono text-slate-900 dark:text-slate-100 font-bold ml-1">{{ formatRupiah(filteredSummary.totalDpp) }}</strong>
      </div>
      <div>
        PPN: <strong class="font-mono text-slate-900 dark:text-slate-100 font-bold ml-1">{{ formatRupiah(filteredSummary.totalPpn) }}</strong>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Search, ChevronDown, X, Check, RotateCcw } from 'lucide-vue-next';
import { useTaxStore, formatRupiah } from '../../store/taxStore';

const store = useTaxStore();

const openDropdown = ref(null);
const statusDropdownRef = ref(null);
const supplierDropdownRef = ref(null);
const monthDropdownRef = ref(null);
const categoryDropdownRef = ref(null);
const companyDropdownRef = ref(null);

const suppliersList = computed(() => store.suppliersList.value);
const categoriesList = computed(() => store.categoriesList.value);
const companiesList = computed(() => store.companiesList.value);
const monthsList = store.monthsList;
const filteredPrograms = computed(() => store.filteredPrograms.value);

const statusOptions = [
  { value: 'all', label: 'Semua Status' },
  { value: 'Dokumen Lengkap', label: 'Dokumen Lengkap' },
  { value: 'Terverifikasi Pajak', label: 'Terverifikasi Pajak' },
  { value: 'Kurang Faktur Pajak', label: 'Kurang Faktur Pajak' },
  { value: 'Kurang Memo/DO', label: 'Kurang Memo/DO' },
  { value: 'Kurang Invoice', label: 'Kurang Invoice' },
  { value: 'Belum Ada Dokumen', label: 'Belum Ada Dokumen' },
];

const currentStatusLabel = computed(() => {
  const match = statusOptions.find(o => o.value === store.state.selectedStatus);
  return match ? match.label : 'Semua Status';
});

const currentMonthLabel = computed(() => {
  const match = monthsList.find(m => m.value === store.state.selectedMonth);
  return match ? match.label : 'Semua Bulan';
});

const currentCompanyLabel = computed(() => {
  if (!store.state.selectedCompany || store.state.selectedCompany === 'all') {
    return 'Semua Company';
  }
  return store.state.selectedCompany;
});

const currentSupplierLabel = computed(() => {
  if (!store.state.selectedSupplier || store.state.selectedSupplier === 'all') {
    return 'Semua Supplier';
  }
  return store.state.selectedSupplier;
});

const hasActiveFilters = computed(() => {
  return (
    !!store.state.searchQuery ||
    store.state.selectedStatus !== 'all' ||
    store.state.selectedSupplier !== 'all' ||
    store.state.selectedCategory !== 'Semua Kategori' ||
    store.state.selectedMonth !== 'all' ||
    store.state.selectedCompany !== 'all'
  );
});

function toggleDropdown(name) {
  openDropdown.value = openDropdown.value === name ? null : name;
}

function selectStatus(val) {
  store.state.selectedStatus = val;
  openDropdown.value = null;
}

function selectSupplier(val) {
  store.state.selectedSupplier = val;
  openDropdown.value = null;
}

function selectCategory(val) {
  store.state.selectedCategory = val;
  openDropdown.value = null;
}

function selectMonth(val) {
  store.state.selectedMonth = val;
  openDropdown.value = null;
}

function selectCompany(val) {
  store.state.selectedCompany = val === 'Semua Company' ? 'all' : val;
  openDropdown.value = null;
}

function resetAllFilters() {
  store.state.searchQuery = '';
  store.state.selectedStatus = 'all';
  store.state.selectedSupplier = 'all';
  store.state.selectedCategory = 'Semua Kategori';
  store.state.selectedMonth = 'all';
  store.state.selectedCompany = 'all';
  openDropdown.value = null;
}

// Close dropdowns on outside click
function handleClickOutside(event) {
  const refs = [
    statusDropdownRef.value,
    supplierDropdownRef.value,
    monthDropdownRef.value,
    categoryDropdownRef.value,
    companyDropdownRef.value
  ];
  if (!refs.some(r => r && r.contains(event.target))) {
    openDropdown.value = null;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

// Summary numbers based on filtered programs
const filteredSummary = computed(() => {
  let totalInvoice = 0;
  let totalDpp = 0;
  let totalPpn = 0;

  filteredPrograms.value.forEach(p => {
    totalInvoice += Number(p.total_invoice) || 0;
    totalDpp += Number(p.dpp) || 0;
    totalPpn += Number(p.ppn) || 0;
  });

  return {
    count: filteredPrograms.value.length,
    totalInvoice,
    totalDpp,
    totalPpn,
  };
});
</script>
