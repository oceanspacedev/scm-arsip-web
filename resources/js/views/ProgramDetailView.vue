<template>
  <div v-if="program" class="flex flex-col gap-6">
    <PageHeader :title="program.program_name" :description="headerDescription">
      <template #actions>
        <Badge :variant="completenessVariant">{{ statusBadgeText }}</Badge>
      </template>
    </PageHeader>

    <!-- Dokumen: tiga kartu berjejer (Invoice | Faktur Pajak | Memo/DO), bertumpuk di layar sempit -->
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
      <Card v-for="cat in documentCategories" :key="cat.type" class="flex min-w-0 flex-col">
        <CardHeader class="flex-row items-center justify-between gap-3 p-5 pb-3">
          <!-- Tinggi minimum setinggi tombol, supaya isi ketiga kartu sejajar walau tombol Tambah tidak tampil -->
          <CardTitle class="flex min-h-8 items-center gap-2">
            {{ cat.label }}
            <span v-if="getDocs(cat.type).length > 0" class="font-normal tabular-nums text-muted-foreground">
              {{ getDocs(cat.type).length }}
            </span>
          </CardTitle>
          <Button
            v-if="getDocs(cat.type).length > 0 && canUploadDoc(cat.type)"
            variant="outline"
            size="sm"
            title="Unggah berkas tambahan"
            @click="openUpload(cat.type, cat.label)"
          >
            Tambah
          </Button>
        </CardHeader>

        <CardContent class="@container flex flex-1 flex-col p-5 pt-0">
          <!-- Daftar berkas: aksi di kanan bila kartu cukup lebar, di bawah nama berkas bila sempit -->
          <ul v-if="getDocs(cat.type).length > 0" class="-mx-2 max-h-52 divide-y overflow-y-auto px-2">
            <li
              v-for="(doc, dIdx) in getDocs(cat.type)"
              :key="doc.id || doc.file_name || dIdx"
              class="flex flex-col gap-1 py-2 first:pt-0 last:pb-0 @xs:flex-row @xs:items-center @xs:gap-3"
            >
              <div class="min-w-0 flex-1">
                <p class="line-clamp-2 text-sm font-medium break-words" :title="doc.file_name">{{ doc.file_name }}</p>
                <p class="truncate text-xs tabular-nums text-muted-foreground">{{ docMeta(doc) }}</p>
              </div>
              <div class="-ml-2 flex shrink-0 items-center @xs:-mr-2 @xs:ml-0">
                <Button variant="ghost" size="sm" class="px-2 text-muted-foreground" @click="openPreviewDoc(doc)">
                  Lihat
                </Button>
                <Button variant="ghost" size="sm" class="px-2 text-muted-foreground" @click="downloadDocItem(doc)">
                  Unduh
                </Button>
                <Button
                  v-if="canDeleteDoc(cat.type)"
                  variant="ghost"
                  size="sm"
                  class="px-2 text-muted-foreground hover:text-destructive"
                  @click="deleteDocItem(doc, cat.label)"
                >
                  Hapus
                </Button>
              </div>
            </li>
          </ul>

          <!-- Belum ada berkas: area unggah, atau keterangan bila tidak berhak mengunggah -->
          <button
            v-else-if="canUploadDoc(cat.type)"
            type="button"
            class="flex min-h-24 flex-1 cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border border-dashed px-4 py-6 text-center transition-colors outline-none hover:border-ring hover:bg-muted/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            @click="openUpload(cat.type, cat.label)"
          >
            <span class="text-sm font-medium">Unggah {{ cat.label }}</span>
            <span class="text-xs text-muted-foreground">PDF, JPG, PNG · maks. 15 MB</span>
          </button>
          <div
            v-else
            class="flex min-h-24 flex-1 flex-col items-center justify-center gap-1 rounded-lg border border-dashed px-4 py-6 text-center"
          >
            <span class="text-sm text-muted-foreground">Belum ada berkas</span>
            <span class="text-xs text-muted-foreground">
              {{ cat.type === 'mou' ? 'Diunggah oleh Tim Gudang / SCM' : 'Diunggah oleh Tim Finance / SCM' }}
            </span>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- Rincian: nilai & perpajakan, serta data program -->
    <div
      v-if="canViewFinanceColumns || canViewMasterColumns"
      class="grid grid-cols-1 gap-4"
      :class="canViewFinanceColumns && canViewMasterColumns && 'lg:grid-cols-2'"
    >
      <Card v-if="canViewFinanceColumns" class="flex min-w-0 flex-col">
        <CardHeader class="p-5 pb-4">
          <CardTitle>Nilai & Perpajakan</CardTitle>
        </CardHeader>
        <CardContent class="@container flex flex-1 flex-col p-5 pt-0">
          <dl class="grid grid-cols-2 gap-x-6 gap-y-4 @lg:grid-cols-3 @4xl:grid-cols-6">
            <div v-for="field in invoiceFields" :key="field.label" class="min-w-0">
              <dt class="text-xs text-muted-foreground">{{ field.label }}</dt>
              <dd class="mt-1 text-sm tabular-nums break-words">{{ field.value || '—' }}</dd>
            </div>
            <div class="min-w-0">
              <dt class="text-xs text-muted-foreground">Status Payment</dt>
              <dd class="mt-1">
                <Select
                  v-if="canEditFinance || isAdmin"
                  :model-value="normalizePayStatus(program.payment_status)"
                  @update:model-value="handlePaymentStatusChange"
                >
                  <SelectTrigger size="sm" class="w-full max-w-48" aria-label="Ubah status payment">
                    <SelectValue v-slot="{ selectedLabel }" class="min-w-0 truncate">
                      {{ selectedLabel.length ? selectedLabel.join(', ') : formatPaymentStatus(program.payment_status) }}
                    </SelectValue>
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="opt in editPaymentStatusOptions" :key="opt.value" :value="opt.value">
                      {{ opt.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <Badge v-else :variant="paymentBadgeVariant(program.payment_status)">
                  {{ formatPaymentStatus(program.payment_status) }}
                </Badge>
              </dd>
            </div>
          </dl>

          <div class="mt-auto pt-5">
            <dl class="divide-y border-y text-sm">
              <div v-for="row in amountRows" :key="row.label" class="flex items-center justify-between gap-4 py-2">
                <dt class="text-muted-foreground">{{ row.label }}</dt>
                <dd class="tabular-nums">{{ formatRupiah(row.value) }}</dd>
              </div>
            </dl>
            <div class="flex items-center justify-between gap-4 pt-4">
              <span class="text-sm font-medium" title="DPP + PPN (termasuk pajak)">Total Payment</span>
              <span class="text-base font-semibold tabular-nums">{{ formatRupiah(program.total_invoice) }}</span>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card v-if="canViewMasterColumns" class="min-w-0">
        <CardHeader class="p-5 pb-4">
          <CardTitle>Data Program & Supplier</CardTitle>
        </CardHeader>
        <CardContent class="@container p-5 pt-0">
          <dl class="grid grid-cols-2 gap-x-6 gap-y-4 @3xl:grid-cols-3">
            <div v-for="field in programFields" :key="field.label" class="min-w-0">
              <dt class="text-xs text-muted-foreground">{{ field.label }}</dt>
              <dd class="mt-1 text-sm break-words" :class="{ 'tabular-nums': field.numeric }">{{ field.value || '—' }}</dd>
            </div>
          </dl>
        </CardContent>
      </Card>
    </div>

    <!-- Ubah data program -->
    <Dialog
      v-model:open="isEditModalOpen"
      title="Ubah Data Program"
      :description="`Wewenang: ${store.currentUser.value?.role || 'Pengguna'}`"
      max-width="max-w-xl"
    >
      <form :id="EDIT_FORM_ID" class="flex flex-col gap-6" @submit.prevent="saveEditProgram">
        <!-- Data Purchase & Vendor -->
        <section class="flex flex-col gap-4">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold">Data Purchase & Pengadaan</h3>
            <span v-if="!canEditPurchase" class="text-xs text-muted-foreground">Hanya baca</span>
          </div>

          <FormField label="Nama Program" for="detail-edit-name">
            <Input id="detail-edit-name" v-model="editForm.program_name" type="text" required :disabled="!canEditPurchase" />
          </FormField>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="Nama Supplier" for="detail-edit-supplier">
              <NativeSelect
                id="detail-edit-supplier"
                v-model="editForm.supplier"
                required
                :disabled="!canEditPurchase"
                @change="onEditSupplierChange"
              >
                <option v-for="s in masterSuppliers" :key="s.name" :value="s.name">{{ s.name }}</option>
              </NativeSelect>
            </FormField>
            <FormField label="NPWP Supplier" for="detail-edit-npwp">
              <Input id="detail-edit-npwp" v-model="editForm.npwp" type="text" class="tabular-nums" :disabled="!canEditPurchase" />
            </FormField>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="Company Name" for="detail-edit-company">
              <NativeSelect id="detail-edit-company" v-model="editForm.company_name" :disabled="!canEditPurchase">
                <option v-for="c in availableCompanies" :key="c" :value="c">{{ c }}</option>
              </NativeSelect>
            </FormField>
            <FormField label="No. PO / SJ" for="detail-edit-posj">
              <Input
                id="detail-edit-posj"
                v-model="editForm.po_sj_number"
                type="text"
                placeholder="PO/SCM/2026/001"
                class="tabular-nums"
                :disabled="!canEditPurchase"
              />
            </FormField>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="Kategori" for="detail-edit-category">
              <NativeSelect id="detail-edit-category" v-model="editForm.category" :disabled="!canEditPurchase">
                <option v-for="cat in availableCategories" :key="cat" :value="cat">{{ cat }}</option>
              </NativeSelect>
            </FormField>
            <FormField label="Brand" for="detail-edit-brand">
              <NativeSelect id="detail-edit-brand" v-model="editForm.brand" :disabled="!canEditPurchase">
                <option v-for="b in availableBrands" :key="b" :value="b">{{ b }}</option>
              </NativeSelect>
            </FormField>
          </div>
        </section>

        <!-- Data Nilai & Perpajakan -->
        <section class="flex flex-col gap-4 border-t pt-6">
          <div class="flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold">Data Nilai & Faktur Pajak</h3>
            <span v-if="!canEditFinance" class="text-xs text-muted-foreground">Hanya baca</span>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="No. Invoice" for="detail-edit-invoice">
              <Input
                id="detail-edit-invoice"
                v-model="editForm.invoice_number"
                type="text"
                placeholder="Contoh: INV/2026/001"
                class="tabular-nums"
                :disabled="!canEditFinance"
              />
            </FormField>
            <FormField label="Status Payment" for="detail-edit-payment">
              <NativeSelect
                id="detail-edit-payment"
                :model-value="normalizePayStatus(editForm.payment_status)"
                :disabled="!canEditFinance && !isAdmin"
                @change="editForm.payment_status = $event.target.value"
              >
                <option v-for="opt in editPaymentStatusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </NativeSelect>
            </FormField>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <FormField label="Nilai DPP (IDR)" for="detail-edit-dpp">
              <!-- Hitung ulang setelah nilai DPP tersimpan ke form -->
              <Input
                id="detail-edit-dpp"
                v-model.number="editForm.dpp"
                type="number"
                required
                class="tabular-nums"
                :disabled="!canEditFinance"
                @update:model-value="calculateTaxes"
              />
            </FormField>
            <FormField
              label="Tarif PPN"
              for="detail-edit-ppn-rate"
              :helper="editForm.ppn_rate === '0' ? 'Non-PPN (0%)' : `PPN ${Math.round(Number(editForm.ppn_rate) * 100)}%`"
            >
              <NativeSelect id="detail-edit-ppn-rate" v-model="editForm.ppn_rate" :disabled="!canEditFinance" @change="calculateTaxes">
                <option value="0.11">11% (Standar)</option>
                <option value="0.12">12%</option>
                <option value="0">0% (Non-PPN)</option>
              </NativeSelect>
            </FormField>
            <FormField label="Nilai PPN (IDR)" for="detail-edit-ppn">
              <Input id="detail-edit-ppn" :model-value="editForm.ppn" type="number" readonly class="bg-muted tabular-nums" />
            </FormField>
          </div>

          <FormField label="Total Invoice (IDR)" for="detail-edit-total" :helper="formatRupiah(editForm.dpp + editForm.ppn)">
            <Input
              id="detail-edit-total"
              :model-value="editForm.dpp + editForm.ppn"
              type="number"
              readonly
              class="bg-muted tabular-nums"
            />
          </FormField>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="Jenis PPh" for="detail-edit-pph-type">
              <NativeSelect id="detail-edit-pph-type" v-model="editForm.pph_type" :disabled="!canEditFinance" @change="onPphTypeChange">
                <option value="NON_PPH">Non PPh</option>
                <option value="PPH_21">PPh 21 (2,5%)</option>
                <option value="PPH_23">PPh 23 (2%)</option>
                <option value="PPH_4_2">PPh 4 Ayat 2 (10%)</option>
                <option value="PPH_23_BONUS">PPh 23 Atas Bonus (15%)</option>
              </NativeSelect>
            </FormField>
            <FormField label="Nilai PPh (IDR)" for="detail-edit-pph" :helper="formatRupiah(editForm.pph || 0)">
              <Input id="detail-edit-pph" v-model.number="editForm.pph" type="number" class="tabular-nums" :disabled="!canEditFinance" />
            </FormField>
          </div>

          <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <FormField label="No. Faktur Pajak" for="detail-edit-faktur" helper="Format: XXX.XXX-XX.XXXXXXXX">
              <Input
                id="detail-edit-faktur"
                v-model="editForm.faktur_number"
                type="text"
                placeholder="010.000-25.00000001"
                class="tabular-nums"
                :disabled="!canEditFinance"
              />
            </FormField>
            <FormField label="Tanggal Faktur Pajak" for="detail-edit-faktur-date">
              <Input
                id="detail-edit-faktur-date"
                v-model="editForm.faktur_date"
                type="date"
                class="tabular-nums"
                :disabled="!canEditFinance"
              />
            </FormField>
          </div>
        </section>
      </form>

      <template #footer>
        <Button variant="outline" :disabled="isSavingEdit" @click="isEditModalOpen = false">Batal</Button>
        <Button type="submit" :form="EDIT_FORM_ID" :disabled="isSavingEdit">
          {{ isSavingEdit ? 'Menyimpan...' : 'Simpan Perubahan' }}
        </Button>
      </template>
    </Dialog>

    <!-- Unggah dokumen -->
    <DocumentUploadModal
      v-model:open="isUploadModalOpen"
      :docType="uploadTargetType"
      :docLabel="uploadTargetLabel"
      @uploaded="handleDocumentUploaded"
    />

    <!-- Pratinjau dokumen -->
    <DocumentPreviewSheet
      v-model:open="isPreviewSheetOpen"
      :document="selectedPreviewDoc"
      :program="program"
    />

    <!-- Konfirmasi hapus berkas dokumen -->
    <AlertDialog
      :open="Boolean(docToDelete)"
      @update:open="open => { if (!open && !isDeletingDoc) docToDelete = null }"
    >
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Hapus berkas dokumen?</AlertDialogTitle>
          <AlertDialogDescription>
            Apakah Anda yakin ingin menghapus berkas {{ docToDelete?.label }}? Tindakan ini tidak dapat dibatalkan.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <dl
          v-if="docToDelete?.fileName"
          class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 rounded-lg border px-4 py-3 text-sm"
        >
          <dt class="text-muted-foreground">Tipe dokumen</dt>
          <dd class="text-right">{{ docToDelete.label }}</dd>
          <dt class="text-muted-foreground">Nama file</dt>
          <dd class="truncate text-right" :title="docToDelete.fileName">{{ docToDelete.fileName }}</dd>
        </dl>
        <AlertDialogFooter>
          <AlertDialogCancel :disabled="isDeletingDoc">Batal</AlertDialogCancel>
          <!-- Tombol biasa (bukan AlertDialogAction) supaya dialog tetap terbuka selama berkas dihapus -->
          <Button variant="destructive" :disabled="isDeletingDoc" @click="confirmDeleteDoc">
            {{ isDeletingDoc ? 'Menghapus...' : 'Hapus' }}
          </Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <!-- Konfirmasi hapus program -->
    <AlertDialog v-model:open="isDeleteProgramModalOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Hapus program ini?</AlertDialogTitle>
          <AlertDialogDescription>
            Apakah Anda yakin ingin menghapus program "{{ program?.program_name }}"? Seluruh berkas perpajakan terkait akan dihapus.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Batal</AlertDialogCancel>
          <AlertDialogAction variant="destructive" @click="executeDeleteProgram">Hapus</AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>

  <!-- Program tidak ditemukan -->
  <div v-else class="flex flex-col gap-6">
    <PageHeader title="Detail Program" />
    <Card class="flex flex-col items-center gap-2 px-6 py-12 text-center">
      <p class="text-base font-semibold">Program tidak ditemukan</p>
      <p class="text-sm text-muted-foreground">Program mungkin telah dihapus atau URL tidak sesuai.</p>
      <Button as-child variant="outline" class="mt-4">
        <router-link to="/programs">Kembali ke Arsip Program</router-link>
      </Button>
    </Card>
  </div>
</template>

<script setup>
import { computed, ref, reactive, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import PageHeader from '@/components/layout/PageHeader.vue';
import Dialog from '@/components/ui/Dialog.vue';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatNpwp, formatRupiah, formatTanggal } from '@/lib/format';
import DocumentUploadModal from '../components/detail/DocumentUploadModal.vue';
import DocumentPreviewSheet from '../components/detail/DocumentPreviewSheet.vue';
import { paymentBadgeVariant } from '../components/programs/programTable';
import {
  useTaxStore,
  formatDate,
  formatUploadDate,
  getCompleteness,
  getProgramMonth,
  getProgramYear,
  getProgramCompanyName,
  getProgramBrand,
  getProgramPoSjNumber,
  getPphLabel,
  calculatePphAmount,
  PPH_OPTIONS,
  isNonPpnCategory
} from '../store/taxStore';

const route = useRoute();
const router = useRouter();
const store = useTaxStore();

const isAdmin = computed(() => store.isAdmin.value);
const isGudang = computed(() => store.isGudang.value);
const isFinance = computed(() => store.isFinance.value);
const isScm = computed(() => store.isScm.value);
const canEditPurchase = computed(() => store.canEditPurchase.value);
const canEditFinance = computed(() => store.canEditFinance.value);
const canEditProgram = computed(() => store.canEditProgram.value);
const canViewMasterColumns = computed(() => store.canViewMasterColumns.value);
const canViewFinanceColumns = computed(() => store.canViewFinanceColumns.value);

function canUploadDoc(docType) {
  return store.canUploadDoc(docType);
}

function canDeleteDoc(docType) {
  return store.canDeleteDoc(docType);
}

const programId = computed(() => route.params.id);
const program = computed(() => store.getProgramById(programId.value));
const completeness = computed(() => getCompleteness(program.value));

const uploaderName = computed(() => store.currentUser.value?.name || 'Bagas Nugroho');

const documentCategories = [
  { type: 'invoice', label: 'Invoice' },
  { type: 'faktur_pajak', label: 'Faktur Pajak' },
  { type: 'mou', label: 'Memo/DO' }
];

// Form edit dikirim lewat tombol di footer dialog (di luar elemen <form>)
const EDIT_FORM_ID = 'program-detail-edit-form';

// Deskripsi judul halaman: No. PO/SJ · tanggal · supplier (PO/SJ & supplier hanya untuk yang boleh melihat data master)
const headerDescription = computed(() => {
  const p = program.value;
  if (!p) return '';
  const date = p.program_date ? formatTanggal(p.program_date) : '';
  const parts = canViewMasterColumns.value
    ? [p.po_sj_number || getProgramPoSjNumber(p), date, p.supplier]
    : [date];
  return parts.filter(Boolean).join(' · ');
});

// Warna status hanya untuk makna: lengkap = success, sebagian = warning, belum ada = netral
const completenessVariant = computed(() => {
  if (completeness.value.count === 3) return 'success';
  return completeness.value.count > 0 ? 'warning' : 'outline';
});

// Keterangan baris berkas: ukuran · waktu unggah (tanpa waktu unggah: tanggal program)
function docMeta(doc) {
  const uploaded = doc.uploaded_at
    ? formatUploadDate(doc.uploaded_at)
    : (program.value?.program_date ? formatTanggal(program.value.program_date) : '');
  return [doc.file_size, uploaded].filter(Boolean).join(' · ');
}

// Rincian kartu bawah: label di atas nilai supaya ringkas. Tanggal & NPWP diformat seperti di Arsip Program.
const invoiceFields = computed(() => {
  const p = program.value;
  if (!p) return [];
  return [
    { label: 'No. Invoice', value: p.invoice_number },
    { label: 'Tanggal Invoice', value: formatTanggal(p.program_date) },
    { label: 'Jatuh Tempo', value: formatTanggal(formatDueDate(p.program_date)) },
    { label: 'No. Faktur Pajak', value: p.faktur_number },
    { label: 'Tanggal Faktur Pajak', value: p.faktur_date ? formatTanggal(p.faktur_date) : '' }
  ];
});

const amountRows = computed(() => {
  const p = program.value;
  if (!p) return [];
  return [
    { label: 'DPP', value: p.dpp },
    { label: 'PPN (11%)', value: p.ppn },
    { label: getPphLabel(p.pph_type), value: p.pph || 0 }
  ];
});

const programFields = computed(() => {
  const p = program.value;
  if (!p) return [];
  return [
    { label: 'Company Name', value: p.company_name || getProgramCompanyName(p) },
    { label: 'No. PO / SJ', value: p.po_sj_number || getProgramPoSjNumber(p), numeric: true },
    { label: 'Nama Supplier', value: p.supplier },
    { label: 'NPWP Supplier', value: formatNpwp(p.npwp), numeric: true },
    { label: 'Kategori', value: p.category },
    { label: 'Brand', value: p.brand || getProgramBrand(p) },
    { label: 'Bulan / Masa Pajak', value: `${getProgramMonth(p.program_date)} ${getProgramYear(p.program_date)}` },
    // Kosong ditampilkan "—" (dulu diisi nomor contoh DO/SCM/2026/…, yang tampak seperti nomor asli)
    { label: 'Nomor Memo/DO', value: p.mou_number, numeric: true },
    { label: 'Periode', value: `${formatTanggal(p.program_date)} – ${formatTanggal(formatDueDate(p.program_date))}`, numeric: true }
  ];
});

function getDocs(docType) {
  return (program.value?.documents || []).filter(d => d.document_type === docType);
}

function getDoc(docType) {
  return (program.value?.documents || []).find(d => d.document_type === docType) || null;
}

// Status Badge text matching requirements
const statusBadgeText = computed(() => {
  const docs = program.value?.documents || [];
  const hasInvoice = docs.some(d => d.document_type === 'invoice');
  const hasFaktur = docs.some(d => d.document_type === 'faktur_pajak');
  const hasMou = docs.some(d => d.document_type === 'mou');

  const count = (hasInvoice ? 1 : 0) + (hasFaktur ? 1 : 0) + (hasMou ? 1 : 0);
  if (count === 3) return 'Dokumen Lengkap';
  if (!hasFaktur && hasInvoice && hasMou) return 'Kurang Faktur Pajak';
  if (!hasInvoice && hasFaktur && hasMou) return 'Kurang Invoice';
  if (!hasMou && hasInvoice && hasFaktur) return 'Kurang Memo/DO';
  if (count === 0) return 'Belum Ada Dokumen';
  return `Kurang ${3 - count} Dokumen`;
});

function formatDueDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const s = String(dateStr).trim();
    let d;
    const dmy = s.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/);
    if (dmy) {
      d = new Date(parseInt(dmy[3], 10), parseInt(dmy[2], 10) - 1, parseInt(dmy[1], 10));
    } else {
      const ymd = s.match(/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/);
      if (ymd) {
        d = new Date(parseInt(ymd[1], 10), parseInt(ymd[2], 10) - 1, parseInt(ymd[3], 10));
      } else {
        d = new Date(s);
      }
    }
    if (isNaN(d.getTime())) return '-';
    d.setDate(d.getDate() + 30);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
  } catch (e) {
    return '-';
  }
}

function goBack() {
  router.push('/programs');
}

// Upload Modal State
const isUploadModalOpen = ref(false);
const uploadTargetType = ref('faktur_pajak');
const uploadTargetLabel = ref('Faktur Pajak');

function openUpload(docType, label) {
  uploadTargetType.value = docType;
  uploadTargetLabel.value = label;
  isUploadModalOpen.value = true;
}

async function handleDocumentUploaded(fileData) {
  if (Array.isArray(fileData)) {
    for (const f of fileData) {
      await store.uploadDocument(program.value.id, f.docType, f);
    }
  } else {
    await store.uploadDocument(program.value.id, fileData.docType, fileData);
  }
}

const isAnalyzingDocId = ref(null);
async function runAiAnalysis(doc) {
  if (!doc?.id || !program.value?.id || isAnalyzingDocId.value) return;
  isAnalyzingDocId.value = doc.id;
  try {
    await store.analyzeDocumentAi(program.value.id, doc.id);
  } finally {
    isAnalyzingDocId.value = null;
  }
}

// Preview Sheet State
const isPreviewSheetOpen = ref(false);
const selectedPreviewDoc = ref(null);

function openPreviewDoc(doc) {
  selectedPreviewDoc.value = doc;
  isPreviewSheetOpen.value = true;
}

function openPreview(docType) {
  const existing = getDoc(docType);
  selectedPreviewDoc.value = existing || {
    document_type: docType,
    file_name: `${docType.toUpperCase()}-${program.value?.invoice_number?.replace(/\//g, '-') || program.value?.id}.pdf`,
    uploaded_at: formatDate(program.value?.program_date),
    uploaded_by: uploaderName.value
  };
  isPreviewSheetOpen.value = true;
}

async function downloadDocItem(doc) {
  if (!doc) return;
  const fileName = doc.file_name || `dokumen-${program.value?.id}.pdf`;

  let downloadUrl = doc.file_data || doc.file_url;
  if (!downloadUrl && doc.id) {
    downloadUrl = await store.loadDocumentContent(doc.id);
  }

  if (downloadUrl) {
    const a = document.createElement('a');
    a.href = downloadUrl;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    store.notify(`Dokumen ${fileName} berhasil diunduh.`);
    return;
  }

  const blob = new Blob([
    `SCM TaxVault Document Archive\nProgram: ${program.value?.program_name}\nSupplier: ${program.value?.supplier}\nBerkas: ${fileName}`
  ], { type: 'application/pdf' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = fileName;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  store.notify(`Dokumen ${fileName} telah diunduh.`);
}

async function downloadDoc(docType) {
  const doc = getDoc(docType);
  if (doc) {
    await downloadDocItem(doc);
  }
}

const docToDelete = ref(null);
const isDeletingDoc = ref(false);
const isDeleteProgramModalOpen = ref(false);

function deleteDocItem(doc, typeLabel) {
  docToDelete.value = {
    id: doc.id,
    type: doc.document_type || doc.type,
    label: typeLabel || store.getDocTypeLabel(doc.document_type || doc.type),
    fileName: doc.file_name || ''
  };
}

function deleteDoc(docType) {
  const label = store.getDocTypeLabel(docType);
  const existing = getDoc(docType);
  docToDelete.value = {
    id: existing?.id,
    type: docType,
    label: label,
    fileName: existing?.file_name || ''
  };
}

async function confirmDeleteDoc() {
  if (!docToDelete.value || isDeletingDoc.value) return;
  isDeletingDoc.value = true;
  try {
    const targetIdentifier = docToDelete.value.id || docToDelete.value.fileName || docToDelete.value.type;
    await store.deleteDocument(program.value.id, targetIdentifier);
  } finally {
    isDeletingDoc.value = false;
    docToDelete.value = null;
  }
}

// Available Categories for Edit Modal (inclusive of all system categories)
const availableCategories = computed(() => {
  const list = [...store.masterCategories.value];
  if (program.value?.category && !list.includes(program.value.category)) {
    list.unshift(program.value.category);
  }
  return list;
});

const masterBrands = computed(() => store.masterBrands.value);
const masterCompanies = computed(() => store.masterCompanies.value);
const masterSuppliers = computed(() => store.masterSuppliers.value);

const availableBrands = computed(() => {
  const list = [...store.masterBrands.value];
  if (editForm.brand && !list.includes(editForm.brand)) {
    list.unshift(editForm.brand);
  }
  return list;
});

const availableCompanies = computed(() => {
  const list = [...store.masterCompanies.value];
  if (editForm.company_name && !list.includes(editForm.company_name)) {
    list.unshift(editForm.company_name);
  }
  return list;
});

function onEditSupplierChange() {
  const match = masterSuppliers.value.find(s => s.name === editForm.supplier);
  if (match && match.npwp) {
    editForm.npwp = match.npwp;
  }
}

// Edit Modal State & Handling
const isEditModalOpen = ref(false);
const isSavingEdit = ref(false);
const editForm = reactive({
  program_name: '',
  supplier: '',
  company_name: '',
  npwp: '',
  po_sj_number: '',
  invoice_number: '',
  category: 'Logistik',
  brand: 'SCM',
  payment_status: 'WAITING PAYMENT',
  dpp: 0,
  ppn: 0,
  ppn_rate: '0.11',
  pph_type: 'NON_PPH',
  pph: 0,
  faktur_number: '',
  faktur_date: ''
});

// Auto-switch ppn_rate saat kategori di edit form berubah
watch(() => editForm.category, (cat) => {
  if (isNonPpnCategory(cat, editForm.program_name)) {
    editForm.ppn_rate = '0';
  } else {
    editForm.ppn_rate = '0.11';
  }
  calculateTaxes();
});

function normalizePayStatus(status) {
  if (!status) return 'WAITING PAYMENT';
  const s = String(status).trim();
  const lower = s.toLowerCase();
  if (lower === 'cbd' || lower === 'cbd (cash before delivery)') return 'cbd';
  if (lower === 'tempo') return 'tempo';
  if (lower === 'paid' || lower === 'lunas' || lower === 'paid (lunas)') return 'PAID';
  if (lower === 'waiting payment') return 'WAITING PAYMENT';
  return s;
}

function formatPaymentStatus(status) {
  if (!status) return 'Waiting Payment';
  const s = String(status).trim();
  const lower = s.toLowerCase();
  if (lower === 'cbd' || lower === 'cbd (cash before delivery)') return 'CBD';
  if (lower === 'tempo') return 'Tempo';
  if (lower === 'paid' || lower === 'lunas' || lower === 'paid (lunas)') return 'Paid';
  if (lower === 'waiting payment') return 'Waiting Payment';
  return s;
}

const masterPaymentStatuses = computed(() => store.masterPaymentStatuses.value);

const editPaymentStatusOptions = computed(() => {
  const list = [];
  const master = masterPaymentStatuses.value.length > 0
    ? masterPaymentStatuses.value
    : ['Waiting Payment', 'CBD (Cash Before Delivery)', 'Tempo', 'Paid (Lunas)'];

  master.forEach(m => {
    list.push({
      value: normalizePayStatus(m),
      label: m
    });
  });

  if (editForm.payment_status) {
    const curNorm = normalizePayStatus(editForm.payment_status);
    if (!list.some(item => item.value === curNorm)) {
      list.unshift({
        value: curNorm,
        label: formatPaymentStatus(editForm.payment_status)
      });
    }
  }

  const seen = new Set();
  return list.filter(item => {
    if (seen.has(item.value)) return false;
    seen.add(item.value);
    return true;
  });
});

async function handlePaymentStatusChange(newStatus) {
  if (!program.value?.id) return;
  await store.updatePaymentStatus(program.value.id, newStatus);
}

function openEditModal() {
  if (!program.value) return;
  // Modal langsung dibuka; data master diperbarui di belakang
  store.fetchMasterData();
  editForm.program_name = program.value.program_name || '';
  editForm.supplier = program.value.supplier || '';
  editForm.company_name = program.value.company_name || getProgramCompanyName(program.value) || '';
  editForm.npwp = program.value.npwp || '';
  editForm.po_sj_number = program.value.po_sj_number || getProgramPoSjNumber(program.value) || '';
  editForm.invoice_number = program.value.invoice_number || '';
  editForm.payment_status = program.value.payment_status || 'WAITING PAYMENT';
  editForm.category = program.value.category || 'Logistik';
  editForm.brand = program.value.brand || getProgramBrand(program.value) || '';
  editForm.dpp = Number(program.value.dpp) || 0;
  // Tentukan ppn_rate berdasar kategori
  const nonPpn = isNonPpnCategory(editForm.category, editForm.program_name);
  editForm.ppn_rate = nonPpn ? '0' : '0.11';
  editForm.ppn = nonPpn ? 0 : (Number(program.value.ppn) || Math.round(editForm.dpp * 0.11));
  editForm.pph_type = program.value.pph_type || 'NON_PPH';
  editForm.pph = Number(program.value.pph) || calculatePphAmount(editForm.dpp, editForm.pph_type);
  editForm.faktur_number = program.value.faktur_number || '';
  editForm.faktur_date = program.value.faktur_date ? String(program.value.faktur_date).slice(0, 10) : '';
  isEditModalOpen.value = true;
}

function calculateTaxes() {
  const rate = Number(editForm.ppn_rate) || 0;
  editForm.ppn = Math.round((Number(editForm.dpp) || 0) * rate);
  if (editForm.pph_type && editForm.pph_type !== 'NON_PPH') {
    editForm.pph = calculatePphAmount(editForm.dpp, editForm.pph_type);
  } else {
    editForm.pph = 0;
  }
}

function onPphTypeChange() {
  editForm.pph = calculatePphAmount(editForm.dpp, editForm.pph_type);
}

async function saveEditProgram() {
  if (isSavingEdit.value) return;
  isSavingEdit.value = true;
  try {
    const res = await store.updateProgram(program.value.id, {
      program_name: editForm.program_name,
      supplier: editForm.supplier,
      company_name: editForm.company_name,
      npwp: editForm.npwp,
      po_sj_number: editForm.po_sj_number,
      invoice_number: editForm.invoice_number || '',
      invoice_no: editForm.invoice_number || '',
      category: editForm.category,
      brand: editForm.brand,
      payment_status: editForm.payment_status,
      dpp: editForm.dpp,
      ppn: editForm.ppn,
      total_invoice: editForm.dpp + editForm.ppn,
      pph_type: editForm.pph_type,
      pph: editForm.pph,
      pph_amount: editForm.pph,
      faktur_number: editForm.faktur_number || '',
      tax_invoice_number: editForm.faktur_number || '',
      faktur_date: editForm.faktur_date || null,
      tax_invoice_date: editForm.faktur_date || null
    });
    if (res?.success) {
      isEditModalOpen.value = false;
    }
  } catch (err) {
    console.error('Gagal menyimpan program:', err);
  } finally {
    isSavingEdit.value = false;
  }
}

function confirmDeleteProgram() {
  isDeleteProgramModalOpen.value = true;
}

async function executeDeleteProgram() {
  isDeleteProgramModalOpen.value = false;
  await store.deleteProgram(program.value.id);
  router.push('/programs');
}
</script>
