<template>
  <!-- Progres unggah invoice & faktur pajak di latar belakang: tampil di semua halaman selama antrean berisi -->
  <section
    v-if="queue.items.length > 0"
    class="fixed bottom-4 right-4 z-40 w-[min(400px,calc(100vw-2rem))] rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-[#111827] shadow-lg text-xs"
  >
    <div class="flex items-center gap-2.5 px-4 py-3">
      <Loader2 v-if="queue.isProcessing" class="w-3.5 h-3.5 shrink-0 animate-spin text-slate-400" />
      <span v-else class="w-1.5 h-1.5 shrink-0 rounded-full" :class="counts.review ? 'bg-amber-500' : (counts.failed ? 'bg-rose-500' : 'bg-emerald-500')"></span>
      <div class="min-w-0 flex-1">
        <p class="font-medium text-slate-900 dark:text-slate-100">Unggah dokumen</p>
        <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ headline }}</p>
      </div>
      <button
        type="button"
        class="shrink-0 text-[11px] font-medium text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 cursor-pointer"
        @click="expanded = !expanded"
      >
        {{ expanded ? 'Sembunyikan' : 'Lihat' }}
      </button>
      <button
        v-if="isAllFinished"
        type="button"
        class="shrink-0 p-1 -mr-1 rounded-md text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer"
        aria-label="Tutup"
        @click="close"
      >
        <X class="w-3.5 h-3.5" />
      </button>
    </div>

    <ul
      v-if="expanded"
      class="max-h-[55vh] overflow-y-auto border-t border-slate-100 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800"
    >
      <li v-for="item in queue.items" :key="item.key" class="px-4 py-2.5">
        <p class="truncate font-medium text-slate-800 dark:text-slate-200" :title="item.file.name">{{ item.file.name }}</p>

        <p class="mt-1 flex items-start gap-1.5 text-[11px] text-slate-500 dark:text-slate-400">
          <Loader2 v-if="item.status === 'processing' || item.saving" class="w-3 h-3 mt-px shrink-0 animate-spin text-slate-400" />
          <span v-else class="w-1.5 h-1.5 mt-1 rounded-full shrink-0" :class="STATUS_DOT[item.status]"></span>
          <span class="min-w-0 flex-1">{{ statusText(item) }}</span>
          <button
            v-if="item.status === 'failed' && canRetry(item)"
            type="button"
            class="shrink-0 font-medium text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white cursor-pointer"
            @click="retry(item)"
          >
            Ulangi
          </button>
        </p>

        <!-- Belum yakin: pengguna memilih programnya, juga jenisnya bila AI tidak bisa membaca dokumen -->
        <div v-if="item.status === 'review'" class="mt-2 space-y-2">
          <ProgramPicker
            v-model="item.choice"
            :candidates="item.candidates"
            :programs="allPrograms"
            :disabled="item.saving"
          />
          <div class="flex items-center gap-2">
            <div
              v-if="!item.documentType"
              class="inline-flex rounded-md border border-slate-200 dark:border-slate-700 p-0.5"
              role="group"
              aria-label="Jenis dokumen"
            >
              <button
                v-for="option in TYPE_OPTIONS"
                :key="option.value"
                type="button"
                class="h-6 px-2 rounded text-[11px] font-medium transition-colors cursor-pointer"
                :class="item.typeChoice === option.value
                  ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900'
                  : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'"
                :aria-pressed="item.typeChoice === option.value"
                :disabled="item.saving"
                @click="item.typeChoice = option.value"
              >
                {{ option.label }}
              </button>
            </div>
            <div class="ml-auto flex items-center gap-1">
              <!-- AI tidak membaca apa pun (mis. batas permintaan): baca ulang lebih dulu -->
              <button
                v-if="canRetry(item)"
                type="button"
                class="h-8 px-1.5 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 cursor-pointer"
                :disabled="item.saving"
                @click="retry(item)"
              >
                Ulangi
              </button>
              <button
                type="button"
                class="h-8 px-1.5 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 cursor-pointer"
                :disabled="item.saving"
                @click="skip(item)"
              >
                Lewati
              </button>
              <button
                type="button"
                class="h-8 px-3 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-xs font-medium text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="!canSave(item)"
                @click="saveChoice(item)"
              >
                Simpan
              </button>
            </div>
          </div>
        </div>
      </li>
    </ul>
  </section>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { Loader2, X } from 'lucide-vue-next';
import { useTaxStore, formatRupiah } from '../../store/taxStore';
import { useDocumentUploadQueue } from '../../store/documentUploadQueue';
import ProgramPicker from './ProgramPicker.vue';

const TYPE_OPTIONS = [
  { value: 'invoice', label: 'Invoice' },
  { value: 'faktur_pajak', label: 'Faktur Pajak' }
];

const STATUS_DOT = {
  pending: 'bg-slate-300 dark:bg-slate-600',
  matched: 'bg-emerald-500',
  duplicate: 'bg-slate-400',
  review: 'bg-amber-500',
  failed: 'bg-rose-500',
  skipped: 'bg-slate-300 dark:bg-slate-600'
};

const store = useTaxStore();
const { queue, counts, canSave, saveChoice, skip, canRetry, retry, clearFinished } = useDocumentUploadQueue();

const expanded = ref(false);
const allPrograms = computed(() => store.programs.value || []);
const isAllFinished = computed(() => counts.value.total > 0 && counts.value.finished === counts.value.total);

const headline = computed(() => {
  const c = counts.value;
  if (queue.isProcessing) {
    return `Memproses ${c.finished + (c.review || 0) + 1} dari ${c.total} berkas…`;
  }
  const parts = [
    c.matched && `${c.matched} masuk ke program`,
    c.duplicate && `${c.duplicate} sudah ada`,
    c.review && `${c.review} perlu dipilih`,
    c.failed && `${c.failed} gagal`,
    c.skipped && `${c.skipped} dilewati`
  ].filter(Boolean);
  return parts.join(' · ') || 'Selesai';
});

// Buka daftar otomatis bila ada berkas yang perlu dipilih atau gagal, supaya tidak terlewat
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

function statusText(item) {
  switch (item.status) {
    case 'pending':
      return 'Menunggu diproses';
    case 'processing':
      return 'AI sedang membaca dokumen…';
    case 'matched': {
      const number = isFaktur(item)
        ? item.extracted?.faktur_number && `No. Faktur ${item.extracted.faktur_number}`
        : item.extracted?.invoice_no && `No. Invoice ${item.extracted.invoice_no}`;
      return [
        `${isFaktur(item) ? 'Faktur Pajak' : 'Invoice'} masuk ke ${item.program?.title}`,
        item.program?.company_name,
        item.reason,
        // Tidak diulang bila alasannya sudah nomor yang sama
        number !== item.reason && number
      ].filter(Boolean).join(' · ');
    }
    case 'duplicate':
      return item.message;
    case 'review': {
      const extracted = item.extracted || {};
      const read = [
        isFaktur(item) ? 'Faktur Pajak' : (extracted.document_type ? 'Invoice' : null),
        isFaktur(item)
          ? extracted.dpp_amount && `DPP ${formatRupiah(extracted.dpp_amount)}`
          : extracted.sj_number && `SJ ${extracted.sj_number}`,
        extracted.program_name,
        extracted.bill_to
      ].filter(Boolean).join(' · ');
      return read ? `${item.message} Terbaca: ${read}` : item.message;
    }
    case 'skipped':
      return 'Dilewati, tidak disimpan';
    default:
      return item.message || 'Gagal diproses.';
  }
}
</script>
