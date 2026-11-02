<template>
  <Dialog
    :open="open"
    title="Unggah Invoice & Faktur Pajak"
    description="Jenis dokumen dikenali otomatis. Invoice dicocokkan lewat No. Surat Jalan, faktur pajak lewat No. Faktur atau nilai DPP/PPN. Prosesnya berjalan di latar belakang, jadi Anda bisa langsung membuka halaman lain."
    maxWidth="max-w-2xl"
    :closeOnBackdrop="!isSending"
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
        <p class="mt-1 text-[11px] text-slate-400 dark:text-slate-500">PDF, JPG, PNG · maks. 15 MB per berkas · PDF gabungan juga bisa</p>
      </div>

      <label class="flex items-start gap-2.5 cursor-pointer">
        <input v-model="splitPages" type="checkbox" class="mt-0.5 w-3.5 h-3.5 rounded accent-slate-700 cursor-pointer" />
        <span>
          <span class="block font-medium text-slate-700 dark:text-slate-300">Pecah PDF per halaman</span>
          <span class="block text-[11px] text-slate-400 dark:text-slate-500">
            Untuk PDF gabungan: tiap halaman diproses sebagai dokumen sendiri dan masuk ke programnya masing-masing.
          </span>
        </span>
      </label>

      <ul
        v-if="entries.length > 0"
        class="max-h-[40vh] overflow-y-auto rounded-lg border border-slate-200 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800"
      >
        <li v-for="(entry, index) in entries" :key="entry.key" class="px-3 py-2 flex items-center justify-between gap-3">
          <div class="min-w-0">
            <p class="truncate font-medium text-slate-800 dark:text-slate-200" :title="entry.file.name">{{ entry.file.name }}</p>
            <p v-if="entry.pages > 1" class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">
              {{ entry.pages }} halaman{{ splitPages ? ` · dipecah jadi ${entry.pages} dokumen` : '' }}
            </p>
          </div>
          <button
            type="button"
            class="shrink-0 text-[11px] text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer"
            :disabled="isSending"
            @click="entries.splice(index, 1)"
          >
            Hapus
          </button>
        </li>
      </ul>
    </div>

    <template #footer>
      <button
        type="button"
        class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer disabled:opacity-50"
        :disabled="isSending"
        @click="handleOpenChange(false)"
      >
        Batal
      </button>
      <button
        type="button"
        class="h-8 px-3.5 rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="entries.length === 0 || isSending"
        @click="send"
      >
        <Loader2 v-if="isSending" class="w-3.5 h-3.5 animate-spin text-slate-500" />
        {{ isSending ? 'Memecah PDF…' : (entries.length > 0 ? `Kirim ${documentCount} dokumen` : 'Kirim') }}
      </button>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import Dialog from '../ui/Dialog.vue';
import { useDocumentUploadQueue } from '../../store/documentUploadQueue';
import { countPdfPages, splitPdfPages } from '../../utils/pdfSplit';

defineProps({
  open: { type: Boolean, default: false }
});
const emit = defineEmits(['update:open']);

const { enqueue } = useDocumentUploadQueue();

const fileInput = ref(null);
const isDragging = ref(false);
const isSending = ref(false);
const splitPages = ref(true);
const entries = ref([]);
let nextKey = 0;

const documentCount = computed(() =>
  entries.value.reduce((sum, entry) => sum + (splitPages.value && entry.pages > 1 ? entry.pages : 1), 0)
);

function addFiles(fileList) {
  for (const file of Array.from(fileList || [])) {
    const entry = { key: nextKey++, file, pages: null };
    entries.value.push(entry);
    // Jumlah halaman dihitung di latar, untuk ditampilkan sebelum dikirim
    countPdfPages(file).then(pages => {
      const target = entries.value.find(e => e.key === entry.key);
      if (target) target.pages = pages;
    });
  }
}

function handlePick(event) {
  addFiles(event.target.files);
  event.target.value = '';
}

function handleDrop(event) {
  isDragging.value = false;
  addFiles(event.dataTransfer.files);
}

// Berkas diserahkan ke antrean latar belakang; progresnya tampil di panel pojok kanan bawah
async function send() {
  isSending.value = true;
  try {
    const files = [];
    for (const entry of entries.value) {
      if (!splitPages.value) {
        files.push(entry.file);
        continue;
      }
      try {
        files.push(...await splitPdfPages(entry.file));
      } catch {
        // PDF rusak/terkunci tidak bisa dipecah: dikirim utuh saja
        files.push(entry.file);
      }
    }
    enqueue(files);
  } finally {
    isSending.value = false;
  }
  handleOpenChange(false);
}

function handleOpenChange(value) {
  if (!value && isSending.value) return;
  if (!value) {
    entries.value = [];
  }
  emit('update:open', value);
}
</script>
