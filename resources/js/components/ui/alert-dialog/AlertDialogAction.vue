<script setup>
import { AlertDialogAction } from 'reka-ui';
import { cn } from '@/lib/utils';
import { buttonVariants } from '@/components/ui/button';

defineOptions({ inheritAttrs: false });

const props = defineProps({
  class: { type: null, default: undefined },
  variant: { type: String, default: 'default' },
});
</script>

<template>
  <AlertDialogAction v-bind="$attrs" :class="cn(buttonVariants({ variant }), props.class)">
    <slot />
  </AlertDialogAction>
</template>
