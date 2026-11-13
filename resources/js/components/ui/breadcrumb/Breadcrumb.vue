<script setup>
import { ChevronRight } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

// items: [{ label, to? }]; item terakhir adalah halaman aktif
const props = defineProps({
  items: { type: Array, default: () => [] },
  class: { type: null, default: undefined },
});
</script>

<template>
  <nav aria-label="breadcrumb" :class="cn('min-w-0', props.class)">
    <ol class="flex min-w-0 items-center gap-1.5 text-sm font-medium text-muted-foreground">
      <template v-for="(item, index) in items" :key="index">
        <li v-if="index > 0" role="presentation" aria-hidden="true" class="shrink-0 [&>svg]:size-3.5">
          <ChevronRight />
        </li>
        <li class="inline-flex min-w-0 items-center">
          <router-link
            v-if="item.to && index < items.length - 1"
            :to="item.to"
            class="truncate transition-colors hover:text-foreground"
          >
            {{ item.label }}
          </router-link>
          <span v-else aria-current="page" class="truncate font-medium text-foreground">{{ item.label }}</span>
        </li>
      </template>
    </ol>
  </nav>
</template>
