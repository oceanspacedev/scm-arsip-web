<template>
  <div>
    <!-- Program terpilih: nama + No. PO/SJ, karena banyak program bernama sama -->
    <div
      v-if="chosen"
      class="flex items-start gap-2 rounded-md border border-slate-300 dark:border-slate-600 px-2.5 py-1.5"
    >
      <div class="min-w-0 flex-1">
        <p class="truncate font-medium text-slate-800 dark:text-slate-200">{{ programTitle(chosen) }}</p>
        <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ programMeta(chosen) }}</p>
      </div>
      <button
        type="button"
        class="shrink-0 text-[11px] font-medium text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 cursor-pointer disabled:cursor-not-allowed"
        :disabled="disabled"
        @click="change"
      >
        Ganti
      </button>
    </div>

    <template v-else>
      <input
        ref="inputEl"
        v-model="query"
        type="text"
        role="combobox"
        aria-autocomplete="list"
        :aria-expanded="isOpen"
        :aria-controls="listId"
        :aria-activedescendant="isOpen && options[activeIndex] ? `${listId}-${activeIndex}` : undefined"
        autocomplete="off"
        :placeholder="placeholder"
        :disabled="disabled"
        class="w-full h-8 px-2.5 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-slate-400"
        @focus="open"
        @input="open"
        @blur="isOpen = false"
        @keydown.down.prevent="move(1)"
        @keydown.up.prevent="move(-1)"
        @keydown.enter.prevent="chooseActive"
        @keydown.esc="isOpen = false"
      />

      <!-- mousedown.prevent: klik di daftar tidak memindahkan fokus dari kolom cari -->
      <div
        v-if="isOpen"
        :id="listId"
        ref="listEl"
        role="listbox"
        class="mt-1 max-h-56 overflow-y-auto rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900"
        @mousedown.prevent
      >
        <template v-for="section in sections" :key="section.label">
          <p
            v-if="sections.length > 1"
            class="px-2.5 pt-2 pb-1 text-[10px] font-medium uppercase tracking-wide text-slate-400 dark:text-slate-500"
          >
            {{ section.label }}
          </p>
          <div
            v-for="row in section.rows"
            :id="`${listId}-${row.index}`"
            :key="row.program.id"
            role="option"
            :aria-selected="row.index === activeIndex"
            class="px-2.5 py-1.5 cursor-pointer"
            :class="row.index === activeIndex ? 'bg-slate-100 dark:bg-slate-800' : ''"
            @mouseenter="activeIndex = row.index"
            @click="choose(row.program)"
          >
            <p class="truncate font-medium text-slate-800 dark:text-slate-200">{{ programTitle(row.program) }}</p>
            <p class="truncate text-[11px] text-slate-500 dark:text-slate-400">{{ programMeta(row.program) }}</p>
          </div>
        </template>
        <p v-if="options.length === 0" class="px-2.5 py-2 text-slate-400 dark:text-slate-500">Tidak ada program yang cocok.</p>
        <p
          v-if="matches.hidden > 0"
          class="px-2.5 py-1.5 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-400 dark:text-slate-500"
        >
          {{ matches.hidden }} program lain, ketik untuk mempersempit.
        </p>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, nextTick, useId } from 'vue';
import { matchesProgram, programMeta, programTitle } from '../../utils/programSearch';

// Batas baris "program lain" yang dirender; sisanya ditemukan lewat pencarian
const LIST_LIMIT = 50;

const props = defineProps({
  modelValue: { type: String, default: '' },
  candidates: { type: Array, default: () => [] },
  programs: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false }
});
const emit = defineEmits(['update:modelValue']);

const listId = useId();
const inputEl = ref(null);
const listEl = ref(null);
const query = ref('');
const isOpen = ref(false);
const activeIndex = ref(0);

const programsById = computed(() => new Map(props.programs.map(p => [String(p.id), p])));

// Kandidat dari server hanya membawa nama & PO/SJ; dilengkapi data program di store (No. Invoice, dll.)
const candidateRows = computed(() =>
  props.candidates.map(c => ({ ...c, ...programsById.value.get(String(c.id)), id: String(c.id) }))
);

const chosen = computed(() => props.modelValue
  ? programsById.value.get(props.modelValue) || candidateRows.value.find(c => c.id === props.modelValue) || null
  : null);

const placeholder = computed(() => props.candidates.length > 0
  ? `${props.candidates.length} program paling mirip, atau ketik untuk mencari…`
  : 'Cari nama program, No. PO/SJ, atau No. Invoice…');

const matches = computed(() => {
  const candidateIds = new Set(candidateRows.value.map(c => c.id));
  const others = props.programs.filter(p => !candidateIds.has(String(p.id)) && matchesProgram(p, query.value));
  return {
    candidates: candidateRows.value.filter(c => matchesProgram(c, query.value)),
    others: others.slice(0, LIST_LIMIT),
    hidden: Math.max(0, others.length - LIST_LIMIT)
  };
});

const options = computed(() => [...matches.value.candidates, ...matches.value.others]);

const sections = computed(() => {
  let index = 0;
  const toRows = (programs) => programs.map(program => ({ program, index: index++ }));
  return [
    { label: 'Paling mirip', rows: toRows(matches.value.candidates) },
    { label: 'Program lain', rows: toRows(matches.value.others) }
  ].filter(section => section.rows.length > 0);
});

function open() {
  isOpen.value = true;
  activeIndex.value = 0;
  // Panel unggah bisa bergulir: pastikan daftarnya terlihat
  nextTick(() => listEl.value?.scrollIntoView({ block: 'nearest' }));
}

function move(step) {
  if (!isOpen.value) return open();
  activeIndex.value = Math.min(Math.max(activeIndex.value + step, 0), Math.max(options.value.length - 1, 0));
  nextTick(() => document.getElementById(`${listId}-${activeIndex.value}`)?.scrollIntoView({ block: 'nearest' }));
}

function chooseActive() {
  const program = options.value[activeIndex.value];
  if (isOpen.value && program) choose(program);
}

function choose(program) {
  emit('update:modelValue', String(program.id));
  query.value = '';
  isOpen.value = false;
}

async function change() {
  emit('update:modelValue', '');
  await nextTick();
  inputEl.value?.focus();
}
</script>
