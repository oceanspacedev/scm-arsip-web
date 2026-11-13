<template>
  <div class="flex flex-col gap-6">
    <PageHeader
      title="Hak Akses & Role"
      description="Atur wewenang setiap peran pengguna melalui centang perizinan. Admin SCM selalu memiliki akses penuh."
    >
      <template #actions>
        <Button variant="outline" :disabled="isSaving" @click="isResetDialogOpen = true">Reset Default</Button>
        <Button :disabled="isSaving" @click="saveChanges">{{ isSaving ? 'Menyimpan…' : 'Simpan Perubahan' }}</Button>
      </template>
    </PageHeader>

    <div class="flex flex-col gap-4">
      <!-- Pencarian izin & centang massal per role -->
      <div class="flex items-center gap-2">
        <div class="relative min-w-0 flex-1 sm:w-64 sm:flex-none">
          <Search class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input v-model="searchQuery" type="text" placeholder="Cari izin…" aria-label="Cari izin" class="h-8 pr-8 pl-8" />
          <Button
            v-if="searchQuery"
            type="button"
            variant="ghost"
            size="icon-sm"
            class="absolute top-1/2 right-1 size-6 -translate-y-1/2 text-muted-foreground"
            aria-label="Hapus pencarian"
            @click="searchQuery = ''"
          >
            <X />
          </Button>
        </div>

        <DropdownMenu :modal="false">
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="sm" class="ml-auto">Centang Massal</Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-48">
            <template v-for="(role, index) in editableRoles" :key="role.key">
              <DropdownMenuSeparator v-if="index > 0" />
              <DropdownMenuLabel>{{ role.label }}</DropdownMenuLabel>
              <DropdownMenuItem @select="toggleRoleAll(role.key, true)">Centang Semua</DropdownMenuItem>
              <DropdownMenuItem @select="toggleRoleAll(role.key, false)">Kosongkan Semua</DropdownMenuItem>
            </template>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>

      <!-- Layar kecil: satu role per tampilan -->
      <FormField label="Role" for="izin-pilih-role" :helper="activeRoleObj.description" class="md:hidden">
        <NativeSelect id="izin-pilih-role" v-model="activeMobileRole">
          <option v-for="role in rolesList" :key="role.key" :value="role.key">{{ role.label }}</option>
        </NativeSelect>
      </FormField>

      <!-- Satu kartu per modul. Baris satu baris; keterangan izin muncul saat kursor diarahkan -->
      <div v-if="filteredGroups.length" class="@container">
        <div class="grid gap-4 @5xl:grid-cols-2">
          <Card v-for="group in filteredGroups" :key="group.id" class="overflow-hidden">
            <Table class="table-fixed">
              <TableHeader>
                <TableRow class="hover:bg-transparent">
                  <TableHead class="h-12 pl-4 text-base font-semibold text-foreground">
                    <span class="block truncate">{{ group.title }}</span>
                  </TableHead>
                  <TableHead
                    v-for="role in rolesList"
                    :key="role.key"
                    :class="cn('h-12 w-16 px-1 text-center', roleColumnClass(role.key))"
                  >
                    <!-- Nama role dua baris (mis. "Staff / Gudang") supaya kolom tetap ramping -->
                    <span class="mx-auto block w-min leading-4 whitespace-normal">{{ role.label }}</span>
                  </TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                <TableRow v-for="item in group.items" :key="item.key">
                  <TableCell class="py-2 pl-4 whitespace-normal">
                    <Tooltip :delay-duration="300">
                      <TooltipTrigger as-child>
                        <!-- Di layar kecil label ikut mencentang role yang sedang ditampilkan -->
                        <label :for="isWide ? undefined : checkboxId(activeMobileRole, item.key)" class="block">
                          {{ item.name }}
                        </label>
                      </TooltipTrigger>
                      <TooltipContent side="top" align="start">
                        <p>{{ item.name }}</p>
                        <p>{{ item.description }}</p>
                      </TooltipContent>
                    </Tooltip>
                  </TableCell>

                  <TableCell
                    v-for="role in rolesList"
                    :key="role.key"
                    :class="cn('px-1 py-2 [&:has([role=checkbox])]:pr-1', roleColumnClass(role.key))"
                  >
                    <div class="flex items-center justify-center">
                      <Checkbox
                        v-if="role.key === 'Admin SCM'"
                        :id="checkboxId(role.key, item.key)"
                        :model-value="true"
                        disabled
                        :aria-label="`${item.name} untuk ${role.label} (akses penuh)`"
                      />
                      <Checkbox
                        v-else
                        :id="checkboxId(role.key, item.key)"
                        :model-value="isPermissionChecked(role.key, item.key)"
                        :aria-label="`${item.name} untuk ${role.label}`"
                        @update:model-value="togglePermission(role.key, item.key)"
                      />
                    </div>
                  </TableCell>
                </TableRow>
              </TableBody>
            </Table>
          </Card>
        </div>
      </div>

      <div
        v-else
        class="flex flex-col items-center justify-center gap-2 rounded-xl border bg-card px-6 py-12 text-center shadow-xs"
      >
        <SearchX class="size-8 text-muted-foreground" />
        <p class="text-sm font-medium">Tidak ada izin yang cocok dengan "{{ searchQuery }}".</p>
        <Button variant="outline" size="sm" class="mt-1" @click="searchQuery = ''">Reset pencarian</Button>
      </div>
    </div>

    <AlertDialog v-model:open="isResetDialogOpen">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Kembalikan hak akses ke default?</AlertDialogTitle>
          <AlertDialogDescription>
            Centang izin setiap role dikembalikan ke pengaturan bawaan dan langsung disimpan.
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Batal</AlertDialogCancel>
          <AlertDialogAction variant="destructive" @click="resetToDefaults">Reset Default</AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useMediaQuery } from '@vueuse/core';
import { Search, SearchX, X } from 'lucide-vue-next';
import PageHeader from '../components/layout/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import { cn } from '@/lib/utils';
import { useTaxStore, DEFAULT_ROLE_PERMISSIONS } from '../store/taxStore';

const store = useTaxStore();

const searchQuery = ref('');
const isSaving = computed(() => store.isSavingPermissions.value);
const activeMobileRole = ref('Staff SCM');

// Local working copy of permissions
const localPermissions = ref(JSON.parse(JSON.stringify(DEFAULT_ROLE_PERMISSIONS)));

const rolesList = [
  {
    key: 'Admin SCM',
    label: 'Admin SCM',
    description: 'Administrator sistem dengan akses penuh.',
  },
  {
    key: 'Staff SCM',
    label: 'Staff SCM',
    description: 'Divisi pengadaan & logistik SCM.',
  },
  {
    key: 'Staff Gudang',
    label: 'Staff Gudang',
    description: 'Petugas lapangan & pergudangan.',
  },
  {
    key: 'Staff Finance',
    label: 'Staff Finance',
    description: 'Tim keuangan & verifikasi pajak.',
  },
];

const activeRoleObj = computed(() => {
  return rolesList.find(r => r.key === activeMobileRole.value) || rolesList[1];
});

// Pengelompokan izin per modul (hanya tampilan: kunci, nama, dan keterangan izin tetap sama)
const permissionGroups = [
  {
    id: 'pages',
    title: 'Akses Halaman',
    items: [
      {
        key: 'view_dashboard',
        name: 'Akses Dashboard',
        description: 'Ringkasan metrik statistik dan overview tagihan'
      },
      {
        key: 'view_programs',
        name: 'Akses Arsip Program',
        description: 'Membuka tabel arsip program SCM'
      },
    ]
  },
  {
    id: 'program_columns',
    title: 'Kolom Arsip Program',
    items: [
      {
        key: 'view_master_columns',
        name: 'Tampilkan Kolom Master Data',
        description: 'Kolom Kategori, Brand, Company, Kode Gudang, No. PO/SJ, dan Supplier'
      },
      {
        key: 'view_finance_columns',
        name: 'Tampilkan Kolom Keuangan',
        description: 'Kolom DPP, PPN, Total Tagihan, Faktur Pajak, dan Status Pembayaran'
      },
    ]
  },
  {
    id: 'programs_actions',
    title: 'Pengelolaan Arsip Program',
    items: [
      {
        key: 'add_program',
        name: 'Tambah Program Baru',
        description: 'Formulir input program baru ke arsip'
      },
      {
        key: 'edit_purchase',
        name: 'Edit Data Pengadaan / Vendor / Master',
        description: 'Ubah nama program, vendor, NPWP, kategori, brand, kode gudang, PO'
      },
      {
        key: 'edit_finance',
        name: 'Edit Data Keuangan & Status Payment',
        description: 'Ubah invoice, nominal DPP/PPN, faktur pajak, dan status payment'
      },
      {
        key: 'delete_program',
        name: 'Hapus Program / Arsip',
        description: 'Hapus permanen program dari database'
      },
      {
        key: 'import_program',
        name: 'Import Excel / CSV',
        description: 'Unggah file spreadsheet untuk sinkronisasi massal'
      },
      {
        key: 'export_program',
        name: 'Ekspor Data ke Excel',
        description: 'Unduh seluruh arsip program ke format spreadsheet'
      },
    ]
  },
  {
    id: 'documents_management',
    title: 'Manajemen Dokumen',
    items: [
      {
        key: 'upload_memo',
        name: 'Unggah Memo / DO (Surat Jalan)',
        description: 'Lampirkan bukti serah terima atau surat jalan'
      },
      {
        key: 'upload_invoice',
        name: 'Unggah Invoice',
        description: 'Lampirkan invoice tagihan resmi vendor'
      },
      {
        key: 'upload_faktur',
        name: 'Unggah Faktur Pajak',
        description: 'Lampirkan berkas e-Faktur PPN masukan DJP'
      },
      {
        key: 'delete_memo',
        name: 'Hapus Memo / DO',
        description: 'Hapus berkas surat jalan/memo terlampir'
      },
      {
        key: 'delete_finance_doc',
        name: 'Hapus Invoice & Faktur Pajak',
        description: 'Hapus berkas invoice atau faktur pajak terlampir'
      },
    ]
  }
];

// Filtered groups based on search query
const filteredGroups = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();
  if (!q) return permissionGroups;

  return permissionGroups
    .map(g => {
      const filteredItems = g.items.filter(item =>
        item.name.toLowerCase().includes(q) ||
        item.description.toLowerCase().includes(q) ||
        item.key.toLowerCase().includes(q)
      );
      return { ...g, items: filteredItems };
    })
    .filter(g => g.items.length > 0);
});

// --- Tampilan ---
// Role yang bisa dicentang massal (Admin SCM selalu akses penuh)
const editableRoles = rolesList.filter(role => role.key !== 'Admin SCM');

// Layar ≥768px menampilkan semua kolom role; di bawahnya hanya role yang dipilih
const isWide = useMediaQuery('(min-width: 768px)');

function roleColumnClass(roleKey) {
  return roleKey === activeMobileRole.value ? '' : 'hidden md:table-cell';
}

function checkboxId(roleKey, permKey) {
  return `izin-${roleKey.toLowerCase().replace(/\s+/g, '-')}-${permKey}`;
}

// Reset Default dikonfirmasi dulu karena langsung menimpa pengaturan yang tersimpan
const isResetDialogOpen = ref(false);

function isPermissionChecked(roleKey, permKey) {
  if (roleKey === 'Admin SCM') return true;
  return Boolean(localPermissions.value?.[roleKey]?.[permKey]);
}

function setPermission(roleKey, permKey, value) {
  if (roleKey === 'Admin SCM') return;
  if (!localPermissions.value[roleKey]) {
    localPermissions.value[roleKey] = {};
  }
  localPermissions.value[roleKey][permKey] = Boolean(value);
}

function togglePermission(roleKey, permKey) {
  setPermission(roleKey, permKey, !isPermissionChecked(roleKey, permKey));
}

function toggleRoleAll(roleKey, stateVal) {
  if (roleKey === 'Admin SCM') return;
  if (!localPermissions.value[roleKey]) {
    localPermissions.value[roleKey] = {};
  }
  permissionGroups.forEach(g => {
    g.items.forEach(item => {
      localPermissions.value[roleKey][item.key] = stateVal;
    });
  });
}

function syncFromStore() {
  const current = store.rolePermissions.value;
  if (current && Object.keys(current).length > 0) {
    localPermissions.value = JSON.parse(JSON.stringify(current));
  } else {
    localPermissions.value = JSON.parse(JSON.stringify(DEFAULT_ROLE_PERMISSIONS));
  }
}

async function resetToDefaults() {
  localPermissions.value = JSON.parse(JSON.stringify(DEFAULT_ROLE_PERMISSIONS));
  await store.resetRolePermissions();
}

async function saveChanges() {
  await store.saveRolePermissions(localPermissions.value);
}

onMounted(async () => {
  await store.fetchRolePermissions();
  syncFromStore();
});
</script>
