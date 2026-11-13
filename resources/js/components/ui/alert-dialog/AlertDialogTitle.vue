<script setup>
import { AlertDialogTitle } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <AlertDialogTitle data-slot="alert-dialog-title" :class="cn('text-base font-semibold', props.class)">
    <slot />
  </AlertDialogTitle>
</template>
