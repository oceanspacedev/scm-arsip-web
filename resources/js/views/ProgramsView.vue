<template>
  <div class="space-y-3 select-none">
    <!-- Page Header matching screenshot -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 min-w-0">
      <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
          Arsip Program SCM
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          Daftar program beserta kelengkapan dokumen perpajakan
        </p>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- Unggah invoice & faktur pajak massal: tiap berkas otomatis masuk ke programnya -->
        <button
          v-if="canUploadFinanceDocs"
          type="button"
          class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer whitespace-nowrap"
          @click="isBulkUploadOpen = true"
        >
          Unggah Invoice & Faktur
        </button>

        <!-- Excel: import & ekspor dalam satu menu (masing-masing sesuai hak akses) -->
        <div v-if="canImportProgram || canExportProgram" ref="excelMenuRef" class="relative flex-1 sm:flex-initial">
          <button
            type="button"
            class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer whitespace-nowrap"
            aria-haspopup="menu"
            :aria-expanded="isExcelMenuOpen"
            @click="isExcelMenuOpen = !isExcelMenuOpen"
          >
            Excel
            <ChevronDown class="w-3 h-3 text-slate-400 transition-transform" :class="{ 'rotate-180': isExcelMenuOpen }" />
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
              class="w-full px-3 py-1.5 text-left text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              @click="runExcelAction(() => store.openImportModal())"
            >
              Import Excel
            </button>
            <button
              v-if="canExportProgram"
              type="button"
              role="menuitem"
              class="w-full px-3 py-1.5 text-left text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
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
          class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg text-xs font-medium transition-colors cursor-pointer whitespace-nowrap bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700"
          @click="isAddSheetOpen = true"
        >
          Tambah Program
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
import { useTaxStore } from '../store/taxStore';

const store = useTaxStore();
const isAddSheetOpen = ref(false);
const isBulkUploadOpen = ref(false);
const canUploadFinanceDocs = computed(() => store.canUploadDoc('invoice') || store.canUploadDoc('faktur_pajak'));
const canAddProgram = computed(() => store.canAddProgram.value);
const canImportProgram = computed(() => store.canImportProgram.value);
const canExportProgram = computed(() => store.canExportProgram.value);

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
