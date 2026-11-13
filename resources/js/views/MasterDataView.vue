<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Data Master" description="Kelola referensi untuk form program dan aturan PPN per kategori.">
      <template #actions>
        <Button variant="outline" @click="openResetModal">Reset Default</Button>
      </template>
    </PageHeader>

    <Tabs v-model="activeTab" class="flex flex-col gap-4">
      <!-- Layar sempit: ketujuh tab tidak muat, jadi jenis data dipilih lewat select -->
      <NativeSelect v-model="activeTab" class="sm:max-w-xs lg:hidden" aria-label="Jenis data master">
        <option v-for="tab in tabs" :key="tab.id" :value="tab.id">{{ tab.label }} ({{ getTabCount(tab.id) }})</option>
      </NativeSelect>
      <TabsList class="hidden lg:inline-flex" aria-label="Jenis data master">
        <TabsTrigger v-for="tab in tabs" :key="tab.id" :value="tab.id">
          {{ tab.label }}
          <span class="text-xs tabular-nums text-muted-foreground">{{ getTabCount(tab.id) }}</span>
        </TabsTrigger>
      </TabsList>

      <!-- Kategori, Brand, Company, Kode Gudang, Supplier, Status Payment -->
      <TabsContent v-for="tab in editableTabs" :key="tab.id" :value="tab.id" class="outline-none">
        <DataTable
          :columns="tableColumns[tab.id]"
          :data="masterLists[tab.id]"
          :global-filter-fn="tableSearch[tab.id]"
          :search-placeholder="tab.searchPlaceholder"
          :row-label="tab.rowLabel"
          :empty-title="masterLists[tab.id].length ? `Tidak ada ${tab.rowLabel} yang cocok` : `Belum ada data ${tab.rowLabel}`"
          :empty-description="masterLists[tab.id].length ? 'Periksa kembali kata kunci pencarian Anda.' : `Tambahkan lewat tombol Tambah ${tab.noun}.`"
        >
          <template #toolbar-actions>
            <Button size="sm" @click="openAddModal">Tambah {{ tab.noun }}</Button>
          </template>

          <template #cell-location="{ value }">
            <span :class="{ 'text-muted-foreground': !value }">{{ value || '—' }}</span>
          </template>

          <template #cell-npwp="{ value }">
            <span class="tabular-nums" :class="{ 'text-muted-foreground': !value }">{{ value || '—' }}</span>
          </template>

          <template #cell-phone="{ value }">
            <span class="tabular-nums" :class="{ 'text-muted-foreground': !value }">{{ value || '—' }}</span>
          </template>

          <template #cell-actions="{ row }">
            <div class="flex justify-end">
              <DropdownMenu :modal="false">
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon-sm" :aria-label="`Aksi untuk ${rowName(tab.id, row)}`">
                    <MoreHorizontal />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-40" @close-auto-focus="keepDialogFocus">
                  <DropdownMenuItem @select="openEditModal(tab.id, row)">Edit</DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem variant="destructive" @select="promptDelete(tab.id, row)">Hapus</DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </template>
        </DataTable>
      </TabsContent>

      <!-- Aturan PPN: tarif 0% / 11% dipilih langsung per kategori -->
      <TabsContent value="ppn_rules" class="outline-none">
        <div class="flex flex-col gap-4">
          <p class="text-sm text-muted-foreground">Pilih tarif PPN tiap kategori: 0% (Non-PPN) atau 11%.</p>
          <DataTable
            :columns="PPN_COLUMNS"
            :data="ppnRows"
            :global-filter-fn="ppnSearch"
            search-placeholder="Cari kategori…"
            row-label="kategori"
            :empty-title="ppnRows.length ? 'Tidak ada kategori yang cocok' : 'Belum ada kategori'"
            :empty-description="ppnRows.length ? 'Periksa kembali kata kunci pencarian Anda.' : 'Tambahkan kategori di tab Kategori Program.'"
          >
            <template #cell-rate="{ row }">
              <div
                role="group"
                :aria-label="`Tarif PPN ${row.name}`"
                class="inline-flex rounded-md border p-0.5 transition-opacity"
                :class="{ 'opacity-50': togglingCategory === row.name }"
              >
                <button
                  v-for="option in ppnOptions"
                  :key="option.label"
                  type="button"
                  :aria-pressed="isCategoryNonPpn(row.name) === option.nonPpn"
                  :disabled="togglingCategory === row.name"
                  class="h-6 min-w-10 cursor-pointer rounded-sm px-2 text-xs tabular-nums transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-wait"
                  :class="isCategoryNonPpn(row.name) === option.nonPpn
                    ? 'bg-accent font-medium text-accent-foreground'
                    : 'text-muted-foreground hover:text-foreground'"
                  @click="setPpnRate(row.name, option.nonPpn)"
                >
                  {{ option.label }}
                </button>
              </div>
            </template>
          </DataTable>
        </div>
      </TabsContent>
    </Tabs>

    <!-- Tambah & edit memakai dialog yang sama -->
    <Dialog
      :open="isFormOpen"
      :title="`${formMode === 'edit' ? 'Edit' : 'Tambah'} ${currentTab.noun}`"
      :max-width="isGridTab ? 'max-w-md' : 'max-w-lg'"
      @update:open="closeForm"
    >
      <form :id="FORM_ID" ref="formRef" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitForm">
        <!-- Kategori, Brand, Company, Status Payment: satu isian nama -->
        <FormField v-if="isGridTab" :label="currentTab.nameLabel" for="master-name" required class="md:col-span-2">
          <Input id="master-name" v-model="form.name" :placeholder="currentTab.namePlaceholder" required />
        </FormField>

        <template v-else-if="activeTab === 'warehouses'">
          <FormField label="Kode Gudang" for="master-code" required>
            <Input
              id="master-code"
              v-model="form.code"
              placeholder="Contoh: GDG-JKT-03"
              required
              class="uppercase tabular-nums placeholder:normal-case"
            />
          </FormField>
          <FormField label="Nama Gudang" for="master-name" required>
            <Input id="master-name" v-model="form.name" placeholder="Contoh: Gudang Daan Mogot" required />
          </FormField>
          <FormField label="Lokasi / Kota" for="master-location" class="md:col-span-2">
            <Input id="master-location" v-model="form.location" placeholder="Contoh: Jakarta Barat" />
          </FormField>
        </template>

        <template v-else-if="activeTab === 'suppliers'">
          <FormField label="Nama Supplier" for="master-name" required class="md:col-span-2">
            <Input id="master-name" v-model="form.name" placeholder="Contoh: PT Unilever Indonesia Tbk" required />
          </FormField>
          <FormField label="NPWP Supplier" for="master-npwp">
            <Input id="master-npwp" v-model="form.npwp" placeholder="01.234.567.8-901.000" class="tabular-nums" />
          </FormField>
          <FormField label="Nomor Telepon / Kontak" for="master-phone">
            <Input id="master-phone" v-model="form.phone" placeholder="Contoh: 021-52995299" class="tabular-nums" />
          </FormField>
        </template>
      </form>

      <template #footer>
        <Button type="button" variant="outline" @click="closeForm">Batal</Button>
        <Button type="submit" :form="FORM_ID">{{ formMode === 'edit' ? 'Simpan Perubahan' : 'Simpan' }}</Button>
      </template>
    </Dialog>

    <!-- Konfirmasi hapus. Tombol Hapus sengaja bukan AlertDialogAction: dialog baru ditutup setelah item terhapus. -->
    <AlertDialog :open="Boolean(itemToDelete)" @update:open="open => !open && (itemToDelete = null)">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Hapus {{ currentTab.noun.toLowerCase() }}?</AlertDialogTitle>
          <AlertDialogDescription>
            "{{ deletePreview?.label }}" akan dihapus dari referensi {{ currentTabLabel.toLowerCase() }}.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Batal</AlertDialogCancel>
          <Button variant="destructive" @click="confirmDelete">Hapus</Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>

    <!-- Konfirmasi reset ke default -->
    <AlertDialog v-model:open="isResetModalOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Reset ke default sistem?</AlertDialogTitle>
          <AlertDialogDescription>
            Seluruh daftar kategori, brand, company, gudang, supplier, status payment, dan aturan PPN akan dikembalikan ke data awal sistem.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Batal</AlertDialogCancel>
          <Button variant="destructive" @click="confirmReset">Reset</Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch, nextTick } from 'vue';
import { MoreHorizontal } from 'lucide-vue-next';
import DataTable from '@/components/data-table/DataTable.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import Dialog from '@/components/ui/Dialog.vue';
import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useTaxStore, isNonPpnCategory } from '../store/taxStore';
import { PPN_COLUMNS, buildMasterColumns, masterMatchesSearch } from './masterDataColumns';

const store = useTaxStore();

const activeTab = ref('categories');
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const itemToEdit = ref(null);
const itemToDelete = ref(null);
const isResetModalOpen = ref(false);

// noun: tombol & judul dialog; rowLabel: teks jumlah baris; nameLabel/namePlaceholder: isian nama
const tabs = [
  {
    id: 'categories',
    label: 'Kategori Program',
    noun: 'Kategori',
    rowLabel: 'kategori',
    columnLabel: 'Kategori',
    nameLabel: 'Nama Kategori Program',
    namePlaceholder: 'Contoh: Digital Promo',
    searchPlaceholder: 'Cari kategori…',
  },
  {
    id: 'brands',
    label: 'Brand',
    noun: 'Brand',
    rowLabel: 'brand',
    columnLabel: 'Brand',
    nameLabel: 'Nama Brand',
    namePlaceholder: 'Contoh: SCM',
    searchPlaceholder: 'Cari brand…',
  },
  {
    id: 'companies',
    label: 'Company',
    noun: 'Company',
    rowLabel: 'company',
    columnLabel: 'Company',
    nameLabel: 'Nama Perusahaan (Company Name)',
    namePlaceholder: 'Contoh: PT SCM Nusantara',
    searchPlaceholder: 'Cari company…',
  },
  { id: 'warehouses', label: 'Kode Gudang', noun: 'Gudang', rowLabel: 'gudang', searchPlaceholder: 'Cari kode, nama, lokasi…' },
  { id: 'suppliers', label: 'Supplier', noun: 'Supplier', rowLabel: 'supplier', searchPlaceholder: 'Cari supplier, NPWP, telepon…' },
  {
    id: 'payment_statuses',
    label: 'Status Payment',
    noun: 'Status Payment',
    rowLabel: 'status payment',
    columnLabel: 'Status Payment',
    nameLabel: 'Nama Status Payment',
    namePlaceholder: 'Contoh: Waiting Payment, Tempo 30 Hari, Paid (Lunas)',
    searchPlaceholder: 'Cari status payment…',
  },
  { id: 'ppn_rules', label: 'Aturan PPN', noun: 'Aturan PPN', rowLabel: 'kategori' },
];

const addForm = reactive({
  name: '',
  code: '',
  location: '',
  npwp: '',
  phone: '',
});

const editForm = reactive({
  name: '',
  code: '',
  location: '',
  npwp: '',
  phone: '',
});

const currentTab = computed(() => {
  return tabs.find(t => t.id === activeTab.value) || tabs[0];
});

const currentTabLabel = computed(() => currentTab.value.label);

const isGridTab = computed(() => {
  return (
    activeTab.value === 'categories' ||
    activeTab.value === 'brands' ||
    activeTab.value === 'companies' ||
    activeTab.value === 'payment_statuses'
  );
});

function getTabCount(tabId) {
  const data = store.masterData.value || {};
  if (tabId === 'ppn_rules') {
    return (data.categories || []).length;
  }
  const list = data[tabId] || [];
  return list.length;
}

// Tabel per jenis data (kolom & pencarian di masterDataColumns.js)
const editableTabs = tabs.filter(tab => tab.id !== 'ppn_rules');
const tableColumns = Object.fromEntries(editableTabs.map(tab => [tab.id, buildMasterColumns(tab.id, tab.columnLabel)]));
const tableSearch = Object.fromEntries(editableTabs.map(tab => [tab.id, masterMatchesSearch(tab.id)]));
const ppnSearch = masterMatchesSearch('ppn_rules');

// Salinan tiap daftar, supaya tabel ikut diperbarui juga saat isi daftar diubah di tempat
const masterLists = computed(() => {
  const data = store.masterData.value || {};
  return Object.fromEntries(editableTabs.map(tab => [tab.id, [...(data[tab.id] || [])]]));
});

function rowName(type, item) {
  if (type === 'warehouses') return item.code;
  if (type === 'suppliers') return item.name;
  return String(item);
}

// Semua kategori untuk tabel Aturan PPN (dari masterData.categories) beserta tarifnya
const ppnRows = computed(() => {
  const cats = store.masterData.value?.categories || [];
  return cats.map(name => ({ name, nonPpn: isCategoryNonPpn(name) }));
});

// Cek apakah kategori ini Non-PPN (reactive langsung terhadap store.masterData)
function isCategoryNonPpn(categoryName) {
  const rules = store.masterData.value?.ppn_rules;
  if (Array.isArray(rules)) {
    const cLower = String(categoryName || '').toLowerCase().trim();
    return rules.some(r => r.toLowerCase().trim() === cLower);
  }
  return isNonPpnCategory(categoryName);
}

// State loading per-kategori saat klik toggle
const togglingCategory = ref(null);

const ppnOptions = [
  { label: '0%', nonPpn: true },
  { label: '11%', nonPpn: false },
];

// Pilih tarif langsung; klik pada tarif yang sudah aktif tidak mengubah apa-apa
function setPpnRate(categoryName, nonPpn) {
  if (isCategoryNonPpn(categoryName) !== nonPpn) {
    togglePpnRule(categoryName);
  }
}

// Toggle PPN rule untuk kategori
async function togglePpnRule(categoryName) {
  if (togglingCategory.value) return;
  togglingCategory.value = categoryName;
  try {
    const currentlyNonPpn = isCategoryNonPpn(categoryName);
    await store.setPpnRule(categoryName, !currentlyNonPpn);
  } finally {
    togglingCategory.value = null;
  }
}

// Dialog form: formMode tidak direset saat ditutup, supaya judul & isian tidak berganti selama animasi tutup
const FORM_ID = 'master-data-form';
const formMode = ref('add');
const formRef = ref(null);
const isFormOpen = computed(() => isAddModalOpen.value || isEditModalOpen.value);
const form = computed(() => (formMode.value === 'edit' ? editForm : addForm));

// Fokus langsung ke isian pertama saat dialog dibuka
watch(isFormOpen, async open => {
  if (!open) return;
  await nextTick();
  formRef.value?.querySelector('input')?.focus();
});

function closeForm() {
  isAddModalOpen.value = false;
  isEditModalOpen.value = false;
}

function submitForm() {
  if (formMode.value === 'edit') submitEdit();
  else submitAdd();
}

function openAddModal() {
  addForm.name = '';
  addForm.code = '';
  addForm.location = '';
  addForm.npwp = '';
  addForm.phone = '';
  formMode.value = 'add';
  isAddModalOpen.value = true;
}

async function submitAdd() {
  let itemPayload = null;

  if (isGridTab.value) {
    itemPayload = addForm.name.trim();
  } else if (activeTab.value === 'warehouses') {
    itemPayload = {
      code: addForm.code.trim().toUpperCase(),
      name: addForm.name.trim(),
      location: addForm.location.trim(),
    };
  } else if (activeTab.value === 'suppliers') {
    itemPayload = {
      name: addForm.name.trim(),
      npwp: addForm.npwp.trim(),
      phone: addForm.phone.trim(),
    };
  }

  if (itemPayload) {
    const res = await store.addMasterItem(activeTab.value, itemPayload);
    if (res && res.success !== false) {
      isAddModalOpen.value = false;
    }
  }
}

function openEditModal(type, item) {
  itemToEdit.value = { type, raw: item };
  if (type === 'warehouses') {
    editForm.code = item.code || '';
    editForm.name = item.name || '';
    editForm.location = item.location || '';
  } else if (type === 'suppliers') {
    editForm.name = item.name || '';
    editForm.npwp = item.npwp || '';
    editForm.phone = item.phone || '';
  } else {
    editForm.name = String(item || '');
  }
  formMode.value = 'edit';
  isEditModalOpen.value = true;
}

async function submitEdit() {
  if (!itemToEdit.value) return;
  const type = itemToEdit.value.type;
  let oldValue = null;
  let newPayload = null;

  if (
    type === 'categories' ||
    type === 'brands' ||
    type === 'companies' ||
    type === 'payment_statuses'
  ) {
    oldValue = itemToEdit.value.raw;
    newPayload = editForm.name.trim();
  } else if (type === 'warehouses') {
    oldValue = itemToEdit.value.raw.code;
    newPayload = {
      code: editForm.code.trim().toUpperCase(),
      name: editForm.name.trim(),
      location: editForm.location.trim(),
    };
  } else if (type === 'suppliers') {
    oldValue = itemToEdit.value.raw.name;
    newPayload = {
      name: editForm.name.trim(),
      npwp: editForm.npwp.trim(),
      phone: editForm.phone.trim(),
    };
  }

  if (newPayload) {
    const res = await store.updateMasterItem(type, oldValue, newPayload);
    if (res && res.success !== false) {
      isEditModalOpen.value = false;
      itemToEdit.value = null;
    }
  }
}

function promptDelete(type, item) {
  let val = '';
  let label = '';
  if (type === 'warehouses') {
    val = item.code;
    label = `${item.name} (${item.code})`;
  } else if (type === 'suppliers') {
    val = item.name;
    label = item.name;
  } else {
    val = item;
    label = item;
  }
  itemToDelete.value = { type, value: val, label };
}

async function confirmDelete() {
  if (!itemToDelete.value) return;
  await store.deleteMasterItem(itemToDelete.value.type, itemToDelete.value.value);
  itemToDelete.value = null;
}

// Teks dialog hapus tetap terisi selama animasi tutup
const deletePreview = ref(null);
watch(itemToDelete, item => {
  if (item) deletePreview.value = item;
});

// Menu ⋯ yang membuka dialog tidak mengembalikan fokus ke tombol ⋯, supaya fokus tetap di dialog
function keepDialogFocus(event) {
  if (isFormOpen.value || itemToDelete.value) event.preventDefault();
}

function openResetModal() {
  isResetModalOpen.value = true;
}

async function confirmReset() {
  await store.resetMasterData();
  isResetModalOpen.value = false;
}

onMounted(() => {
  store.fetchMasterData();
});
</script>
