<script setup>
import { cn } from '@/lib/utils';
import { badgeVariants } from '.';

const props = defineProps({
  variant: { type: String, default: 'default' },
  class: { type: null, default: undefined },
});
</script>

<template>
  <span data-slot="badge" :class="cn(badgeVariants({ variant }), props.class)">
    <slot />
  </span>
</template>
