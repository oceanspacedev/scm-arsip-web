<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
  class: { type: null, default: undefined },
});
</script>

<template>
  <!-- Logo "A" (Arsip): huruf A dengan dua palang bertingkat seperti lembar arsip yang bertumpuk.
       Warna mengikuti token, jadi otomatis terbalik di mode gelap. -->
  <svg viewBox="0 0 32 32" :class="cn('size-8 shrink-0', props.class)" role="img" aria-label="Logo Arsip">
    <rect width="32" height="32" rx="9" class="fill-primary" />
    <g class="stroke-primary-foreground" fill="none" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 24.5 16 7.5l7 17" stroke-width="3.2" />
      <path d="M12.3 17.2h7.4M10.9 20.9h10.2" stroke-width="2.2" />
    </g>
  </svg>
</template>
