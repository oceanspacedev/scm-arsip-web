<script setup>
import { AlertDialogDescription } from 'reka-ui';
import { cn } from '@/lib/utils';

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <AlertDialogDescription data-slot="alert-dialog-description" :class="cn('text-sm text-muted-foreground', props.class)">
    <slot />
  </AlertDialogDescription>
</template>
