<script setup>
import { computed } from 'vue';
import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-vue-next';
import { cn } from '@/lib/utils';

const props = defineProps({
  column: { type: Object, required: true },
  title: { type: String, default: '' },
  align: { type: String, default: 'left' },
});

const sorted = computed(() => props.column.getIsSorted());
</script>

<template>
  <span v-if="!column.getCanSort()" :class="cn('block', align === 'right' && 'text-right', align === 'center' && 'text-center')">
    {{ title }}
  </span>
  <div v-else :class="cn('flex', align === 'right' && 'justify-end', align === 'center' && 'justify-center')">
    <button
      type="button"
      :class="cn(
        'inline-flex h-8 cursor-pointer items-center gap-1 rounded-md px-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
        align === 'right' ? '-mr-2' : '-ml-2',
        sorted && 'text-foreground',
      )"
      @click="column.getToggleSortingHandler()?.($event)"
    >
      <span>{{ title }}</span>
      <ArrowUp v-if="sorted === 'asc'" class="size-3.5" />
      <ArrowDown v-else-if="sorted === 'desc'" class="size-3.5" />
      <ChevronsUpDown v-else class="size-3.5 opacity-50" />
    </button>
  </div>
</template>
