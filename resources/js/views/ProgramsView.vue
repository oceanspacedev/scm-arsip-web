<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Arsip Program" description="Daftar program beserta kelengkapan dokumen perpajakan.">
      <template #actions>
        <!-- Unggah invoice & faktur pajak massal: tiap berkas otomatis masuk ke programnya -->
        <Button v-if="canUploadFinanceDocs" variant="outline" @click="isBulkUploadOpen = true">
          Unggah Invoice & Faktur
        </Button>
        <!-- Excel: import & ekspor dalam satu menu (masing-masing sesuai hak akses) -->
        <DropdownMenu v-if="canImportProgram || canExportProgram" :modal="false">
          <DropdownMenuTrigger as-child>
            <Button variant="outline">Excel</Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-44">
            <DropdownMenuItem v-if="canImportProgram" @select="store.openImportModal()">Import Excel</DropdownMenuItem>
            <DropdownMenuItem v-if="canExportProgram" @select="exportFiltered">Ekspor Excel</DropdownMenuItem>
          </DropdownMenuContent>
        </DropdownMenu>
        <Button v-if="canAddProgram" @click="isAddSheetOpen = true">Tambah Program</Button>
      </template>
    </PageHeader>

    <StatGrid :values="[formatNumber(summary.count), ...(canViewFinanceColumns ? [formatRupiah(summary.total), formatRupiah(summary.dpp), formatRupiah(summary.ppn)] : [])]">
      <StatCard label="Total Program" :icon="ArchiveBoxIcon" :value="formatNumber(summary.count)" :hint="summaryHint || 'Semua program di arsip'" />
      <template v-if="canViewFinanceColumns">
        <StatCard label="Total Invoice" :icon="DocumentTextIcon" :value="formatRupiah(summary.total)" hint="Nilai invoice termasuk PPN" />
        <StatCard label="DPP" :icon="BanknotesIcon" :value="formatRupiah(summary.dpp)" hint="Dasar Pengenaan Pajak" />
        <StatCard label="PPN" :icon="ReceiptPercentIcon" :value="formatRupiah(summary.ppn)" hint="Pajak Pertambahan Nilai" />
      </template>
    </StatGrid>

    <ProgramDataTable
      :programs="programs"
      :loading="loading"
      @open="openSheet"
      @edit="openEdit"
      @delete="askDelete"
      @document="openDocument"
      @filtered="rows => (filteredPrograms = rows)"
    />

    <ProgramDetailSheet v-model:open="isSheetOpen" :program-id="sheetProgramId" @edit="openEdit" @document="openDocument" />

    <EditProgramDialog v-model:open="isEditOpen" :program="editTarget" />

    <AlertDialog v-model:open="isDeleteOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Hapus program?</AlertDialogTitle>
          <AlertDialogDescription>
            "{{ deleteTarget?.program_name }}" akan dihapus dari arsip. Data yang dihapus tidak bisa dipulihkan.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Batal</AlertDialogCancel>
          <AlertDialogAction variant="destructive" @click="confirmDelete">Hapus</AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <DocumentPreviewSheet v-model:open="isPreviewOpen" :document="previewDocument" :program="previewProgram" />

    <DocumentUploadModal
      v-model:open="isUploadOpen"
      :docType="uploadDocType"
      :docLabel="uploadDocLabel"
      @uploaded="handleUploaded"
    />

    <AddProgramSheet v-model:open="isAddSheetOpen" />
    <BulkDocumentUploadModal v-model:open="isBulkUploadOpen" />
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { ArchiveBoxIcon, BanknotesIcon, DocumentTextIcon, ReceiptPercentIcon } from '@heroicons/vue/24/outline';
import PageHeader from '../components/layout/PageHeader.vue';
import StatCard from '../components/layout/StatCard.vue';
import StatGrid from '../components/layout/StatGrid.vue';
import ProgramDataTable from '../components/programs/ProgramDataTable.vue';
import ProgramDetailSheet from '../components/programs/ProgramDetailSheet.vue';
import EditProgramDialog from '../components/programs/EditProgramDialog.vue';
import AddProgramSheet from '../components/programs/AddProgramSheet.vue';
import BulkDocumentUploadModal from '../components/programs/BulkDocumentUploadModal.vue';
import DocumentPreviewSheet from '../components/detail/DocumentPreviewSheet.vue';
import DocumentUploadModal from '../components/detail/DocumentUploadModal.vue';
import { DOC_TYPES, findDocument } from '../components/programs/programTable';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { formatNumber, formatRupiah } from '@/lib/format';
import { useTaxStore } from '../store/taxStore';

const store = useTaxStore();

const programs = computed(() => store.programs.value);
const canUploadFinanceDocs = computed(() => store.canUploadDoc('invoice') || store.canUploadDoc('faktur_pajak'));
const canAddProgram = computed(() => store.canAddProgram.value);
const canImportProgram = computed(() => store.canImportProgram.value);
const canExportProgram = computed(() => store.canExportProgram.value);
const canViewFinanceColumns = computed(() => store.canViewFinanceColumns.value);

// Skeleton hanya saat belum ada data sama sekali (tanpa cache) dan data sedang diambil
const loading = ref(false);
onMounted(async () => {
  if (programs.value.length) return;
  loading.value = true;
  try {
    await store.fetchPrograms();
  } finally {
    loading.value = false;
  }
});

// Ringkasan & ekspor mengikuti filter yang sedang aktif di tabel
const filteredPrograms = ref([]);
const summary = computed(() => filteredPrograms.value.reduce(
  (acc, program) => {
    acc.count += 1;
    acc.total += Number(program.total_invoice) || 0;
    acc.dpp += Number(program.dpp) || 0;
    acc.ppn += Number(program.ppn) || 0;
    return acc;
  },
  { count: 0, total: 0, dpp: 0, ppn: 0 },
));
const summaryHint = computed(() =>
  filteredPrograms.value.length !== programs.value.length ? `Sesuai filter, dari ${formatNumber(programs.value.length)} program` : '',
);

function exportFiltered() {
  store.exportToCsv(filteredPrograms.value);
}

// Tambah program & unggah invoice/faktur sekaligus (tombol di judul halaman)
const isAddSheetOpen = ref(false);
const isBulkUploadOpen = ref(false);

// Panel detail (klik baris)
const isSheetOpen = ref(false);
const sheetProgramId = ref(null);
function openSheet(program) {
  sheetProgramId.value = String(program.id);
  isSheetOpen.value = true;
}

// Edit
const isEditOpen = ref(false);
const editTarget = ref(null);
function openEdit(program) {
  isSheetOpen.value = false;
  editTarget.value = program;
  isEditOpen.value = true;
}

// Hapus (hanya admin, seperti sebelumnya).
// Status buka dialog terpisah dari targetnya: tombol Hapus menutup dialog lebih dulu,
// baru confirmDelete berjalan, jadi target tidak boleh ikut dikosongkan saat dialog tertutup.
const deleteTarget = ref(null);
const isDeleteOpen = ref(false);
function askDelete(program) {
  deleteTarget.value = program;
  isDeleteOpen.value = true;
}
async function confirmDelete() {
  const program = deleteTarget.value;
  isDeleteOpen.value = false;
  if (!program) return;
  if (sheetProgramId.value === String(program.id)) isSheetOpen.value = false;
  await store.deleteProgram(program.id);
}

// Dokumen: lihat bila sudah ada, unggah bila belum (sesuai hak akses)
const isPreviewOpen = ref(false);
const previewDocument = ref(null);
const previewProgram = ref(null);
const isUploadOpen = ref(false);
const uploadTarget = ref(null);
const uploadDocType = ref('invoice');
const uploadDocLabel = ref('Invoice');

function openDocument({ program, type }) {
  const label = DOC_TYPES.find(doc => doc.type === type)?.label || type;
  const existing = findDocument(program, type);
  if (existing) {
    previewProgram.value = program;
    previewDocument.value = existing;
    isPreviewOpen.value = true;
    return;
  }
  if (!store.canUploadDoc(type)) {
    store.notify(`Role ${store.currentUser.value?.role || 'Anda'} tidak memiliki akses mengunggah ${label}.`, 'warning');
    return;
  }
  uploadTarget.value = program;
  uploadDocType.value = type;
  uploadDocLabel.value = `${label} (${program.program_name})`;
  isUploadOpen.value = true;
}

async function handleUploaded(fileData) {
  if (!uploadTarget.value) return;
  for (const file of Array.isArray(fileData) ? fileData : [fileData]) {
    await store.uploadDocument(uploadTarget.value.id, file.docType, file);
  }
  isUploadOpen.value = false;
}
</script>
