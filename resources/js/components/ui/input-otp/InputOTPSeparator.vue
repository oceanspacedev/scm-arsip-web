<script setup>
import { Minus } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const props = defineProps({
  class: { type: [String, Object, Array], default: undefined },
})
</script>

<template>
  <div role="separator" :class="cn('flex items-center px-1 text-muted-foreground', props.class)">
    <Minus class="size-4" />
  </div>
</template>
