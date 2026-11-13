<template>
  <StatGrid :values="cards.map(card => card.value)">
    <StatCard
      v-for="card in cards"
      :key="card.label"
      :label="card.label"
      :icon="card.icon"
      :value="card.value"
      :hint="card.note"
    />
  </StatGrid>
</template>

<script setup>
import { computed } from 'vue';
import { BanknotesIcon, CheckBadgeIcon, DocumentTextIcon, ReceiptPercentIcon } from '@heroicons/vue/24/outline';
import StatCard from '@/components/layout/StatCard.vue';
import StatGrid from '@/components/layout/StatGrid.vue';
import { formatNumber, formatRupiah } from '@/lib/format';
import { useTaxStore } from '../../store/taxStore';

const store = useTaxStore();
const metrics = computed(() => store.summaryMetrics.value);

// Ikon sama dengan kartu di Arsip Program: invoice = dokumen, DPP = uang, PPN = struk persen
const cards = computed(() => [
  { label: 'Total Nilai Invoice', icon: DocumentTextIcon, value: formatRupiah(metrics.value.totalInvoice), note: `${formatNumber(metrics.value.totalPrograms)} program terarsip` },
  { label: 'Total DPP', icon: BanknotesIcon, value: formatRupiah(metrics.value.totalDpp), note: 'Dasar Pengenaan Pajak' },
  { label: 'Total PPN (11%)', icon: ReceiptPercentIcon, value: formatRupiah(metrics.value.totalPpn), note: 'Pajak Pertambahan Nilai' },
  {
    label: 'Siap Audit',
    icon: CheckBadgeIcon,
    value: `${formatNumber(metrics.value.lengkapCount)} / ${formatNumber(metrics.value.totalPrograms)}`,
    note: 'Program dengan berkas lengkap',
  },
]);
</script>
