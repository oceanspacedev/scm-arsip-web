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
          class="bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 w-full max-w-3xl h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150"
        >
          <!-- Header -->
          <div class="px-5 py-3 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between shrink-0 gap-4">
            <div class="min-w-0">
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 truncate">
                {{ document?.file_name || documentTitle }}
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                {{ documentTitle }}<span v-if="document?.uploaded_at"> · Diunggah {{ formatUploadDate(document?.uploaded_at) }}</span><span v-if="program?.program_name"> · {{ program.program_name }}</span>
              </p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <a
                v-if="actualFileUrl"
                :href="actualFileUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-medium transition-colors cursor-pointer"
                title="Buka di tab baru (zoom, cetak, cari teks)"
              >
                Tab baru
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
                class="ml-1 p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                @click="close"
                title="Tutup pratinjau"
                aria-label="Tutup pratinjau"
              >
                <X class="w-4 h-4" />
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

            <!-- PDF Viewer: halaman dirender sendiri dengan PDF.js, jadi latar dan scrollbar (tipis) seragam dengan aplikasi -->
            <div
              v-else-if="actualFileUrl && isPdf"
              ref="pdfScroller"
              class="absolute inset-0 overflow-y-auto"
            >
              <div ref="pdfPages" class="flex flex-col items-center gap-4 p-5"></div>

              <div v-if="pdfRendering" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-slate-400">
                <div class="w-6 h-6 border-2 border-slate-300 dark:border-slate-700 border-t-slate-700 dark:border-t-slate-200 rounded-full animate-spin"></div>
                <span class="text-xs">Memuat dokumen...</span>
              </div>

              <div v-if="pdfError" class="absolute inset-0 flex items-center justify-center p-6">
                <p class="text-xs text-slate-500 dark:text-slate-400 text-center">
                  {{ pdfError }}
                  <a :href="actualFileUrl" target="_blank" rel="noopener noreferrer" class="text-slate-900 dark:text-slate-100 underline underline-offset-2">Buka di tab baru</a>
                </p>
              </div>
            </div>

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
                class="px-4 py-2 rounded-lg text-xs font-medium transition-colors cursor-pointer bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700"
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
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { X } from 'lucide-vue-next';
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
  resetPdf();
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

const pdfScroller = ref(null);
const pdfPages = ref(null);
const pdfRendering = ref(false);
const pdfError = ref('');
// Naik setiap kali dokumen berganti atau ditutup, supaya proses render yang lama berhenti
let renderToken = 0;
let loadingTask = null;

function resetPdf() {
  renderToken++;
  pdfRendering.value = false;
  pdfError.value = '';
  // destroy() ada di loading task (bukan di dokumen PDF.js 6) dan ikut membatalkan unduhan yang
  // masih berjalan; kegagalannya tidak boleh menghalangi modal ditutup
  loadingTask?.destroy().catch(() => {});
  loadingTask = null;
}

async function renderPdf(url) {
  const token = ++renderToken;
  pdfRendering.value = true;
  await nextTick();
  if (token !== renderToken || !pdfPages.value) return;
  pdfPages.value.replaceChildren();

  try {
    // Dimuat hanya saat pratinjau dibuka, tidak ikut bundle utama.
    // ?worker&url: berkas worker disajikan Vite tanpa klien HMR (lewat ?url biasa klien itu ikut
    // dan butuh `document`). Saat dev, halaman (127.0.0.1:8000 / .test) dan aset Vite (localhost:5173)
    // beda origin; PDF.js sendiri membungkus workerSrc lintas origin dengan blob, jadi pakai workerSrc.
    const [pdfjs, { default: workerUrl }] = await Promise.all([
      import('pdfjs-dist'),
      import('pdfjs-dist/build/pdf.worker.min.mjs?worker&url'),
    ]);
    pdfjs.GlobalWorkerOptions.workerSrc = workerUrl;

    const task = pdfjs.getDocument({ url });
    loadingTask = task;
    const pdf = await task.promise;
    if (token !== renderToken) return;

    // Halaman selebar area pratinjau (dikurangi padding), dirender tajam sesuai devicePixelRatio
    const available = pdfScroller.value.clientWidth - 40;
    const dpr = window.devicePixelRatio || 1;

    for (let n = 1; n <= pdf.numPages; n++) {
      const page = await pdf.getPage(n);
      if (token !== renderToken) return;

      const scale = Math.min(available / page.getViewport({ scale: 1 }).width, 1.5);
      const viewport = page.getViewport({ scale: scale * dpr });
      const canvas = document.createElement('canvas');
      canvas.width = Math.floor(viewport.width);
      canvas.height = Math.floor(viewport.height);
      canvas.style.width = `${Math.floor(viewport.width / dpr)}px`;
      canvas.className = 'bg-white shadow-sm ring-1 ring-slate-200 dark:ring-slate-800';
      pdfPages.value.appendChild(canvas);

      await page.render({ canvas, viewport }).promise;
      if (n === 1) pdfRendering.value = false;
    }
  } catch (e) {
    if (token === renderToken) {
      console.warn('Pratinjau PDF gagal:', e);
      pdfError.value = 'Pratinjau PDF tidak dapat ditampilkan.';
    }
  } finally {
    if (token === renderToken) pdfRendering.value = false;
  }
}

watch([actualFileUrl, isPdf], ([url, pdf]) => {
  resetPdf();
  if (url && pdf) renderPdf(url);
}, { immediate: true });

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
