<script setup>
import { Label } from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <Label
    data-slot="label"
    v-bind="$attrs"
    :class="cn('flex items-center gap-1 text-sm leading-none font-medium select-none peer-disabled:cursor-not-allowed peer-disabled:opacity-50', props.class)"
  >
    <slot />
  </Label>
</template>
