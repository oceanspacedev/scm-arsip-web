<script setup>
import { cn } from '@/lib/utils';

const props = defineProps({
  class: { type: null, default: undefined },
  // Kontainer bisa diganti, mis. supaya header sticky terhadap halaman di layar lebar
  containerClass: { type: null, default: 'relative w-full overflow-x-auto' },
});
</script>

<template>
  <div data-slot="table-container" :class="props.containerClass">
    <table data-slot="table" :class="cn('w-full caption-bottom text-sm', props.class)">
      <slot />
    </table>
  </div>
</template>
