<template>
  <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200/90 dark:border-slate-800 p-3 sm:px-4 sm:py-3 shadow-2xs space-y-2.5">
    <!-- Grid 4 Columns: 8 Filter Controls Perfectly Symmetrical -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
      <!-- 1. PENCARIAN -->
      <div>
        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-sans">
          PENCARIAN
        </label>
        <div class="relative">
          <Search class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
          <input
            v-model="store.state.searchQuery"
            type="text"
            placeholder="Cari program, vendor, no. invoice..."
            class="w-full h-[32px] pl-8 pr-7 py-1 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-800 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
          />
          <button
            v-if="store.state.searchQuery"
            type="button"
            class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-0.5 cursor-pointer"
            @click="store.state.searchQuery = ''"
            title="Hapus pencarian"
          >
            <X class="w-3 h-3" />
          </button>
        </div>
      </div>

      <!-- 2. STATUS KELENGKAPAN -->
      <div class="relative" ref="statusDropdownRef">
        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-sans">
          STATUS KELENGKAPAN
        </label>
        <button
          type="button"
          class="w-full h-[32px] flex items-center justify-between px-2.5 rounded-lg border text-xs transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          :class="store.state.selectedStatus !== 'all' ? 'border-blue-400 dark:border-blue-600 bg-blue-50/50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 font-medium' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
          @click="toggleDropdown('status')"
        >
          <span class="truncate">{{ currentStatusLabel }}</span>
          <ChevronDown class="w-3 h-3 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'status' }" />
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="openDropdown === 'status'"
          class="absolute left-0 mt-1 w-60 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="opt in statusOptions"
            :key="opt.value"
            type="button"
            class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedStatus === opt.value }"
            @click="selectStatus(opt.value)"
          >
            <span>{{ opt.label }}</span>
            <Check
              v-if="store.state.selectedStatus === opt.value"
              class="w-3.5 h-3.5 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>

      <!-- 3. STATUS PAYMENT -->
      <div class="relative" ref="paymentDropdownRef">
        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-sans">
          STATUS PAYMENT
        </label>
        <button
          type="button"
          class="w-full h-[32px] flex items-center justify-between px-2.5 rounded-lg border text-xs transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          :class="store.state.selectedPaymentStatus !== 'all' ? 'border-blue-400 dark:border-blue-600 bg-blue-50/50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 font-medium' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
          @click="toggleDropdown('payment')"
        >
          <span class="truncate">{{ currentPaymentStatusLabel }}</span>
          <ChevronDown class="w-3 h-3 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'payment' }" />
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="openDropdown === 'payment'"
          class="absolute left-0 mt-1 w-60 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="opt in paymentStatusOptions"
            :key="opt.value"
            type="button"
            class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedPaymentStatus === opt.value }"
            @click="selectPaymentStatus(opt.value)"
          >
            <span>{{ opt.label }}</span>
            <Check
              v-if="store.state.selectedPaymentStatus === opt.value"
              class="w-3.5 h-3.5 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>

      <!-- 4. BULAN -->
      <div class="relative" ref="monthDropdownRef">
        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-sans">
          BULAN
        </label>
        <button
          type="button"
          class="w-full h-[32px] flex items-center justify-between px-2.5 rounded-lg border text-xs transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          :class="store.state.selectedMonth !== 'all' ? 'border-blue-400 dark:border-blue-600 bg-blue-50/50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 font-medium' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
          @click="toggleDropdown('month')"
        >
          <span class="truncate">{{ currentMonthLabel }}</span>
          <ChevronDown class="w-3 h-3 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'month' }" />
        </button>

        <!-- Dropdown Menu -->
        <div
          v-if="openDropdown === 'month'"
          class="absolute right-0 mt-1 w-52 max-h-60 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="m in monthsList"
            :key="m.value"
            type="button"
            class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedMonth === m.value }"
            @click="selectMonth(m.value)"
          >
            <span>{{ m.label }}</span>
            <Check
              v-if="store.state.selectedMonth === m.value"
              class="w-3.5 h-3.5 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>

      <!-- 5. KATEGORI -->
      <div class="relative" ref="categoryDropdownRef">
        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-sans">
          KATEGORI
        </label>
        <button
          type="button"
          class="w-full h-[32px] flex items-center justify-between px-2.5 rounded-lg border text-xs transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          :class="store.state.selectedCategory !== 'Semua Kategori' ? 'border-blue-400 dark:border-blue-600 bg-blue-50/50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 font-medium' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
          @click="toggleDropdown('category')"
        >
          <span class="truncate">{{ store.state.selectedCategory || 'Semua Kategori' }}</span>
          <ChevronDown class="w-3 h-3 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'category' }" />
        </button>

        <div
          v-if="openDropdown === 'category'"
          class="absolute left-0 mt-1 w-60 max-h-64 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="cat in categoriesList"
            :key="cat"
            type="button"
            class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer truncate"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedCategory === cat }"
            @click="selectCategory(cat)"
          >
            <span class="truncate">{{ cat }}</span>
            <Check
              v-if="store.state.selectedCategory === cat"
              class="w-3.5 h-3.5 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>

      <!-- 6. BRAND -->
      <div class="relative" ref="brandDropdownRef">
        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-sans">
          BRAND
        </label>
        <button
          type="button"
          class="w-full h-[32px] flex items-center justify-between px-2.5 rounded-lg border text-xs transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          :class="(store.state.selectedBrand !== 'all' && store.state.selectedBrand !== 'Semua Brand') ? 'border-blue-400 dark:border-blue-600 bg-blue-50/50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 font-medium' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
          @click="toggleDropdown('brand')"
        >
          <span class="truncate">{{ currentBrandLabel }}</span>
          <ChevronDown class="w-3 h-3 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'brand' }" />
        </button>

        <div
          v-if="openDropdown === 'brand'"
          class="absolute left-0 mt-1 w-60 max-h-64 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="b in brandsList"
            :key="b"
            type="button"
            class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer truncate"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedBrand === b || (b === 'Semua Brand' && store.state.selectedBrand === 'all') }"
            @click="selectBrand(b)"
          >
            <span class="truncate">{{ b }}</span>
            <Check
              v-if="store.state.selectedBrand === b || (b === 'Semua Brand' && store.state.selectedBrand === 'all')"
              class="w-3.5 h-3.5 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>

      <!-- 7. COMPANY NAME -->
      <div class="relative" ref="companyDropdownRef">
        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-sans">
          COMPANY NAME
        </label>
        <button
          type="button"
          class="w-full h-[32px] flex items-center justify-between px-2.5 rounded-lg border text-xs transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          :class="(store.state.selectedCompany !== 'all' && store.state.selectedCompany !== 'Semua Company') ? 'border-blue-400 dark:border-blue-600 bg-blue-50/50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 font-medium' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
          @click="toggleDropdown('company')"
        >
          <span class="truncate">{{ currentCompanyLabel }}</span>
          <ChevronDown class="w-3 h-3 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'company' }" />
        </button>

        <div
          v-if="openDropdown === 'company'"
          class="absolute left-0 mt-1 w-64 max-h-64 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="comp in companiesList"
            :key="comp"
            type="button"
            class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer truncate"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedCompany === comp || (comp === 'Semua Company' && store.state.selectedCompany === 'all') }"
            @click="selectCompany(comp)"
          >
            <span class="truncate">{{ comp }}</span>
            <Check
              v-if="store.state.selectedCompany === comp || (comp === 'Semua Company' && store.state.selectedCompany === 'all')"
              class="w-3.5 h-3.5 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>

      <!-- 8. SUPPLIER -->
      <div class="relative" ref="supplierDropdownRef">
        <label class="block text-[10px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-1 font-sans">
          SUPPLIER
        </label>
        <button
          type="button"
          class="w-full h-[32px] flex items-center justify-between px-2.5 rounded-lg border text-xs transition-colors cursor-pointer text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600"
          :class="(store.state.selectedSupplier !== 'all' && store.state.selectedSupplier !== 'Semua Supplier') ? 'border-blue-400 dark:border-blue-600 bg-blue-50/50 dark:bg-blue-950/30 text-blue-700 dark:text-blue-300 font-medium' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
          @click="toggleDropdown('supplier')"
        >
          <span class="truncate">{{ currentSupplierLabel }}</span>
          <ChevronDown class="w-3 h-3 text-slate-400 shrink-0 ml-1.5 transition-transform" :class="{ 'rotate-180': openDropdown === 'supplier' }" />
        </button>

        <div
          v-if="openDropdown === 'supplier'"
          class="absolute right-0 mt-1 w-64 max-h-64 overflow-y-auto rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30 animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            type="button"
            class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedSupplier === 'all' }"
            @click="selectSupplier('all')"
          >
            <span>Semua Supplier</span>
            <Check
              v-if="store.state.selectedSupplier === 'all'"
              class="w-3.5 h-3.5 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
          <button
            v-for="sup in suppliersList"
            :key="sup"
            type="button"
            class="w-full flex items-center justify-between px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors text-left cursor-pointer truncate"
            :class="{ 'font-semibold text-slate-900 dark:text-white bg-slate-50/70 dark:bg-slate-800/80': store.state.selectedSupplier === sup }"
            @click="selectSupplier(sup)"
          >
            <span class="truncate">{{ sup }}</span>
            <Check
              v-if="store.state.selectedSupplier === sup"
              class="w-3.5 h-3.5 text-slate-800 dark:text-slate-200 shrink-0 ml-2"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- Divider -->
    <div class="border-t border-slate-100 dark:border-slate-800 pt-2.5">
      <!-- Bottom Summary Strip with Reset Action -->
      <div class="flex flex-wrap items-center justify-between gap-2.5 text-xs">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-slate-500 dark:text-slate-400 text-[11px]">
          <div>
            Menampilkan <strong class="text-slate-900 dark:text-slate-100 font-semibold">{{ filteredSummary.count }}</strong> program
          </div>
          <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">•</span>
          <div>
            Total Invoice: <strong class="tabular-nums text-slate-800 dark:text-slate-200 font-semibold ml-1">{{ formatRupiah(filteredSummary.totalInvoice) }}</strong>
          </div>
          <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">•</span>
          <div>
            DPP: <strong class="tabular-nums text-slate-800 dark:text-slate-200 font-semibold ml-1">{{ formatRupiah(filteredSummary.totalDpp) }}</strong>
          </div>
          <span class="text-slate-300 dark:text-slate-700 hidden sm:inline">•</span>
          <div>
            PPN: <strong class="tabular-nums text-slate-800 dark:text-slate-200 font-semibold ml-1">{{ formatRupiah(filteredSummary.totalPpn) }}</strong>
          </div>
        </div>

        <!-- Filter Count & Reset Button -->
        <div v-if="hasActiveFilters" class="flex items-center gap-2">
          <span class="text-[10px] font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 border border-blue-200/80 dark:border-blue-800/80 px-2 py-0.5 rounded-full">
            {{ activeFiltersCount }} filter aktif
          </span>
          <button
            type="button"
            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-rose-700 dark:text-rose-300 text-[11px] font-medium transition-colors cursor-pointer shadow-2xs"
            title="Reset semua filter"
            @click="resetAllFilters"
          >
            <RotateCcw class="w-3 h-3" />
            <span>Reset</span>
          </button>
        </div>
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
const paymentDropdownRef = ref(null);
const supplierDropdownRef = ref(null);
const monthDropdownRef = ref(null);
const categoryDropdownRef = ref(null);
const brandDropdownRef = ref(null);
const companyDropdownRef = ref(null);

const suppliersList = computed(() => store.suppliersList.value);
const categoriesList = computed(() => store.categoriesList.value);
const brandsList = computed(() => store.brandsList.value);
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

const paymentStatusOptions = [
  { value: 'all', label: 'Semua Status Payment' },
  { value: 'WAITING PAYMENT', label: 'Waiting Payment' },
  { value: 'cbd', label: 'CBD' },
  { value: 'tempo', label: 'Tempo' },
  { value: 'PAID', label: 'Paid' },
];

const currentStatusLabel = computed(() => {
  const match = statusOptions.find(o => o.value === store.state.selectedStatus);
  return match ? match.label : 'Semua Status';
});

const currentPaymentStatusLabel = computed(() => {
  const match = paymentStatusOptions.find(o => o.value === store.state.selectedPaymentStatus);
  return match ? match.label : 'Semua Status Payment';
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

const currentBrandLabel = computed(() => {
  if (!store.state.selectedBrand || store.state.selectedBrand === 'all') {
    return 'Semua Brand';
  }
  return store.state.selectedBrand;
});

const currentSupplierLabel = computed(() => {
  if (!store.state.selectedSupplier || store.state.selectedSupplier === 'all') {
    return 'Semua Supplier';
  }
  return store.state.selectedSupplier;
});

const activeFiltersCount = computed(() => {
  let count = 0;
  if (store.state.searchQuery) count++;
  if (store.state.selectedStatus !== 'all') count++;
  if (store.state.selectedPaymentStatus !== 'all') count++;
  if (store.state.selectedSupplier !== 'all') count++;
  if (store.state.selectedCategory !== 'Semua Kategori') count++;
  if (store.state.selectedBrand !== 'all') count++;
  if (store.state.selectedMonth !== 'all') count++;
  if (store.state.selectedCompany !== 'all') count++;
  return count;
});

const hasActiveFilters = computed(() => {
  return activeFiltersCount.value > 0;
});

function toggleDropdown(name) {
  openDropdown.value = openDropdown.value === name ? null : name;
}

function selectStatus(val) {
  store.state.selectedStatus = val;
  openDropdown.value = null;
}

function selectPaymentStatus(val) {
  store.state.selectedPaymentStatus = val;
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

function selectBrand(val) {
  store.state.selectedBrand = val === 'Semua Brand' ? 'all' : val;
  openDropdown.value = null;
}

function resetAllFilters() {
  store.state.searchQuery = '';
  store.state.selectedStatus = 'all';
  store.state.selectedPaymentStatus = 'all';
  store.state.selectedSupplier = 'all';
  store.state.selectedCategory = 'Semua Kategori';
  store.state.selectedBrand = 'all';
  store.state.selectedMonth = 'all';
  store.state.selectedCompany = 'all';
  openDropdown.value = null;
}

// Close dropdowns on outside click
function handleClickOutside(event) {
  const refs = [
    statusDropdownRef.value,
    paymentDropdownRef.value,
    supplierDropdownRef.value,
    monthDropdownRef.value,
    categoryDropdownRef.value,
    brandDropdownRef.value,
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
