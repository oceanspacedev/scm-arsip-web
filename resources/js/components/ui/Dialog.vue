<template>
  <!-- Dibangun di atas Dialog reka-ui: fokus terkunci di dalam & kembali ke pemicu, Escape/klik luar
       hanya menutup lapisan paling atas (menu ⋯, Select, konfirmasi hapus), scroll halaman dikunci. -->
  <DialogRoot :open="open" @update:open="onOpenChange">
    <DialogPortal>
      <DialogOverlay
        class="fixed inset-0 z-50 bg-black/50 data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0"
      />
      <DialogContent
        v-bind="{ ...$attrs, ...(description ? {} : { 'aria-describedby': undefined }) }"
        :class="cn(
          'fixed top-1/2 left-1/2 z-50 flex max-h-[90vh] w-[calc(100%-2rem)] -translate-x-1/2 -translate-y-1/2 flex-col overflow-hidden rounded-lg border bg-background text-foreground shadow-lg outline-none duration-200',
          'data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95',
          maxWidth,
        )"
        @interact-outside="onInteractOutside"
      >
        <!-- Header -->
        <div class="flex shrink-0 flex-col gap-1 border-b py-4 pr-12 pl-6">
          <DialogTitle class="text-base font-semibold">{{ title }}</DialogTitle>
          <DialogDescription v-if="description" class="text-sm text-muted-foreground">{{ description }}</DialogDescription>
        </div>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-6 py-5 text-sm">
          <slot />
        </div>

        <!-- Footer: tombol rata kanan -->
        <div v-if="$slots.footer" class="flex shrink-0 flex-wrap items-center justify-end gap-2 border-t px-6 py-4">
          <slot name="footer" />
        </div>

        <!-- Tombol tutup terakhir di urutan fokus, supaya fokus awal jatuh ke isian pertama -->
        <DialogClose
          class="absolute top-3.5 right-4 flex size-7 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
        >
          <X class="size-4" />
          <span class="sr-only">Tutup</span>
        </DialogClose>
      </DialogContent>
    </DialogPortal>
  </DialogRoot>
</template>

<script setup>
import { X } from 'lucide-vue-next';
import {
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogOverlay,
  DialogPortal,
  DialogRoot,
  DialogTitle,
} from 'reka-ui';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

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

function onOpenChange(value) {
  if (value) {
    emit('update:open', true);
    return;
  }
  emit('update:open', false);
  emit('close');
}

// Klik di luar panel hanya menutup bila diizinkan (mis. tidak saat sedang mengunggah)
function onInteractOutside(event) {
  if (!props.closeOnBackdrop) event.preventDefault();
}
</script>
