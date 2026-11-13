<script setup>
import { computed, onMounted, provide, ref } from 'vue';
import { useEventListener, useMediaQuery, useResizeObserver } from '@vueuse/core';

/*
 * Grid kartu ringkasan. Kartu tetap 4 sejajar selama angka terpanjang muat utuh; bila tidak,
 * turun ke 2 kolom (1 kolom di ponsel). Angka 30px bila muat, 24px bila tidak. Semua kartu dalam
 * satu grid memakai ukuran angka yang sama supaya seimbang.
 */
const props = defineProps({
  // Teks angka tiap kartu (mis. "Rp 311.972.485"), dipakai untuk mengukur lebar yang dibutuhkan
  values: { type: Array, default: () => [] },
});

const SLOTS = 4;
const CARD_CHROME = 50; // padding kiri-kanan 24px + garis tepi 1px

const gridEl = ref(null);
const width = ref(0);
const isMd = useMediaQuery('(min-width: 768px)');
const gap = computed(() => (isMd.value ? 24 : 16));

function measure() {
  width.value = gridEl.value?.clientWidth || 0;
}
useResizeObserver(gridEl, entries => { width.value = entries[0].contentRect.width; });
useEventListener(window, 'resize', measure);

// Lebar teks diukur ulang setelah font Inter selesai dimuat
const fontsReady = ref(false);
onMounted(() => {
  measure();
  document.fonts?.ready.then(() => { fontsReady.value = true; });
});

let canvas;
function textWidth(text, px) {
  void fontsReady.value;
  canvas ||= document.createElement('canvas');
  const context = canvas.getContext('2d');
  context.font = `600 ${px}px Inter, ui-sans-serif, system-ui, sans-serif`;
  // Cadangan 6% untuk angka tabular
  return context.measureText(text).width * 1.06;
}

const longest = computed(() => props.values.map(String).reduce((a, b) => (b.length > a.length ? b : a), ''));
const innerWidth = columns => (width.value - gap.value * (columns - 1)) / columns - CARD_CHROME;

const columns = computed(() => {
  if (!width.value) return 0;
  const needed = textWidth(longest.value, 24);
  return [SLOTS, 2, 1].find(count => innerWidth(count) >= needed) || 1;
});

const bigValues = computed(() => columns.value > 0 && innerWidth(columns.value) >= textWidth(longest.value, 30));
provide('statValueBig', bigValues);
</script>

<template>
  <!-- Sebelum lebarnya terukur, pakai kolom bawaan (1 / 2 / 4) -->
  <div
    ref="gridEl"
    class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4"
    :style="columns ? { gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` } : undefined"
  >
    <slot />
  </div>
</template>
