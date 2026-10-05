<template>
  <div class="space-y-3.5 max-w-6xl pb-8 select-none font-sans text-xs">
    <!-- Page Header (Clean, consistent font & normal weight) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-lg font-semibold tracking-tight text-slate-800 dark:text-slate-100">
          Hak Akses & Role Permission
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 font-normal">
          Atur wewenang setiap peran pengguna melalui centang perizinan
        </p>
      </div>

      <!-- Action Buttons (Standard SCM styling, not pitch black) -->
      <div class="flex items-center gap-2">
        <button
          type="button"
          class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 text-xs font-medium transition-colors cursor-pointer flex items-center gap-1.5 shadow-2xs"
          :disabled="isSaving"
          @click="resetToDefaults"
        >
          <RotateCcw class="w-3.5 h-3.5 text-slate-400" />
          <span>Reset Default</span>
        </button>

        <button
          type="button"
          class="h-8 px-3.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors cursor-pointer flex items-center gap-1.5 shadow-2xs disabled:opacity-50"
          :disabled="isSaving"
          @click="saveChanges"
        >
          <Save v-if="!isSaving" class="w-3.5 h-3.5" />
          <Loader2 v-else class="w-3.5 h-3.5 animate-spin" />
          <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
        </button>
      </div>
    </div>

    <!-- Search & Mobile Role Switcher Bar -->
    <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200/90 dark:border-slate-800 p-2.5 sm:px-3 sm:py-2.5 shadow-2xs flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
      <!-- Search -->
      <div class="relative w-full sm:w-72">
        <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari izin..."
          class="w-full h-8 pl-8 pr-7 text-xs rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-slate-400 transition-colors font-normal"
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

      <!-- Mobile Role Filter Tabs -->
      <div class="flex items-center gap-1 overflow-x-auto pb-0.5 sm:pb-0 scrollbar-none lg:hidden">
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

    <!-- Desktop Comparison Table (Consistent text size, normal weights, clean borders) -->
    <div class="hidden lg:block bg-white dark:bg-[#111827] rounded-xl border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden">
      <table class="w-full text-left text-xs border-collapse">
        <thead>
          <tr class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 text-slate-500 dark:text-slate-400 font-sans">
            <th class="py-2.5 px-3.5 w-[40%] text-[11px] font-semibold uppercase tracking-wider">
              Perizinan
            </th>
            <th
              v-for="role in rolesList"
              :key="role.key"
              class="py-2.5 px-3 text-center w-[15%]"
            >
              <div class="flex flex-col items-center">
                <span class="font-medium text-slate-700 dark:text-slate-200 text-xs">
                  {{ role.label }}
                </span>
                <!-- Quick Toggle (Check All / Uncheck) -->
                <div v-if="role.key !== 'Admin SCM'" class="flex items-center gap-1 mt-0.5 text-[11px] font-normal">
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
                </div>
                <span v-else class="text-[11px] text-slate-400 mt-0.5 font-normal">
                  Akses Penuh
                </span>
              </div>
            </th>
          </tr>
        </thead>

        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          <template v-for="group in filteredGroups" :key="group.id">
            <!-- Section Group Header Row (Subtle gray, medium weight) -->
            <tr class="bg-slate-50/60 dark:bg-slate-900/40">
              <td colspan="5" class="py-2 px-3.5 font-medium text-[11px] uppercase tracking-wider text-slate-500 dark:text-slate-400">
                {{ group.title }}
              </td>
            </tr>

            <!-- Permission Items -->
            <tr
              v-for="item in group.items"
              :key="item.key"
              class="hover:bg-slate-50/60 dark:hover:bg-slate-800/30 transition-colors"
            >
              <!-- Permission Info (Consistent font-sans, normal weight, matching size) -->
              <td class="py-2.5 px-3.5">
                <div class="text-xs font-normal text-slate-700 dark:text-slate-200 leading-snug">
                  {{ item.name }}
                </div>
                <div class="text-[11px] font-normal text-slate-400 dark:text-slate-500 mt-0.5 leading-snug">
                  {{ item.description }}
                </div>
              </td>

              <!-- Roles Checkboxes -->
              <td
                v-for="role in rolesList"
                :key="role.key"
                class="py-2.5 px-3 text-center align-middle"
              >
                <!-- Admin SCM: Fixed checkmark -->
                <div v-if="role.key === 'Admin SCM'" class="flex items-center justify-center">
                  <Check class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" />
                </div>

                <!-- Other Roles: Clean Checkbox -->
                <label
                  v-else
                  class="inline-flex items-center justify-center cursor-pointer p-0.5 rounded hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                >
                  <input
                    type="checkbox"
                    :checked="isPermissionChecked(role.key, item.key)"
                    @change="setPermission(role.key, item.key, $event.target.checked)"
                    class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-0 focus:outline-none cursor-pointer"
                  />
                </label>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>

    <!-- Mobile Card View (Consistent typography, no bold, tidy layout) -->
    <div class="lg:hidden space-y-3">
      <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 p-3 shadow-2xs">
        <!-- Active Role Header -->
        <div class="flex items-center justify-between pb-2 border-b border-slate-100 dark:border-slate-800 text-xs">
          <div>
            <span class="font-medium text-slate-800 dark:text-slate-200">{{ activeRoleObj?.label }}</span>
            <span class="text-slate-400 text-[11px] block font-normal">{{ activeRoleObj?.description }}</span>
          </div>

          <div v-if="activeMobileRole !== 'Admin SCM'" class="flex items-center gap-1.5 text-[11px] font-normal">
            <button
              type="button"
              class="text-slate-500 hover:text-slate-700 dark:hover:text-slate-200"
              @click="toggleRoleAll(activeMobileRole, true)"
            >
              Semua
            </button>
            <span class="text-slate-300 dark:text-slate-700">·</span>
            <button
              type="button"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
              @click="toggleRoleAll(activeMobileRole, false)"
            >
              Kosong
            </button>
          </div>
        </div>

        <!-- Groups -->
        <div class="divide-y divide-slate-100 dark:divide-slate-800">
          <div
            v-for="group in filteredGroups"
            :key="group.id"
            class="py-2 space-y-1.5"
          >
            <div class="text-[11px] font-medium uppercase tracking-wider text-slate-400">
              {{ group.title }}
            </div>

            <div class="space-y-1">
              <label
                v-for="item in group.items"
                :key="item.key"
                class="flex items-start justify-between gap-2.5 p-2 rounded-lg border border-slate-100 dark:border-slate-800/80 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors cursor-pointer"
              >
                <div class="min-w-0 flex-1">
                  <div class="text-xs font-normal text-slate-700 dark:text-slate-200 leading-snug">
                    {{ item.name }}
                  </div>
                  <div class="text-[11px] font-normal text-slate-400 dark:text-slate-500 mt-0.5 leading-tight">
                    {{ item.description }}
                  </div>
                </div>

                <div class="shrink-0 pt-0.5">
                  <div v-if="activeMobileRole === 'Admin SCM'" class="w-3.5 h-3.5 text-slate-400 flex items-center justify-center">
                    <Check class="w-3.5 h-3.5" />
                  </div>
                  <input
                    v-else
                    type="checkbox"
                    :checked="isPermissionChecked(activeMobileRole, item.key)"
                    @change="setPermission(activeMobileRole, item.key, $event.target.checked)"
                    class="w-3.5 h-3.5 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-0 focus:outline-none cursor-pointer"
                  />
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>
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
