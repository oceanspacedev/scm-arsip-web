<template>
  <Dialog
    :open="open"
    :title="modalTitle"
    description="Pilih satu atau beberapa berkas untuk diunggah ke kategori ini."
    maxWidth="max-w-md"
    @update:open="$emit('update:open', $event)"
  >
    <div class="flex flex-col gap-4">
      <input
        ref="fileInput"
        type="file"
        multiple
        accept=".pdf, .jpg, .jpeg, .png"
        class="hidden"
        @change="handleFileChange"
      />

      <!-- Area tarik & lepas; diklik untuk memilih berkas dari komputer -->
      <button
        type="button"
        class="flex w-full cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed px-4 py-8 text-center transition-colors outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
        :class="isDragging ? 'border-ring bg-muted' : 'hover:border-ring hover:bg-muted/50'"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleFileDrop"
        @click="$refs.fileInput.click()"
      >
        <span class="text-sm font-medium">
          Tarik berkas ke sini atau <span class="underline underline-offset-4">pilih dari komputer</span>
        </span>
        <span class="text-xs text-muted-foreground">
          Mendukung PDF, JPG, PNG (bisa pilih banyak berkas, maks. 15 MB)
        </span>
      </button>

      <!-- Berkas terpilih -->
      <div v-if="selectedFiles.length > 0" class="flex flex-col gap-2">
        <div class="flex items-center justify-between gap-3">
          <p class="text-xs text-muted-foreground">{{ selectedFiles.length }} berkas dipilih</p>
          <Button
            variant="ghost"
            size="sm"
            class="px-2 text-muted-foreground"
            :disabled="isUploading"
            @click="selectedFiles = []"
          >
            Hapus Semua
          </Button>
        </div>

        <ul class="max-h-40 divide-y overflow-y-auto rounded-lg border">
          <li v-for="(f, idx) in selectedFiles" :key="idx" class="flex items-center gap-3 py-2 pr-2 pl-3">
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-medium" :title="f.name">{{ f.name }}</p>
              <p class="text-xs tabular-nums text-muted-foreground">{{ formatSize(f.size) }}</p>
            </div>
            <Button
              v-if="!isUploading"
              variant="ghost"
              size="icon-sm"
              class="text-muted-foreground"
              title="Batalkan berkas ini"
              aria-label="Batalkan berkas ini"
              @click="removeFile(idx)"
            >
              <X />
            </Button>
          </li>
        </ul>

        <!-- Progres saat mengunggah -->
        <div v-if="isUploading" class="flex flex-col gap-2 pt-1">
          <div class="flex items-center justify-between gap-3 text-xs text-muted-foreground">
            <span>Mengunggah berkas...</span>
            <span class="tabular-nums">{{ uploadProgress }}%</span>
          </div>
          <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
            <div
              class="h-full rounded-full bg-primary transition-all duration-300"
              :style="{ width: uploadProgress + '%' }"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <Button variant="outline" :disabled="isUploading" @click="$emit('update:open', false)">
        Batal
      </Button>
      <Button :disabled="selectedFiles.length === 0 || isUploading" @click="startUpload">
        Unggah {{ selectedFiles.length > 0 ? `(${selectedFiles.length}) Berkas` : 'Berkas' }}
      </Button>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { X } from 'lucide-vue-next';
import Dialog from '../ui/Dialog.vue';
import { Button } from '@/components/ui/button';
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
