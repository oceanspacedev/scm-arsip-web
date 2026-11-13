<script setup>
import { DropdownMenuLabel } from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <DropdownMenuLabel data-slot="dropdown-menu-label" v-bind="$attrs" :class="cn('px-2 py-1.5 text-xs font-medium text-muted-foreground', props.class)">
    <slot />
  </DropdownMenuLabel>
</template>
