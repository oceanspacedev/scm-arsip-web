<script setup>
import { AlertDialogCancel } from 'reka-ui';
import { cn } from '@/lib/utils';
import { buttonVariants } from '@/components/ui/button';

defineOptions({ inheritAttrs: false });

const props = defineProps({ class: { type: null, default: undefined } });
</script>

<template>
  <AlertDialogCancel v-bind="$attrs" :class="cn(buttonVariants({ variant: 'outline' }), props.class)">
    <slot />
  </AlertDialogCancel>
</template>
