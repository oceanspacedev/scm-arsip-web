<template>
  <Card class="flex flex-col">
    <CardHeader class="flex-row flex-wrap items-start justify-between gap-x-6 gap-y-3">
      <div class="flex min-w-0 flex-col gap-1.5">
        <CardTitle>Rekap DPP & PPN per Bulan</CardTitle>
        <CardDescription>Berdasarkan tanggal invoice program</CardDescription>
      </div>
      <!-- Keterangan: kotak untuk batang, garis untuk total -->
      <ul class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted-foreground">
        <li class="flex items-center gap-1.5">
          <span class="size-2.5 rounded-xs bg-chart-1" aria-hidden="true" />
          DPP
        </li>
        <li class="flex items-center gap-1.5">
          <span class="size-2.5 rounded-xs bg-chart-3" aria-hidden="true" />
          PPN (11%)
        </li>
        <li class="flex items-center gap-1.5">
          <span class="h-0.5 w-3 rounded-full bg-chart-2" aria-hidden="true" />
          Total Invoice
        </li>
      </ul>
    </CardHeader>

    <CardContent class="flex flex-1 flex-col">
      <!-- Ukuran grafik mengikuti kontainer: 1 satuan SVG = 1px, jadi teks & titik tidak ikut melar -->
      <div ref="chartEl" class="relative min-h-60 flex-1 select-none">
        <p v-if="!hasData" class="absolute inset-0 flex items-center justify-center text-center text-sm text-muted-foreground">
          Belum ada data pada tahun pajak ini.
        </p>

        <svg
          v-else-if="width > 0 && height > 0"
          class="absolute inset-0 size-full overflow-visible text-xs tabular-nums"
          :viewBox="`0 0 ${width} ${height}`"
          role="img"
          aria-label="Grafik DPP, PPN, dan total invoice per bulan"
          @mouseleave="hoveredIndex = null"
        >
          <!-- Sorotan kolom bulan yang disorot -->
          <rect
            v-if="hoveredIndex !== null"
            :x="plot.left + band * hoveredIndex"
            :y="plot.top"
            :width="band"
            :height="plot.bottom - plot.top"
            rx="4"
            fill="var(--muted)"
          />

          <!-- Garis bantu & label sumbu Y -->
          <g>
            <template v-for="tick in yTicks" :key="`tick-${tick}`">
              <line
                :x1="plot.left"
                :x2="plot.right"
                :y1="getY(tick)"
                :y2="getY(tick)"
                stroke="var(--border)"
                stroke-width="1"
                shape-rendering="crispEdges"
              />
              <text
                :x="plot.left - 8"
                :y="getY(tick)"
                text-anchor="end"
                dominant-baseline="middle"
                fill="var(--muted-foreground)"
              >
                {{ formatAxis(tick) }}
              </text>
            </template>
          </g>

          <!-- Batang DPP & PPN, berdampingan dengan jeda 2px -->
          <g>
            <template v-for="(item, idx) in monthlyData" :key="`bars-${item.month}`">
              <path
                v-if="item.dpp > 0"
                class="chart-bar"
                :style="{ '--i': idx }"
                :d="barPath(getX(idx) - BAR_GAP / 2 - barWidth, item.dpp)"
                fill="var(--chart-1)"
              />
              <path
                v-if="item.ppn > 0"
                class="chart-bar"
                :style="{ '--i': idx }"
                :d="barPath(getX(idx) + BAR_GAP / 2, item.ppn)"
                fill="var(--chart-3)"
              />
            </template>
          </g>

          <!-- Garis total invoice -->
          <g class="chart-fade">
            <path
              :d="trendLinePath"
              fill="none"
              stroke="var(--chart-2)"
              stroke-width="2"
              stroke-linejoin="round"
              stroke-linecap="round"
            />
            <circle
              v-for="(item, idx) in monthlyData"
              :key="`dot-${item.month}`"
              :cx="getX(idx)"
              :cy="getY(item.total)"
              :r="hoveredIndex === idx ? 5 : 4"
              fill="var(--chart-2)"
              :stroke="hoveredIndex === idx ? 'var(--muted)' : 'var(--card)'"
              stroke-width="2"
            />
          </g>

          <!-- Label bulan (selang-seling bila kolom sempit) -->
          <g>
            <template v-for="(item, idx) in monthlyData" :key="`label-${item.month}`">
              <text
                v-if="showMonthLabel(idx)"
                :x="getX(idx)"
                :y="plot.bottom + 18"
                text-anchor="middle"
                :fill="hoveredIndex === idx ? 'var(--foreground)' : 'var(--muted-foreground)'"
                :font-weight="hoveredIndex === idx ? 500 : undefined"
              >
                {{ monthNames[idx].name }}
              </text>
            </template>
          </g>

          <!-- Area hover: satu kolom penuh per bulan, di atas semua elemen -->
          <rect
            v-for="(item, idx) in monthlyData"
            :key="`hit-${item.month}`"
            :x="plot.left + band * idx"
            y="0"
            :width="band"
            :height="height"
            fill="transparent"
            @mouseenter="hoveredIndex = idx"
            @click="hoveredIndex = idx"
          />
        </svg>

        <Transition
          enter-active-class="transition-opacity duration-100"
          enter-from-class="opacity-0"
          leave-active-class="transition-opacity duration-75"
          leave-to-class="opacity-0"
        >
          <div
            v-if="hoveredMonth && hasData"
            class="pointer-events-none absolute top-0 z-10 w-max rounded-md bg-foreground px-3 py-1.5 text-xs text-background"
            :style="tooltipStyle"
          >
            <p class="font-medium">{{ hoveredTitle }}</p>
            <dl class="mt-1 grid grid-cols-[auto_auto] gap-x-4 gap-y-0.5">
              <dt class="text-background/70">DPP</dt>
              <dd class="text-right font-medium tabular-nums">{{ formatRupiah(hoveredMonth.dpp) }}</dd>
              <dt class="text-background/70">PPN (11%)</dt>
              <dd class="text-right font-medium tabular-nums">{{ formatRupiah(hoveredMonth.ppn) }}</dd>
              <dt class="text-background/70">Total Invoice</dt>
              <dd class="text-right font-medium tabular-nums">{{ formatRupiah(hoveredMonth.total) }}</dd>
            </dl>
          </div>
        </Transition>
      </div>
    </CardContent>
  </Card>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useElementSize } from '@vueuse/core';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatCompact, formatRupiah } from '@/lib/format';
import { useTaxStore } from '../../store/taxStore';

const store = useTaxStore();
const hoveredIndex = ref(null);

const monthNames = [
  { name: 'Jan', num: '01' },
  { name: 'Feb', num: '02' },
  { name: 'Mar', num: '03' },
  { name: 'Apr', num: '04' },
  { name: 'Mei', num: '05' },
  { name: 'Jun', num: '06' },
  { name: 'Jul', num: '07' },
  { name: 'Agu', num: '08' },
  { name: 'Sep', num: '09' },
  { name: 'Okt', num: '10' },
  { name: 'Nov', num: '11' },
  { name: 'Des', num: '12' },
];

const selectedYear = computed(() => store.selectedFiscalYear.value || String(new Date().getFullYear()));
const shortYear = computed(() => {
  const y = selectedYear.value;
  return y === 'all' ? 'All' : y.slice(-2);
});

// Dynamic monthly data aggregated from dashboardPrograms
const monthlyData = computed(() => {
  const progs = store.dashboardPrograms.value || [];
  
  return monthNames.map(m => {
    let dpp = 0;
    let ppn = 0;
    let total = 0;

    progs.forEach(p => {
      const dateStr = p.program_date || p.due_date || '';
      if (dateStr.length >= 7) {
        const parts = dateStr.split('-');
        if (parts[1] === m.num) {
          dpp += Number(p.dpp) || 0;
          ppn += Number(p.ppn) || 0;
          total += Number(p.total_invoice) || 0;
        }
      }
    });

    return {
      month: `${m.name} ${shortYear.value}`,
      dpp,
      ppn,
      total
    };
  });
});

const hoveredMonth = computed(() => {
  return (hoveredIndex.value !== null && monthlyData.value[hoveredIndex.value]) ? monthlyData.value[hoveredIndex.value] : null;
});

// Skala sumbu Y mengikuti data: puncaknya angka bulat tepat di atas nilai tertinggi
// (kelipatan 1 / 2 / 2,5 / 5), paling banyak 5 interval. Batang kecil tetap terbaca.
const yScale = computed(() => {
  const highest = monthlyData.value.reduce((acc, m) => Math.max(acc, m.total, m.dpp, m.ppn), 0);
  const target = highest > 0 ? highest * 1.1 : 1000000000;
  const magnitude = 10 ** Math.floor(Math.log10(target / 5));
  const step = [1, 2, 2.5, 5, 10].map(f => f * magnitude).find(s => target / s <= 5);
  return { step, max: Math.ceil(target / step) * step };
});
const maxVal = computed(() => yScale.value.max);

// --- Tampilan grafik (hanya geometri & label; data di atas tidak berubah) ---
const PAD = { top: 8, right: 4, bottom: 28, left: 48 };
const BAR_GAP = 2;
const BAR_RADIUS = 4;

const chartEl = ref(null);
const { width, height } = useElementSize(chartEl);

const plot = computed(() => ({
  left: PAD.left,
  right: Math.max(PAD.left + 1, width.value - PAD.right),
  top: PAD.top,
  bottom: Math.max(PAD.top + 1, height.value - PAD.bottom),
}));
const band = computed(() => (plot.value.right - plot.value.left) / Math.max(1, monthlyData.value.length));
// Dua batang + jeda mengisi ±55% kolom, maksimal 16px per batang
const barWidth = computed(() => Math.min(16, Math.max(3, (band.value * 0.55 - BAR_GAP) / 2)));

const hasData = computed(() => monthlyData.value.some(m => m.dpp || m.ppn || m.total));

function getX(idx) {
  return plot.value.left + band.value * (idx + 0.5);
}

function getY(value) {
  const { top, bottom } = plot.value;
  const ratio = Math.min(1, Math.max(0, value / maxVal.value));
  return bottom - (ratio * (bottom - top));
}

// Batang dengan ujung atas membulat 4px dan dasar rata di garis nol
function barPath(x, value) {
  const { bottom } = plot.value;
  const h = Math.max(2, bottom - getY(value));
  const top = bottom - h;
  const w = barWidth.value;
  const r = Math.min(BAR_RADIUS, w / 2, h);
  return `M${x},${bottom}V${top + r}A${r},${r} 0 0 1 ${x + r},${top}H${x + w - r}A${r},${r} 0 0 1 ${x + w},${top + r}V${bottom}Z`;
}

const trendLinePath = computed(() => {
  return monthlyData.value.reduce((path, item, idx) => {
    const x = getX(idx);
    const y = getY(item.total);
    return idx === 0 ? `M ${x} ${y}` : `${path} L ${x} ${y}`;
  }, '');
});

// Garis bantu di setiap kelipatan skala, dari 0 sampai puncak
const yTicks = computed(() => {
  const { step, max } = yScale.value;
  const ticks = [];
  for (let i = 0; i * step <= max * (1 + 1e-9); i++) ticks.push(i * step);
  return ticks;
});

const formatAxis = formatCompact;

// Kolom sempit (layar kecil): label bulan selang-seling, bulan yang disorot selalu tampil
function showMonthLabel(idx) {
  return band.value >= 32 || idx % 2 === 0 || hoveredIndex.value === idx;
}

const monthLong = new Intl.DateTimeFormat('id-ID', { month: 'long' });
const hoveredTitle = computed(() => {
  if (hoveredIndex.value === null) return '';
  const name = monthLong.format(new Date(2000, Number(monthNames[hoveredIndex.value].num) - 1, 1));
  return selectedYear.value === 'all' ? `${name}, semua tahun` : `${name} ${selectedYear.value}`;
});

// Tooltip di samping kolom yang disorot; pada grafik sempit digeser agar tetap di dalam grafik
const tooltipStyle = computed(() => {
  if (hoveredIndex.value === null || !width.value) return {};
  const x = getX(hoveredIndex.value);
  if (width.value < 520) {
    return { left: `${x}px`, transform: `translateX(-${(x / width.value) * 100}%)` };
  }
  const offset = band.value / 2 + 8;
  return x <= width.value / 2 ? { left: `${x + offset}px` } : { right: `${width.value - x + offset}px` };
});
</script>

<style scoped>
/* Batang tumbuh dari garis nol saat pertama tampil */
.chart-bar {
  transform-box: fill-box;
  transform-origin: bottom;
  animation: chart-bar-rise 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: calc(var(--i, 0) * 30ms);
}

@keyframes chart-bar-rise {
  from {
    transform: scaleY(0);
  }
}

.chart-fade {
  animation: chart-fade-in 0.4s ease-out 0.3s both;
}

@keyframes chart-fade-in {
  from {
    opacity: 0;
  }
}

@media (prefers-reduced-motion: reduce) {
  .chart-bar,
  .chart-fade {
    animation: none;
  }
}
</style>
