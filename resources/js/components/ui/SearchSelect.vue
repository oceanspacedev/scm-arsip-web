<template>
  <!--
    Dropdown dengan kotak cari: ketik untuk menyaring, ↑ ↓ untuk memilih, Enter untuk memakai, Esc untuk menutup.
    Daftar pilihan dirender di <body> dengan posisi tetap, jadi tidak terpotong di dalam dialog atau area yang bisa digulir.
  -->
  <div class="relative">
    <button
      ref="triggerRef"
      type="button"
      class="w-full h-9 pl-3 pr-2 flex items-center justify-between gap-2 rounded-lg border bg-white dark:bg-slate-900 text-sm text-left outline-none focus-visible:border-blue-500 focus-visible:ring-2 focus-visible:ring-blue-500/20 transition-colors disabled:bg-slate-50 dark:disabled:bg-slate-800/60 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
      :class="[
        active ? 'border-slate-400 dark:border-slate-500 text-slate-900 dark:text-slate-100' : `${borderClass} text-slate-900 dark:text-slate-100`,
        open ? 'border-blue-500! dark:border-blue-400! ring-2 ring-blue-500/20' : 'enabled:hover:border-slate-400 dark:enabled:hover:border-slate-500',
        disabled ? '' : 'cursor-pointer'
      ]"
      :disabled="disabled"
      :aria-label="ariaLabel || undefined"
      :aria-labelledby="labelledby || undefined"
      aria-haspopup="listbox"
      :aria-expanded="open"
      @click="toggle"
      @keydown.down.prevent="openMenu"
    >
      <span class="truncate" :class="{ 'text-slate-400 dark:text-slate-500': !hasValue && placeholder }">{{ selectedLabel }}</span>
      <ChevronDown v-if="!disabled" class="w-4 h-4 shrink-0 text-slate-400 transition-transform" :class="{ 'rotate-180': open }" :stroke-width="1.75" />
    </button>

    <Teleport to="body">
      <Transition
        enter-active-class="transition-[opacity,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)]"
        enter-from-class="opacity-0 -translate-y-1"
        leave-active-class="transition-opacity duration-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="open"
          ref="popoverRef"
          class="fixed z-[70] rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-[0_8px_24px_rgba(0,0,0,0.12)] dark:shadow-[0_8px_24px_rgba(0,0,0,0.5)]"
          :style="{ top: pos.top + 'px', left: pos.left + 'px', width: pos.width + 'px' }"
        >
          <div v-if="searchable" class="p-2 border-b border-slate-200 dark:border-slate-700">
            <div class="relative">
              <Search class="w-4 h-4 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" :stroke-width="1.75" />
              <input
                ref="searchRef"
                v-model="query"
                type="text"
                :placeholder="searchPlaceholder"
                class="w-full h-8 pl-8 pr-2 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                role="combobox"
                aria-autocomplete="list"
                :aria-controls="`${uid}-list`"
                :aria-activedescendant="activeId"
                @keydown.down.prevent="move(1)"
                @keydown.up.prevent="move(-1)"
                @keydown.enter.prevent="chooseHighlighted"
                @keydown.esc.prevent.stop="close(true)"
                @keydown.tab="close(false)"
              />
            </div>
          </div>

          <ul
            :id="`${uid}-list`"
            ref="listRef"
            role="listbox"
            :aria-label="ariaLabel || undefined"
            tabindex="-1"
            class="max-h-64 overflow-y-auto p-1 outline-none"
            @keydown.down.prevent="move(1)"
            @keydown.up.prevent="move(-1)"
            @keydown.enter.prevent="chooseHighlighted"
            @keydown.esc.prevent.stop="close(true)"
          >
            <li
              v-for="(opt, idx) in filtered"
              :id="`${uid}-opt-${idx}`"
              :key="String(opt.value)"
              role="option"
              :aria-selected="opt.value === modelValue"
              class="flex items-center gap-2 min-h-9 py-1.5 px-2.5 rounded-lg text-sm cursor-pointer select-none"
              :class="idx === highlighted
                ? 'bg-slate-100 dark:bg-slate-700 text-slate-900 dark:text-slate-100'
                : 'text-slate-700 dark:text-slate-200'"
              @mouseenter="highlighted = idx"
              @mousedown.prevent
              @click="choose(opt.value)"
            >
              <span class="flex-1 min-w-0">
                <span class="block truncate" :title="opt.label">{{ opt.label }}</span>
                <span v-if="opt.hint" class="block truncate text-xs text-slate-500 dark:text-slate-400">{{ opt.hint }}</span>
              </span>
              <Check v-if="opt.value === modelValue" class="w-4 h-4 shrink-0 text-blue-600 dark:text-blue-400" :stroke-width="2" />
            </li>
            <li v-if="filtered.length === 0" class="px-2.5 py-3 text-sm text-slate-500 dark:text-slate-400">Tidak ada yang cocok</li>
          </ul>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, nextTick, watch, useId } from 'vue';
import { onClickOutside, useEventListener } from '@vueuse/core';
import { ChevronDown, Check, Search } from 'lucide-vue-next';

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  // [{ value, label, hint? }]
  options: { type: Array, required: true },
  ariaLabel: { type: String, default: '' },
  // id label <label> di formulir, sebagai ganti ariaLabel
  labelledby: { type: String, default: '' },
  placeholder: { type: String, default: '' },
  searchPlaceholder: { type: String, default: 'Cari' },
  // Garis lebih tegas bila filter sedang dipakai
  active: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  borderClass: { type: String, default: 'border-slate-300 dark:border-slate-700' },
  // Kotak cari muncul bila pilihannya lebih dari angka ini
  searchThreshold: { type: Number, default: 7 },
});

const emit = defineEmits(['update:modelValue']);

const uid = useId();
const triggerRef = ref(null);
const popoverRef = ref(null);
const searchRef = ref(null);
const listRef = ref(null);
const open = ref(false);
const query = ref('');
const highlighted = ref(0);
const pos = reactive({ top: 0, left: 0, width: 256 });

const searchable = computed(() => props.options.length > props.searchThreshold);
const hasValue = computed(() => props.options.some(o => o.value === props.modelValue));
const selectedLabel = computed(() => {
  const match = props.options.find(o => o.value === props.modelValue);
  if (match) return match.label;
  return props.placeholder || props.modelValue || props.options[0]?.label || '';
});

// Pencarian tidak peka huruf besar dan mengabaikan titik/koma, jadi "pt scm" cocok dengan "PT. SCM"
const normalize = (text) => String(text ?? '').toLowerCase().replace(/[.,]/g, '').replace(/\s+/g, ' ').trim();
const filtered = computed(() => {
  const q = normalize(query.value);
  if (!q) return props.options;
  return props.options.filter(opt => normalize(opt.label).includes(q) || normalize(opt.hint).includes(q));
});

const activeId = computed(() => (filtered.value.length ? `${uid}-opt-${highlighted.value}` : undefined));

watch(query, () => { highlighted.value = 0; });

// Di bawah tombol; naik ke atas bila ruang di bawah tidak cukup
function place() {
  const rect = triggerRef.value?.getBoundingClientRect();
  if (!rect) return;
  if (rect.bottom < 0 || rect.top > window.innerHeight) {
    close(false);
    return;
  }
  const width = Math.max(rect.width, 256);
  const height = popoverRef.value?.offsetHeight || (searchable.value ? 320 : 280);
  const opensUp = rect.bottom + 8 + height > window.innerHeight - 8 && rect.top - 8 - height > 8;
  pos.width = width;
  pos.left = Math.min(Math.max(8, rect.left), window.innerWidth - width - 8);
  pos.top = opensUp ? rect.top - 8 - height : rect.bottom + 8;
}

function openMenu() {
  if (open.value || props.disabled) return;
  open.value = true;
  query.value = '';
  const current = props.options.findIndex(o => o.value === props.modelValue);
  highlighted.value = Math.max(0, current);
  place();
  nextTick(() => {
    place();
    (searchable.value ? searchRef.value : listRef.value)?.focus({ preventScroll: true });
    scrollToHighlighted();
  });
}

function close(returnFocus) {
  if (!open.value) return;
  open.value = false;
  if (returnFocus) triggerRef.value?.focus({ preventScroll: true });
}

function toggle() {
  open.value ? close(false) : openMenu();
}

function move(step) {
  if (!filtered.value.length) return;
  highlighted.value = (highlighted.value + step + filtered.value.length) % filtered.value.length;
  nextTick(scrollToHighlighted);
}

function scrollToHighlighted() {
  const el = document.getElementById(`${uid}-opt-${highlighted.value}`);
  el?.scrollIntoView({ block: 'nearest' });
}

function choose(value) {
  emit('update:modelValue', value);
  close(true);
}

function chooseHighlighted() {
  const opt = filtered.value[highlighted.value];
  if (opt) choose(opt.value);
}

watch(filtered, () => nextTick(() => open.value && place()));

onClickOutside(triggerRef, () => close(false), { ignore: [popoverRef] });
useEventListener(window, 'scroll', (e) => {
  // Gulir di dalam daftar sendiri tidak memindahkan posisi
  if (open.value && !popoverRef.value?.contains(e.target)) place();
}, { capture: true, passive: true });
useEventListener(window, 'resize', () => open.value && place());
</script>
