<template>
  <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 p-5 flex flex-col justify-between">
    <!-- Header -->
    <div class="mb-4">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
        Supplier Teratas
      </h3>
      <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
        Berdasarkan nilai invoice
      </p>
    </div>

    <!-- Horizontal Bars Container -->
    <div class="space-y-2.5 relative my-auto">
      <div
        v-for="(supplier, idx) in suppliers"
        :key="supplier.name"
        class="flex items-center text-xs group cursor-pointer"
        @mouseenter="hoveredSupplier = supplier"
        @mouseleave="hoveredSupplier = null"
      >
        <!-- Label Left (PT Unilever Ind...) -->
        <div class="w-28 sm:w-32 shrink-0 text-right pr-3 truncate text-[11px] text-slate-600 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-200 transition-colors">
          {{ supplier.name }}
        </div>

        <!-- Bar Track & Fill -->
        <div class="flex-1 bg-slate-50 dark:bg-slate-800/80 rounded-r h-6 relative flex items-center overflow-hidden border-l border-slate-300 dark:border-slate-700">
          <div
            class="supplier-bar h-full rounded-r transition-all duration-300 group-hover:brightness-110 flex items-center justify-end pr-2"
            :style="{
              width: (supplier.value / maxVal) * 100 + '%',
              backgroundColor: supplierColors[idx] || '#79BEAC',
              '--delay': (idx * 0.08) + 's'
            }"
          >
            <!-- Optional inline value display on hover -->
            <span
              v-if="hoveredSupplier?.name === supplier.name"
              class="text-[10px] font-mono font-bold text-white drop-shadow-xs"
            >
              {{ formatShort(supplier.value) }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom X-axis Ticks -->
    <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[10px] font-mono text-slate-400 dark:text-slate-500 pl-24 sm:pl-32">
      <span>Rp 0</span>
      <span class="hidden sm:inline">{{ formatShort(maxVal * 0.25) }}</span>
      <span>{{ formatShort(maxVal * 0.5) }}</span>
      <span class="hidden sm:inline">{{ formatShort(maxVal * 0.75) }}</span>
      <span>{{ formatShort(maxVal) }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useTaxStore } from '../../store/taxStore';

const store = useTaxStore();
const hoveredSupplier = ref(null);

// Color gradient from primary blue to soft tints
const supplierColors = [
  '#2563EB', // Top 1: Brand Blue
  '#3B82F6', // Top 2
  '#60A5FA', // Top 3
  '#93C5FD', // Top 4
  '#BFDBFE', // Top 5
  '#DBEAFE', // Top 6
];

const suppliers = computed(() => {
  const map = new Map();
  (store.dashboardPrograms.value || []).forEach(p => {
    const name = p.supplier || 'Lainnya';
    const current = map.get(name) || 0;
    map.set(name, current + (Number(p.total_invoice) || 0));
  });

  return Array.from(map.entries())
    .map(([name, value]) => ({ name, value }))
    .sort((a, b) => b.value - a.value)
    .slice(0, 6);
});

const maxVal = computed(() => {
  if (suppliers.value.length === 0) return 1000000000;
  return Math.max(...suppliers.value.map(s => s.value), 1000000);
});

function formatShort(val) {
  if (val >= 1000000000) return 'Rp ' + (val / 1000000000).toFixed(1) + ' M';
  if (val >= 1000000) return 'Rp ' + (val / 1000000).toFixed(0) + ' Jt';
  return 'Rp ' + Math.round(val).toLocaleString('id-ID');
}
</script>

<style scoped>
.supplier-bar {
  transform-origin: left center;
  animation: growBar 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: var(--delay, 0s);
}

@keyframes growBar {
  from {
    transform: scaleX(0);
    opacity: 0.3;
  }
  to {
    transform: scaleX(1);
    opacity: 1;
  }
}
</style>
