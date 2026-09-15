<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto"
  >
    <!-- Backdrop -->
    <div
      class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
      @click="closeModal"
    ></div>

    <!-- Modal Dialog -->
    <div class="relative bg-white dark:bg-[#111827] rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 w-full max-w-4xl overflow-hidden z-10 flex flex-col max-h-[90vh] animate-in fade-in zoom-in-95 duration-150">
      <!-- Header -->
      <div class="px-6 py-4.5 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700 flex items-center justify-center shrink-0">
            <ExcelIcon class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white leading-tight">
              Import Data Program & Invoice
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Unggah file Excel (.xlsx, .xls) atau CSV untuk memasukkan data program sekaligus
            </p>
          </div>
        </div>
        <button
          type="button"
          class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors cursor-pointer"
          @click="closeModal"
        >
          <X class="w-5 h-5" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 overflow-y-auto space-y-5 flex-1 text-xs">
        <!-- Format Column Reference Note -->
        <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 flex items-start gap-3 text-slate-700 dark:text-slate-300">
          <Info class="w-4 h-4 text-slate-500 dark:text-slate-400 mt-0.5 shrink-0" />
          <div class="flex-1 text-[11px] leading-relaxed">
            <p class="font-bold text-slate-900 dark:text-white">Format Kolom yang Didukung:</p>
            <div class="mt-1.5 flex flex-wrap gap-1.5">
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">BULAN</span>
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">KATEGORI</span>
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">COMPANY NAME</span>
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">NO. PO/SJ</span>
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">PROGRAM</span>
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">SUPPLIER</span>
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">NO. INVOICE</span>
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">DPP</span>
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">PPN</span>
              <span class="px-2 py-0.5 bg-white dark:bg-slate-800 rounded border border-slate-200 dark:border-slate-700 font-mono font-semibold text-slate-700 dark:text-slate-300">TOTAL INVOICE</span>
            </div>
            <p class="text-slate-500 dark:text-slate-400 mt-1.5 text-[10px]">
              * PPN (11%) dan Total Invoice akan dihitung otomatis jika nilainya dikosongkan.
            </p>
          </div>
          <div class="shrink-0 flex flex-col sm:flex-row gap-1.5">
            <button
              type="button"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[11px] font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700/60 transition-colors shadow-2xs cursor-pointer"
              @click="downloadTemplate('xlsx')"
            >
              <ExcelIcon class="w-3.5 h-3.5" />
              <span>Unduh Template (.xlsx)</span>
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[11px] font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/60 transition-colors shadow-2xs cursor-pointer"
              @click="downloadTemplate('csv')"
            >
              <span>Unduh CSV</span>
            </button>
          </div>
        </div>

        <!-- Dropzone -->
        <div
          v-if="parsedRows.length === 0"
          class="border-2 border-dashed rounded-xl p-7 text-center cursor-pointer transition-colors"
          :class="isDragging ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20' : 'border-slate-300 dark:border-slate-700 hover:border-emerald-400 bg-slate-50/40 dark:bg-slate-900/40'"
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="handleDrop"
          @click="triggerFileInput"
        >
          <input
            ref="fileInputRef"
            type="file"
            accept=".xlsx, .xls, .csv"
            class="hidden"
            @change="handleFileSelect"
          />

          <div class="flex flex-col items-center justify-center gap-2">
            <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-1">
              <UploadCloud class="w-6 h-6" />
            </div>
            <p class="font-bold text-slate-800 dark:text-slate-200 text-sm">
              Tarik file Excel / CSV ke sini, atau <span class="text-blue-600 dark:text-blue-400 underline">pilih dari perangkat</span>
            </p>
            <p class="text-slate-400 dark:text-slate-500 text-[11px]">
              Mendukung format .xlsx, .xls, atau .csv (Maksimal 10 MB)
            </p>
          </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="py-8 text-center text-slate-500 dark:text-slate-400 flex flex-col items-center justify-center gap-2">
          <div class="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
          <p class="font-medium text-xs">Membaca dan memvalidasi file data...</p>
        </div>

        <!-- Error Banner -->
        <div
          v-if="errorMessage"
          class="p-4 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900 text-rose-800 dark:text-rose-300 text-xs flex items-start gap-3"
        >
          <AlertCircle class="w-4 h-4 text-rose-600 mt-0.5 shrink-0" />
          <div class="flex-1">
            <p class="font-bold">Gagal Memproses File</p>
            <p class="mt-0.5 text-rose-700 dark:text-rose-300 leading-relaxed">{{ errorMessage }}</p>
          </div>
          <button
            type="button"
            class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300 p-1 cursor-pointer"
            @click="errorMessage = ''"
          >
            <X class="w-4 h-4" />
          </button>
        </div>

        <!-- Parsed Data Preview -->
        <div v-if="parsedRows.length > 0 && !isLoading" class="space-y-3 animate-in fade-in duration-150">
          <!-- File Selected Info Banner -->
          <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center">
                <ExcelIcon class="w-4 h-4" />
              </div>
              <div>
                <p class="font-bold text-slate-900 dark:text-white text-xs">
                  {{ selectedRawFile?.name || 'File Excel Terpilih' }}
                </p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                  {{ parsedRows.length }} baris data siap dimasukkan ke database Arsip Program
                </p>
              </div>
            </div>
            <button
              type="button"
              class="px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer"
              @click="resetFile"
            >
              Ganti File
            </button>
          </div>

          <!-- Preview Table -->
          <div class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-2xs">
            <div class="overflow-x-auto max-h-72">
              <table class="w-full text-left text-xs border-collapse">
                <thead class="sticky top-0 bg-slate-100 dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400 font-bold uppercase text-[10px]">
                  <tr>
                    <th class="py-2.5 px-3">NO</th>
                    <th class="py-2.5 px-2">BULAN</th>
                    <th class="py-2.5 px-2">KATEGORI</th>
                    <th class="py-2.5 px-2">COMPANY</th>
                    <th class="py-2.5 px-2">NO. PO/SJ</th>
                    <th class="py-2.5 px-3">PROGRAM</th>
                    <th class="py-2.5 px-3">SUPPLIER</th>
                    <th class="py-2.5 px-3">NO. INVOICE</th>
                    <th class="py-2.5 px-3 text-right">DPP</th>
                    <th class="py-2.5 px-3 text-right">PPN</th>
                    <th class="py-2.5 px-3 text-right">TOTAL INVOICE</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 bg-white dark:bg-[#111827]">
                  <tr
                    v-for="(row, idx) in parsedRows.slice(0, 15)"
                    :key="idx"
                    class="hover:bg-slate-50/70 dark:hover:bg-slate-800/50 transition-colors"
                  >
                    <td class="py-2 px-3 text-slate-400 dark:text-slate-500 font-mono text-[10px]">{{ idx + 1 }}</td>
                    <td class="py-2 px-2 text-slate-700 dark:text-slate-300 whitespace-nowrap text-[11px] font-medium">
                      {{ getProgramMonth(row.program_date) }}
                    </td>
                    <td class="py-2 px-2 text-slate-700 dark:text-slate-300 whitespace-nowrap">
                      <span class="px-1.5 py-0.5 bg-slate-100 dark:bg-slate-800 rounded text-[10px] font-medium text-slate-700 dark:text-slate-300">{{ row.category }}</span>
                    </td>
                    <td class="py-2 px-2 text-slate-700 dark:text-slate-300 max-w-[120px] truncate" :title="row.company_name">
                      {{ row.company_name }}
                    </td>
                    <td class="py-2 px-2 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap text-[10px]">
                      {{ row.po_sj_number }}
                    </td>
                    <td class="py-2 px-3 font-semibold text-slate-900 dark:text-white max-w-[140px] truncate" :title="row.program_name">
                      {{ row.program_name }}
                    </td>
                    <td class="py-2 px-3 text-slate-700 dark:text-slate-300 max-w-[120px] truncate" :title="row.supplier">
                      {{ row.supplier }}
                    </td>
                    <td class="py-2 px-3 font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap text-[10px]">
                      {{ row.invoice_number }}
                    </td>
                    <td class="py-2 px-3 font-mono text-right text-slate-700 dark:text-slate-300 whitespace-nowrap">
                      {{ formatRupiah(row.dpp) }}
                    </td>
                    <td class="py-2 px-3 font-mono text-right text-amber-700 dark:text-amber-400 font-medium whitespace-nowrap">
                      {{ formatRupiah(row.ppn) }}
                    </td>
                    <td class="py-2 px-3 font-mono text-right font-bold text-slate-900 dark:text-white whitespace-nowrap">
                      {{ formatRupiah(row.total_invoice) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div v-if="parsedRows.length > 15" class="p-2 bg-slate-50 dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 text-center text-[10px] text-slate-500 dark:text-slate-400">
              Menampilkan 15 dari {{ parsedRows.length }} baris data
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-3.5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 flex items-center justify-between">
        <span class="text-slate-500 dark:text-slate-400 text-xs">
          {{ parsedRows.length > 0 ? `${parsedRows.length} data siap dimasukkan` : 'Pilih file terlebih dahulu' }}
        </span>
        <div class="flex items-center gap-2.5">
          <button
            type="button"
            class="px-4 py-2 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors cursor-pointer"
            @click="closeModal"
          >
            Batal
          </button>
          <button
            type="button"
            :disabled="parsedRows.length === 0 || isLoading || isImporting"
            class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-2xs transition-all cursor-pointer"
            @click="executeImport"
          >
            <UploadCloud v-if="!isImporting" class="w-4 h-4" />
            <span v-else class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            <span>{{ isImporting ? 'Menyimpan Data...' : `Konfirmasi & Import ${parsedRows.length > 0 ? `${parsedRows.length} Data` : ''}` }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  UploadCloud,
  X,
  Info,
  Download,
  AlertCircle
} from 'lucide-vue-next';
import ExcelIcon from '../ui/ExcelIcon.vue';
import { useTaxStore, formatRupiah, getProgramMonth } from '../../store/taxStore';

const router = useRouter();
const store = useTaxStore();

const isOpen = computed(() => store.isImportModalOpen.value);

const isDragging = ref(false);
const isLoading = ref(false);
const isImporting = ref(false);
const errorMessage = ref('');
const parsedRows = ref([]);
const selectedRawFile = ref(null);
const fileInputRef = ref(null);

function closeModal() {
  resetFile();
  store.closeImportModal();
}

function resetFile() {
  parsedRows.value = [];
  selectedRawFile.value = null;
  errorMessage.value = '';
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
  if (!val) return new Date().toISOString().split('T')[0];

  // If already YYYY-MM-DD
  if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(val.trim())) {
    return val.trim();
  }

  // Handle Excel Serial Number (e.g. 45367)
  if (typeof val === 'number' && val > 30000 && val < 60000) {
    const excelEpoch = new Date(1899, 11, 30);
    const date = new Date(excelEpoch.getTime() + val * 86400000);
    if (!isNaN(date.getTime())) {
      return date.toISOString().split('T')[0];
    }
  }

  const str = String(val).trim();

  // Check for Indonesian month names
  const indoMonths = [
    { name: 'januari', num: '01' },
    { name: 'februari', num: '02' },
    { name: 'maret', num: '03' },
    { name: 'april', num: '04' },
    { name: 'mei', num: '05' },
    { name: 'juni', num: '06' },
    { name: 'juli', num: '07' },
    { name: 'agustus', num: '08' },
    { name: 'september', num: '09' },
    { name: 'oktober', num: '10' },
    { name: 'november', num: '11' },
    { name: 'desember', num: '12' }
  ];

  const lower = str.toLowerCase();
  for (const m of indoMonths) {
    if (lower.includes(m.name)) {
      const yearMatch = str.match(/\b(20\d\d)\b/);
      const year = yearMatch ? yearMatch[1] : new Date().getFullYear().toString();
      const dayMatch = str.match(/\b(\d{1,2})\s+[a-zA-Z]/);
      const day = dayMatch ? String(dayMatch[1]).padStart(2, '0') : '01';
      return `${year}-${m.num}-${day}`;
    }
  }

  // Check DD/MM/YYYY or DD-MM-YYYY
  const dmy = str.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/);
  if (dmy) {
    const day = String(dmy[1]).padStart(2, '0');
    const month = String(dmy[2]).padStart(2, '0');
    const year = dmy[3];
    return `${year}-${month}-${day}`;
  }

  // Check MM/YYYY
  const my = str.match(/^(\d{1,2})[\/\-](\d{4})$/);
  if (my) {
    const month = String(my[1]).padStart(2, '0');
    const year = my[2];
    return `${year}-${month}-01`;
  }

  try {
    const d = new Date(str);
    if (!isNaN(d.getTime())) {
      return d.toISOString().split('T')[0];
    }
  } catch (e) {}

  return new Date().toISOString().split('T')[0];
}

function mapRawRow(raw) {
  let program_name = '';
  let supplier = '';
  let invoice_number = '';
  let company_name = '';
  let po_sj_number = '';
  let dpp = 0;
  let ppn = 0;
  let total_invoice = 0;
  let npwp = '';
  let category = 'Logistik';
  let program_date = new Date().toISOString().split('T')[0];
  let faktur_number = '';
  let faktur_date = null;

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
    // 9. NPWP
    else if (k.includes('npwp')) {
      npwp = String(val || '').trim();
    }
    // 10. KATEGORI
    else if (k.includes('kategori') || k.includes('category')) {
      category = String(val || '').trim();
    }
    // 11. TANGGAL / BULAN
    else if (
      k.includes('tanggal') ||
      k.includes('date') ||
      k.includes('tgl') ||
      k.includes('bulan') ||
      k.includes('month') ||
      k.includes('periode')
    ) {
      program_date = parseImportDate(val);
    }
    // 12. FAKTUR PAJAK / TAX INVOICE
    else if (
      k.includes('fakturpajak') ||
      k.includes('taxinvoice') ||
      k.includes('nofp') ||
      (k.includes('faktur') && k.includes('pajak'))
    ) {
      if (k.includes('tanggal') || k.includes('date') || k.includes('tgl')) {
        faktur_date = parseImportDate(val);
      } else {
        faktur_number = String(val || '').trim();
      }
    }
  }

  // Automatic Fallbacks & Calculations
  if (dpp > 0 && ppn === 0) {
    ppn = Math.round(dpp * 0.11);
  }
  if (total_invoice === 0) {
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
    po_sj_number: po_sj_number || `PO-${Date.now().toString().slice(-4)}`,
    invoice_number,
    dpp,
    ppn,
    total_invoice,
    npwp,
    category,
    program_date,
    faktur_number: faktur_number || null,
    faktur_date: faktur_date || null,
    tax_invoice_number: faktur_number || null,
    tax_invoice_date: faktur_date || null
  };
}

async function processFile(file) {
  errorMessage.value = '';
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
  const lines = text.split(/\r\n|\n/).map(l => l.trim()).filter(Boolean);
  if (lines.length < 2) return [];

  const delimiter = lines[0].includes(';') ? ';' : ',';
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
  link.href = `/${fileName}`;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  store.notify(`Template Dummy (${format.toUpperCase()}) berhasil diunduh.`);
}

async function executeImport() {
  if (parsedRows.value.length === 0 || isImporting.value) return;
  isImporting.value = true;
  errorMessage.value = '';
  try {
    await store.importPrograms(parsedRows.value, selectedRawFile.value);

    // Deteksi tahun dari data yang diimport & set filter dashboard
    const yearCounts = {};
    parsedRows.value.forEach(row => {
      const dateStr = String(row.program_date || row.due_date || row.BULAN || '');
      const yr = dateStr.slice(0, 4);
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
    errorMessage.value = err.message || 'Gagal menyimpan data import ke server.';
  } finally {
    isImporting.value = false;
  }
}
</script>
