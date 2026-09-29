<template>
  <Dialog
    :open="open"
    :title="modalTitle"
    description="Pilih satu atau beberapa berkas untuk diunggah ke kategori ini."
    maxWidth="max-w-md"
    @update:open="$emit('update:open', $event)"
  >
    <div class="space-y-4 text-xs font-sans">
      <!-- Drag & Drop Area -->
      <div
        class="border-2 border-dashed rounded-xl p-5 text-center transition-colors cursor-pointer"
        :class="isDragging ? 'border-slate-500 bg-slate-100/70 dark:bg-slate-800/70' : 'border-slate-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-500 bg-slate-50/50 dark:bg-slate-900/40 hover:bg-slate-100/40 dark:hover:bg-slate-800/40'"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleFileDrop"
        @click="$refs.fileInput.click()"
      >
        <input
          ref="fileInput"
          type="file"
          multiple
          accept=".pdf, .jpg, .jpeg, .png"
          class="hidden"
          @change="handleFileChange"
        />

        <div class="flex flex-col items-center justify-center space-y-2">
          <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center shadow-2xs">
            <Upload class="w-5 h-5 stroke-[2]" />
          </div>
          <div>
            <p class="text-xs font-bold text-slate-800 dark:text-slate-200">
              Tarik berkas ke sini atau <span class="text-slate-900 dark:text-slate-100 underline font-semibold hover:text-black dark:hover:text-white">pilih dari komputer</span>
            </p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
              Mendukung: <strong>PDF, JPG, PNG</strong> (bisa pilih banyak berkas, maks 15 MB)
            </p>
          </div>
        </div>
      </div>

      <!-- Selected Files List -->
      <div v-if="selectedFiles.length > 0" class="space-y-2">
        <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400 font-medium px-1">
          <span>{{ selectedFiles.length }} berkas dipilih:</span>
          <button
            type="button"
            class="text-rose-600 dark:text-rose-400 hover:underline cursor-pointer"
            :disabled="isUploading"
            @click="selectedFiles = []"
          >
            Hapus Semua
          </button>
        </div>

        <div class="max-h-40 overflow-y-auto space-y-1.5 pr-1">
          <div
            v-for="(f, idx) in selectedFiles"
            :key="idx"
            class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 flex items-center justify-between gap-2 shadow-2xs"
          >
            <div class="flex items-center gap-2 min-w-0">
              <FileText class="w-4 h-4 text-slate-500 dark:text-slate-400 shrink-0" />
              <div class="min-w-0">
                <p class="font-semibold text-slate-800 dark:text-slate-200 truncate text-xs">{{ f.name }}</p>
                <p class="text-[10px] text-slate-400 dark:text-slate-500 font-mono">{{ formatSize(f.size) }}</p>
              </div>
            </div>
            <button
              v-if="!isUploading"
              type="button"
              class="p-1 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded transition-colors cursor-pointer"
              title="Batalkan berkas ini"
              @click="removeFile(idx)"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Progress Bar when Uploading -->
        <div v-if="isUploading" class="p-3 bg-slate-50 dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 space-y-1.5">
          <div class="flex items-center justify-between text-[11px] text-slate-600 dark:text-slate-300">
            <span>Mengunggah berkas...</span>
            <span class="font-mono font-bold">{{ uploadProgress }}%</span>
          </div>
          <div class="w-full bg-slate-200 dark:bg-slate-800 rounded-full h-1.5 overflow-hidden">
            <div
              class="bg-slate-900 dark:bg-slate-100 h-1.5 rounded-full transition-all duration-300"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-2">
        <Button
          variant="outline"
          size="sm"
          :disabled="isUploading"
          @click="$emit('update:open', false)"
        >
          Batal
        </Button>
        <button
          type="button"
          class="h-8 px-3.5 rounded-lg bg-slate-900 hover:bg-slate-800 active:bg-slate-950 text-white dark:bg-slate-100 dark:hover:bg-white dark:text-slate-900 text-xs font-semibold transition-colors shadow-2xs cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
          :disabled="selectedFiles.length === 0 || isUploading"
          @click="startUpload"
        >
          <svg
            v-if="isUploading"
            class="animate-spin -ml-0.5 mr-1 h-3.5 w-3.5 text-current"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
          </svg>
          <span>Unggah {{ selectedFiles.length > 0 ? `(${selectedFiles.length}) Berkas` : 'Berkas' }}</span>
        </button>
      </div>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Upload, FileText, X } from 'lucide-vue-next';
import Dialog from '../ui/Dialog.vue';
import Button from '../ui/Button.vue';
import { useTaxStore } from '../../store/taxStore';

const store = useTaxStore();

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  docType: {
    type: String,
    default: 'faktur_pajak'
  },
  docLabel: {
    type: String,
    default: 'Faktur Pajak'
  }
});

const emit = defineEmits(['update:open', 'uploaded']);

const isDragging = ref(false);
const selectedFiles = ref([]);
const uploadProgress = ref(0);
const isUploading = ref(false);

const modalTitle = computed(() => {
  return `Unggah ${props.docLabel}`;
});

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    selectedFiles.value = [];
    uploadProgress.value = 0;
    isUploading.value = false;
  }
});

function handleFileChange(e) {
  const files = Array.from(e.target.files || []);
  if (files.length > 0) {
    processFiles(files);
  }
  // reset input so the same files can be reselected if needed
  e.target.value = '';
}

function handleFileDrop(e) {
  isDragging.value = false;
  const files = Array.from(e.dataTransfer.files || []);
  if (files.length > 0) {
    processFiles(files);
  }
}

function formatSize(bytes) {
  if (!bytes) return '0 B';
  if (bytes >= 1048576) {
    return `${(bytes / 1048576).toFixed(1)} MB`;
  }
  return `${(bytes / 1024).toFixed(1)} KB`;
}

function processFiles(files) {
  const maxBytes = 15 * 1024 * 1024; // 15 MB
  const validFiles = [];

  for (const file of files) {
    if (file.size > maxBytes) {
      store.notify(`Berkas "${file.name}" melebihi batas 15 MB.`, 'warning');
      continue;
    }
    // Check if duplicate in current list
    if (!selectedFiles.value.some(f => f.name === file.name && f.size === file.size)) {
      validFiles.push(file);
    }
  }

  selectedFiles.value.push(...validFiles);
}

function removeFile(index) {
  selectedFiles.value.splice(index, 1);
}

async function readFileAsDataUrl(file) {
  return new Promise((resolve) => {
    const reader = new FileReader();
    reader.onload = (e) => resolve(e.target.result);
    reader.onerror = () => resolve(null);
    reader.readAsDataURL(file);
  });
}

async function startUpload() {
  if (selectedFiles.value.length === 0) return;

  isUploading.value = true;
  uploadProgress.value = 20;

  const total = selectedFiles.value.length;
  const uploadedResults = [];

  for (let i = 0; i < total; i++) {
    const file = selectedFiles.value[i];
    const dataUrl = await readFileAsDataUrl(file);
    const sizeFormatted = formatSize(file.size);

    uploadedResults.push({
      docType: props.docType,
      file: file,
      name: file.name,
      sizeFormatted: sizeFormatted,
      type: file.type || 'application/pdf',
      dataUrl: dataUrl
    });

    uploadProgress.value = Math.min(95, Math.round(((i + 1) / total) * 90));
  }

  uploadProgress.value = 100;

  setTimeout(() => {
    emit('uploaded', uploadedResults);
    isUploading.value = false;
    emit('update:open', false);
  }, 200);
}
</script>
