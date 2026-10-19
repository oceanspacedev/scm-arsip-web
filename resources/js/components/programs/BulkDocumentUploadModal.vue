<template>
  <Dialog
    :open="open"
    title="Unggah Invoice & Faktur Pajak"
    description="Jenis dokumen dikenali otomatis. Invoice dicocokkan lewat No. Surat Jalan, faktur pajak lewat No. Faktur atau nilai DPP/PPN. Prosesnya berjalan di latar belakang, jadi Anda bisa langsung membuka halaman lain."
    maxWidth="max-w-2xl"
    @update:open="handleOpenChange"
  >
    <div class="space-y-4 text-xs">
      <div
        class="rounded-lg border border-dashed px-4 py-6 text-center transition-colors cursor-pointer"
        :class="isDragging
          ? 'border-slate-500 bg-slate-50 dark:bg-slate-800/60'
          : 'border-slate-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/40'"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        @click="fileInput?.click()"
      >
        <input ref="fileInput" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="hidden" @change="handlePick" />
        <p class="font-medium text-slate-700 dark:text-slate-300">Tarik invoice / faktur pajak ke sini atau pilih dari komputer</p>
        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">PDF, JPG, PNG · maks. 15 MB per berkas</p>
      </div>

      <ul
        v-if="files.length > 0"
        class="max-h-[40vh] overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800"
      >
        <li v-for="(file, index) in files" :key="`${file.name}-${index}`" class="px-3 py-2 flex items-center justify-between gap-3">
          <p class="min-w-0 truncate font-medium text-slate-800 dark:text-slate-200" :title="file.name">{{ file.name }}</p>
          <button
            type="button"
            class="shrink-0 text-[11px] text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer"
            @click="files.splice(index, 1)"
          >
            Hapus
          </button>
        </li>
      </ul>
    </div>

    <template #footer>
      <button
        type="button"
        class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer"
        @click="handleOpenChange(false)"
      >
        Batal
      </button>
      <button
        type="button"
        class="h-8 px-3.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="files.length === 0"
        @click="send"
      >
        {{ files.length > 0 ? `Kirim ${files.length} berkas` : 'Kirim' }}
      </button>
    </template>
  </Dialog>
</template>

<script setup>
import { ref } from 'vue';
import Dialog from '../ui/Dialog.vue';
import { useDocumentUploadQueue } from '../../store/documentUploadQueue';

defineProps({
  open: { type: Boolean, default: false }
});
const emit = defineEmits(['update:open']);

const { enqueue } = useDocumentUploadQueue();

const fileInput = ref(null);
const isDragging = ref(false);
const files = ref([]);

function handlePick(event) {
  files.value.push(...Array.from(event.target.files || []));
  event.target.value = '';
}

function handleDrop(event) {
  isDragging.value = false;
  files.value.push(...Array.from(event.dataTransfer.files || []));
}

// Berkas diserahkan ke antrean latar belakang; progresnya tampil di panel pojok kanan bawah
function send() {
  enqueue(files.value);
  handleOpenChange(false);
}

function handleOpenChange(value) {
  if (!value) {
    files.value = [];
  }
  emit('update:open', value);
}
</script>
