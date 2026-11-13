<template>
  <Dialog
    :open="isOpen"
    title="Import Data Program & Invoice"
    description="Unggah file Excel (.xlsx, .xls) atau CSV untuk memasukkan data program sekaligus."
    max-width="max-w-4xl"
    @update:open="value => { if (!value) closeModal(); }"
  >
    <div class="space-y-6">
      <!-- Format kolom & template -->
      <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 space-y-2">
          <p class="font-medium">Kolom yang didukung</p>
          <div class="flex flex-wrap gap-1">
            <Badge v-for="col in supportedColumns" :key="col" variant="outline" class="font-normal">
              {{ col }}
            </Badge>
          </div>
          <p class="text-xs text-muted-foreground">
            PPN (11%) dan Total Invoice dihitung otomatis jika nilainya dikosongkan.
          </p>
        </div>
        <div class="flex shrink-0 gap-2">
          <Button type="button" variant="outline" size="sm" @click="downloadTemplate('xlsx')">Template Excel</Button>
          <Button type="button" variant="outline" size="sm" @click="downloadTemplate('csv')">Template CSV</Button>
        </div>
      </div>

      <!-- Area unggah -->
      <div
        v-if="parsedRows.length === 0"
        role="button"
        tabindex="0"
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed p-8 text-center transition-colors outline-none hover:bg-muted/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
        :class="isDragging ? 'border-ring bg-muted/50' : ''"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        @click="triggerFileInput"
        @keydown.enter.prevent="triggerFileInput"
        @keydown.space.prevent="triggerFileInput"
      >
        <input
          ref="fileInputRef"
          type="file"
          accept=".xlsx, .xls, .csv"
          class="hidden"
          @change="handleFileSelect"
        />
        <div class="mb-2 flex size-10 items-center justify-center rounded-full border bg-background">
          <UploadCloud class="size-4 text-muted-foreground" />
        </div>
        <p class="font-medium">
          Tarik file Excel / CSV ke sini, atau <span class="underline underline-offset-4">pilih dari perangkat</span>
        </p>
        <p class="text-xs text-muted-foreground">Mendukung format .xlsx, .xls, atau .csv (maksimal 10 MB)</p>
      </div>

      <!-- Sedang membaca file -->
      <div v-if="isLoading" class="flex flex-col items-center justify-center gap-2 py-8 text-center text-muted-foreground">
        <Loader2 class="size-4 animate-spin" />
        <p>Membaca dan memvalidasi file data…</p>
      </div>

      <!-- Pesan galat: ringkasan singkat + daftar baris duplikat (bila ada) -->
      <div v-if="errorMessage" role="alert" class="overflow-hidden rounded-lg border">
        <div class="flex items-start gap-3 p-4">
          <span class="flex h-5 shrink-0 items-center" aria-hidden="true">
            <AlertCircle class="size-4 text-destructive" />
          </span>
          <div class="min-w-0 flex-1">
            <p class="font-medium">
              {{ duplicateRows.length ? 'Upload ditolak' : 'Gagal memproses file' }}
            </p>
            <p class="mt-1 text-muted-foreground">{{ errorMessage }}</p>
          </div>
          <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            class="-mt-1 -mr-2 shrink-0 text-muted-foreground"
            aria-label="Tutup pesan"
            @click="dismissError"
          >
            <X />
          </Button>
        </div>

        <ul v-if="duplicateRows.length" class="max-h-36 divide-y overflow-y-auto border-t">
          <li
            v-for="dup in duplicateRows"
            :key="dup.row"
            class="flex items-center gap-3 px-4 py-2 text-xs"
          >
            <span class="w-14 shrink-0 tabular-nums text-muted-foreground">Baris {{ dup.row }}</span>
            <span class="shrink-0 font-medium tabular-nums">{{ dup.po_sj_number }}</span>
            <span class="min-w-0 truncate text-muted-foreground">{{ dup.company_name }} · {{ dup.category }}</span>
            <span v-if="dup.reason === 'in_file'" class="ml-auto shrink-0 text-muted-foreground">ganda di file</span>
          </li>
        </ul>
      </div>

      <!-- Pratinjau data -->
      <div v-if="parsedRows.length > 0 && !isLoading" class="space-y-4">
        <div class="flex items-center justify-between gap-4 rounded-lg border p-4">
          <div class="min-w-0">
            <p class="truncate font-medium" :title="selectedRawFile?.name">
              {{ selectedRawFile?.name || 'File Excel Terpilih' }}
            </p>
            <p class="text-xs text-muted-foreground">
              <span class="tabular-nums">{{ parsedRows.length }}</span> baris data siap dimasukkan ke database Arsip Program
            </p>
          </div>
          <Button type="button" variant="outline" size="sm" class="shrink-0" @click="resetFile">Ganti file</Button>
        </div>

        <div class="overflow-hidden rounded-lg border">
          <Table container-class="relative max-h-72 w-full overflow-auto">
            <TableHeader class="[&_tr]:border-b-0">
              <TableRow class="hover:bg-transparent">
                <TableHead :class="previewHeadClass">No</TableHead>
                <TableHead :class="previewHeadClass">Tanggal</TableHead>
                <TableHead :class="previewHeadClass">Kategori</TableHead>
                <TableHead :class="previewHeadClass">Brand</TableHead>
                <TableHead :class="previewHeadClass">Company</TableHead>
                <TableHead :class="previewHeadClass">Kode Gudang</TableHead>
                <TableHead :class="previewHeadClass">No. PO/SJ</TableHead>
                <TableHead :class="previewHeadClass">Program</TableHead>
                <TableHead :class="previewHeadClass">Supplier</TableHead>
                <TableHead :class="previewHeadClass">No. Invoice</TableHead>
                <TableHead :class="previewHeadClass">Faktur Pajak</TableHead>
                <TableHead :class="[previewHeadClass, 'text-right']">DPP</TableHead>
                <TableHead :class="[previewHeadClass, 'text-right']">PPN</TableHead>
                <TableHead :class="[previewHeadClass, 'text-right']">Total</TableHead>
                <TableHead :class="previewHeadClass">Status Payment</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="(row, idx) in parsedRows.slice(0, 15)" :key="idx">
                <TableCell class="tabular-nums text-muted-foreground">{{ idx + 1 }}</TableCell>
                <TableCell class="tabular-nums">{{ formatTanggal(row.program_date) }}</TableCell>
                <TableCell>{{ row.category }}</TableCell>
                <TableCell>{{ row.brand || 'SCM' }}</TableCell>
                <TableCell>
                  <span class="block max-w-32 truncate" :title="row.company_name">{{ row.company_name }}</span>
                </TableCell>
                <TableCell class="tabular-nums">{{ row.kode_gudang || '—' }}</TableCell>
                <TableCell class="tabular-nums">{{ row.po_sj_number || '—' }}</TableCell>
                <TableCell>
                  <span class="block max-w-40 truncate" :title="row.program_name">{{ row.program_name }}</span>
                </TableCell>
                <TableCell>
                  <span class="block max-w-32 truncate" :title="row.supplier">{{ row.supplier }}</span>
                </TableCell>
                <TableCell class="tabular-nums">{{ row.invoice_number }}</TableCell>
                <TableCell>
                  <template v-if="row.tax_invoice_number || row.faktur_number || row.tax_invoice_date || row.faktur_date">
                    <p
                      class="max-w-40 truncate tabular-nums"
                      :title="row.tax_invoice_number || row.faktur_number"
                    >
                      {{ row.tax_invoice_number || row.faktur_number || '—' }}
                    </p>
                    <p class="text-xs tabular-nums text-muted-foreground">
                      {{ formatTanggal(row.tax_invoice_date || row.faktur_date) }}
                    </p>
                  </template>
                  <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell class="text-right tabular-nums">{{ formatRupiah(row.dpp) }}</TableCell>
                <TableCell class="text-right tabular-nums">{{ formatRupiah(row.ppn) }}</TableCell>
                <TableCell class="text-right tabular-nums">{{ formatRupiah(row.total_invoice) }}</TableCell>
                <TableCell>
                  <Badge :variant="paymentBadgeVariant(row.payment_status)">{{ formatPaymentStatus(row.payment_status) }}</Badge>
                </TableCell>
              </TableRow>
            </TableBody>
          </Table>
          <p
            v-if="parsedRows.length > 15"
            class="border-t px-4 py-2 text-center text-xs tabular-nums text-muted-foreground"
          >
            Menampilkan 15 dari {{ parsedRows.length }} baris data
          </p>
        </div>
      </div>
    </div>

    <template #footer>
      <p class="mr-auto hidden text-xs text-muted-foreground sm:block">
        {{ parsedRows.length > 0 ? `${parsedRows.length} data siap dimasukkan` : 'Pilih file terlebih dahulu' }}
      </p>
      <Button type="button" variant="outline" @click="closeModal">Batal</Button>
      <Button
        type="button"
        :disabled="parsedRows.length === 0 || isLoading || isImporting || duplicateRows.length > 0"
        @click="executeImport"
      >
        {{ isImporting ? 'Menyimpan data…' : (parsedRows.length > 0 ? `Import ${parsedRows.length} data` : 'Import') }}
      </Button>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { AlertCircle, Loader2, UploadCloud, X } from 'lucide-vue-next';
import Dialog from '@/components/ui/Dialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatRupiah, formatTanggal } from '@/lib/format';
import { useTaxStore, getProgramYear, isNonPpnCategory } from '../../store/taxStore';
import { formatPaymentStatus, paymentBadgeVariant } from './programTable';

// Judul kolom pratinjau menempel di atas saat tabel digulir (garis bawah lewat shadow, seperti DataTable)
const previewHeadClass = 'sticky top-0 z-10 bg-background shadow-[inset_0_-1px_0_var(--border)]';

const router = useRouter();
const store = useTaxStore();

const supportedColumns = [
  'TANGGAL', 'BULAN', 'KATEGORI', 'BRAND', 'COMPANY NAME', 'KODE GUDANG', 'NO. PO/SJ', 'PROGRAM',
  'SUPPLIER', 'NO. INVOICE', 'DPP', 'PPN', 'TOTAL INVOICE', 'NO. FAKTUR PAJAK', 'TAX INVOICE DATE'
];

const isOpen = computed(() => store.isImportModalOpen.value);

const isDragging = ref(false);
const isLoading = ref(false);
const isImporting = ref(false);
const errorMessage = ref('');
// Baris yang ditolak server karena sudah terdaftar: [{ row, po_sj_number, company_name, category, reason }]
const duplicateRows = ref([]);
const parsedRows = ref([]);
const selectedRawFile = ref(null);
const fileInputRef = ref(null);

function closeModal() {
  resetFile();
  store.closeImportModal();
}

function dismissError() {
  errorMessage.value = '';
  duplicateRows.value = [];
}

function resetFile() {
  parsedRows.value = [];
  selectedRawFile.value = null;
  dismissError();
  isLoading.value = false;
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
}

function triggerFileInput() {
  if (fileInputRef.value) {
    fileInputRef.value.click();
  }
}

function handleFileSelect(e) {
  const file = e.target.files?.[0];
  if (file) {
    processFile(file);
  }
}

function handleDrop(e) {
  isDragging.value = false;
  const file = e.dataTransfer.files?.[0];
  if (file) {
    processFile(file);
  }
}

function normalizeKey(key) {
  return String(key || '')
    .trim()
    .toLowerCase()
    .replace(/[^a-z0-9]/g, '');
}

function cleanNumber(val) {
  if (val === null || val === undefined || val === '') return 0;
  if (typeof val === 'number') return val;
  const str = String(val)
    .replace(/rp/gi, '')
    .replace(/\s+/g, '')
    .replace(/\./g, '')
    .replace(/,/g, '.');
  const num = parseFloat(str);
  return isNaN(num) ? 0 : num;
}

function parseImportDate(val) {
  if (!val) {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  // 1. If already standard YYYY-MM-DD
  if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val.trim())) {
    return val.trim();
  }

  // 2. Handle Excel Serial Number (e.g. 45367 or 37677)
  // Must avoid local time toISOString() conversion because in positive timezones like UTC+7 (Indonesia),
  // midnight local time converted to UTC becomes 17:00 of the previous day, shifting the day backwards (25 to 24)!
  const numVal = typeof val === 'number' ? val : (typeof val === 'string' && /^\d+(\.\d+)?$/.test(val.trim()) ? Number(val) : NaN);
  if (!isNaN(numVal) && numVal > 1000 && numVal < 100000) {
    if (window.XLSX && window.XLSX.SSF && typeof window.XLSX.SSF.parse_date_code === 'function') {
      try {
        const parsed = window.XLSX.SSF.parse_date_code(numVal);
        if (parsed && parsed.y && parsed.m && parsed.d) {
          const y = String(parsed.y);
          const m = String(parsed.m).padStart(2, '0');
          const d = String(parsed.d).padStart(2, '0');
          return `${y}-${m}-${d}`;
        }
      } catch (e) {}
    }
    // Mathematical epoch conversion without timezone shift
    const utcDays = Math.floor(numVal - 25569);
    const dateObj = new Date(utcDays * 86400 * 1000);
    const y = dateObj.getUTCFullYear();
    const m = String(dateObj.getUTCMonth() + 1).padStart(2, '0');
    const d = String(dateObj.getUTCDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  // 3. Handle Date object if passed
  if (val instanceof Date && !isNaN(val.getTime())) {
    if (val.getUTCHours() === 0 && val.getUTCMinutes() === 0) {
      const y = val.getUTCFullYear();
      const m = String(val.getUTCMonth() + 1).padStart(2, '0');
      const d = String(val.getUTCDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    }
    const y = val.getFullYear();
    const m = String(val.getMonth() + 1).padStart(2, '0');
    const d = String(val.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }

  const str = String(val).trim();

  // 4. Check DD-MM-YYYY or DD/MM/YYYY or DD.MM.YYYY
  const dmy = str.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/);
  if (dmy) {
    const day = String(dmy[1]).padStart(2, '0');
    const month = String(dmy[2]).padStart(2, '0');
    const year = dmy[3];
    return `${year}-${month}-${day}`;
  }

  // 4b. Check DD-MM-YY or DD/MM/YY or DD.MM.YY (2-digit year)
  const dmy2 = str.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{2})$/);
  if (dmy2) {
    const day = String(dmy2[1]).padStart(2, '0');
    const month = String(dmy2[2]).padStart(2, '0');
    const yy = parseInt(dmy2[3], 10);
    const year = yy < 50 ? `20${dmy2[3]}` : `19${dmy2[3]}`;
    return `${year}-${month}-${day}`;
  }

  // 5. Check YYYY/MM/DD or YYYY.MM.DD
  const ymd = str.match(/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})$/);
  if (ymd) {
    const year = ymd[1];
    const month = String(ymd[2]).padStart(2, '0');
    const day = String(ymd[3]).padStart(2, '0');
    return `${year}-${month}-${day}`;
  }

  // 6. Check for Indonesian month names
  const indoMonths = [
    { name: 'januari', short: 'jan', num: '01' },
    { name: 'februari', short: 'feb', num: '02' },
    { name: 'maret', short: 'mar', num: '03' },
    { name: 'april', short: 'apr', num: '04' },
    { name: 'mei', short: 'mei', num: '05' },
    { name: 'juni', short: 'jun', num: '06' },
    { name: 'juli', short: 'jul', num: '07' },
    { name: 'agustus', short: 'agu', num: '08' },
    { name: 'agustus', short: 'agt', num: '08' },
    { name: 'september', short: 'sep', num: '09' },
    { name: 'oktober', short: 'okt', num: '10' },
    { name: 'november', short: 'nov', num: '11' },
    { name: 'desember', short: 'des', num: '12' }
  ];

  const lower = str.toLowerCase();
  for (const m of indoMonths) {
    if (lower.includes(m.name) || lower.includes(m.short)) {
      const yearMatch = str.match(/\b(19\d\d|20\d\d)\b/);
      const year = yearMatch ? yearMatch[1] : new Date().getFullYear().toString();
      const dayMatch = str.match(/\b(\d{1,2})\s+[a-zA-Z]/);
      const day = dayMatch ? String(dayMatch[1]).padStart(2, '0') : '01';
      return `${year}-${m.num}-${day}`;
    }
  }

  // 7. Check MM/YYYY or MM-YYYY
  const my = str.match(/^(\d{1,2})[\/\-\.](\d{4})$/);
  if (my) {
    const month = String(my[1]).padStart(2, '0');
    const year = my[2];
    return `${year}-${month}-01`;
  }

  // 8. Fallback date parsing using local components (never toISOString())
  try {
    const d = new Date(str);
    if (!isNaN(d.getTime())) {
      const y = d.getFullYear();
      const m = String(d.getMonth() + 1).padStart(2, '0');
      const day = String(d.getDate()).padStart(2, '0');
      return `${y}-${m}-${day}`;
    }
  } catch (e) {}

  const now = new Date();
  return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
}

function mapRawRow(raw) {
  let program_name = '';
  let supplier = '';
  let invoice_number = '';
  let company_name = '';
  let kode_gudang = '';
  let po_sj_number = '';
  let dpp = 0;
  let ppn = 0;
  let total_invoice = 0;
  let npwp = '';
  let category = 'Logistik';
  let brand = '';
  let raw_tanggal = null;
  let raw_bulan = null;
  let program_date = new Date().toISOString().split('T')[0];
  let faktur_number = '';
  let faktur_date = null;
  let payment_status = 'WAITING PAYMENT';

  for (const [key, val] of Object.entries(raw)) {
    const k = normalizeKey(key);

    // 1. TOTAL INVOICE (Wajib dicek sebelum invoice_number agar 'totalinvoice' tidak tertangkap sebagai invoice_number)
    if (
      (k.includes('total') || k.includes('grand') || k.includes('jumlah') || k.includes('totaltagihan')) &&
      !k.includes('dpp') &&
      !k.includes('ppn')
    ) {
      total_invoice = cleanNumber(val);
    }
    // 2. DPP
    else if (k === 'dpp' || (k.includes('dpp') && !k.includes('ppn')) || k.includes('dasar')) {
      dpp = cleanNumber(val);
    }
    // 3. PPN
    else if (
      k === 'ppn' ||
      (k.includes('ppn') && !k.includes('dpp')) ||
      (k.includes('pajak') && !k.includes('faktur') && !k.includes('npwp'))
    ) {
      ppn = cleanNumber(val);
    }
    // 4. NO. INVOICE (Eksklusif untuk nomor invoice/tagihan, abaikan total, tanggal, dan faktur pajak)
    else if (
      (k.includes('noinvoice') || k.includes('nomorinvoice') || k.includes('invoice') || k.includes('inv') || k.includes('tagihan') || (k.includes('faktur') && !k.includes('pajak'))) &&
      !k.includes('total') &&
      !k.includes('grand') &&
      !k.includes('jumlah') &&
      !k.includes('nilai') &&
      !k.includes('tanggal') &&
      !k.includes('tgl') &&
      !k.includes('date')
    ) {
      invoice_number = String(val || '').trim();
    }
    // 5. PROGRAM
    else if (k.includes('program') || k === 'nama' || k === 'project' || k.includes('namaprogram') || k.includes('namaproject')) {
      program_name = String(val || '').trim();
    }
    // 6. SUPPLIER
    else if (k.includes('supplier') || k.includes('vendor') || k.includes('suplier') || k.includes('namasupplier') || k.includes('namavendor')) {
      supplier = String(val || '').trim();
    }
    // 7. COMPANY NAME
    else if (k.includes('company') || k.includes('perusahaan') || k.includes('entitas') || k === 'pt') {
      company_name = String(val || '').trim();
    }
    // 7.5 KODE GUDANG
    else if (k.includes('kodegudang') || k.includes('gudang') || k.includes('warehouse') || k.includes('whcode') || k === 'wh' || k === 'gdg') {
      kode_gudang = String(val || '').trim();
    }
    // 8. NO. PO / SJ
    else if (
      k.includes('posj') ||
      k.includes('noposj') ||
      k.includes('suratjalan') ||
      k.includes('purchaseorder') ||
      k.includes('nopo') ||
      k.includes('nosj') ||
      k === 'po' ||
      k === 'sj' ||
      (k.includes('po') && k.includes('sj'))
    ) {
      po_sj_number = String(val || '').trim();
    }
    // 8.5 STATUS PAYMENT
    else if (
      k.includes('statuspay') ||
      k.includes('paymentstatus') ||
      k.includes('statuspembayaran') ||
      k.includes('carabayar') ||
      k === 'payment' ||
      k === 'statuspayment'
    ) {
      const pVal = String(val || '').trim().toLowerCase();
      if (pVal.includes('cbd')) payment_status = 'cbd';
      else if (pVal.includes('tempo')) payment_status = 'tempo';
      else if (pVal.includes('paid') || pVal.includes('lunas')) payment_status = 'PAID';
      else payment_status = 'WAITING PAYMENT';
    }
    // 9. NPWP
    else if (k.includes('npwp')) {
      npwp = String(val || '').trim();
    }
    // 10. KATEGORI
    else if (k.includes('kategori') || k.includes('category')) {
      category = String(val || '').trim();
    }
    // 10.5 BRAND / MERK
    else if (k === 'brand' || k.includes('brand') || k.includes('merk')) {
      brand = String(val || '').trim();
    }
    // 11. FAKTUR PAJAK / TAX INVOICE (Harus dicek SEBELUM tanggal/bulan agar 'taxinvoicedate' tidak dianggap program_date)
    else if (
      k.includes('taxinvoice') ||
      k.includes('fakturpajak') ||
      k.includes('tglfp') ||
      k.includes('nofp') ||
      k.includes('tanggalfp') ||
      (k.includes('faktur') && (k.includes('pajak') || k.includes('date') || k.includes('tgl') || k.includes('tanggal') || k.includes('no')))
    ) {
      if (k.includes('tanggal') || k.includes('date') || k.includes('tgl')) {
        faktur_date = (val !== null && val !== undefined && String(val).trim() !== '') ? parseImportDate(val) : null;
      } else {
        faktur_number = String(val || '').trim();
      }
    }
    // 12. TANGGAL TRANSAKSI / PROGRAM
    else if (
      (k === 'tanggal' || k === 'tgl' || k.includes('tanggal') || k.includes('tgl') || k.includes('duedate') || k.includes('programdate') || k.includes('date')) &&
      !k.includes('bulan') && !k.includes('month') && !k.includes('faktur') && !k.includes('tax')
    ) {
      if (val !== null && val !== undefined && String(val).trim() !== '') {
        raw_tanggal = val;
      }
    }
    // 13. BULAN / PERIODE
    else if (k.includes('bulan') || k.includes('month') || k.includes('periode')) {
      if (val !== null && val !== undefined && String(val).trim() !== '') {
        raw_bulan = val;
      }
    }
  }

  // Resolusi Tanggal: Jika ada TANGGAL spesifik maka gunakan TANGGAL, jika hanya BULAN maka gunakan BULAN
  if (raw_tanggal) {
    program_date = parseImportDate(raw_tanggal);
  } else if (raw_bulan) {
    program_date = parseImportDate(raw_bulan);
  }

  // Deduce or normalize category if detected from program_name or column
  let finalCategory = category || '';
  const searchCorpus = `${program_name || ''} ${finalCategory}`.toLowerCase();
  if (!finalCategory) {
    if (searchCorpus.includes('price protection') || searchCorpus.includes('price_protection') || searchCorpus.includes('price protect')) {
      finalCategory = 'Price Protection';
    } else if (searchCorpus.includes('marketing service') || searchCorpus.includes('service fee') || searchCorpus.includes('msf')) {
      finalCategory = 'Marketing Service Fee';
    } else if (searchCorpus.includes('bonus')) {
      finalCategory = 'Bonus';
    } else if (searchCorpus.includes('branding')) {
      finalCategory = 'Branding';
    } else if (searchCorpus.includes('purchase order') || searchCorpus.includes('po ')) {
      finalCategory = 'Purchase Order';
    } else if (searchCorpus.includes('rebate')) {
      finalCategory = 'Rebate';
    }
  }

  // Automatic Fallbacks & Calculations
  // PPN Rule:
  // Price Protection: PPN 0
  // Bonus: PPN 0
  // Rebate: PPN 0
  // Marketing Service Fee: PPN 11%
  // Branding: PPN 11%
  // Purchase Order: PPN 11%
  const isNonPpn = isNonPpnCategory(finalCategory, program_name);
  if (isNonPpn) {
    ppn = 0;
  } else if (dpp > 0 && (ppn === 0 || ppn === null || ppn === undefined)) {
    ppn = Math.round(dpp * 0.11);
  }

  if (total_invoice === 0 || (isNonPpn && total_invoice > dpp && ppn === 0)) {
    total_invoice = dpp + ppn;
  }
  if (!invoice_number) {
    invoice_number = `INV-${Date.now().toString().slice(-6)}`;
  }
  if (!npwp) {
    npwp = '01.234.567.8-012.000';
  }

  return {
    program_name: program_name || 'Program SCM',
    supplier: supplier || 'Vendor SCM',
    company_name: company_name || 'PT SCM Nusantara',
    kode_gudang: kode_gudang || '',
    // Jangan isi PO palsu: baris tanpa PO akan dianggap duplikat satu sama lain
    po_sj_number: po_sj_number || '',
    invoice_number,
    dpp,
    ppn,
    total_invoice,
    npwp,
    category: finalCategory || 'Logistik',
    brand: brand || 'SCM',
    payment_status,
    program_date,
    faktur_number: faktur_number || null,
    faktur_date: faktur_date || null,
    tax_invoice_number: faktur_number || null,
    tax_invoice_date: faktur_date || null
  };
}

async function processFile(file) {
  dismissError();
  isLoading.value = true;
  parsedRows.value = [];
  selectedRawFile.value = file;

  try {
    const fileName = file.name.toLowerCase();
    let rawData = [];

    if (fileName.endsWith('.xlsx') || fileName.endsWith('.xls')) {
      if (!window.XLSX) {
        throw new Error('Pustaka SheetJS belum termuat. Silakan muat ulang halaman atau unggah format .csv.');
      }
      const buffer = await file.arrayBuffer();
      const workbook = window.XLSX.read(new Uint8Array(buffer), { type: 'array' });
      const firstSheet = workbook.SheetNames[0];
      const worksheet = workbook.Sheets[firstSheet];
      rawData = window.XLSX.utils.sheet_to_json(worksheet, { defval: '' });
    } else if (fileName.endsWith('.csv')) {
      const text = await file.text();
      rawData = parseCsvText(text);
    } else {
      throw new Error('Format file tidak didukung. Harap unggah file .xlsx, .xls, atau .csv.');
    }

    if (!rawData || rawData.length === 0) {
      throw new Error('File tidak memiliki data atau baris kosong.');
    }

    const mapped = rawData.map(mapRawRow).filter(r => r.program_name && (r.dpp > 0 || r.total_invoice > 0 || r.supplier));

    if (mapped.length === 0) {
      throw new Error('Tidak ada data valid yang dapat dikenali. Pastikan terdapat kolom PROGRAM, SUPPLIER, NO. INVOICE, DPP, PPN, dan TOTAL INVOICE.');
    }

    parsedRows.value = mapped;
  } catch (err) {
    console.error('Import error:', err);
    errorMessage.value = err.message || 'Terjadi kesalahan saat memproses file.';
  } finally {
    isLoading.value = false;
  }
}

function parseCsvText(text) {
  let lines = text.split(/\r\n|\n/).map(l => l.trim()).filter(Boolean);
  if (lines.length < 2) return [];

  let delimiter = lines[0].includes(';') ? ';' : ',';
  if (lines[0].toLowerCase().startsWith('sep=')) {
    delimiter = lines[0].slice(4).trim() || delimiter;
    lines.shift();
  }
  if (lines.length < 2) return [];

  if (!lines[0].includes(delimiter) && lines[0].includes(';')) {
    delimiter = ';';
  } else if (!lines[0].includes(delimiter) && lines[0].includes(',')) {
    delimiter = ',';
  }

  const headers = lines[0].split(delimiter).map(h => h.replace(/^["']|["']$/g, '').trim());

  const rows = [];
  for (let i = 1; i < lines.length; i++) {
    const line = lines[i];
    const values = line.split(delimiter).map(v => v.replace(/^["']|["']$/g, '').trim());
    const obj = {};
    headers.forEach((h, idx) => {
      obj[h] = values[idx] || '';
    });
    rows.push(obj);
  }
  return rows;
}

function downloadTemplate(format = 'xlsx') {
  const fileName = format === 'csv'
    ? 'Template_Import_Arsip_Program_SCM.csv'
    : 'Template_Import_Arsip_Program_SCM.xlsx';

  const link = document.createElement('a');
  link.href = `/templates/${fileName}`;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  store.notify(`Template Dummy (${format.toUpperCase()}) berhasil diunduh.`);
}

async function executeImport() {
  if (parsedRows.value.length === 0 || isImporting.value) return;
  isImporting.value = true;
  dismissError();
  try {
    await store.importPrograms(parsedRows.value, selectedRawFile.value);

    // Deteksi tahun dari data yang diimport & set filter dashboard
    const yearCounts = {};
    parsedRows.value.forEach(row => {
      const dateStr = String(row.program_date || row.due_date || row.BULAN || '');
      const yr = getProgramYear(dateStr);
      if (/^\d{4}$/.test(yr)) {
        yearCounts[yr] = (yearCounts[yr] || 0) + 1;
      }
    });
    const mostCommonYear = Object.keys(yearCounts).sort((a, b) => yearCounts[b] - yearCounts[a])[0];
    if (mostCommonYear) {
      store.setFiscalYear(mostCommonYear);
    }

    closeModal();
    // Navigasi ke Programs untuk melihat data yang baru diimport
    router.push('/programs');
  } catch (err) {
    duplicateRows.value = err.duplicates || [];
    errorMessage.value = duplicateRows.value.length
      ? `${duplicateRows.value.length} data sudah terdaftar dengan Company, No. PO, dan Category yang sama, jadi tidak ada yang diimpor. Ganti file untuk mengimpor data baru.`
      : (err.message || 'Gagal menyimpan data import ke server.');
  } finally {
    isImporting.value = false;
  }
}
</script>
