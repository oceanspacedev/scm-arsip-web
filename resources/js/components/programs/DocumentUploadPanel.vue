<template>
  <!-- Progres unggah invoice & faktur pajak di latar belakang: tampil di semua halaman selama antrean berisi -->
  <section
    v-if="queue.items.length > 0"
    aria-label="Unggah dokumen"
    class="fixed right-4 bottom-4 z-40 w-[min(420px,calc(100vw-2rem))] overflow-hidden rounded-xl border bg-card text-card-foreground shadow-lg"
  >
    <!-- Kepala panel: status keseluruhan + ringkasan (tidak dipotong) -->
    <div class="flex items-start gap-3 px-4 py-3">
      <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center" aria-hidden="true">
        <Loader2 v-if="queue.isProcessing" class="size-5 animate-spin text-muted-foreground" />
        <ExclamationTriangleIcon v-else-if="counts.review" class="size-5 text-warning" />
        <XCircleIcon v-else-if="counts.failed" class="size-5 text-destructive" />
        <CheckCircleIcon v-else class="size-5 text-success" />
      </span>
      <div class="min-w-0 flex-1">
        <p class="font-medium">{{ headline }}</p>
        <p class="mt-0.5 text-xs text-muted-foreground" aria-live="polite">{{ subline }}</p>
      </div>
      <button
        type="button"
        class="flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
        :aria-expanded="expanded"
        :aria-label="expanded ? 'Sembunyikan daftar berkas' : 'Tampilkan daftar berkas'"
        :title="expanded ? 'Sembunyikan daftar' : 'Tampilkan daftar'"
        @click="expanded = !expanded"
      >
        <ChevronDownIcon v-if="expanded" class="size-5" />
        <ChevronUpIcon v-else class="size-5" />
      </button>
      <button
        v-if="isAllFinished"
        type="button"
        class="-mr-1 flex size-7 shrink-0 cursor-pointer items-center justify-center rounded-md text-muted-foreground transition-colors outline-none hover:bg-accent hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
        aria-label="Tutup panel unggah"
        title="Tutup"
        @click="close"
      >
        <XMarkIcon class="size-5" />
      </button>
    </div>

    <!-- Garis kemajuan selama berkas masih dibaca -->
    <div
      v-if="queue.isProcessing"
      class="h-1 bg-muted"
      role="progressbar"
      :aria-valuenow="progressDone"
      aria-valuemin="0"
      :aria-valuemax="counts.total"
      aria-label="Kemajuan unggah"
    >
      <div class="h-full bg-primary transition-[width] duration-500 ease-out" :style="{ width: `${progressPercent}%` }"></div>
    </div>

    <ul v-if="expanded" class="max-h-[55vh] divide-y overflow-y-auto border-t">
      <li v-for="item in orderedItems" :key="item.key" class="px-4 py-3">
        <div class="flex items-start gap-3">
          <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center" aria-hidden="true">
            <Loader2 v-if="item.status === 'processing' || item.saving" class="size-4 animate-spin text-muted-foreground" />
            <component :is="STATUS[item.status]?.icon" v-else class="size-5" :class="STATUS[item.status]?.iconClass" />
          </span>

          <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
              <!-- Judul: program tujuan bila sudah diketahui, selain itu nama berkasnya (utuh) -->
              <p class="min-w-0 leading-5 font-medium break-words">{{ titleOf(item) }}</p>
              <Badge v-if="STATUS[item.status]" :variant="STATUS[item.status].variant" class="mt-px">
                {{ STATUS[item.status].label }}
              </Badge>
            </div>
            <p v-for="line in detailLines(item)" :key="line" class="mt-1 text-xs tabular-nums break-words text-muted-foreground">
              {{ line }}
            </p>
            <!-- Nama berkas sebagai keterangan; disingkat di tengah supaya ujung nama yang membedakan tetap terlihat -->
            <p v-if="hasProgramTitle(item)" class="mt-1 text-xs text-muted-foreground/80" :title="item.file.name">
              {{ shortFileName(item.file.name) }}
            </p>
            <Button
              v-if="item.status === 'failed' && canRetry(item)"
              type="button"
              variant="outline"
              size="sm"
              class="mt-2"
              @click="retry(item)"
            >
              Ulangi
            </Button>
          </div>
        </div>

        <!-- Belum yakin: pengguna memilih programnya, juga jenisnya bila AI tidak bisa membaca dokumen -->
        <div v-if="item.status === 'review'" class="mt-3 space-y-2 pl-8">
          <ProgramPicker
            v-model="item.choice"
            :candidates="item.candidates"
            :programs="allPrograms"
            :disabled="item.saving"
          />
          <div class="flex flex-wrap items-center gap-2">
            <div
              v-if="!item.documentType"
              class="inline-flex h-8 items-center rounded-lg bg-muted p-1"
              role="group"
              aria-label="Jenis dokumen"
            >
              <button
                v-for="option in TYPE_OPTIONS"
                :key="option.value"
                type="button"
                class="inline-flex h-full cursor-pointer items-center rounded-md px-3 text-sm font-medium transition-[color,box-shadow] outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:pointer-events-none disabled:opacity-50"
                :class="item.typeChoice === option.value
                  ? 'bg-background text-foreground shadow-xs'
                  : 'text-muted-foreground hover:text-foreground'"
                :aria-pressed="item.typeChoice === option.value"
                :disabled="item.saving"
                @click="item.typeChoice = option.value"
              >
                {{ option.label }}
              </button>
            </div>
            <div class="ml-auto flex items-center gap-2">
              <!-- AI tidak membaca apa pun (mis. batas permintaan): baca ulang lebih dulu -->
              <Button
                v-if="canRetry(item)"
                type="button"
                variant="ghost"
                size="sm"
                :disabled="item.saving"
                @click="retry(item)"
              >
                Ulangi
              </Button>
              <Button type="button" variant="ghost" size="sm" :disabled="item.saving" @click="skip(item)">
                Lewati
              </Button>
              <Button type="button" size="sm" :disabled="!canSave(item)" @click="saveChoice(item)">
                Simpan
              </Button>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import {
  CheckCircleIcon,
  ChevronDownIcon,
  ChevronUpIcon,
  ClockIcon,
  DocumentDuplicateIcon,
  ExclamationTriangleIcon,
  MinusCircleIcon,
  XCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/20/solid';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatNumber, formatRupiah } from '@/lib/format';
import { useTaxStore } from '../../store/taxStore';
import { useDocumentUploadQueue } from '../../store/documentUploadQueue';
import ProgramPicker from './ProgramPicker.vue';

const TYPE_OPTIONS = [
  { value: 'invoice', label: 'Invoice' },
  { value: 'faktur_pajak', label: 'Faktur Pajak' }
];

// Ikon & label per status, warnanya sama dengan halaman History
const STATUS = {
  pending: { label: 'Menunggu', variant: 'outline', icon: ClockIcon, iconClass: 'text-muted-foreground/60' },
  processing: { label: 'Membaca', variant: 'outline' },
  matched: { label: 'Masuk', variant: 'success', icon: CheckCircleIcon, iconClass: 'text-success' },
  duplicate: { label: 'Sudah ada', variant: 'secondary', icon: DocumentDuplicateIcon, iconClass: 'text-muted-foreground' },
  review: { label: 'Perlu dipilih', variant: 'warning', icon: ExclamationTriangleIcon, iconClass: 'text-warning' },
  failed: { label: 'Gagal', variant: 'destructive', icon: XCircleIcon, iconClass: 'text-destructive' },
  skipped: { label: 'Dilewati', variant: 'outline', icon: MinusCircleIcon, iconClass: 'text-muted-foreground/60' }
};

const store = useTaxStore();
const { queue, counts, canSave, saveChoice, skip, canRetry, retry, clearFinished } = useDocumentUploadQueue();

const expanded = ref(false);
const allPrograms = computed(() => store.programs.value || []);

// Berkas yang butuh tindakan (perlu dipilih / gagal) di paling atas; sisanya tetap urutan unggah
const NEEDS_ACTION = ['review', 'failed'];
const orderedItems = computed(() => [
  ...queue.items.filter(item => NEEDS_ACTION.includes(item.status)),
  ...queue.items.filter(item => !NEEDS_ACTION.includes(item.status)),
]);
const isAllFinished = computed(() => counts.value.total > 0 && counts.value.finished === counts.value.total);

// Berkas yang sudah selesai dibaca AI (termasuk yang menunggu dipilih pengguna)
const progressDone = computed(() => counts.value.finished + (counts.value.review || 0));
const progressPercent = computed(() => (counts.value.total ? Math.round((progressDone.value / counts.value.total) * 100) : 0));

const headline = computed(() => {
  if (queue.isProcessing) return 'Mengunggah dokumen';
  if (counts.value.review) return 'Ada dokumen yang perlu dipilih';
  return 'Unggahan selesai';
});

const subline = computed(() => {
  const c = counts.value;
  if (queue.isProcessing) {
    return `${formatNumber(progressDone.value)} dari ${formatNumber(c.total)} berkas selesai · boleh pindah halaman`;
  }
  const parts = [
    c.matched && `${c.matched} masuk ke program`,
    c.duplicate && `${c.duplicate} sudah ada`,
    c.review && `${c.review} perlu dipilih`,
    c.failed && `${c.failed} gagal`,
    c.skipped && `${c.skipped} dilewati`
  ].filter(Boolean);
  return parts.join(' · ') || `${formatNumber(c.total)} berkas`;
});

// Daftar langsung terbuka saat berkas baru masuk antrean, atau ada yang perlu dipilih / gagal
watch(
  () => counts.value.total,
  (total, before) => {
    if (total > (before || 0)) expanded.value = true;
  }
);
watch(
  () => (counts.value.review || 0) + (counts.value.failed || 0),
  (needsAttention, before) => {
    if (needsAttention > (before || 0)) expanded.value = true;
  }
);

function close() {
  clearFinished();
  expanded.value = false;
}

function isFaktur(item) {
  return item.documentType === 'faktur_pajak';
}

function docLabel(item) {
  if (isFaktur(item)) return 'Faktur Pajak';
  return item.documentType || item.extracted?.document_type ? 'Invoice' : '';
}

function hasProgramTitle(item) {
  return ['matched', 'duplicate'].includes(item.status) && Boolean(item.program?.title);
}

function titleOf(item) {
  return hasProgramTitle(item) ? item.program.title : item.file.name;
}

/** "RX00282-DBDA3137-CV TOP…AUG 2026_FAKTUR_PAJAK.pdf": awal & akhir nama tetap terlihat */
function shortFileName(name, max = 52) {
  if (!name || name.length <= max) return name;
  const tail = Math.ceil((max - 1) * 0.55);
  return `${name.slice(0, max - 1 - tail)}…${name.slice(-tail)}`;
}

// Keterangan per baris (tidak dirangkai jadi satu kalimat panjang)
function detailLines(item) {
  const extracted = item.extracted || {};
  switch (item.status) {
    case 'pending':
      return ['Menunggu giliran'];
    case 'processing':
      return ['Membaca isi dokumen…'];
    case 'matched': {
      const number = isFaktur(item)
        ? extracted.faktur_number && `No. Faktur ${extracted.faktur_number}`
        : extracted.invoice_no && `No. Invoice ${extracted.invoice_no}`;
      return [
        [docLabel(item), item.program?.company_name].filter(Boolean).join(' · '),
        // Tidak diulang bila alasannya sudah nomor yang sama
        [item.reason, number !== item.reason && number].filter(Boolean).join(' · ')
      ].filter(Boolean);
    }
    case 'duplicate':
      return hasProgramTitle(item)
        ? [[docLabel(item), 'sudah ada di program ini, tidak disimpan ulang'].filter(Boolean).join(' ')]
        : [item.message];
    case 'review': {
      // Penjual → pembeli ikut ditampilkan: No. SJ yang sama bisa milik badan usaha lain
      const parties = [extracted.supplier_name, extracted.bill_to].filter(Boolean).join(' → ');
      const read = [
        docLabel(item),
        extracted.sj_number
          ? `SJ ${extracted.sj_number}`
          : isFaktur(item) && extracted.dpp_amount && `DPP ${formatRupiah(extracted.dpp_amount)}`,
        extracted.program_name,
        parties
      ].filter(Boolean).join(' · ');
      return [item.message, read && `Terbaca: ${read}`].filter(Boolean);
    }
    case 'skipped':
      return ['Tidak disimpan'];
    default:
      return [item.message || 'Gagal diproses.'];
  }
}
</script>
