<template>
  <div class="space-y-6">
    <!-- Judul + pilihan tahun pajak -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 min-w-0">
      <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-medium text-slate-900 dark:text-slate-100">Dashboard program SCM</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Ringkasan arsip dokumen dan rekap pajak program</p>
      </div>
      <div class="w-full sm:w-52">
        <SearchSelect
          :model-value="selectedYear"
          aria-label="Tahun pajak"
          :options="yearOptions"
          border-class="border-slate-200 dark:border-slate-700"
          @update:model-value="chooseYear"
        />
      </div>
    </div>

    <!-- Ringkasan angka -->
    <DashboardMetricCards />

    <!-- Rekap bulanan + supplier teratas -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-stretch">
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
import { computed } from 'vue';
import DashboardMetricCards from '../components/dashboard/DashboardMetricCards.vue';
import MonthlyTaxChart from '../components/dashboard/MonthlyTaxChart.vue';
import TopSuppliersChart from '../components/dashboard/TopSuppliersChart.vue';
import IncompleteAlertSection from '../components/dashboard/IncompleteAlertSection.vue';
import SearchSelect from '../components/ui/SearchSelect.vue';
import { useTaxStore } from '../store/taxStore';

const store = useTaxStore();

// Tahun yang sedang dipakai: pilihan pengguna, atau tahun terbaru yang punya data
const selectedYear = computed(() => store.effectiveFiscalYear.value);

const yearOptions = computed(() => {
  // Tahun yang punya data program, ditambah tahun berjalan
  const years = new Set([String(new Date().getFullYear())]);
  (store.programs?.value || []).forEach(p => {
    const yr = String(p.program_date || p.due_date || '').slice(0, 4);
    if (/^\d{4}$/.test(yr)) years.add(yr);
  });

  return [
    ...Array.from(years).sort((a, b) => Number(b) - Number(a)).map(y => ({ value: y, label: `Tahun pajak ${y}` })),
    { value: 'all', label: 'Semua tahun' },
  ];
});

function chooseYear(yr) {
  store.setFiscalYear(yr);
}
</script>
