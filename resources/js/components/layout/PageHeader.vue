<script setup>
defineProps({
  title: { type: String, required: true },
  description: { type: String, default: '' },
});
</script>

<template>
  <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
    <div class="min-w-0">
      <!-- Judul 30px (24px di ponsel), setara angka di kartu ringkasan -->
      <h1 class="text-2xl font-semibold tracking-tight text-balance sm:text-3xl">{{ title }}</h1>
      <!-- tabular-nums: deskripsi bisa berisi nomor PO/SJ & tanggal (halaman detail) -->
      <p v-if="description" class="mt-1 text-sm text-muted-foreground tabular-nums">{{ description }}</p>
    </div>
    <div v-if="$slots.actions" class="flex flex-wrap items-center gap-2">
      <slot name="actions" />
    </div>
  </div>
</template>
