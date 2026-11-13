<script setup>
import { computed } from 'vue'
import { useVueOTPContext } from 'vue-input-otp'
import { cn } from '@/lib/utils'

const props = defineProps({
  index: { type: Number, required: true },
  class: { type: [String, Object, Array], default: undefined },
})

const context = useVueOTPContext()
const slot = computed(() => context?.value?.slots?.[props.index] || {})
</script>

<template>
  <div
    :data-active="slot.isActive || undefined"
    :class="cn(
      'relative flex size-10 items-center justify-center border-y border-r border-input bg-background text-sm font-medium tabular-nums shadow-xs transition-[color,box-shadow] outline-none select-none first:rounded-l-md first:border-l last:rounded-r-md dark:bg-input/30',
      'data-[active=true]:z-10 data-[active=true]:border-ring data-[active=true]:ring-[3px] data-[active=true]:ring-ring/50',
      props.class
    )"
  >
    {{ slot.char }}
    <div
      v-if="slot.hasFakeCaret"
      class="pointer-events-none absolute inset-0 flex items-center justify-center"
    >
      <div class="h-4 w-px animate-caret-blink bg-foreground" />
    </div>
  </div>
</template>
