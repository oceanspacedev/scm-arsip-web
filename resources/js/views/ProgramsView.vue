<template>
  <div class="space-y-4">
    <!-- Judul + ringkasan hasil filter -->
    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-3 min-w-0">
      <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-medium text-slate-900 dark:text-slate-100">
          Arsip program SCM
        </h1>
        <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
          <span><span class="text-slate-900 dark:text-slate-100 tabular-nums">{{ summary.count }}</span> program</span>
          <template v-if="canViewFinance">
            <span class="text-slate-300 dark:text-slate-600">·</span>
            <span>Total invoice <span class="text-slate-900 dark:text-slate-100 tabular-nums">{{ formatRupiah(summary.totalInvoice) }}</span></span>
            <span class="text-slate-300 dark:text-slate-600">·</span>
            <span>DPP <span class="text-slate-900 dark:text-slate-100 tabular-nums">{{ formatRupiah(summary.totalDpp) }}</span></span>
            <span class="text-slate-300 dark:text-slate-600">·</span>
            <span>PPN <span class="text-slate-900 dark:text-slate-100 tabular-nums">{{ formatRupiah(summary.totalPpn) }}</span></span>
          </template>
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-2 lg:flex-nowrap lg:shrink-0">
        <!-- Unggah invoice & faktur pajak massal: tiap berkas otomatis masuk ke programnya -->
        <button
          v-if="canUploadFinanceDocs"
          type="button"
          class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 h-9 px-3 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer whitespace-nowrap"
          @click="isBulkUploadOpen = true"
        >
          Unggah dokumen
        </button>

        <!-- Excel: import & ekspor dalam satu menu (masing-masing sesuai hak akses) -->
        <div v-if="canImportProgram || canExportProgram" ref="excelMenuRef" class="relative flex-1 sm:flex-initial">
          <button
            type="button"
            class="w-full inline-flex items-center justify-center gap-2 h-9 px-3 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer whitespace-nowrap"
            aria-haspopup="menu"
            :aria-expanded="isExcelMenuOpen"
            @click="isExcelMenuOpen = !isExcelMenuOpen"
          >
            Excel
            <ChevronDown class="w-4 h-4 text-slate-400 transition-transform" :stroke-width="1.75" :class="{ 'rotate-180': isExcelMenuOpen }" />
          </button>

          <div
            v-if="isExcelMenuOpen"
            role="menu"
            class="absolute right-0 mt-1 w-44 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-xl py-1 z-30"
          >
            <button
              v-if="canImportProgram"
              type="button"
              role="menuitem"
              class="w-full px-3 py-2 text-left text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              @click="runExcelAction(() => store.openImportModal())"
            >
              Impor Excel
            </button>
            <button
              v-if="canExportProgram"
              type="button"
              role="menuitem"
              class="w-full px-3 py-2 text-left text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              @click="runExcelAction(handleExport)"
            >
              Ekspor Excel
            </button>
          </div>
        </div>

        <!-- Tambah Program (Hanya Staff SCM & Admin SCM) -->
        <button
          v-if="canAddProgram"
          type="button"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-2 h-9 px-4 rounded-lg text-sm font-medium transition-colors cursor-pointer whitespace-nowrap bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
          @click="isAddSheetOpen = true"
        >
          Tambah program
        </button>
      </div>
    </div>

    <!-- Filter & Summary Strip Card -->
    <ProgramToolbar />

    <!-- Program Table matching screenshot -->
    <ProgramTable />

    <!-- Add Program Sheet -->
    <AddProgramSheet
      v-model:open="isAddSheetOpen"
    />

    <BulkDocumentUploadModal v-model:open="isBulkUploadOpen" />
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { onClickOutside, onKeyStroke } from '@vueuse/core';
import { ChevronDown } from 'lucide-vue-next';
import ProgramToolbar from '../components/programs/ProgramToolbar.vue';
import ProgramTable from '../components/programs/ProgramTable.vue';
import AddProgramSheet from '../components/programs/AddProgramSheet.vue';
import BulkDocumentUploadModal from '../components/programs/BulkDocumentUploadModal.vue';
import { useTaxStore, formatRupiah } from '../store/taxStore';

const store = useTaxStore();
const isAddSheetOpen = ref(false);
const isBulkUploadOpen = ref(false);
const canUploadFinanceDocs = computed(() => store.canUploadDoc('invoice') || store.canUploadDoc('faktur_pajak'));
const canAddProgram = computed(() => store.canAddProgram.value);
const canImportProgram = computed(() => store.canImportProgram.value);
const canExportProgram = computed(() => store.canExportProgram.value);
const canViewFinance = computed(() => store.canViewFinanceColumns.value);

// Ringkasan program yang sedang tampil (ikut filter)
const summary = computed(() => {
  const list = store.filteredPrograms.value;
  return list.reduce((acc, p) => {
    acc.totalInvoice += Number(p.total_invoice) || 0;
    acc.totalDpp += Number(p.dpp) || 0;
    acc.totalPpn += Number(p.ppn) || 0;
    return acc;
  }, { count: list.length, totalInvoice: 0, totalDpp: 0, totalPpn: 0 });
});

function handleExport() {
  store.exportToCsv();
}

// Menu Excel: tutup saat klik di luar, tekan Esc, atau setelah memilih
const excelMenuRef = ref(null);
const isExcelMenuOpen = ref(false);
onClickOutside(excelMenuRef, () => (isExcelMenuOpen.value = false));
onKeyStroke('Escape', () => (isExcelMenuOpen.value = false));

function runExcelAction(action) {
  isExcelMenuOpen.value = false;
  action();
}
</script>
