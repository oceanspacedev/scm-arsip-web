<template>
  <div class="bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800 p-5 flex flex-col">
    <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3 mb-6">
      <div>
        <h3 class="text-sm font-medium text-slate-900 dark:text-slate-100">Rekap DPP dan PPN per bulan</h3>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ yearLabel }}, berdasarkan tanggal invoice program</p>
      </div>
      <!-- Legenda -->
      <div v-if="hasData" class="flex items-center gap-4 text-xs text-slate-600 dark:text-slate-400">
        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm bg-blue-600 dark:bg-blue-500"></span>DPP</span>
        <span class="inline-flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-sm bg-slate-300 dark:bg-slate-600"></span>PPN</span>
        <span class="inline-flex items-center gap-2"><span class="w-4 h-0.5 rounded-full bg-slate-700 dark:bg-slate-300"></span>Total invoice</span>
      </div>
    </div>

    <!-- Grafik: batang DPP (utama) dan PPN, garis tipis untuk total invoice -->
    <div v-if="hasData" class="flex-1 flex flex-col min-h-0">
      <div class="flex gap-3 h-56">
        <!-- Sumbu Y -->
        <div class="relative w-16 shrink-0 text-right text-xs tabular-nums text-slate-500 dark:text-slate-400">
          <span
            v-for="tick in ticks"
            :key="tick"
            class="absolute right-0 translate-y-1/2"
            :style="{ bottom: (tick / scaleMax) * 100 + '%' }"
          >{{ formatShort(tick) }}</span>
        </div>

        <div class="relative flex-1 min-w-0" @mouseleave="hoveredIndex = null">
          <!-- Garis bantu -->
          <div
            v-for="tick in ticks"
            :key="'grid-' + tick"
            class="absolute inset-x-0 border-t"
            :class="tick === 0 ? 'border-slate-300 dark:border-slate-700' : 'border-dashed border-slate-200 dark:border-slate-800'"
            :style="{ bottom: (tick / scaleMax) * 100 + '%' }"
          ></div>

          <!-- Kolom per bulan -->
          <div class="absolute inset-0 grid grid-cols-12">
            <div
              v-for="(item, idx) in monthlyData"
              :key="item.month"
              class="relative flex items-end justify-center gap-1 rounded-md cursor-default transition-colors"
              :class="hoveredIndex === idx ? 'bg-slate-100/70 dark:bg-slate-800/50' : ''"
              @mouseenter="hoveredIndex = idx"
            >
              <span
                class="chart-bar w-[28%] max-w-3 rounded-t-sm bg-blue-600 dark:bg-blue-500"
                :style="{ height: barHeight(item.dpp), '--i': idx }"
              ></span>
              <span
                class="chart-bar w-[28%] max-w-3 rounded-t-sm bg-slate-300 dark:bg-slate-600"
                :style="{ height: barHeight(item.ppn), '--i': idx }"
              ></span>
            </div>
          </div>

          <!-- Garis total invoice -->
          <svg class="absolute inset-0 w-full h-full pointer-events-none overflow-visible" viewBox="0 0 1200 100" preserveAspectRatio="none" aria-hidden="true">
            <polyline
              :points="linePoints"
              fill="none"
              class="stroke-slate-700 dark:stroke-slate-300"
              stroke-width="1.5"
              stroke-linejoin="round"
              vector-effect="non-scaling-stroke"
            />
          </svg>
          <span
            v-for="(item, idx) in monthlyData"
            :key="'dot-' + item.month"
            class="absolute w-2 h-2 -translate-x-1/2 translate-y-1/2 rounded-full border-2 pointer-events-none border-white dark:border-graphite-card"
            :class="hoveredIndex === idx ? 'bg-blue-600 dark:bg-blue-400 scale-125' : 'bg-slate-700 dark:bg-slate-300'"
            :style="{ left: ((idx + 0.5) / 12) * 100 + '%', bottom: (item.total / scaleMax) * 100 + '%' }"
          ></span>

          <!-- Tooltip -->
          <div
            v-if="hoveredMonth"
            class="absolute top-0 z-10 w-56 -translate-x-1/2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-[0_8px_24px_rgba(0,0,0,0.12)] dark:shadow-[0_8px_24px_rgba(0,0,0,0.5)] px-3 py-2.5 text-xs pointer-events-none"
            :style="{ left: tooltipLeft }"
          >
            <p class="font-medium text-slate-900 dark:text-slate-100">{{ hoveredMonth.fullName }}</p>
            <dl class="mt-2 space-y-1.5 tabular-nums">
              <div class="flex justify-between gap-3">
                <dt class="flex items-center gap-2 text-slate-500 dark:text-slate-400"><span class="w-2 h-2 rounded-sm bg-blue-600 dark:bg-blue-500"></span>DPP</dt>
                <dd class="text-slate-900 dark:text-slate-100">{{ formatRupiah(hoveredMonth.dpp) }}</dd>
              </div>
              <div class="flex justify-between gap-3">
                <dt class="flex items-center gap-2 text-slate-500 dark:text-slate-400"><span class="w-2 h-2 rounded-sm bg-slate-300 dark:bg-slate-600"></span>PPN</dt>
                <dd class="text-slate-900 dark:text-slate-100">{{ formatRupiah(hoveredMonth.ppn) }}</dd>
              </div>
              <div class="flex justify-between gap-3 pt-1.5 border-t border-slate-200 dark:border-slate-700">
                <dt class="text-slate-500 dark:text-slate-400">Total invoice</dt>
                <dd class="font-medium text-slate-900 dark:text-slate-100">{{ formatRupiah(hoveredMonth.total) }}</dd>
              </div>
              <div class="flex justify-between gap-3">
                <dt class="text-slate-500 dark:text-slate-400">Program</dt>
                <dd class="text-slate-900 dark:text-slate-100">{{ hoveredMonth.count }}</dd>
              </div>
            </dl>
          </div>
        </div>
      </div>

      <!-- Sumbu X -->
      <div class="flex gap-3 mt-2">
        <div class="w-16 shrink-0"></div>
        <div class="flex-1 grid grid-cols-12 text-center text-xs text-slate-500 dark:text-slate-400">
          <span
            v-for="(item, idx) in monthlyData"
            :key="'label-' + item.month"
            :class="{ 'text-slate-900 dark:text-slate-100': hoveredIndex === idx }"
          >{{ item.month }}</span>
        </div>
      </div>
    </div>

    <div v-else class="flex-1 min-h-56 flex flex-col items-center justify-center text-center">
      <p class="text-sm text-slate-700 dark:text-slate-300">Belum ada nilai invoice di {{ yearLabel.toLowerCase() }}</p>
      <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Pilih tahun pajak lain atau tambahkan program</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useTaxStore, formatRupiah } from '../../store/taxStore';

const store = useTaxStore();
const hoveredIndex = ref(null);

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
const MONTHS_FULL = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

const year = computed(() => store.effectiveFiscalYear.value);
const yearLabel = computed(() => (year.value === 'all' ? 'Semua tahun' : `Tahun pajak ${year.value}`));

// Jumlah per bulan dari program yang tampil di dashboard (sudah disaring tahun pajak)
const monthlyData = computed(() => {
  const rows = MONTHS.map((month, i) => ({
    month,
    fullName: year.value === 'all' ? MONTHS_FULL[i] : `${MONTHS_FULL[i]} ${year.value}`,
    dpp: 0,
    ppn: 0,
    total: 0,
    count: 0,
  }));
  (store.dashboardPrograms.value || []).forEach(p => {
    const monthIdx = Number(String(p.program_date || p.due_date || '').slice(5, 7)) - 1;
    if (monthIdx < 0 || monthIdx > 11) return;
    const row = rows[monthIdx];
    row.dpp += Number(p.dpp) || 0;
    row.ppn += Number(p.ppn) || 0;
    row.total += Number(p.total_invoice) || 0;
    row.count += 1;
  });
  return rows;
});

const highest = computed(() => Math.max(0, ...monthlyData.value.map(m => Math.max(m.total, m.dpp, m.ppn))));
const hasData = computed(() => highest.value > 0);

// Skala sumbu Y dengan langkah bulat (1, 2, 2,5, 5 × 10ⁿ), sekitar 4 garis
const step = computed(() => {
  const raw = highest.value / 4 || 1;
  const magnitude = 10 ** Math.floor(Math.log10(raw));
  const norm = raw / magnitude;
  const nice = norm <= 1 ? 1 : norm <= 2 ? 2 : norm <= 2.5 ? 2.5 : norm <= 5 ? 5 : 10;
  return nice * magnitude;
});
const scaleMax = computed(() => Math.max(step.value, Math.ceil(highest.value / step.value) * step.value));
const ticks = computed(() => {
  const list = [];
  for (let v = 0; v <= scaleMax.value + step.value / 2; v += step.value) list.push(v);
  return list;
});

function barHeight(value) {
  return value > 0 ? `${Math.max(1, (value / scaleMax.value) * 100)}%` : '0%';
}

const linePoints = computed(() => monthlyData.value
  .map((m, i) => `${(i + 0.5) * 100},${100 - (m.total / scaleMax.value) * 100}`)
  .join(' '));

const hoveredMonth = computed(() => (hoveredIndex.value === null ? null : monthlyData.value[hoveredIndex.value]));

// Tooltip di atas kolom yang disorot, tetap di dalam area grafik
const tooltipLeft = computed(() => {
  const pct = ((hoveredIndex.value + 0.5) / 12) * 100;
  return `clamp(7rem, ${pct}%, calc(100% - 7rem))`;
});

function formatShort(value) {
  if (value === 0) return 'Rp 0';
  if (value >= 1e9) return `Rp ${(value / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 })} M`;
  if (value >= 1e6) return `Rp ${(value / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 0 })} Jt`;
  return `Rp ${Math.round(value).toLocaleString('id-ID')}`;
}
</script>

<style scoped>
/* Batang tumbuh dari bawah saat pertama tampil */
.chart-bar {
  transform-origin: bottom center;
  animation: barRise 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
  animation-delay: calc(var(--i, 0) * 0.03s);
}

@keyframes barRise {
  from { transform: scaleY(0); }
  to { transform: scaleY(1); }
}
</style>
