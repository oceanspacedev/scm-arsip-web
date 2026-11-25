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
    :class="cn(
      'relative flex h-11 w-11 items-center justify-center border-y border-r border-slate-300 dark:border-slate-700 text-base font-medium tabular-nums transition-colors first:rounded-l-lg first:border-l last:rounded-r-lg bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 select-none',
      slot.isActive && 'z-10 ring-2 ring-blue-500/30 border-blue-500 dark:border-blue-400',
      props.class
    )"
  >
    {{ slot.char }}
    <div
      v-if="slot.hasFakeCaret"
      class="pointer-events-none absolute inset-0 flex items-center justify-center"
    >
      <div class="h-4 w-px animate-caret-blink bg-slate-900 dark:bg-slate-100 duration-1000" />
    </div>
  </div>
</template>
