<script setup>
import { AvatarFallback, AvatarRoot } from 'reka-ui';
import { cn } from '@/lib/utils';

// Avatar inisial nama (aplikasi tidak menyimpan foto profil)
const props = defineProps({
  initials: { type: String, default: '' },
  class: { type: null, default: undefined },
});
</script>

<template>
  <AvatarRoot data-slot="avatar" :class="cn('relative flex size-8 shrink-0 overflow-hidden rounded-full', props.class)">
    <AvatarFallback class="flex size-full items-center justify-center rounded-full bg-muted text-xs font-medium text-foreground">
      {{ initials }}
    </AvatarFallback>
  </AvatarRoot>
</template>
