<script setup>
import { AlertDialogRoot } from 'reka-ui';
</script>

<template>
  <AlertDialogRoot v-slot="slotProps">
    <slot v-bind="slotProps" />
  </AlertDialogRoot>
</template>
