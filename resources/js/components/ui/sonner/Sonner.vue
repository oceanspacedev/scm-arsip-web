<script setup>
import { Toaster as Sonner } from 'vue-sonner';
import 'vue-sonner/style.css';
</script>

<template>
  <Sonner
    class="toaster group"
    :style="{
      '--normal-bg': 'var(--popover)',
      '--normal-text': 'var(--popover-foreground)',
      '--normal-border': 'var(--border)',
      '--border-radius': 'var(--radius)',
    }"
    v-bind="$attrs"
  />
</template>
