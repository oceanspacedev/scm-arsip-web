<template>
  <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 p-5">
    <!-- Header matching screenshot -->
    <div class="mb-4">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
        Rekap DPP & PPN per Bulan
      </h3>
      <p class="text-xs text-slate-400 dark:text-slate-500 mt-0.5">
        Berdasarkan tanggal invoice program
      </p>
    </div>

    <!-- Chart Canvas Container -->
    <div class="relative w-full h-[185px] select-none">
      <svg
        class="w-full h-full overflow-visible"
        viewBox="0 0 640 175"
        preserveAspectRatio="none"
        @mouseleave="hoveredIndex = null"
      >
        <defs>
          <!-- Gradient for DPP bar -->
          <linearGradient id="dppGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#3B82F6" />
            <stop offset="100%" stop-color="#2563EB" />
          </linearGradient>

          <!-- Gradient for PPN bar -->
          <linearGradient id="ppnGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#F59E0B" />
            <stop offset="100%" stop-color="#D97706" />
          </linearGradient>

          <!-- Shadow Filter for Trend Dots -->
          <filter id="dotShadow" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="0" dy="1" stdDeviation="1.5" flood-opacity="0.25" />
          </filter>
        </defs>

        <!-- Grid Lines & Y-axis labels with Ticks matching screenshot -->
        <g class="text-[10px] font-mono fill-slate-400">
          <!-- Left vertical axis line -->
          <line x1="58" y1="20" x2="58" y2="142" stroke="#CBD5E1" stroke-width="1.2" />

          <!-- 2.2 M -->
          <line x1="53" y1="30" x2="58" y2="30" stroke="#CBD5E1" stroke-width="1.2" />
          <line x1="58" y1="30" x2="630" y2="30" stroke="#F1F5F9" stroke-dasharray="3 3" />
          <text x="48" y="33" text-anchor="end">Rp 2.2 M</text>

          <!-- 1.6 M -->
          <line x1="53" y1="60" x2="58" y2="60" stroke="#CBD5E1" stroke-width="1.2" />
          <line x1="58" y1="60" x2="630" y2="60" stroke="#F1F5F9" stroke-dasharray="3 3" />
          <text x="48" y="63" text-anchor="end">Rp 1.6 M</text>

          <!-- 1.1 M -->
          <line x1="53" y1="88" x2="58" y2="88" stroke="#CBD5E1" stroke-width="1.2" />
          <line x1="58" y1="88" x2="630" y2="88" stroke="#F1F5F9" stroke-dasharray="3 3" />
          <text x="48" y="91" text-anchor="end">Rp 1.1 M</text>

          <!-- 550 Jt -->
          <line x1="53" y1="115" x2="58" y2="115" stroke="#CBD5E1" stroke-width="1.2" />
          <line x1="58" y1="115" x2="630" y2="115" stroke="#F1F5F9" stroke-dasharray="3 3" />
          <text x="48" y="118" text-anchor="end">Rp 550 Jt</text>

          <!-- Baseline 0 -->
          <line x1="53" y1="142" x2="58" y2="142" stroke="#CBD5E1" stroke-width="1.2" />
          <line x1="58" y1="142" x2="630" y2="142" stroke="#CBD5E1" stroke-width="1.2" />
          <text x="48" y="145" text-anchor="end">Rp 0</text>
        </g>

        <!-- Column Background Hover Highlight & Bars -->
        <g v-for="(item, idx) in monthlyData" :key="item.month">
          <!-- Column Hover Zone / Highlight Pill -->
          <rect
            :x="getX(idx) - 22"
            y="15"
            width="44"
            height="130"
            rx="4"
            :fill="hoveredIndex === idx ? '#F8FAFC' : 'transparent'"
            :stroke="hoveredIndex === idx ? '#E2E8F0' : 'transparent'"
            stroke-width="1"
            class="transition-colors duration-150 cursor-pointer"
            @mouseenter="hoveredIndex = idx"
          />

          <!-- DPP Bar (Emerald) -->
          <rect
            :x="getX(idx) - 11"
            :y="getY(item.dpp)"
            width="10"
            :height="Math.max(2, 142 - getY(item.dpp))"
            fill="url(#dppGradient)"
            rx="2"
            class="chart-bar cursor-pointer transition-all duration-200"
            :style="{ '--i': idx }"
            :class="hoveredIndex === idx ? 'brightness-110 filter drop-shadow-sm' : ''"
            @mouseenter="hoveredIndex = idx"
          />

          <!-- PPN Bar (Amber) -->
          <rect
            :x="getX(idx) + 1"
            :y="getY(item.ppn)"
            width="10"
            :height="Math.max(2, 142 - getY(item.ppn))"
            fill="url(#ppnGradient)"
            rx="2"
            class="chart-bar cursor-pointer transition-all duration-200"
            :style="{ '--i': idx + 0.3 }"
            :class="hoveredIndex === idx ? 'brightness-110 filter drop-shadow-sm' : ''"
            @mouseenter="hoveredIndex = idx"
          />

          <!-- X Axis Month Label -->
          <text
            :x="getX(idx)"
            y="160"
            text-anchor="middle"
            class="text-[10px] font-medium transition-colors duration-150"
            :class="hoveredIndex === idx ? 'fill-slate-900 font-bold' : 'fill-slate-500'"
          >
            {{ item.month }}
          </text>
        </g>

        <!-- Total Invoice Trend Line with Animated Draw -->
        <path
          :d="trendLinePath"
          fill="none"
          stroke="#1E293B"
          stroke-width="2"
          stroke-linejoin="round"
          stroke-linecap="round"
          class="chart-line"
        />

        <!-- Dots on Trend Line with Pop Animation -->
        <g v-for="(item, idx) in monthlyData" :key="'dot-' + item.month">
          <!-- Outer Pulsing Ring when Hovered -->
          <circle
            v-if="hoveredIndex === idx"
            :cx="getX(idx)"
            :cy="getY(item.total)"
            r="8"
            fill="#2563EB"
            fill-opacity="0.2"
            class="animate-ping"
          />

          <!-- Inner Dot -->
          <circle
            :cx="getX(idx)"
            :cy="getY(item.total)"
            :r="hoveredIndex === idx ? 5 : 3.5"
            :fill="hoveredIndex === idx ? '#2563EB' : '#1E293B'"
            stroke="#FFFFFF"
            stroke-width="1.8"
            filter="url(#dotShadow)"
            class="chart-dot cursor-pointer transition-all duration-200"
            :style="{ '--i': idx }"
            @mouseenter="hoveredIndex = idx"
          />
        </g>
      </svg>

      <!-- Floating Tooltip -->
      <transition
        enter-active-class="transition duration-150 ease-out"
        enter-from-class="opacity-0 translate-y-1 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-100 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 translate-y-1 scale-95"
      >
        <div
          v-if="hoveredMonth"
          class="absolute top-1 right-2 bg-slate-900/95 backdrop-blur-xs text-white px-3 py-2 rounded-lg shadow-xl text-xs space-y-1 border border-slate-700/80 pointer-events-none z-20 min-w-[175px]"
        >
          <div class="flex items-center justify-between border-b border-slate-700/70 pb-1">
            <span class="font-bold text-blue-400 text-[11px]">
              {{ hoveredMonth.month }} 2025
            </span>
            <span class="text-[9px] text-slate-400 uppercase tracking-wider">Perpajakan</span>
          </div>

          <div class="flex justify-between items-center text-slate-300 text-[11px] pt-0.5">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-[#2563EB]"></span>
              <span>DPP:</span>
            </span>
            <span class="font-mono font-medium text-white">{{ formatRupiah(hoveredMonth.dpp) }}</span>
          </div>

          <div class="flex justify-between items-center text-slate-300 text-[11px]">
            <span class="flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-[#F59E0B]"></span>
              <span>PPN (11%):</span>
            </span>
            <span class="font-mono font-medium text-white">{{ formatRupiah(hoveredMonth.ppn) }}</span>
          </div>

          <div class="flex justify-between items-center text-slate-200 pt-1 border-t border-slate-700/70 text-[11px] font-semibold">
            <span>Total Invoice:</span>
            <span class="font-mono text-blue-300">{{ formatRupiah(hoveredMonth.total) }}</span>
          </div>
        </div>
      </transition>
    </div>

    <!-- Chart Legend (Bottom) -->
    <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-800 flex items-center justify-center gap-6 text-xs text-slate-600 dark:text-slate-400">
      <div class="flex items-center gap-1.5 cursor-default hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
        <span class="w-2.5 h-2.5 rounded-full bg-[#2563EB] shadow-2xs"></span>
        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">DPP</span>
      </div>
      <div class="flex items-center gap-1.5 cursor-default hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
        <span class="w-2.5 h-2.5 rounded-full bg-[#F59E0B] shadow-2xs"></span>
        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">PPN (11%)</span>
      </div>
      <div class="flex items-center gap-1.5 cursor-default hover:text-slate-900 dark:hover:text-slate-200 transition-colors">
        <div class="flex items-center">
          <div class="w-3.5 h-0.5 bg-[#1E293B] dark:bg-slate-300"></div>
          <div class="w-2 h-2 rounded-full bg-[#1E293B] dark:bg-slate-300 -ml-1 border border-white dark:border-slate-900"></div>
        </div>
        <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Total Invoice</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useTaxStore, formatRupiah } from '../../store/taxStore';

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

const maxVal = computed(() => {
  const highest = monthlyData.value.reduce((acc, m) => Math.max(acc, m.total, m.dpp, m.ppn), 0);
  if (highest <= 0) return 2000000000;
  return Math.max(2000000000, Math.ceil((highest * 1.15) / 500000000) * 500000000);
});

function getX(idx) {
  const startX = 85;
  const step = 56;
  return startX + idx * step;
}

function getY(value) {
  const baseline = 142;
  const availableHeight = 112; // 142 - 30
  const ratio = Math.min(1, Math.max(0, value / maxVal.value));
  return baseline - (ratio * availableHeight);
}

const trendLinePath = computed(() => {
  return monthlyData.value.reduce((path, item, idx) => {
    const x = getX(idx);
    const y = getY(item.total);
    return idx === 0 ? `M ${x} ${y}` : `${path} L ${x} ${y}`;
  }, '');
});
</script>

<style scoped>
/* Rise-up animation for bars with transform-origin at bottom */
.chart-bar {
  transform-box: fill-box;
  transform-origin: bottom center;
  animation: barRise 0.75s cubic-bezier(0.16, 1, 0.3, 1) both;
  animation-delay: calc(var(--i, 0) * 0.045s);
}

@keyframes barRise {
  from {
    transform: scaleY(0);
    opacity: 0.2;
  }
  to {
    transform: scaleY(1);
    opacity: 1;
  }
}

/* SVG Line Draw-in animation */
.chart-line {
  stroke-dasharray: 1000;
  stroke-dashoffset: 1000;
  animation: lineDraw 1.1s cubic-bezier(0.2, 0.8, 0.2, 1) 0.25s forwards;
}

@keyframes lineDraw {
  to {
    stroke-dashoffset: 0;
  }
}

/* Pop-in animation for dots */
.chart-dot {
  transform-box: fill-box;
  transform-origin: center;
  animation: dotPop 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both;
  animation-delay: calc(0.35s + var(--i, 0) * 0.05s);
}

@keyframes dotPop {
  from {
    transform: scale(0);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
