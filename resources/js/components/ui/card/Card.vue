<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'rounded-xl border border-slate-200/80 dark:border-slate-800 bg-white dark:bg-graphite-card text-slate-950 dark:text-slate-100 shadow-sm',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
