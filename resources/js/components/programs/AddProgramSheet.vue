<template>
  <!-- Dialog tambah program: tata letak sama dengan dialog ubah program -->
  <Teleport to="body">
    <div v-if="open" :class="f.dialogOverlay" @click.self="close">
      <form :class="f.dialogPanel" role="dialog" aria-labelledby="add-program-title" @submit.prevent="handleSubmit">
        <div :class="f.dialogHeader">
          <div class="min-w-0">
            <h2 id="add-program-title" class="text-base font-medium text-slate-900 dark:text-slate-100">Tambah program</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">PPN, total, dan PPh dihitung otomatis dari DPP</p>
          </div>
          <button type="button" :class="f.closeButton" aria-label="Tutup" @click="close">
            <X class="w-4 h-4" :stroke-width="1.75" />
          </button>
        </div>

        <div :class="f.dialogBody">
          <!-- Data program -->
          <section>
            <div :class="f.sectionHead">
              <h3 :class="f.sectionTitle">Data program</h3>
              <span class="text-xs text-slate-500 dark:text-slate-400"><span class="text-rose-500">*</span> wajib diisi</span>
            </div>
            <div :class="f.fieldGrid">
              <div class="sm:col-span-2">
                <label for="ap-name" :class="f.fieldLabel">Nama program <span class="text-rose-500">*</span></label>
                <input id="ap-name" v-model="form.program_name" type="text" required placeholder="Program Supply Chain Optimization" :class="f.fieldInput" />
              </div>

              <div>
                <label id="ap-category" :class="f.fieldLabel">Kategori <span class="text-rose-500">*</span></label>
                <SearchSelect v-model="form.category" labelledby="ap-category" search-placeholder="Cari kategori" :options="categoryOptions" />
                <input v-if="form.category === '__custom__'" v-model="customCategory" type="text" required placeholder="Kategori baru" :class="[f.fieldInput, 'mt-2']" />
              </div>
              <div>
                <label id="ap-brand" :class="f.fieldLabel">Brand</label>
                <SearchSelect v-model="form.brand" labelledby="ap-brand" search-placeholder="Cari brand" :options="brandOptions" />
                <input v-if="form.brand === '__custom__'" v-model="customBrand" type="text" placeholder="Brand baru" :class="[f.fieldInput, 'mt-2']" />
              </div>

              <div>
                <label id="ap-supplier" :class="f.fieldLabel">Supplier <span class="text-rose-500">*</span></label>
                <SearchSelect
                  :model-value="form.supplier"
                  labelledby="ap-supplier"
                  placeholder="Pilih supplier"
                  search-placeholder="Cari supplier"
                  :options="supplierOptions"
                  @update:model-value="(v) => { form.supplier = v; onSupplierChange(); }"
                />
                <input v-if="form.supplier === '__custom__'" v-model="customSupplier" type="text" required placeholder="Nama supplier baru" :class="[f.fieldInput, 'mt-2']" />
              </div>
              <div>
                <label for="ap-npwp" :class="f.fieldLabel">NPWP supplier</label>
                <input id="ap-npwp" v-model="form.npwp" type="text" placeholder="01.234.567.8-901.000" :class="[f.fieldInput, 'tabular-nums']" />
                <p :class="f.fieldHint">Terisi dari data master supplier</p>
              </div>

              <div>
                <label id="ap-company" :class="f.fieldLabel">Company</label>
                <SearchSelect v-model="form.company_name" labelledby="ap-company" search-placeholder="Cari company" :options="companyOptions" />
                <input v-if="form.company_name === '__custom__'" v-model="customCompany" type="text" placeholder="Nama company baru" :class="[f.fieldInput, 'mt-2']" />
              </div>
              <div>
                <label for="ap-po" :class="f.fieldLabel">No. PO/SJ</label>
                <input id="ap-po" v-model="form.po_sj_number" type="text" placeholder="PO/2025/1016" :class="[f.fieldInput, 'tabular-nums']" />
              </div>

              <div>
                <label id="ap-warehouse" :class="f.fieldLabel">Kode gudang</label>
                <SearchSelect v-model="form.kode_gudang" labelledby="ap-warehouse" placeholder="Belum dipilih" search-placeholder="Cari kode gudang" :options="warehouseOptions" />
                <input v-if="form.kode_gudang === '__custom__'" v-model="customWarehouse" type="text" placeholder="GDG-JKT-03" :class="[f.fieldInput, 'mt-2 uppercase']" />
              </div>
              <div>
                <label for="ap-memo" :class="f.fieldLabel">No. memo/DO</label>
                <input id="ap-memo" v-model="form.mou_number" type="text" placeholder="DO/SCM/2026/088" :class="[f.fieldInput, 'tabular-nums']" />
              </div>

              <div>
                <label for="ap-start" :class="f.fieldLabel">Periode mulai</label>
                <input id="ap-start" v-model="form.start_date" type="date" :class="f.fieldInput" />
              </div>
              <div>
                <label for="ap-end" :class="f.fieldLabel">Periode selesai</label>
                <input id="ap-end" v-model="form.end_date" type="date" :class="f.fieldInput" />
              </div>
            </div>
          </section>

          <!-- Invoice dan pajak -->
          <section>
            <div :class="f.sectionHead">
              <h3 :class="f.sectionTitle">Invoice dan pajak</h3>
            </div>
            <div :class="f.fieldGrid">
              <div class="sm:col-span-2">
                <label for="ap-invoice" :class="f.fieldLabel">No. invoice <span class="text-rose-500">*</span></label>
                <input id="ap-invoice" v-model="form.invoice_number" type="text" required placeholder="INV/SCM/2025/1042" :class="[f.fieldInput, 'tabular-nums']" />
              </div>

              <div>
                <label for="ap-date" :class="f.fieldLabel">Tanggal invoice <span class="text-rose-500">*</span></label>
                <input id="ap-date" v-model="form.program_date" type="date" required :class="f.fieldInput" />
              </div>
              <div>
                <label for="ap-due" :class="f.fieldLabel">Jatuh tempo</label>
                <input id="ap-due" v-model="form.due_date" type="date" :class="f.fieldInput" />
              </div>

              <div>
                <label for="ap-dpp" :class="f.fieldLabel">DPP <span class="text-rose-500">*</span></label>
                <input id="ap-dpp" v-model.number="form.dpp" type="number" min="0" step="1000" required :class="f.fieldNumber" />
              </div>
              <div>
                <label id="ap-ppn-rate" :class="f.fieldLabel">Tarif PPN</label>
                <SearchSelect v-model="form.ppn_rate" labelledby="ap-ppn-rate" :options="PPN_RATE_OPTIONS" />
                <p :class="f.fieldHint">{{ form.ppn_rate === '0' ? 'Kategori ini tidak dikenai PPN' : 'Menyesuaikan kategori secara otomatis' }}</p>
              </div>

              <div>
                <label id="ap-pph-type" :class="f.fieldLabel">Jenis PPh</label>
                <SearchSelect v-model="form.pph_type" labelledby="ap-pph-type" :options="PPH_TYPE_OPTIONS" />
              </div>
              <div></div>

              <div>
                <label for="ap-faktur" :class="f.fieldLabel">No. faktur pajak</label>
                <input id="ap-faktur" v-model="form.tax_invoice_number" type="text" placeholder="010.000-25.00000001" :class="[f.fieldInput, 'tabular-nums']" />
                <p :class="f.fieldHint">Format XXX.XXX-XX.XXXXXXXX</p>
              </div>
              <div>
                <label for="ap-faktur-date" :class="f.fieldLabel">Tanggal faktur pajak</label>
                <input id="ap-faktur-date" v-model="form.tax_invoice_date" type="date" :class="f.fieldInput" />
              </div>

              <div class="sm:col-span-2">
                <label for="ap-notes" :class="f.fieldLabel">Catatan pemeriksaan pajak</label>
                <textarea
                  id="ap-notes"
                  v-model="form.tax_notes"
                  rows="2"
                  placeholder="Catatan kepatuhan pajak, verifikasi e-Faktur, nomor registrasi DJP"
                  :class="[f.fieldInput, 'h-auto py-2 resize-none']"
                ></textarea>
              </div>

              <label class="sm:col-span-2 flex items-center gap-2 w-fit text-sm text-slate-700 dark:text-slate-300 cursor-pointer">
                <input v-model="form.is_verified" type="checkbox" class="w-4 h-4 rounded accent-blue-600 cursor-pointer" />
                Sudah diverifikasi Tim Pajak
              </label>
            </div>

            <dl :class="f.summaryBox">
              <div :class="f.summaryRow">
                <dt class="text-slate-500 dark:text-slate-400">DPP</dt>
                <dd class="tabular-nums text-slate-800 dark:text-slate-200">{{ formatRupiah(Number(form.dpp) || 0) }}</dd>
              </div>
              <div :class="f.summaryRow">
                <dt class="text-slate-500 dark:text-slate-400">PPN {{ Math.round(Number(form.ppn_rate) * 100) }}%</dt>
                <dd class="tabular-nums text-slate-800 dark:text-slate-200">{{ formatRupiah(calculatedPpn) }}</dd>
              </div>
              <div :class="f.summaryRow">
                <dt class="font-medium text-slate-900 dark:text-slate-100">Total invoice</dt>
                <dd class="tabular-nums font-medium text-slate-900 dark:text-slate-100">{{ formatRupiah(calculatedTotal) }}</dd>
              </div>
              <div v-if="form.pph_type !== 'NON_PPH'" :class="f.summaryRow">
                <dt class="text-slate-500 dark:text-slate-400">PPh dipotong</dt>
                <dd class="tabular-nums text-slate-800 dark:text-slate-200">{{ formatRupiah(calculatedPph) }}</dd>
              </div>
            </dl>
          </section>
        </div>

        <div :class="f.dialogFooter">
          <button type="button" :class="f.buttonSecondary" @click="close">Batal</button>
          <button type="submit" :class="f.buttonPrimary" :disabled="isSaving">
            <span v-if="isSaving" class="w-4 h-4 rounded-full border-2 border-current/30 border-t-current animate-spin"></span>
            {{ isSaving ? 'Menyimpan…' : 'Simpan program' }}
          </button>
        </div>
      </form>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import { onKeyStroke } from '@vueuse/core';
import { X } from 'lucide-vue-next';
import SearchSelect from '../ui/SearchSelect.vue';
import * as f from './formStyles';
import { PPH_TYPE_OPTIONS } from './formStyles';
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

// Opsi dropdown ({ value, label }); "__custom__" membuka isian manual di bawahnya
const withCustom = (list, label) => [...list, { value: '__custom__', label }];
const categoryOptions = computed(() => withCustom(masterCategories.value.map(c => ({ value: c, label: c })), '+ Kategori lain (ketik manual)'));
const brandOptions = computed(() => withCustom(masterBrands.value.map(b => ({ value: b, label: b })), '+ Brand lain (ketik manual)'));
const companyOptions = computed(() => withCustom(masterCompanies.value.map(c => ({ value: c, label: c })), '+ Company lain (ketik manual)'));
const supplierOptions = computed(() => withCustom(
  masterSuppliers.value.map(s => ({ value: s.name, label: s.name, hint: s.npwp || '' })),
  '+ Supplier lain (ketik manual)'
));
const warehouseOptions = computed(() => withCustom(
  [{ value: '', label: 'Belum dipilih' }, ...masterWarehouses.value.map(w => ({ value: w.code, label: w.code, hint: w.name !== w.code ? w.name : '' }))],
  '+ Kode gudang lain (ketik manual)'
));
const PPN_RATE_OPTIONS = [
  { value: '0.11', label: '11% (standar)' },
  { value: '0.12', label: '12%' },
  { value: '0', label: '0% (non-PPN)' },
];

const isSaving = ref(false);

function close() {
  emit('update:open', false);
}

onKeyStroke('Escape', () => {
  if (props.open) close();
});

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

  if (isSaving.value) return;
  isSaving.value = true;
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
  }).finally(() => { isSaving.value = false; });

  // Ditolak server (mis. data sudah terdaftar): biarkan form terbuka agar bisa diperbaiki
  if (!created) return;

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
