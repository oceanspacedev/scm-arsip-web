<template>
  <Dialog
    :open="open"
    title="Tambah Program"
    description="Lengkapi data program & perpajakan. Nilai PPN, total dan PPh dihitung otomatis dari DPP."
    max-width="max-w-2xl"
    @update:open="$emit('update:open', $event)"
  >
    <!-- Tombol Simpan ada di footer dialog; terhubung ke form ini lewat atribut form -->
    <form id="add-program-form" class="space-y-6" @submit.prevent="handleSubmit">
      <section class="space-y-4">
        <h3 class="text-base font-semibold">Data purchase & pengadaan</h3>
        <div class="grid gap-4 md:grid-cols-2">
          <FormField label="Nama program" for="add-program-name" required class="md:col-span-2">
            <Input
              id="add-program-name"
              v-model="form.program_name"
              type="text"
              placeholder="Contoh: Program Supply Chain Optimization"
              required
            />
          </FormField>

          <FormField label="Kategori" for="add-program-category" required>
            <NativeSelect id="add-program-category" v-model="form.category" required>
              <option v-for="cat in masterCategories" :key="cat" :value="cat">
                {{ cat }}
              </option>
              <option value="__custom__">+ Ketik Kategori Lain...</option>
            </NativeSelect>
            <Input
              v-if="form.category === '__custom__'"
              v-model="customCategory"
              type="text"
              placeholder="Ketik kategori baru"
              aria-label="Kategori baru"
              required
            />
          </FormField>

          <FormField label="Brand" for="add-program-brand">
            <NativeSelect id="add-program-brand" v-model="form.brand">
              <option v-for="b in masterBrands" :key="b" :value="b">
                {{ b }}
              </option>
              <option value="__custom__">+ Ketik Brand Lain...</option>
            </NativeSelect>
            <Input
              v-if="form.brand === '__custom__'"
              v-model="customBrand"
              type="text"
              placeholder="Ketik nama brand baru"
              aria-label="Brand baru"
            />
          </FormField>

          <FormField label="Company" for="add-program-company">
            <NativeSelect id="add-program-company" v-model="form.company_name">
              <option v-for="c in masterCompanies" :key="c" :value="c">
                {{ c }}
              </option>
              <option value="__custom__">+ Ketik Company Lain...</option>
            </NativeSelect>
            <Input
              v-if="form.company_name === '__custom__'"
              v-model="customCompany"
              type="text"
              placeholder="Ketik nama company baru"
              aria-label="Company baru"
            />
          </FormField>

          <FormField label="Kode gudang" for="add-program-warehouse">
            <NativeSelect id="add-program-warehouse" v-model="form.kode_gudang">
              <option value="">Pilih kode gudang</option>
              <option v-for="w in masterWarehouses" :key="w.code" :value="w.code">
                {{ w.code }} - {{ w.name }}
              </option>
              <option value="__custom__">+ Ketik Kode Gudang Lain...</option>
            </NativeSelect>
            <Input
              v-if="form.kode_gudang === '__custom__'"
              v-model="customWarehouse"
              type="text"
              placeholder="Contoh: GDG-JKT-03"
              aria-label="Kode gudang baru"
              class="uppercase"
            />
          </FormField>

          <FormField label="Supplier" for="add-program-supplier" required>
            <NativeSelect id="add-program-supplier" v-model="form.supplier" required @change="onSupplierChange">
              <option value="" disabled>Pilih supplier</option>
              <option v-for="s in masterSuppliers" :key="s.name" :value="s.name">
                {{ s.name }}
              </option>
              <option value="__custom__">+ Ketik Supplier Lain...</option>
            </NativeSelect>
            <Input
              v-if="form.supplier === '__custom__'"
              v-model="customSupplier"
              type="text"
              placeholder="Ketik nama supplier baru"
              aria-label="Supplier baru"
              required
            />
          </FormField>

          <FormField label="NPWP supplier" for="add-program-npwp" helper="Format: XX.XXX.XXX.X-XXX.XXX">
            <Input
              id="add-program-npwp"
              v-model="form.npwp"
              type="text"
              placeholder="01.234.567.8-901.000"
              class="tabular-nums"
            />
          </FormField>

          <FormField label="No. PO/SJ" for="add-program-po-sj">
            <Input
              id="add-program-po-sj"
              v-model="form.po_sj_number"
              type="text"
              placeholder="Contoh: PO/2025/1016 / SJ-1016"
              class="tabular-nums"
            />
          </FormField>

          <FormField label="No. memo/DO" for="add-program-mou">
            <Input
              id="add-program-mou"
              v-model="form.mou_number"
              type="text"
              placeholder="Contoh: DO/SCM/2026/088 atau MEMO/088"
              class="tabular-nums"
            />
          </FormField>

          <FormField label="Periode mulai" for="add-program-start">
            <Input id="add-program-start" v-model="form.start_date" type="date" class="tabular-nums" />
          </FormField>

          <FormField label="Periode selesai" for="add-program-end">
            <Input id="add-program-end" v-model="form.end_date" type="date" class="tabular-nums" />
          </FormField>
        </div>
      </section>

      <section class="space-y-4 border-t pt-6">
        <h3 class="text-base font-semibold">Nilai & faktur pajak</h3>
        <div class="grid gap-4 md:grid-cols-2">
          <FormField label="No. invoice" for="add-program-invoice" required class="md:col-span-2">
            <Input
              id="add-program-invoice"
              v-model="form.invoice_number"
              type="text"
              placeholder="Contoh: INV/SCM/2025/1042"
              required
              class="tabular-nums"
            />
          </FormField>

          <FormField label="Tanggal invoice" for="add-program-date" required>
            <Input id="add-program-date" v-model="form.program_date" type="date" required class="tabular-nums" />
          </FormField>

          <FormField label="Jatuh tempo" for="add-program-due">
            <Input id="add-program-due" v-model="form.due_date" type="date" class="tabular-nums" />
          </FormField>

          <FormField label="Nilai DPP (Rp)" for="add-program-dpp" required>
            <Input
              id="add-program-dpp"
              v-model.number="form.dpp"
              type="number"
              min="0"
              step="1000"
              required
              class="tabular-nums"
            />
            <template #helper>
              <span class="tabular-nums">{{ formatRupiah(form.dpp) }}</span>
            </template>
          </FormField>

          <FormField
            label="Tarif PPN"
            for="add-program-ppn-rate"
            required
            :helper="form.ppn_rate === '0' ? 'Kategori Non-PPN (PPN 0%)' : `Dikenakan PPN ${Math.round(Number(form.ppn_rate) * 100)}%`"
          >
            <NativeSelect id="add-program-ppn-rate" v-model="form.ppn_rate">
              <option value="0.11">11% (Standar)</option>
              <option value="0.12">12%</option>
              <option value="0">0% (Non-PPN)</option>
            </NativeSelect>
          </FormField>

          <!-- Nilai hitungan: hanya tampilan, yang disimpan tetap hasil perhitungan di script -->
          <FormField label="Nilai PPN (Rp)" for="add-program-ppn" helper="Dihitung otomatis dari DPP">
            <output id="add-program-ppn" aria-live="off" :class="computedFieldClass">
              {{ formatRupiah(calculatedPpn) }}
            </output>
          </FormField>

          <FormField label="Total invoice (Rp)" for="add-program-total">
            <output id="add-program-total" aria-live="off" :class="[computedFieldClass, 'font-medium']">
              {{ formatRupiah(calculatedTotal) }}
            </output>
          </FormField>

          <FormField label="Jenis PPh" for="add-program-pph-type">
            <NativeSelect id="add-program-pph-type" v-model="form.pph_type">
              <option value="NON_PPH">Non PPh</option>
              <option value="PPH_21">PPh 21 (2,5%)</option>
              <option value="PPH_23">PPh 23 (2%)</option>
              <option value="PPH_4_2">PPh 4 Ayat 2 (10%)</option>
              <option value="PPH_23_BONUS">PPh 23 Atas Bonus (15%)</option>
            </NativeSelect>
          </FormField>

          <FormField label="Nilai PPh (Rp)" for="add-program-pph">
            <output id="add-program-pph" aria-live="off" :class="computedFieldClass">
              {{ formatRupiah(calculatedPph) }}
            </output>
          </FormField>

          <FormField label="No. faktur pajak" for="add-program-faktur" helper="Format: XXX.XXX-XX.XXXXXXXX">
            <Input
              id="add-program-faktur"
              v-model="form.tax_invoice_number"
              type="text"
              placeholder="010.000-25.00000001"
              class="tabular-nums"
            />
          </FormField>

          <FormField label="Tanggal faktur pajak" for="add-program-faktur-date">
            <Input id="add-program-faktur-date" v-model="form.tax_invoice_date" type="date" class="tabular-nums" />
          </FormField>

          <FormField label="Catatan pemeriksaan pajak" for="add-program-notes" class="md:col-span-2">
            <Textarea
              id="add-program-notes"
              v-model="form.tax_notes"
              rows="2"
              placeholder="Catatan kepatuhan pajak, verifikasi e-Faktur, nomor registrasi DJP..."
              class="resize-none"
            />
          </FormField>

          <div class="flex items-center gap-2 md:col-span-2">
            <Checkbox id="add-program-verified" v-model="form.is_verified" />
            <Label for="add-program-verified" class="cursor-pointer">Sudah diverifikasi Tim Pajak</Label>
          </div>
        </div>
      </section>
    </form>

    <template #footer>
      <Button type="button" variant="outline" @click="$emit('update:open', false)">Batal</Button>
      <Button type="submit" form="add-program-form">Simpan</Button>
    </template>
  </Dialog>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue';
import Dialog from '@/components/ui/Dialog.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Textarea } from '@/components/ui/textarea';
import { formatRupiah } from '@/lib/format';
import { useTaxStore, isNonPpnCategory } from '../../store/taxStore';

// Kotak nilai hitungan (PPN, total, PPh): hanya tampilan, tidak bisa diketik
const computedFieldClass = 'flex h-9 w-full min-w-0 items-center rounded-md border bg-muted px-3 text-sm tabular-nums';

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
