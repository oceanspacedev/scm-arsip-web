<script setup>
import { DropdownMenuRoot } from 'reka-ui';
</script>

<template>
  <DropdownMenuRoot v-slot="slotProps">
    <slot v-bind="slotProps" />
  </DropdownMenuRoot>
</template>
