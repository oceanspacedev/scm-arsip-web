<template>
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 left-4 sm:left-auto z-[60] flex flex-col items-end gap-2 pointer-events-none">
      <Transition
        enter-active-class="transition-[opacity,transform] duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]"
        enter-from-class="opacity-0 translate-y-2"
        leave-active-class="transition-opacity duration-150"
        leave-to-class="opacity-0"
      >
        <div
          v-if="notification"
          :key="notification.id"
          role="status"
          aria-live="polite"
          class="pointer-events-auto flex w-full sm:w-auto sm:max-w-sm items-start gap-3 py-3 pl-4 pr-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-[0_8px_24px_rgba(0,0,0,0.08)] dark:shadow-[0_8px_24px_rgba(0,0,0,0.4)] text-sm"
        >
          <component :is="icon.component" :class="['w-4 h-4 shrink-0 mt-0.5', icon.color]" />

          <p class="flex-1 text-slate-800 dark:text-slate-100 leading-5">
            {{ notification.message }}
          </p>

          <button
            type="button"
            aria-label="Tutup notifikasi"
            class="-my-1 p-1 rounded-md text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:text-slate-500 dark:hover:text-slate-200 dark:hover:bg-slate-700 transition-colors cursor-pointer"
            @click="dismiss"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </Transition>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { CircleCheck, TriangleAlert, Info, X } from 'lucide-vue-next';
import { useTaxStore } from '../../store/taxStore';

const store = useTaxStore();
const notification = computed(() => store.state.activeNotification);

// Warna hanya di ikon; permukaan toast mengikuti tema (putih / grafit)
const icon = computed(() => {
  const type = notification.value?.type;
  if (type === 'success') return { component: CircleCheck, color: 'text-emerald-600 dark:text-emerald-400' };
  if (type === 'warning') return { component: TriangleAlert, color: 'text-amber-600 dark:text-amber-400' };
  if (type === 'error') return { component: TriangleAlert, color: 'text-rose-600 dark:text-rose-400' };
  return { component: Info, color: 'text-blue-600 dark:text-blue-400' };
});

function dismiss() {
  store.state.activeNotification = null;
}
</script>
