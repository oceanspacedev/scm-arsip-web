<script setup>
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

// Satu isian form: label di atas, kontrol di slot, lalu pesan error atau teks bantuan (text-xs)
const props = defineProps({
  label: { type: String, default: '' },
  // id kontrol di dalam slot, supaya label bisa diklik
  for: { type: String, default: undefined },
  helper: { type: String, default: '' },
  error: { type: String, default: '' },
  required: { type: Boolean, default: false },
  class: { type: null, default: undefined },
});
</script>

<template>
  <div :class="cn('grid min-w-0 content-start gap-2', props.class)">
    <Label v-if="label || $slots.label" :for="props.for">
      <slot name="label">{{ label }}</slot>
      <span v-if="required" class="text-destructive" aria-hidden="true">*</span>
    </Label>
    <slot />
    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
    <p v-else-if="helper || $slots.helper" class="text-xs text-muted-foreground">
      <slot name="helper">{{ helper }}</slot>
    </p>
  </div>
</template>
