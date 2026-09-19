<template>
  <div class="space-y-5 select-none">
    <!-- Page Header matching screenshot -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 min-w-0">
      <div class="min-w-0">
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-sans">
          Arsip Program SCM
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          Daftar program beserta kelengkapan dokumen perpajakan
        </p>
      </div>

      <!-- Action Buttons matching screenshot -->
      <div class="flex flex-wrap items-center gap-2">
        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium mr-1 hidden lg:inline">
          {{ store.filteredPrograms.value.length }} Program terdaftar
        </span>

        <!-- Import Excel (Hanya Staff SCM & Admin SCM) -->
        <button
          v-if="canImportProgram"
          type="button"
          class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-3.5 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs transition-colors cursor-pointer whitespace-nowrap"
          @click="store.openImportModal()"
        >
          <ExcelIcon class="w-4 h-4" />
          <span>Import Excel</span>
        </button>

        <!-- Ekspor Excel (Hanya Staff SCM & Admin SCM) -->
        <button
          v-if="canExportProgram"
          type="button"
          class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-3.5 py-2 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-2xs transition-colors cursor-pointer whitespace-nowrap"
          @click="handleExport"
        >
          <ExcelIcon class="w-4 h-4" />
          <span>Ekspor Excel</span>
        </button>

        <!-- Tambah Program (Hanya Staff SCM & Admin SCM) -->
        <button
          v-if="canAddProgram"
          type="button"
          class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-2xs transition-colors cursor-pointer whitespace-nowrap"
          @click="isAddSheetOpen = true"
        >
          <Plus class="w-3.5 h-3.5" />
          <span>Tambah Program</span>
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
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Download, Plus } from 'lucide-vue-next';
import ExcelIcon from '../components/ui/ExcelIcon.vue';
import ProgramToolbar from '../components/programs/ProgramToolbar.vue';
import ProgramTable from '../components/programs/ProgramTable.vue';
import AddProgramSheet from '../components/programs/AddProgramSheet.vue';
import { useTaxStore } from '../store/taxStore';

const store = useTaxStore();
const isAddSheetOpen = ref(false);
const canAddProgram = computed(() => store.canAddProgram.value);
const canImportProgram = computed(() => store.canImportProgram.value);
const canExportProgram = computed(() => store.canExportProgram.value);

function handleExport() {
  store.exportToCsv();
}
</script>
