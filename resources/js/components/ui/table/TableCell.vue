<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <td data-slot="table-cell" :class="cn('px-2 py-3 align-middle whitespace-nowrap [&:has([role=checkbox])]:pr-0', props.class)">
    <slot />
  </td>
</template>
