<script setup>
import { DropdownMenuSeparator } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <DropdownMenuSeparator data-slot="dropdown-menu-separator" :class="cn('-mx-1 my-1 h-px bg-border', props.class)" />
</template>
