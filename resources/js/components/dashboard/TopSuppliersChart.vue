<template>
  <div class="bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800 p-5 flex flex-col">
    <div class="mb-5">
      <h3 class="text-sm font-medium text-slate-900 dark:text-slate-100">Supplier teratas</h3>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Berdasarkan nilai invoice</p>
    </div>

    <!-- Daftar peringkat: bar tipis, hanya supplier teratas yang memakai aksen -->
    <ol v-if="suppliers.length" class="space-y-4">
      <li v-for="(supplier, idx) in suppliers" :key="supplier.name">
        <div class="flex items-baseline justify-between gap-3 text-xs">
          <span class="truncate text-slate-700 dark:text-slate-300" :title="supplier.name">{{ supplier.name }}</span>
          <span class="shrink-0 tabular-nums text-slate-500 dark:text-slate-400">{{ formatShort(supplier.value) }}</span>
        </div>
        <div class="mt-2 h-1.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
          <div
            class="supplier-bar h-full rounded-full"
            :class="idx === 0 ? 'bg-blue-600 dark:bg-blue-500' : 'bg-slate-400 dark:bg-slate-500'"
            :style="{ width: Math.max((supplier.value / maxVal) * 100, 2) + '%', '--delay': (idx * 0.05) + 's' }"
          ></div>
        </div>
      </li>
    </ol>

    <div v-else class="flex-1 flex flex-col items-center justify-center py-8 text-center">
      <p class="text-sm text-slate-600 dark:text-slate-300">Belum ada invoice di periode ini</p>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Supplier muncul setelah program punya nilai invoice</p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useTaxStore } from '../../store/taxStore';

const store = useTaxStore();

const suppliers = computed(() => {
  const map = new Map();
  (store.dashboardPrograms.value || []).forEach(p => {
    const name = p.supplier || 'Lainnya';
    map.set(name, (map.get(name) || 0) + (Number(p.total_invoice) || 0));
  });

  return Array.from(map.entries())
    .map(([name, value]) => ({ name, value }))
    .filter(s => s.value > 0)
    .sort((a, b) => b.value - a.value)
    .slice(0, 6);
});

const maxVal = computed(() => Math.max(...suppliers.value.map(s => s.value), 1));

function formatShort(val) {
  if (val >= 1000000000) return 'Rp ' + (val / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
  if (val >= 1000000) return 'Rp ' + Math.round(val / 1000000).toLocaleString('id-ID') + ' Jt';
  return 'Rp ' + Math.round(val).toLocaleString('id-ID');
}
</script>

<style scoped>
.supplier-bar {
  transform-origin: left center;
  animation: growBar 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: var(--delay, 0s);
}

@keyframes growBar {
  from { transform: scaleX(0); }
  to { transform: scaleX(1); }
}
</style>
