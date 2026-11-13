<template>
  <Dialog
    :open="open"
    title="Edit Program"
    :description="`Wewenang: ${store.currentUser.value?.role || 'Pengguna'}`"
    max-width="max-w-2xl"
    @update:open="value => emit('update:open', value)"
  >
    <!-- Tombol Simpan ada di footer dialog; terhubung ke form ini lewat atribut form -->
    <form id="edit-program-form" class="space-y-6" @submit.prevent="saveEdit">
      <section class="space-y-4">
        <div class="flex items-center justify-between gap-4">
          <h3 class="text-base font-semibold">Data purchase & pengadaan</h3>
          <span v-if="!canEditPurchase" class="text-xs text-muted-foreground">Hanya baca</span>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <!-- Tanda wajib (*) hanya tampil bila bagian ini bisa diubah -->
          <FormField label="Nama program" for="edit-program-name" :required="canEditPurchase" class="md:col-span-2">
            <Input
              id="edit-program-name"
              v-model="editForm.program_name"
              type="text"
              required
              :disabled="!canEditPurchase"
            />
          </FormField>

          <FormField label="Kategori" for="edit-program-category">
            <NativeSelect id="edit-program-category" v-model="editForm.category" :disabled="!canEditPurchase">
              <option v-for="cat in editCategoryOptions" :key="cat" :value="cat">
                {{ cat }}
              </option>
              <option value="__custom__">+ Ketik Kategori Lain...</option>
            </NativeSelect>
            <Input
              v-if="editForm.category === '__custom__'"
              v-model="customEditCategory"
              type="text"
              placeholder="Ketik kategori baru..."
              aria-label="Kategori baru"
              required
            />
          </FormField>

          <FormField label="Brand" for="edit-program-brand">
            <NativeSelect id="edit-program-brand" v-model="editForm.brand" :disabled="!canEditPurchase">
              <option v-for="b in editBrandOptions" :key="b" :value="b">
                {{ b }}
              </option>
              <option value="__custom__">+ Ketik Brand Lain...</option>
            </NativeSelect>
            <Input
              v-if="editForm.brand === '__custom__'"
              v-model="customEditBrand"
              type="text"
              placeholder="Ketik nama brand baru..."
              aria-label="Brand baru"
            />
          </FormField>

          <FormField label="Company" for="edit-program-company">
            <NativeSelect id="edit-program-company" v-model="editForm.company_name" :disabled="!canEditPurchase">
              <option v-for="c in editCompanyOptions" :key="c" :value="c">
                {{ c }}
              </option>
              <option value="__custom__">+ Ketik Company Lain...</option>
            </NativeSelect>
            <Input
              v-if="editForm.company_name === '__custom__'"
              v-model="customEditCompany"
              type="text"
              placeholder="Ketik nama company baru..."
              aria-label="Company baru"
            />
          </FormField>

          <FormField label="Kode gudang" for="edit-program-warehouse">
            <NativeSelect id="edit-program-warehouse" v-model="editForm.kode_gudang" :disabled="!canEditPurchase">
              <option value="">Pilih kode gudang</option>
              <option v-for="w in editWarehouseOptions" :key="w.code" :value="w.code">
                {{ w.code }} - {{ w.name }}
              </option>
              <option value="__custom__">+ Ketik Kode Gudang Lain...</option>
            </NativeSelect>
            <Input
              v-if="editForm.kode_gudang === '__custom__'"
              v-model="customEditWarehouse"
              type="text"
              placeholder="Contoh: GDG-JKT-03"
              aria-label="Kode gudang baru"
              class="uppercase"
            />
          </FormField>

          <FormField label="Supplier" for="edit-program-supplier" :required="canEditPurchase">
            <NativeSelect
              id="edit-program-supplier"
              v-model="editForm.supplier"
              required
              :disabled="!canEditPurchase"
              @change="onEditSupplierChange"
            >
              <option v-for="s in editSupplierOptions" :key="s.name" :value="s.name">
                {{ s.name }}
              </option>
              <option value="__custom__">+ Ketik Supplier Lain...</option>
            </NativeSelect>
            <Input
              v-if="editForm.supplier === '__custom__'"
              v-model="customEditSupplier"
              type="text"
              placeholder="Ketik nama supplier baru..."
              aria-label="Supplier baru"
              required
            />
          </FormField>

          <FormField label="NPWP supplier" for="edit-program-npwp">
            <Input
              id="edit-program-npwp"
              v-model="editForm.npwp"
              type="text"
              :disabled="!canEditPurchase"
              class="tabular-nums"
            />
          </FormField>

          <FormField label="No. PO/SJ" for="edit-program-po-sj">
            <Input
              id="edit-program-po-sj"
              v-model="editForm.po_sj_number"
              type="text"
              placeholder="Contoh: PO/2025/1016 / SJ-1016"
              :disabled="!canEditPurchase"
              class="tabular-nums"
            />
          </FormField>
        </div>
      </section>

      <section class="space-y-4 border-t pt-6">
        <div class="flex items-center justify-between gap-4">
          <h3 class="text-base font-semibold">Nilai & faktur pajak</h3>
          <span v-if="!canEditFinance" class="text-xs text-muted-foreground">Hanya baca</span>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
          <FormField label="No. invoice" for="edit-program-invoice">
            <Input
              id="edit-program-invoice"
              v-model="editForm.invoice_number"
              type="text"
              :disabled="!canEditFinance"
              class="tabular-nums"
            />
          </FormField>

          <!-- Nilai yang tampil dinormalkan, pilihan baru langsung disimpan apa adanya (sama seperti :value + @change) -->
          <FormField label="Status payment" for="edit-program-payment">
            <NativeSelect
              id="edit-program-payment"
              :model-value="normalizePayStatus(editForm.payment_status)"
              :disabled="!canEditFinance && !isAdmin"
              @update:model-value="value => (editForm.payment_status = value)"
            >
              <option v-for="opt in editPaymentStatusOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </NativeSelect>
          </FormField>

          <!-- PPN & PPh dihitung ulang setelah nilai DPP baru masuk ke form (Input mengirim nilainya lewat update:modelValue) -->
          <FormField label="Nilai DPP (Rp)" for="edit-program-dpp" :required="canEditFinance" class="md:col-span-2">
            <Input
              id="edit-program-dpp"
              v-model.number="editForm.dpp"
              type="number"
              required
              :disabled="!canEditFinance"
              class="tabular-nums"
              @update:model-value="calculateTaxes"
            />
            <template #helper>
              <span class="tabular-nums">{{ formatRupiah(editForm.dpp) }}</span>
            </template>
          </FormField>

          <FormField label="Nilai PPN (Rp)" for="edit-program-ppn" helper="Dihitung otomatis 11% dari DPP">
            <Input
              id="edit-program-ppn"
              v-model.number="editForm.ppn"
              type="number"
              :disabled="!canEditFinance"
              class="tabular-nums"
            />
          </FormField>

          <FormField label="Total invoice (Rp)" for="edit-program-total">
            <output
              id="edit-program-total"
              aria-live="off"
              class="flex h-9 w-full min-w-0 items-center rounded-md border bg-muted px-3 text-sm font-medium tabular-nums"
            >
              {{ formatRupiah(editForm.dpp + editForm.ppn) }}
            </output>
          </FormField>

          <FormField label="Jenis PPh" for="edit-program-pph-type">
            <NativeSelect
              id="edit-program-pph-type"
              v-model="editForm.pph_type"
              :disabled="!canEditFinance"
              @change="onPphTypeChange"
            >
              <option value="NON_PPH">Non PPh</option>
              <option value="PPH_21">PPh 21 (2,5%)</option>
              <option value="PPH_23">PPh 23 (2%)</option>
              <option value="PPH_4_2">PPh 4 Ayat 2 (10%)</option>
              <option value="PPH_23_BONUS">PPh 23 Atas Bonus (15%)</option>
            </NativeSelect>
          </FormField>

          <FormField label="Nilai PPh (Rp)" for="edit-program-pph">
            <Input
              id="edit-program-pph"
              v-model.number="editForm.pph"
              type="number"
              :disabled="!canEditFinance"
              class="tabular-nums"
            />
          </FormField>

          <FormField label="No. faktur pajak" for="edit-program-faktur">
            <Input
              id="edit-program-faktur"
              v-model="editForm.faktur_number"
              type="text"
              placeholder="010.000-25.00000001"
              :disabled="!canEditFinance"
              class="tabular-nums"
            />
          </FormField>

          <FormField label="Tanggal faktur pajak" for="edit-program-faktur-date">
            <Input
              id="edit-program-faktur-date"
              v-model="editForm.faktur_date"
              type="date"
              :disabled="!canEditFinance"
              class="tabular-nums"
            />
          </FormField>
        </div>
      </section>
    </form>

    <template #footer>
      <Button type="button" variant="outline" :disabled="isSavingEdit" @click="close">Batal</Button>
      <Button type="submit" form="edit-program-form" :disabled="isSavingEdit">
        {{ isSavingEdit ? 'Menyimpan…' : 'Simpan Perubahan' }}
      </Button>
    </template>
  </Dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue';
import Dialog from '@/components/ui/Dialog.vue';
import { Button } from '@/components/ui/button';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { formatRupiah } from '@/lib/format';
import {
  useTaxStore,
  getProgramCompanyName,
  getProgramBrand,
  getProgramPoSjNumber,
  calculatePphAmount,
} from '../../store/taxStore';
import { formatPaymentStatus, normalizePayStatus } from './programTable';

// Form edit program (dipindahkan apa adanya dari tabel Arsip Program lama)
const props = defineProps({
  open: { type: Boolean, default: false },
  program: { type: Object, default: null },
});
const emit = defineEmits(['update:open']);

const store = useTaxStore();
const isAdmin = computed(() => store.isAdmin.value);
const canEditPurchase = computed(() => store.canEditPurchase.value);
const canEditFinance = computed(() => store.canEditFinance.value);

function close() {
  emit('update:open', false);
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

  // Deduplicate by value
  const seen = new Set();
  return list.filter(item => {
    if (seen.has(item.value)) return false;
    seen.add(item.value);
    return true;
  });
});

const isSavingEdit = ref(false);
const editProgramId = ref(null);
const editForm = reactive({
  program_name: '',
  supplier: '',
  company_name: '',
  kode_gudang: '',
  po_sj_number: '',
  npwp: '',
  invoice_number: '',
  faktur_number: '',
  faktur_date: '',
  category: 'Logistik',
  brand: 'SCM',
  payment_status: 'WAITING PAYMENT',
  dpp: 0,
  ppn: 0,
  pph_type: 'NON_PPH',
  pph: 0
});

const masterCategories = computed(() => store.masterCategories.value);
const masterBrands = computed(() => store.masterBrands.value);
const masterCompanies = computed(() => store.masterCompanies.value);
const masterWarehouses = computed(() => store.masterWarehouses.value);
const masterSuppliers = computed(() => store.masterSuppliers.value);

const customEditCategory = ref('');
const customEditBrand = ref('');
const customEditCompany = ref('');
const customEditWarehouse = ref('');
const customEditSupplier = ref('');

const editCategoryOptions = computed(() => {
  const set = new Set(masterCategories.value);
  if (editForm.category && editForm.category !== '__custom__') {
    set.add(editForm.category);
  }
  return Array.from(set);
});

const editBrandOptions = computed(() => {
  const set = new Set(masterBrands.value);
  if (editForm.brand && editForm.brand !== '__custom__') {
    set.add(editForm.brand);
  }
  return Array.from(set);
});

const editCompanyOptions = computed(() => {
  const set = new Set(masterCompanies.value);
  if (editForm.company_name && editForm.company_name !== '__custom__') {
    set.add(editForm.company_name);
  }
  return Array.from(set);
});

const editWarehouseOptions = computed(() => {
  const list = [...masterWarehouses.value];
  if (editForm.kode_gudang && editForm.kode_gudang !== '__custom__') {
    if (!list.some(w => w.code === editForm.kode_gudang)) {
      list.unshift({ code: editForm.kode_gudang, name: editForm.kode_gudang });
    }
  }
  return list;
});

const editSupplierOptions = computed(() => {
  const list = [...masterSuppliers.value];
  if (editForm.supplier && editForm.supplier !== '__custom__') {
    if (!list.some(s => s.name === editForm.supplier)) {
      list.unshift({ name: editForm.supplier, npwp: editForm.npwp || '' });
    }
  }
  return list;
});

function onEditSupplierChange() {
  if (editForm.supplier === '__custom__') return;
  const match = masterSuppliers.value.find(s => s.name === editForm.supplier);
  if (match && match.npwp) {
    editForm.npwp = match.npwp;
  }
}

function fillForm(program) {
  // Modal langsung dibuka; data master diperbarui di belakang dan dropdown ikut terisi saat datanya tiba
  store.fetchMasterData();
  editProgramId.value = program.id;
  editForm.program_name = program.program_name || '';
  editForm.supplier = program.supplier || '';
  editForm.company_name = program.company_name || getProgramCompanyName(program);
  editForm.kode_gudang = program.kode_gudang || '';
  editForm.po_sj_number = program.po_sj_number || getProgramPoSjNumber(program);
  editForm.npwp = program.npwp || '';
  editForm.invoice_number = program.invoice_number || '';
  editForm.faktur_number = program.faktur_number || '';
  editForm.faktur_date = program.faktur_date ? String(program.faktur_date).slice(0, 10) : '';
  editForm.payment_status = program.payment_status || 'WAITING PAYMENT';
  editForm.category = program.category || masterCategories.value[0] || 'Promosi';
  editForm.brand = program.brand || getProgramBrand(program) || masterBrands.value[0] || '';
  editForm.dpp = Number(program.dpp) || 0;
  editForm.ppn = Number(program.ppn) || Math.round((Number(program.dpp) || 0) * 0.11);
  editForm.pph_type = program.pph_type || 'NON_PPH';
  editForm.pph = Number(program.pph) || calculatePphAmount(editForm.dpp, editForm.pph_type);

  customEditCategory.value = '';
  customEditBrand.value = '';
  customEditCompany.value = '';
  customEditWarehouse.value = '';
  customEditSupplier.value = '';
}

watch(
  () => [props.open, props.program],
  ([open, program]) => {
    if (open && program) fillForm(program);
  },
  { immediate: true }
);

function calculateTaxes() {
  editForm.ppn = Math.round((Number(editForm.dpp) || 0) * 0.11);
  if (editForm.pph_type && editForm.pph_type !== 'NON_PPH') {
    editForm.pph = calculatePphAmount(editForm.dpp, editForm.pph_type);
  } else {
    editForm.pph = 0;
  }
}

function onPphTypeChange() {
  editForm.pph = calculatePphAmount(editForm.dpp, editForm.pph_type);
}

async function saveEdit() {
  if (!editProgramId.value || isSavingEdit.value) return;

  const finalCategory = editForm.category === '__custom__' ? customEditCategory.value.trim() : editForm.category;
  const finalBrand = editForm.brand === '__custom__' ? customEditBrand.value.trim() : editForm.brand;
  const finalCompany = editForm.company_name === '__custom__' ? customEditCompany.value.trim() : editForm.company_name;
  const finalWarehouse = editForm.kode_gudang === '__custom__' ? customEditWarehouse.value.trim() : editForm.kode_gudang;
  const finalSupplier = editForm.supplier === '__custom__' ? customEditSupplier.value.trim() : editForm.supplier;

  isSavingEdit.value = true;
  try {
    if (editForm.brand === '__custom__' && finalBrand && !masterBrands.value.includes(finalBrand)) {
      store.addMasterItem('brands', finalBrand);
    }
    if (editForm.category === '__custom__' && finalCategory && !masterCategories.value.includes(finalCategory)) {
      store.addMasterItem('categories', finalCategory);
    }
    if (editForm.company_name === '__custom__' && finalCompany && !masterCompanies.value.includes(finalCompany)) {
      store.addMasterItem('companies', finalCompany);
    }
    if (editForm.supplier === '__custom__' && finalSupplier && !masterSuppliers.value.some(s => s.name === finalSupplier)) {
      store.addMasterItem('suppliers', { name: finalSupplier, npwp: editForm.npwp || '' });
    }

    const res = await store.updateProgram(editProgramId.value, {
      program_name: editForm.program_name,
      supplier: finalSupplier,
      company_name: finalCompany,
      kode_gudang: finalWarehouse,
      po_sj_number: editForm.po_sj_number,
      invoice_number: editForm.invoice_number || '',
      invoice_no: editForm.invoice_number || '',
      faktur_number: editForm.faktur_number || '',
      tax_invoice_number: editForm.faktur_number || '',
      faktur_date: editForm.faktur_date || null,
      tax_invoice_date: editForm.faktur_date || null,
      category: finalCategory,
      brand: finalBrand,
      payment_status: editForm.payment_status,
      dpp: editForm.dpp,
      ppn: editForm.ppn,
      total_invoice: editForm.dpp + editForm.ppn,
      pph_type: editForm.pph_type,
      pph: editForm.pph,
      pph_amount: editForm.pph
    });
    if (res?.success) {
      close();
    }
  } catch (err) {
    console.error('Error saving program from table:', err);
  } finally {
    isSavingEdit.value = false;
  }
}
</script>