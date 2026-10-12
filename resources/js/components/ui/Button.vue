<template>
  <button
    :type="type"
    :disabled="disabled || loading"
    :class="[
      'inline-flex items-center justify-center font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#0F766E]/20 focus-visible:ring-offset-1 disabled:opacity-50 disabled:pointer-events-none cursor-pointer',
      variantClasses[variant] || variantClasses.default,
      sizeClasses[size] || sizeClasses.default,
      className
    ]"
    @click="$emit('click', $event)"
  >
    <svg
      v-if="loading"
      class="animate-spin -ml-1 mr-2 h-4 w-4"
      xmlns="http://www.w3.org/2000/svg"
      fill="none"
      viewBox="0 0 24 24"
    >
      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
    </svg>
    <slot />
  </button>
</template>

<script setup>
defineProps({
  type: {
    type: String,
    default: 'button'
  },
  variant: {
    type: String,
    default: 'default' // default (primary teal), secondary, outline, ghost, destructive, link
  },
  size: {
    type: String,
    default: 'default' // sm, default, lg, icon
  },
  disabled: {
    type: Boolean,
    default: false
  },
  loading: {
    type: Boolean,
    default: false
  },
  className: {
    type: String,
    default: ''
  }
});

defineEmits(['click']);

const variantClasses = {
  default: 'rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium',
  secondary: 'bg-slate-100 text-slate-800 hover:bg-slate-200 active:bg-slate-300 rounded-lg border border-slate-200',
  outline: 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:text-slate-900 rounded-lg shadow-2xs',
  ghost: 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg',
  destructive: 'rounded-lg bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-medium',
  subtleTeal: 'bg-sky-50 text-sky-700 border border-sky-200 hover:bg-sky-100 rounded-lg',
};

const sizeClasses = {
  sm: 'h-8 px-2.5 text-xs gap-1.5',
  default: 'h-9 px-3.5 text-sm gap-2',
  lg: 'h-10 px-5 text-sm gap-2.5',
  icon: 'h-8 w-8 p-0 rounded-md',
  iconSm: 'h-7 w-7 p-0 rounded-md'
};
</script>
