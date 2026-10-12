<template>
  <div class="space-y-4 max-w-6xl pb-8 select-none font-sans text-xs">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
          Hak Akses & Role
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          Atur wewenang setiap peran pengguna melalui centang perizinan
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-medium transition-colors cursor-pointer flex items-center gap-1.5"
          :disabled="isSaving"
          @click="resetToDefaults"
        >
          <RotateCcw class="w-3.5 h-3.5 text-slate-400" />
          <span>Reset Default</span>
        </button>

        <button
          type="button"
          class="h-8 px-3.5 rounded-lg text-xs font-medium transition-colors cursor-pointer flex items-center gap-1.5 disabled:opacity-50 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700"
          :disabled="isSaving"
          @click="saveChanges"
        >
          <Save v-if="!isSaving" class="w-3.5 h-3.5" />
          <Loader2 v-else class="w-3.5 h-3.5 animate-spin" />
          <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
        </button>
      </div>
    </div>

    <!-- Search & Mobile Role Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
      <div class="relative w-full sm:w-72">
        <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari izin..."
          class="w-full h-8 pl-8 pr-7 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-slate-400 transition-colors"
        />
        <button
          v-if="searchQuery"
          type="button"
          class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-0.5 cursor-pointer"
          @click="searchQuery = ''"
        >
          <X class="w-3 h-3" />
        </button>
      </div>

      <div class="flex items-center gap-1 overflow-x-auto no-scrollbar lg:hidden">
        <button
          v-for="role in rolesList"
          :key="role.key"
          type="button"
          class="px-2.5 py-1 rounded-md text-xs font-medium whitespace-nowrap transition-colors cursor-pointer"
          :class="activeMobileRole === role.key
            ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-slate-100'
            : 'text-slate-500 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/50'"
          @click="activeMobileRole = role.key"
        >
          {{ role.label }}
        </button>
      </div>
    </div>

    <!-- Desktop: matriks izin x peran, satu baris per izin (keterangan muncul saat kursor diarahkan) -->
    <div class="hidden lg:block bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400">
            <th class="py-2.5 px-4 w-[40%] text-[11px] font-medium">Perizinan</th>
            <th
              v-for="role in rolesList"
              :key="role.key"
              class="py-2.5 px-3 text-center w-[15%] font-normal"
            >
              <span class="block font-medium text-slate-700 dark:text-slate-200 text-xs">{{ role.label }}</span>
              <span v-if="role.key === 'Admin SCM'" class="block text-[11px] text-slate-400 mt-0.5">Akses penuh</span>
              <span v-else class="flex items-center justify-center gap-1 mt-0.5 text-[11px]">
                <button
                  type="button"
                  class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer"
                  @click="toggleRoleAll(role.key, true)"
                >
                  Semua
                </button>
                <span class="text-slate-300 dark:text-slate-700">·</span>
                <button
                  type="button"
                  class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 cursor-pointer"
                  @click="toggleRoleAll(role.key, false)"
                >
                  Kosong
                </button>
              </span>
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          <template v-for="group in filteredGroups" :key="group.id">
            <tr>
              <td colspan="5" class="pt-3 pb-1.5 px-4 text-[11px] font-medium text-slate-400 dark:text-slate-500">
                {{ group.title }}
              </td>
            </tr>

            <tr
              v-for="item in group.items"
              :key="item.key"
              class="hover:bg-slate-50/70 dark:hover:bg-slate-800/30 transition-colors"
            >
              <td class="py-1.5 px-4 text-slate-700 dark:text-slate-200" :title="item.description">
                {{ item.name }}
              </td>
              <td
                v-for="role in rolesList"
                :key="role.key"
                class="py-1.5 px-3 text-center align-middle"
              >
                <Check v-if="role.key === 'Admin SCM'" class="w-3.5 h-3.5 text-slate-300 dark:text-slate-600 mx-auto" />
                <input
                  v-else
                  type="checkbox"
                  :checked="isPermissionChecked(role.key, item.key)"
                  :aria-label="`${item.name} untuk ${role.label}`"
                  class="w-3.5 h-3.5 align-middle rounded accent-slate-900 dark:accent-slate-300 cursor-pointer"
                  @change="setPermission(role.key, item.key, $event.target.checked)"
                />
              </td>
            </tr>
          </template>

          <tr v-if="filteredGroups.length === 0">
            <td colspan="5" class="py-10 text-center text-slate-400 dark:text-slate-500">
              Tidak ada izin yang cocok dengan "{{ searchQuery }}".
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile: satu peran per tampilan -->
    <div class="lg:hidden bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800">
      <div class="flex items-center justify-between gap-3 px-3.5 py-2.5 border-b border-slate-100 dark:border-slate-800">
        <div class="min-w-0">
          <p class="font-medium text-slate-800 dark:text-slate-200">{{ activeRoleObj?.label }}</p>
          <p class="text-[11px] text-slate-400 truncate">{{ activeRoleObj?.description }}</p>
        </div>
        <div v-if="activeMobileRole !== 'Admin SCM'" class="flex items-center gap-1.5 shrink-0 text-[11px]">
          <button
            type="button"
            class="text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 cursor-pointer"
            @click="toggleRoleAll(activeMobileRole, true)"
          >
            Semua
          </button>
          <span class="text-slate-300 dark:text-slate-700">·</span>
          <button
            type="button"
            class="text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 cursor-pointer"
            @click="toggleRoleAll(activeMobileRole, false)"
          >
            Kosong
          </button>
        </div>
      </div>

      <div v-for="group in filteredGroups" :key="group.id" class="px-3.5 pb-1">
        <p class="pt-3 pb-1 text-[11px] font-medium text-slate-400 dark:text-slate-500">{{ group.title }}</p>
        <label
          v-for="item in group.items"
          :key="item.key"
          class="flex items-center justify-between gap-3 py-2 border-b border-slate-100 dark:border-slate-800/80 last:border-b-0 cursor-pointer"
          :title="item.description"
        >
          <span class="min-w-0 truncate text-slate-700 dark:text-slate-200">{{ item.name }}</span>
          <Check v-if="activeMobileRole === 'Admin SCM'" class="w-3.5 h-3.5 shrink-0 text-slate-300 dark:text-slate-600" />
          <input
            v-else
            type="checkbox"
            :checked="isPermissionChecked(activeMobileRole, item.key)"
            class="w-3.5 h-3.5 shrink-0 rounded accent-slate-900 dark:accent-slate-300 cursor-pointer"
            @change="setPermission(activeMobileRole, item.key, $event.target.checked)"
          />
        </label>
      </div>

      <p v-if="filteredGroups.length === 0" class="py-8 text-center text-slate-400 dark:text-slate-500">
        Tidak ada izin yang cocok dengan "{{ searchQuery }}".
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import {
  RotateCcw,
  Save,
  Search,
  X,
  Check,
  Loader2
} from 'lucide-vue-next';
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

// Grouping of permissions with clean labels
const permissionGroups = [
  {
    id: 'modules_visibility',
    title: 'Visibilitas Kolom & Modul',
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
