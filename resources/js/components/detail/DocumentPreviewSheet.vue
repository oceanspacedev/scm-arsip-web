<template>
  <!-- Panel kanan (Sheet bersama): Escape, klik di luar, kunci scroll halaman, dan fokus ditangani komponennya -->
  <Sheet :open="open" @update:open="value => emit('update:open', value)">
    <SheetContent side="right" class="flex w-full flex-col gap-0 p-0 sm:max-w-3xl">
      <SheetHeader class="gap-1 border-b px-6 py-4 pr-12 text-left">
        <SheetTitle class="truncate" :title="document?.file_name || documentTitle">
          {{ document?.file_name || documentTitle }}
        </SheetTitle>
        <SheetDescription class="truncate tabular-nums">
          {{ documentTitle }}<template v-if="document?.uploaded_at"> · Diunggah {{ formatUploadDate(document?.uploaded_at) }}</template><template v-if="program?.program_name"> · {{ program.program_name }}</template>
        </SheetDescription>
      </SheetHeader>

      <!-- Area pratinjau -->
      <div class="relative flex min-h-0 flex-1 items-center justify-center overflow-hidden bg-muted">
        <div v-if="isLoading" class="flex flex-col items-center gap-2 text-muted-foreground">
          <Loader2 class="size-5 animate-spin" />
          <span class="text-xs">Memuat dokumen...</span>
        </div>

        <!-- PDF Viewer: halaman dirender sendiri dengan PDF.js, jadi latar dan scrollbar (tipis) seragam dengan aplikasi -->
        <div
          v-else-if="actualFileUrl && isPdf"
          ref="pdfScroller"
          class="absolute inset-0 overflow-y-auto"
        >
          <!-- p-5 (20px) dipakai perhitungan lebar halaman di renderPdf (clientWidth - 40) -->
          <div ref="pdfPages" class="flex flex-col items-center gap-4 p-5"></div>

          <div v-if="pdfRendering" class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-muted-foreground">
            <Loader2 class="size-5 animate-spin" />
            <span class="text-xs">Memuat dokumen...</span>
          </div>

          <div v-if="pdfError" class="absolute inset-0 flex items-center justify-center p-6">
            <p class="text-center text-sm text-muted-foreground">
              {{ pdfError }}
              <a
                :href="actualFileUrl"
                target="_blank"
                rel="noopener noreferrer"
                class="font-medium text-foreground underline underline-offset-4"
              >Buka di tab baru</a>
            </p>
          </div>
        </div>

        <!-- Image Viewer -->
        <div
          v-else-if="actualFileUrl && isImage"
          class="flex h-full w-full items-center justify-center overflow-auto p-6"
        >
          <img
            :src="actualFileUrl"
            :alt="document?.file_name"
            class="max-h-full max-w-full rounded-md border bg-background object-contain shadow-sm"
          />
        </div>

        <!-- Berkas lain: unduh untuk dibuka di perangkat -->
        <div
          v-else-if="actualFileUrl"
          class="mx-6 flex max-w-md flex-col items-center gap-2 rounded-xl border bg-card p-6 text-center shadow-xs"
        >
          <p class="text-sm font-medium break-words">{{ document?.file_name }}</p>
          <p class="text-sm text-muted-foreground">Berkas ini dapat diunduh langsung untuk dibuka pada perangkat Anda.</p>
          <Button variant="outline" class="mt-2" @click="handleDownload">Unduh Berkas</Button>
        </div>

        <!-- Belum ada berkas (tidak ditampilkan saat panel sedang menutup) -->
        <div
          v-else-if="open"
          class="mx-6 flex max-w-md flex-col items-center gap-2 rounded-xl border bg-card p-6 text-center shadow-xs"
        >
          <p class="text-base font-semibold">Berkas belum tersedia</p>
          <p class="text-sm text-muted-foreground">
            Belum ada berkas fisik yang diunggah untuk tipe {{ documentTitle }}.
          </p>
        </div>
      </div>

      <SheetFooter class="flex-row justify-end gap-2 border-t p-4">
        <Button
          v-if="actualFileUrl"
          as="a"
          :href="actualFileUrl"
          target="_blank"
          rel="noopener noreferrer"
          variant="outline"
          title="Buka di tab baru (zoom, cetak, cari teks)"
        >
          Tab baru
        </Button>
        <Button title="Unduh berkas" @click="handleDownload">Unduh</Button>
      </SheetFooter>
    </SheetContent>
  </Sheet>
</template>

<script setup>
import { ref, computed, watch, nextTick, onUnmounted } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
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

// Escape, klik di luar, dan kunci scroll halaman diurus Sheet (reka-ui). Kunci scroll-nya menyimpan
// lalu memulihkan body.style.overflow, jadi di sini overflow tidak boleh diubah sendiri.
onUnmounted(() => {
  resetPdf();
});

watch(
  () => [props.open, props.document],
  async ([isOpen, doc]) => {
    if (isOpen && doc) {
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
      // Latar putih halaman diisi PDF.js sendiri saat mulai menggambar
      canvas.className = 'shadow-sm ring-1 ring-border';
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
