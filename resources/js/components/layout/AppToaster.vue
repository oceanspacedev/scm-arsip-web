<script setup>
import { watch } from 'vue';
import { toast } from 'vue-sonner';
import { Toaster } from '@/components/ui/sonner';
import { isDark } from '@/lib/theme';
import { useTaxStore } from '../../store/taxStore';

const store = useTaxStore();

// Notifikasi dari store.notify() ditampilkan sebagai toast (sonner). Tiap notifikasi punya id baru.
watch(
  () => store.state.activeNotification?.id,
  id => {
    const notification = store.state.activeNotification;
    if (!id || !notification) return;
    // Pesan panjang butuh waktu baca lebih lama, sama seperti sebelumnya
    const duration = Math.min(12000, Math.max(4000, String(notification.message).length * 60));
    const show = { success: toast.success, warning: toast.warning, error: toast.error }[notification.type] || toast.info;
    show(notification.message, { duration });
  },
);
</script>

<template>
  <Toaster position="top-right" :offset="{ top: '76px' }" :theme="isDark ? 'dark' : 'light'" />
</template>
