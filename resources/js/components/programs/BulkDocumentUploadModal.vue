<template>
  <Dialog
    :open="open"
    title="Unggah Invoice & Faktur Pajak"
    description="Jenis dokumen dikenali otomatis. Invoice dicocokkan lewat No. Surat Jalan, faktur pajak lewat Referensi (No. SJ) di pojok kiri bawah, lalu dicek badan usaha dan periodenya. Prosesnya berjalan di latar belakang, jadi Anda bisa langsung membuka halaman lain."
    max-width="max-w-2xl"
    :close-on-backdrop="!isSending"
    @update:open="handleOpenChange"
  >
    <div class="space-y-4">
      <div
        role="button"
        tabindex="0"
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-8 text-center transition-colors outline-none hover:bg-muted/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
        :class="isDragging ? 'border-ring bg-muted/50' : ''"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        @click="fileInput?.click()"
        @keydown.enter.prevent="fileInput?.click()"
        @keydown.space.prevent="fileInput?.click()"
      >
        <input ref="fileInput" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="hidden" @change="handlePick" />
        <div class="mb-2 flex size-10 items-center justify-center rounded-full border bg-background">
          <UploadCloud class="size-4 text-muted-foreground" />
        </div>
        <p class="font-medium">Tarik invoice / faktur pajak ke sini atau pilih dari komputer</p>
        <p class="text-xs text-muted-foreground">PDF, JPG, PNG · maks. 15 MB per berkas · PDF gabungan juga bisa</p>
      </div>

      <div class="flex items-start gap-3">
        <Checkbox id="bulk-upload-split-pages" v-model="splitPages" />
        <div class="grid gap-2">
          <Label for="bulk-upload-split-pages" class="cursor-pointer">Pecah PDF per halaman</Label>
          <p class="text-xs text-muted-foreground">
            Untuk PDF gabungan: tiap halaman diproses sebagai dokumen sendiri dan masuk ke programnya masing-masing.
          </p>
        </div>
      </div>

      <ul v-if="entries.length > 0" class="max-h-[40vh] divide-y overflow-y-auto rounded-lg border">
        <li v-for="(entry, index) in entries" :key="entry.key" class="flex items-center justify-between gap-3 py-2 pr-2 pl-4">
          <div class="min-w-0">
            <p class="truncate font-medium" :title="entry.file.name">{{ entry.file.name }}</p>
            <p v-if="entry.pages > 1" class="text-xs tabular-nums text-muted-foreground">
              {{ entry.pages }} halaman{{ splitPages ? ` · dipecah jadi ${entry.pages} dokumen` : '' }}
            </p>
          </div>
          <Button
            type="button"
            variant="ghost"
            size="sm"
            class="shrink-0"
            :disabled="isSending"
            @click="entries.splice(index, 1)"
          >
            Hapus
          </Button>
        </li>
      </ul>
    </div>

    <template #footer>
      <Button type="button" variant="outline" :disabled="isSending" @click="handleOpenChange(false)">Batal</Button>
      <Button type="button" :disabled="entries.length === 0 || isSending" @click="send">
        {{ isSending ? 'Memecah PDF…' : (entries.length > 0 ? `Kirim ${documentCount} dokumen` : 'Kirim') }}
      </Button>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import { UploadCloud } from 'lucide-vue-next';
import Dialog from '@/components/ui/Dialog.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
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
