<template>
  <Dialog
    :open="open"
    title="Tambah Program SCM"
    description="Lengkapi data program & perpajakan. Nilai PPN, total dan PPh dihitung otomatis dari DPP."
    maxWidth="max-w-2xl"
    @update:open="$emit('update:open', $event)"
  >
    <form @submit.prevent="handleSubmit" class="space-y-4 text-xs select-none">
      <!-- SECTION 1: DATA PROGRAM -->
      <div>
        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 tracking-wider uppercase mb-3 font-sans flex items-center gap-1.5">
          <span>DATA PROGRAM</span>
        </h4>

        <!-- Row 1: Nama Program, Kategori Program & Brand -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NAMA PROGRAM <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.program_name"
              type="text"
              placeholder="Contoh: Program Supply Chain Optimization"
              required
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>

          <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              KATEGORI PROGRAM <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.category"
              required
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors cursor-pointer"
            >
              <option v-for="cat in masterCategories" :key="cat" :value="cat">
                {{ cat }}
              </option>
              <option value="__custom__">+ Ketik Kategori Lain...</option>
            </select>
            <input
              v-if="form.category === '__custom__'"
              v-model="customCategory"
              type="text"
              placeholder="Ketik kategori baru"
              required
              class="mt-1.5 w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>

          <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              BRAND
            </label>
            <select
              v-model="form.brand"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors cursor-pointer"
            >
              <option v-for="b in masterBrands" :key="b" :value="b">
                {{ b }}
              </option>
              <option value="__custom__">+ Ketik Brand Lain...</option>
            </select>
            <input
              v-if="form.brand === '__custom__'"
              v-model="customBrand"
              type="text"
              placeholder="Ketik nama brand baru"
              class="mt-1.5 w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>
        </div>

        <!-- Row 1.5: Company Name, Kode Gudang & No. PO / SJ -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
          <div class="md:col-span-4">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              COMPANY NAME
            </label>
            <select
              v-model="form.company_name"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors cursor-pointer"
            >
              <option v-for="c in masterCompanies" :key="c" :value="c">
                {{ c }}
              </option>
              <option value="__custom__">+ Ketik Company Lain...</option>
            </select>
            <input
              v-if="form.company_name === '__custom__'"
              v-model="customCompany"
              type="text"
              placeholder="Ketik nama company baru"
              class="mt-1.5 w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>

          <div class="md:col-span-4">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              KODE GUDANG
            </label>
            <select
              v-model="form.kode_gudang"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors cursor-pointer font-mono"
            >
              <option value="">-- Pilih Kode Gudang --</option>
              <option v-for="w in masterWarehouses" :key="w.code" :value="w.code">
                {{ w.code }} - {{ w.name }}
              </option>
              <option value="__custom__">+ Ketik Kode Gudang Lain...</option>
            </select>
            <input
              v-if="form.kode_gudang === '__custom__'"
              v-model="customWarehouse"
              type="text"
              placeholder="Contoh: GDG-JKT-03"
              class="mt-1.5 w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors uppercase font-mono"
            />
          </div>

          <div class="md:col-span-4">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NO. PO / SJ
            </label>
            <input
              v-model="form.po_sj_number"
              type="text"
              placeholder="Contoh: PO/2025/1016 / SJ-1016"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors font-mono"
            />
          </div>
        </div>

        <!-- Row 2: Nama Supplier & NPWP Supplier -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NAMA SUPPLIER <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.supplier"
              required
              @change="onSupplierChange"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors cursor-pointer"
            >
              <option value="" disabled>-- Pilih Supplier --</option>
              <option v-for="s in masterSuppliers" :key="s.name" :value="s.name">
                {{ s.name }}
              </option>
              <option value="__custom__">+ Ketik Supplier Lain...</option>
            </select>
            <input
              v-if="form.supplier === '__custom__'"
              v-model="customSupplier"
              type="text"
              placeholder="Ketik nama supplier baru"
              required
              class="mt-1.5 w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>

          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NPWP SUPPLIER
            </label>
            <input
              v-model="form.npwp"
              type="text"
              placeholder="01.234.567.8-901.000"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors font-mono"
            />
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Format: XX.XXX.XXX.X-XXX.XXX</p>
          </div>
        </div>

        <!-- Row 3: Nomor Memo / DO & Periode -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NOMOR MEMO / DO
            </label>
            <input
              v-model="form.mou_number"
              type="text"
              placeholder="Contoh: DO/SCM/2026/088 atau MEMO/088"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors font-mono"
            />
          </div>

          <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              PERIODE MULAI
            </label>
            <input
              v-model="form.start_date"
              type="date"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>

          <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              PERIODE SELESAI
            </label>
            <input
              v-model="form.end_date"
              type="date"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>
        </div>
      </div>

      <!-- SECTION 2: INVOICE & PERPAJAKAN -->
      <div class="pt-3 border-t border-slate-100 dark:border-slate-800">
        <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 tracking-wider uppercase mb-3 font-sans flex items-center gap-1.5">
          <span>INVOICE & PERPAJAKAN</span>
        </h4>

        <!-- Row 4: No. Invoice, Tanggal Invoice, Jatuh Tempo -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NO. INVOICE <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.invoice_number"
              type="text"
              placeholder="Contoh: INV/SCM/2025/1042"
              required
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors font-mono"
            />
          </div>

          <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              TANGGAL INVOICE <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.program_date"
              type="date"
              required
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>

          <div class="md:col-span-3">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              JATUH TEMPO
            </label>
            <input
              v-model="form.due_date"
              type="date"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>
        </div>

        <!-- Row 5: Nilai DPP (Rp) & Tarif PPN -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
          <div class="md:col-span-7">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NILAI DPP (RP) <span class="text-red-500">*</span>
            </label>
            <input
              v-model.number="form.dpp"
              type="number"
              min="0"
              step="1000"
              required
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors font-mono"
            />
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-mono">
              {{ formatRupiah(form.dpp || 0) }}
            </p>
          </div>

          <div class="md:col-span-5">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              TARIF PPN <span class="text-red-500">*</span>
            </label>
            <select
              v-model="form.ppn_rate"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors cursor-pointer"
            >
              <option value="0.11">11% (Standar)</option>
              <option value="0.12">12%</option>
              <option value="0">0% (Non-PPN)</option>
            </select>
            <p v-if="form.ppn_rate === '0'" class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-1 font-medium">
              Kategori Non-PPN (PPN 0%)
            </p>
            <p v-else class="text-[10px] text-blue-600 dark:text-blue-400 mt-1 font-medium">
              Dikenakan PPN 11%
            </p>
          </div>
        </div>

        <!-- Row 6: Nilai PPN (Rp) & Total Invoice (Rp) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NILAI PPN (RP)
            </label>
            <input
              :value="calculatedPpn"
              type="number"
              readonly
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-xs text-slate-700 dark:text-slate-300 font-mono cursor-not-allowed"
            />
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">
              Dihitung otomatis dari DPP
            </p>
          </div>

          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              TOTAL INVOICE (RP)
            </label>
            <input
              :value="calculatedTotal"
              type="number"
              readonly
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-xs font-bold text-slate-900 dark:text-white font-mono cursor-not-allowed"
            />
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-mono">
              {{ formatRupiah(calculatedTotal) }}
            </p>
          </div>
        </div>

        <!-- Row 7: Jenis PPh & Nilai PPh (Rp) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              JENIS PPH
            </label>
            <select
              v-model="form.pph_type"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors cursor-pointer"
            >
              <option value="NON_PPH">Non PPh</option>
              <option value="PPH_21">PPh 21 (2,5%)</option>
              <option value="PPH_23">PPh 23 (2%)</option>
              <option value="PPH_4_2">PPh 4 Ayat 2 (10%)</option>
              <option value="PPH_23_BONUS">PPh 23 Atas Bonus (15%)</option>
            </select>
          </div>

          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NILAI PPH (RP)
            </label>
            <input
              :value="calculatedPph"
              type="number"
              readonly
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900/60 text-xs text-slate-700 dark:text-slate-300 font-mono cursor-not-allowed"
            />
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1 font-mono">
              {{ formatRupiah(calculatedPph) }}
            </p>
          </div>
        </div>

        <!-- Row 8: No. Faktur Pajak & Tanggal Faktur Pajak -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 mb-3">
          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              NO. FAKTUR PAJAK
            </label>
            <input
              v-model="form.tax_invoice_number"
              type="text"
              placeholder="010.000-25.00000001"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors font-mono"
            />
            <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-1">Format: XXX.XXX-XX.XXXXXXXX</p>
          </div>

          <div class="md:col-span-6">
            <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
              TANGGAL FAKTUR PAJAK
            </label>
            <input
              v-model="form.tax_invoice_date"
              type="date"
              class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
          </div>
        </div>

        <!-- Row 9: Catatan Pemeriksaan Pajak -->
        <div class="mb-3">
          <label class="block text-[10px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1 font-sans">
            CATATAN PEMERIKSAAN PAJAK
          </label>
          <textarea
            v-model="form.tax_notes"
            rows="2"
            placeholder="Catatan kepatuhan pajak, verifikasi e-Faktur, nomor registrasi DJP..."
            class="w-full px-3 py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors resize-none"
          ></textarea>
        </div>

        <!-- Row 10: Sudah diverifikasi Tim Pajak -->
        <div class="flex items-center gap-2 pt-1">
          <input
            id="is_verified"
            v-model="form.is_verified"
            type="checkbox"
            class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-blue-600 focus:ring-blue-600 cursor-pointer"
          />
          <label for="is_verified" class="text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
            Sudah diverifikasi Tim Pajak
          </label>
        </div>
      </div>

      <!-- Action Buttons Footer -->
      <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5">
        <button
          type="button"
          class="px-4 py-2 text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700 shadow-2xs transition-colors cursor-pointer"
          @click="$emit('update:open', false)"
        >
          Batal
        </button>
        <button
          type="submit"
          class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-2xs transition-colors cursor-pointer"
        >
          Simpan
        </button>
      </div>
    </form>
  </Dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import Dialog from '../ui/Dialog.vue';
import { useTaxStore, formatRupiah, isNonPpnCategory } from '../../store/taxStore';

const props = defineProps({
  open: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['update:open', 'created']);

const store = useTaxStore();

const masterCategories = computed(() => store.masterCategories.value);
const masterBrands = computed(() => store.masterBrands.value);
const masterCompanies = computed(() => store.masterCompanies.value);
const masterWarehouses = computed(() => store.masterWarehouses.value);
const masterSuppliers = computed(() => store.masterSuppliers.value);

const customCategory = ref('');
const customBrand = ref('');
const customCompany = ref('');
const customWarehouse = ref('');
const customSupplier = ref('');

function onSupplierChange() {
  if (form.supplier === '__custom__') {
    return;
  }
  const match = masterSuppliers.value.find(
    s => (s.name || '').toLowerCase() === (form.supplier || '').toLowerCase().trim()
  );
  if (match && match.npwp) {
    form.npwp = match.npwp;
  }
}

async function syncDefaults() {
  await store.fetchMasterData();

  if (masterCategories.value.length > 0 && (!form.category || form.category === '__custom__' || !masterCategories.value.includes(form.category))) {
    form.category = masterCategories.value[0];
  }
  if (masterBrands.value.length > 0 && (!form.brand || form.brand === '__custom__' || !masterBrands.value.includes(form.brand))) {
    form.brand = masterBrands.value[0];
  }
  if (masterCompanies.value.length > 0 && (!form.company_name || form.company_name === '__custom__' || !masterCompanies.value.includes(form.company_name))) {
    form.company_name = masterCompanies.value[0];
  }
}

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    syncDefaults();
  }
});

onMounted(() => {
  syncDefaults();
});

const form = reactive({
  program_name: '',
  category: 'Promosi',
  brand: '',
  company_name: 'PT SCM Nusantara',
  kode_gudang: '',
  po_sj_number: '',
  supplier: '',
  npwp: '',
  mou_number: '',
  start_date: '',
  end_date: '',
  invoice_number: '',
  program_date: new Date().toISOString().split('T')[0],
  due_date: '',
  dpp: 0,
  ppn_rate: '0.11',
  pph_type: 'NON_PPH',
  tax_invoice_number: '',
  tax_invoice_date: '',
  tax_notes: '',
  is_verified: false,
});

// Auto-switch PPN rate based on Category & Program Name:
// Price Protection, Bonus, Rebate -> 0% (Non-PPN)
// Marketing Service Fee, Branding, Purchase Order -> 11%
watch(() => [form.category, customCategory.value, form.program_name], ([cat, customCat, progName]) => {
  const effectiveCat = cat === '__custom__' ? customCat : cat;
  if (isNonPpnCategory(effectiveCat, progName)) {
    form.ppn_rate = '0';
  } else if (
    effectiveCat === 'Marketing Service Fee' ||
    effectiveCat === 'Branding' ||
    effectiveCat === 'Purchase Order' ||
    (form.ppn_rate === '0' && !isNonPpnCategory(effectiveCat, progName))
  ) {
    form.ppn_rate = '0.11';
  }
});

// Automatic calculation of PPN from DPP
const calculatedPpn = computed(() => {
  const dpp = Number(form.dpp) || 0;
  const rate = Number(form.ppn_rate) || 0;
  return Math.round(dpp * rate);
});

// Automatic calculation of Total from DPP + PPN
const calculatedTotal = computed(() => {
  const dpp = Number(form.dpp) || 0;
  return dpp + calculatedPpn.value;
});

// Automatic calculation of PPh from DPP
const calculatedPph = computed(() => {
  const dpp = Number(form.dpp) || 0;
  switch (form.pph_type) {
    case 'PPH_21':
      return Math.round(dpp * 0.025);
    case 'PPH_23':
      return Math.round(dpp * 0.02);
    case 'PPH_4_2':
      return Math.round(dpp * 0.10);
    case 'PPH_23_BONUS':
      return Math.round(dpp * 0.15);
    default:
      return 0;
  }
});

async function handleSubmit() {
  const finalCategory = form.category === '__custom__' ? customCategory.value.trim() : form.category;
  const finalBrand = form.brand === '__custom__' ? customBrand.value.trim() : form.brand;
  const finalCompany = form.company_name === '__custom__' ? customCompany.value.trim() : form.company_name;
  const finalWarehouse = form.kode_gudang === '__custom__' ? customWarehouse.value.trim() : form.kode_gudang;
  const finalSupplier = form.supplier === '__custom__' ? customSupplier.value.trim() : form.supplier;

  if (!form.program_name || !finalSupplier || !form.invoice_number) {
    store.notify('Mohon lengkapi data yang bertanda bintang (*)', 'warning');
    return;
  }

  // Auto-save newly typed custom items to Master Data in background
  if (form.brand === '__custom__' && finalBrand && !masterBrands.value.includes(finalBrand)) {
    store.addMasterItem('brands', finalBrand);
  }
  if (form.category === '__custom__' && finalCategory && !masterCategories.value.includes(finalCategory)) {
    store.addMasterItem('categories', finalCategory);
  }
  if (form.company_name === '__custom__' && finalCompany && !masterCompanies.value.includes(finalCompany)) {
    store.addMasterItem('companies', finalCompany);
  }
  if (form.supplier === '__custom__' && finalSupplier && !masterSuppliers.value.some(s => s.name === finalSupplier)) {
    store.addMasterItem('suppliers', { name: finalSupplier, npwp: form.npwp || '' });
  }

  const created = await store.addProgram({
    program_name: form.program_name,
    category: finalCategory || 'Promosi',
    brand: finalBrand || masterBrands.value[0] || 'SCM',
    company_name: finalCompany || 'PT SCM Nusantara',
    kode_gudang: finalWarehouse,
    po_sj_number: form.po_sj_number,
    supplier: finalSupplier,
    npwp: form.npwp,
    mou_number: form.mou_number,
    start_date: form.start_date,
    end_date: form.end_date,
    invoice_number: form.invoice_number,
    program_date: form.program_date,
    due_date: form.due_date,
    dpp: Number(form.dpp) || 0,
    ppn: calculatedPpn.value,
    total_invoice: calculatedTotal.value,
    pph_type: form.pph_type,
    pph: calculatedPph.value,
    tax_invoice_number: form.tax_invoice_number,
    tax_invoice_date: form.tax_invoice_date,
    tax_notes: form.tax_notes,
    is_verified: form.is_verified,
    documents: []
  });

  // Reset form to defaults
  form.program_name = '';
  form.category = masterCategories.value[0] || 'Promosi';
  form.brand = masterBrands.value[0] || '';
  form.company_name = masterCompanies.value[0] || 'PT SCM Nusantara';
  form.kode_gudang = '';
  form.po_sj_number = '';
  form.supplier = '';
  form.npwp = '';
  form.mou_number = '';
  form.start_date = '';
  form.end_date = '';
  form.invoice_number = '';
  form.program_date = new Date().toISOString().split('T')[0];
  form.due_date = '';
  form.dpp = 0;
  form.ppn_rate = '0.11';
  form.pph_type = 'NON_PPH';
  form.tax_invoice_number = '';
  form.tax_invoice_date = '';
  form.tax_notes = '';
  form.is_verified = false;

  customCategory.value = '';
  customBrand.value = '';
  customCompany.value = '';
  customWarehouse.value = '';
  customSupplier.value = '';

  emit('update:open', false);
  emit('created', created);
}
</script>
