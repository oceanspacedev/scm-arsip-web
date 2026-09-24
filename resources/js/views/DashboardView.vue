<template>
  <div class="space-y-5 select-none">
    <!-- Header with Title & Fiscal Year Filter -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 min-w-0">
      <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-sans">
          Dashboard Program SCM
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          Ringkasan arsip dokumen & rekap perpajakan program
        </p>
      </div>

      <!-- Fiscal Year Filter Dropdown -->
      <div class="relative self-start sm:self-auto">
        <button
          type="button"
          class="inline-flex items-center gap-2 px-3.5 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs transition-colors cursor-pointer"
          @click="showYearMenu = !showYearMenu"
        >
          <Calendar class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" />
          <span>{{ currentYearLabel }}</span>
          <ChevronDown class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 ml-0.5 transition-transform" :class="{ 'rotate-180': showYearMenu }" />
        </button>

        <div
          v-if="showYearMenu"
          class="absolute right-0 mt-1.5 w-48 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-xl py-1.5 z-30 text-xs animate-in fade-in zoom-in-95 duration-100"
        >
          <button
            v-for="yr in yearOptions"
            :key="yr.value"
            type="button"
            class="w-full text-left px-3.5 py-2 hover:bg-slate-50 dark:hover:bg-slate-800/80 flex items-center justify-between cursor-pointer transition-colors"
            :class="selectedYear === yr.value ? 'font-semibold text-blue-600 dark:text-blue-400 bg-blue-50/60 dark:bg-blue-950/40' : 'text-slate-700 dark:text-slate-300'"
            @click="chooseYear(yr.value)"
          >
            <span>{{ yr.label }}</span>
            <span v-if="selectedYear === yr.value" class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400"></span>
          </button>
        </div>
      </div>
    </div>

    <!-- 4 Metric Cards Row matching screenshot -->
    <DashboardMetricCards />

    <!-- Two Charts Grid Row matching screenshot -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-stretch">
      <!-- Left (Rekap DPP & PPN per Bulan) -->
      <div class="lg:col-span-7 xl:col-span-8 flex flex-col">
        <MonthlyTaxChart class="h-full" />
      </div>

      <!-- Right (Supplier Teratas) -->
      <div class="lg:col-span-5 xl:col-span-4 flex flex-col">
        <TopSuppliersChart class="h-full" />
      </div>
    </div>

    <!-- Bottom Section: Peringatan Dokumen Belum Lengkap -->
    <div>
      <IncompleteAlertSection />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Calendar, ChevronDown } from 'lucide-vue-next';
import DashboardMetricCards from '../components/dashboard/DashboardMetricCards.vue';
import MonthlyTaxChart from '../components/dashboard/MonthlyTaxChart.vue';
import TopSuppliersChart from '../components/dashboard/TopSuppliersChart.vue';
import IncompleteAlertSection from '../components/dashboard/IncompleteAlertSection.vue';
import { useTaxStore } from '../store/taxStore';

const store = useTaxStore();
const showYearMenu = ref(false);

const selectedYear = computed(() => store.state.selectedFiscalYear || String(new Date().getFullYear()));

const yearOptions = computed(() => {
  // Kumpulkan semua tahun dari data program
  const years = new Set();
  (store.programs?.value || []).forEach(p => {
    const date = p.program_date || p.due_date || '';
    const yr = String(date).slice(0, 4);
    if (yr && /^\d{4}$/.test(yr)) years.add(yr);
  });

  // Selalu tampilkan tahun saat ini dan 2 tahun sebelumnya
  const currentYear = new Date().getFullYear();
  for (let y = currentYear; y >= currentYear - 2; y--) {
    years.add(String(y));
  }

  const sortedYears = Array.from(years).sort((a, b) => Number(b) - Number(a));

  return [
    ...sortedYears.map(y => ({ value: y, label: `Tahun Pajak ${y}` })),
    { value: 'all', label: 'Semua Tahun Pajak' }
  ];
});

const currentYearLabel = computed(() => {
  const found = yearOptions.value.find(o => o.value === selectedYear.value);
  return found ? found.label : `Tahun Pajak ${selectedYear.value}`;
});

function chooseYear(yr) {
  store.setFiscalYear(yr);
  showYearMenu.value = false;
}
</script>
