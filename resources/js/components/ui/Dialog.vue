<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 dark:bg-black/70 backdrop-blur-[2px]"
        @click.self="handleBackdropClick"
      >
        <Transition
          enter-active-class="transition duration-200 ease-out"
          enter-from-class="opacity-0 scale-98 translate-y-2"
          enter-to-class="opacity-100 scale-100 translate-y-0"
          leave-active-class="transition duration-150 ease-in"
          leave-from-class="opacity-100 scale-100 translate-y-0"
          leave-to-class="opacity-0 scale-98 translate-y-2"
        >
          <div
            :class="[
              'w-full bg-white dark:bg-graphite-card rounded-lg border border-slate-200 dark:border-slate-800 shadow-xl overflow-hidden flex flex-col max-h-[90vh]',
              maxWidthClass
            ]"
          >
            <!-- Header -->
            <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-graphite-card shrink-0">
              <div>
                <h3 class="text-base font-semibold text-slate-900 dark:text-slate-100">
                  {{ title }}
                </h3>
                <p v-if="description" class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                  {{ description }}
                </p>
              </div>
              <button
                type="button"
                class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 p-1.5 rounded-md hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                @click="close"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 overflow-y-auto flex-1 text-slate-800 dark:text-slate-200">
              <slot />
            </div>

            <!-- Footer -->
            <div
              v-if="$slots.footer"
              class="px-6 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900/60 flex items-center justify-end gap-2.5 shrink-0"
            >
              <slot name="footer" />
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: ''
  },
  description: {
    type: String,
    default: ''
  },
  maxWidth: {
    type: String,
    default: 'max-w-lg' // max-w-md, max-w-lg, max-w-xl, max-w-2xl, max-w-3xl, max-w-4xl
  },
  closeOnBackdrop: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['update:open', 'close']);

const maxWidthClass = computed(() => props.maxWidth);

function close() {
  emit('update:open', false);
  emit('close');
}

function handleBackdropClick() {
  if (props.closeOnBackdrop) {
    close();
  }
}

function handleKeydown(e) {
  if (e.key === 'Escape' && props.open) {
    close();
  }
}

watch(() => props.open, (val) => {
  if (val) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
});

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});
</script>
