<script setup>
import { computed, inject } from 'vue';

// Kartu ringkasan bergaya Filament: ikon + label, angka besar, keterangan di bawah
const props = defineProps({
  label: { type: String, required: true },
  value: { type: String, required: true },
  hint: { type: String, default: '' },
  // Komponen ikon Heroicons (outline), mis. BanknotesIcon
  icon: { type: [Object, Function], default: null },
});

// Di dalam StatGrid ukuran angka ditentukan bersama (30px bila semua muat, selain itu 24px)
const sharedBig = inject('statValueBig', null);
const valueClass = computed(() => {
  if (sharedBig) return sharedBig.value ? 'text-3xl' : 'text-2xl';
  return 'text-2xl @[18rem]/stat:text-3xl';
});
</script>

<template>
  <div class="@container/stat flex min-w-0 flex-col rounded-xl border bg-card p-6 text-card-foreground shadow-xs">
    <p class="flex min-w-0 items-center gap-2 text-sm font-medium text-muted-foreground">
      <component :is="props.icon" v-if="props.icon" class="size-5 shrink-0" aria-hidden="true" />
      <span class="truncate">{{ props.label }}</span>
    </p>
    <p :class="['mt-3 truncate font-semibold tracking-tight tabular-nums', valueClass]" :title="props.value">{{ props.value }}</p>
    <p v-if="props.hint" class="mt-2 text-sm text-muted-foreground">{{ props.hint }}</p>
  </div>
</template>
