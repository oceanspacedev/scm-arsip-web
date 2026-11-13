<script setup>
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const model = defineModel({ type: [String, Number], default: undefined });
const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <textarea
    v-model="model"
    data-slot="textarea"
    v-bind="$attrs"
    :class="cn(
      'flex min-h-16 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground',
      'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive dark:bg-input/30',
      props.class,
    )"
  />
</template>
