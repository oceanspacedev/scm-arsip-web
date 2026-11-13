<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Dashboard" description="Ringkasan arsip dokumen & rekap perpajakan program.">
      <template #actions>
        <!-- Tahun pajak berlaku untuk semua angka, grafik, dan daftar di bawahnya -->
        <Select :model-value="selectedYear" @update:model-value="chooseYear">
          <SelectTrigger class="w-48" aria-label="Tahun pajak">
            <SelectValue>{{ currentYearLabel }}</SelectValue>
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="yr in yearOptions" :key="yr.value" :value="yr.value">{{ yr.label }}</SelectItem>
          </SelectContent>
        </Select>
      </template>
    </PageHeader>

    <DashboardMetricCards />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-12">
      <MonthlyTaxChart class="lg:col-span-7 xl:col-span-8" />
      <TopSuppliersChart class="lg:col-span-5 xl:col-span-4" />
    </div>

    <IncompleteAlertSection />
  </div>
</template>

<script setup>
import { computed } from 'vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import DashboardMetricCards from '../components/dashboard/DashboardMetricCards.vue';
import MonthlyTaxChart from '../components/dashboard/MonthlyTaxChart.vue';
import TopSuppliersChart from '../components/dashboard/TopSuppliersChart.vue';
import IncompleteAlertSection from '../components/dashboard/IncompleteAlertSection.vue';
import { useTaxStore } from '../store/taxStore';

const store = useTaxStore();

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

// Label tetap tampil walau tahun tersimpan tidak ada di daftar pilihan
const currentYearLabel = computed(() => {
  const found = yearOptions.value.find(o => o.value === selectedYear.value);
  return found ? found.label : `Tahun Pajak ${selectedYear.value}`;
});

function chooseYear(yr) {
  if (yr) store.setFiscalYear(yr);
}
</script>
