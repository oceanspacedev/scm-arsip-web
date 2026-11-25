<template>
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div
      v-for="card in cards"
      :key="card.label"
      class="bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800 p-5"
    >
      <p class="text-xs text-slate-500 dark:text-slate-400">{{ card.label }}</p>
      <p class="mt-2 text-xl font-medium text-slate-900 dark:text-slate-100 tabular-nums truncate" :title="card.value">
        {{ card.value }}
      </p>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ card.note }}</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTaxStore, formatRupiah } from '../../store/taxStore';

const store = useTaxStore();
const metrics = computed(() => store.summaryMetrics.value);

const cards = computed(() => [
  { label: 'Total nilai invoice', value: formatRupiah(metrics.value.totalInvoice), note: `${metrics.value.totalPrograms} program terarsip` },
  { label: 'Total DPP', value: formatRupiah(metrics.value.totalDpp), note: 'Dasar pengenaan pajak' },
  { label: 'Total PPN (11%)', value: formatRupiah(metrics.value.totalPpn), note: 'Pajak pertambahan nilai' },
  { label: 'Siap audit', value: `${metrics.value.lengkapCount} / ${metrics.value.totalPrograms}`, note: `${metrics.value.lengkapCount} program berkas lengkap` }
]);
</script>
