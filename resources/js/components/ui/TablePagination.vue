<template>
  <!-- Pagination tabel: ringkasan baris, jumlah baris per halaman, dan nomor halaman -->
  <div class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3 border-t border-slate-200 dark:border-slate-800 text-sm text-slate-500 dark:text-slate-400">
    <p class="tabular-nums">
      <template v-if="total > 0">
        <span class="text-slate-900 dark:text-slate-100">{{ from }}–{{ to }}</span> dari {{ total }} {{ itemLabel }}
      </template>
      <template v-else>0 {{ itemLabel }}</template>
    </p>

    <div class="flex items-center gap-4">
      <label class="hidden sm:flex items-center gap-2">
        <span>Baris</span>
        <select
          :value="pageSize"
          class="h-8 pl-2 pr-7 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 tabular-nums cursor-pointer outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
          @change="changePageSize(Number($event.target.value))"
        >
          <option v-for="size in pageSizes" :key="size" :value="size">{{ size }}</option>
        </select>
      </label>

      <nav v-if="totalPages > 1" class="flex items-center gap-1" aria-label="Halaman tabel">
        <button
          type="button"
          :class="navButtonClass"
          :disabled="page <= 1"
          aria-label="Halaman sebelumnya"
          @click="goTo(page - 1)"
        >
          <ChevronLeft class="w-4 h-4" :stroke-width="1.75" />
        </button>

        <span class="sm:hidden px-2 tabular-nums text-slate-900 dark:text-slate-100">{{ page }} / {{ totalPages }}</span>

        <template v-for="(item, idx) in pageItems" :key="idx">
          <span v-if="item === '…'" class="hidden sm:inline-block w-8 text-center text-slate-400 dark:text-slate-500">…</span>
          <button
            v-else
            type="button"
            class="hidden sm:inline-flex h-8 min-w-8 px-2 items-center justify-center rounded-lg tabular-nums cursor-pointer transition-colors"
            :class="item === page
              ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
              : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100'"
            :aria-current="item === page ? 'page' : undefined"
            @click="goTo(item)"
          >
            {{ item }}
          </button>
        </template>

        <button
          type="button"
          :class="navButtonClass"
          :disabled="page >= totalPages"
          aria-label="Halaman berikutnya"
          @click="goTo(page + 1)"
        >
          <ChevronRight class="w-4 h-4" :stroke-width="1.75" />
        </button>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
  total: { type: Number, required: true },
  page: { type: Number, required: true },
  pageSize: { type: Number, required: true },
  pageSizes: { type: Array, default: () => [10, 25, 50] },
  itemLabel: { type: String, default: 'data' },
});

const emit = defineEmits(['update:page', 'update:pageSize']);

const totalPages = computed(() => Math.max(1, Math.ceil(props.total / props.pageSize)));
const from = computed(() => (props.total === 0 ? 0 : (props.page - 1) * props.pageSize + 1));
const to = computed(() => Math.min(props.page * props.pageSize, props.total));

// Maksimal 7 tombol: 1 … 4 5 6 … 12
const pageItems = computed(() => {
  const last = totalPages.value;
  const current = props.page;
  if (last <= 7) return Array.from({ length: last }, (_, i) => i + 1);
  if (current <= 4) return [1, 2, 3, 4, 5, '…', last];
  if (current >= last - 3) return [1, '…', last - 4, last - 3, last - 2, last - 1, last];
  return [1, '…', current - 1, current, current + 1, '…', last];
});

const navButtonClass = 'h-8 w-8 inline-flex items-center justify-center rounded-lg text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100 transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:bg-transparent';

function goTo(target) {
  const next = Math.min(Math.max(1, target), totalPages.value);
  if (next !== props.page) emit('update:page', next);
}

function changePageSize(size) {
  emit('update:pageSize', size);
  emit('update:page', 1);
}
</script>
