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
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-5 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs font-sans"
        @click.self="close"
      >
        <div
          class="bg-white dark:bg-[#111827] rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-5xl h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150"
        >
          <!-- Clean, Minimal, Non-Slop Header -->
          <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-[#111827] shrink-0 gap-4">
            <div class="min-w-0">
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
                {{ document?.file_name || documentTitle }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ documentTitle }}<span v-if="document?.uploaded_at"> · Diunggah {{ formatUploadDate(document?.uploaded_at) }}</span><span v-if="program?.program_name"> · {{ program.program_name }}</span>
              </p>
            </div>

            <!-- Action buttons (Clean text buttons without icons) -->
            <div class="flex items-center gap-2 shrink-0">
              <a
                v-if="actualFileUrl"
                :href="actualFileUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium transition-colors cursor-pointer"
                title="Buka berkas di tab baru"
              >
                Tab Baru
              </a>

              <button
                type="button"
                class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium transition-colors cursor-pointer"
                @click="handleDownload"
                title="Unduh berkas"
              >
                Unduh
              </button>

              <button
                type="button"
                class="px-3 py-1.5 rounded-lg bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium transition-colors cursor-pointer"
                @click="close"
                title="Tutup pratinjau"
              >
                Tutup
              </button>
            </div>
          </div>

          <!-- Document Viewer (Main Hero Canvas) -->
          <div class="flex-1 bg-slate-100 dark:bg-slate-950 relative overflow-hidden flex items-center justify-center">
            <!-- Loading Indicator -->
            <div v-if="isLoading" class="flex flex-col items-center justify-center text-slate-400 gap-2">
              <div class="w-6 h-6 border-2 border-slate-300 dark:border-slate-700 border-t-slate-700 dark:border-t-slate-200 rounded-full animate-spin"></div>
              <span class="text-xs">Memuat dokumen...</span>
            </div>

            <!-- PDF Viewer -->
            <iframe
              v-else-if="actualFileUrl && isPdf"
              :src="pdfEmbedUrl"
              class="w-full h-full border-none bg-slate-100 dark:bg-slate-950"
              title="Pratinjau Dokumen PDF"
            ></iframe>

            <!-- Image Viewer -->
            <div
              v-else-if="actualFileUrl && isImage"
              class="w-full h-full flex items-center justify-center p-6 overflow-auto bg-slate-900/5 dark:bg-black/40"
            >
              <img
                :src="actualFileUrl"
                :alt="document?.file_name"
                class="max-h-full max-w-full object-contain rounded-lg shadow-md border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900"
              />
            </div>

            <!-- Generic file fallback -->
            <div
              v-else-if="actualFileUrl"
              class="text-center p-8 max-w-md bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-3"
            >
              <p class="font-semibold text-slate-900 dark:text-slate-100 text-sm">{{ document?.file_name }}</p>
              <p class="text-xs text-slate-500 dark:text-slate-400">Berkas ini dapat diunduh langsung untuk dibuka pada perangkat Anda.</p>
              <button
                type="button"
                class="px-4 py-2 bg-slate-900 dark:bg-slate-800 text-white rounded-lg text-xs font-medium hover:bg-slate-800 dark:hover:bg-slate-700 transition-colors cursor-pointer"
                @click="handleDownload"
              >
                Unduh Berkas
              </button>
            </div>

            <!-- Empty state -->
            <div
              v-else
              class="text-center p-8 max-w-md bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 shadow-xs space-y-2"
            >
              <h4 class="font-semibold text-slate-800 dark:text-slate-200 text-sm">Berkas Belum Tersedia</h4>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Belum ada berkas fisik yang diunggah untuk tipe {{ documentTitle }}.
              </p>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { formatUploadDate, useTaxStore } from '../../store/taxStore';

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  },
  document: {
    type: Object,
    default: null
  },
  program: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['update:open']);

const store = useTaxStore();

const actualFileUrl = ref(null);
const isLoading = ref(false);

function close() {
  emit('update:open', false);
}

function handleKeydown(e) {
  if (e.key === 'Escape' && props.open) {
    close();
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
  document.body.style.overflow = '';
});

watch(
  () => [props.open, props.document],
  async ([isOpen, doc]) => {
    if (isOpen && doc) {
      document.body.style.overflow = 'hidden';
      isLoading.value = true;
      try {
        let url = doc.file_data || doc.file_url || null;
        if (!url && doc.id) {
          url = await store.loadDocumentContent(doc.id);
        }
        actualFileUrl.value = url;
      } finally {
        isLoading.value = false;
      }
    } else {
      actualFileUrl.value = null;
      document.body.style.overflow = '';
    }
  },
  { immediate: true }
);

const isPdf = computed(() => {
  const mime = props.document?.mime_type || '';
  const name = props.document?.file_name || '';
  const url = actualFileUrl.value || '';
  return mime.includes('pdf') || name.toLowerCase().endsWith('.pdf') || url.startsWith('data:application/pdf');
});

const isImage = computed(() => {
  const mime = props.document?.mime_type || '';
  const name = props.document?.file_name || '';
  const url = actualFileUrl.value || '';
  return mime.includes('image') || /\.(png|jpe?g|webp|gif|svg)$/i.test(name) || url.startsWith('data:image/');
});

const pdfEmbedUrl = computed(() => {
  if (!actualFileUrl.value) return '';
  if (actualFileUrl.value.startsWith('data:') || actualFileUrl.value.startsWith('blob:')) {
    return actualFileUrl.value;
  }
  return `${actualFileUrl.value}#toolbar=1&navpanes=0`;
});

const documentTitle = computed(() => {
  if (!props.document) return 'Pratinjau Dokumen';
  return store.getDocTypeLabel(props.document.document_type);
});

function handleDownload() {
  if (!props.document) return;
  const fileName = props.document.file_name || `${props.document.document_type}-${props.program?.id}.pdf`;

  if (actualFileUrl.value) {
    const a = document.createElement('a');
    a.href = actualFileUrl.value;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    store.notify(`Dokumen ${fileName} berhasil diunduh.`);
    return;
  }

  store.notify('Berkas dokumen tidak ditemukan.', 'warning');
}
</script>
