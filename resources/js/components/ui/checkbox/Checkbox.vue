<script setup>
import { Check, Minus } from 'lucide-vue-next';
import { CheckboxIndicator, CheckboxRoot } from 'reka-ui';
import { cn } from '@/lib/utils';

// v-model (true / false / 'indeterminate') dan atribut lain diteruskan ke reka-ui CheckboxRoot
defineOptions({ inheritAttrs: false });

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <CheckboxRoot
    v-slot="{ state }"
    data-slot="checkbox"
    v-bind="$attrs"
    :class="cn(
      'peer size-4 shrink-0 cursor-pointer rounded-[4px] border border-input shadow-xs transition-shadow outline-none dark:bg-input/30',
      'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50',
      // Kotak bergaris dengan tanda centang gelap (bukan kotak hitam penuh), sesuai gaya putih yang diminta
      'data-[state=checked]:border-primary data-[state=checked]:text-primary',
      'data-[state=indeterminate]:border-primary data-[state=indeterminate]:text-primary',
      props.class,
    )"
  >
    <CheckboxIndicator class="flex items-center justify-center text-current">
      <Minus v-if="state === 'indeterminate'" class="size-3.5" />
      <Check v-else class="size-3.5" />
    </CheckboxIndicator>
  </CheckboxRoot>
</template>
