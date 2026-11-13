<template>
  <Card class="flex flex-col">
    <CardHeader>
      <CardTitle>Supplier Teratas</CardTitle>
      <CardDescription>Berdasarkan nilai invoice</CardDescription>
    </CardHeader>

    <CardContent class="flex flex-1 flex-col">
      <p v-if="suppliers.length === 0" class="flex min-h-40 flex-1 items-center justify-center text-center text-sm text-muted-foreground">
        Belum ada data supplier.
      </p>

      <!-- Satu seri, satu warna: panjang batang = nilai invoice relatif terhadap supplier teratas -->
      <ul v-else class="flex flex-col gap-2">
        <li v-for="(supplier, idx) in suppliers" :key="supplier.name">
          <Tooltip>
            <TooltipTrigger as-child>
              <div
                class="-mx-2 rounded-md px-2 py-1 transition-colors"
                :class="hoveredSupplier?.name === supplier.name && 'bg-muted/50'"
                @mouseenter="hoveredSupplier = supplier"
                @mouseleave="hoveredSupplier = null"
              >
                <div class="flex items-baseline justify-between gap-3 text-sm">
                  <span class="min-w-0 truncate">{{ supplier.name }}</span>
                  <span class="shrink-0 tabular-nums text-muted-foreground">{{ formatShort(supplier.value) }}</span>
                </div>
                <div class="mt-2 h-2">
                  <div
                    class="supplier-bar h-full rounded-r-full bg-chart-1"
                    :style="{
                      width: Math.max(0, supplier.value / maxVal) * 100 + '%',
                      '--delay': (idx * 0.06) + 's'
                    }"
                  />
                </div>
              </div>
            </TooltipTrigger>
            <TooltipContent side="top">
              <p class="font-medium">{{ supplier.name }}</p>
              <p class="tabular-nums">{{ formatRupiah(supplier.value) }}</p>
            </TooltipContent>
          </Tooltip>
        </li>
      </ul>
    </CardContent>
  </Card>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { formatRupiah, formatRupiahShort } from '@/lib/format';
import { useTaxStore } from '../../store/taxStore';

const store = useTaxStore();
const hoveredSupplier = ref(null);

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

// Nilai ringkas berformat Indonesia: "Rp 2,4 M", "Rp 412 Jt"
const formatShort = formatRupiahShort;
</script>

<style scoped>
/* Batang tumbuh dari kiri saat pertama tampil */
.supplier-bar {
  transform-origin: left center;
  animation: supplier-bar-grow 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: var(--delay, 0s);
}

@keyframes supplier-bar-grow {
  from {
    transform: scaleX(0);
  }
}

@media (prefers-reduced-motion: reduce) {
  .supplier-bar {
    animation: none;
  }
}
</style>
