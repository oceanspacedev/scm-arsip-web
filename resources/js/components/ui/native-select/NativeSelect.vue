<script setup>
import { ChevronDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

// <select> bawaan dengan gaya yang sama seperti Input. Pakai v-model; atribut lain (disabled,
// required, @change, id) diteruskan ke <select>. Isi <option> lewat slot.
defineOptions({ inheritAttrs: false });

const model = defineModel({ type: null, default: undefined });
const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <div :class="cn('relative w-full min-w-0', props.class)">
    <select
      v-model="model"
      data-slot="native-select"
      v-bind="$attrs"
      class="h-9 w-full min-w-0 cursor-pointer appearance-none rounded-md border border-input bg-background py-1 pr-9 pl-3 text-sm shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive dark:bg-input/30 [&>option]:bg-popover [&>option]:text-popover-foreground"
    >
      <slot />
    </select>
    <ChevronDown class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted-foreground" />
  </div>
</template>
