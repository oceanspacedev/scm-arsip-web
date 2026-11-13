<script setup>
import { TabsList } from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <TabsList
    data-slot="tabs-list"
    v-bind="$attrs"
    :class="cn('inline-flex h-9 w-fit max-w-full items-center justify-start overflow-x-auto rounded-lg bg-muted p-[3px] text-muted-foreground no-scrollbar', props.class)"
  >
    <slot />
  </TabsList>
</template>
