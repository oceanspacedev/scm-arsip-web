<script setup>
import { TabsTrigger } from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <TabsTrigger
    data-slot="tabs-trigger"
    v-bind="$attrs"
    :class="cn(
      'inline-flex h-full shrink-0 cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-3 text-sm font-medium whitespace-nowrap text-muted-foreground transition-[color,box-shadow] outline-none',
      'hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50',
      'data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-xs dark:data-[state=active]:border-input dark:data-[state=active]:bg-input/30',
      props.class,
    )"
  >
    <slot />
  </TabsTrigger>
</template>
