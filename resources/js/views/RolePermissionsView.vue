<template>
  <div class="space-y-4 max-w-6xl pb-8 text-sm">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-xl sm:text-2xl font-medium text-slate-900 dark:text-slate-100">
          Hak akses & role
        </h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
          Atur wewenang setiap peran pengguna melalui centang perizinan
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="h-9 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 text-sm font-medium transition-colors cursor-pointer"
          :disabled="isSaving"
          @click="resetToDefaults"
        >
          Reset ke default
        </button>

        <button
          type="button"
          class="h-9 px-4 rounded-lg text-sm font-medium transition-colors cursor-pointer flex items-center gap-2 disabled:opacity-60 bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
          :disabled="isSaving"
          @click="saveChanges"
        >
          <Loader2 v-if="isSaving" class="w-4 h-4 animate-spin" />
          <span>{{ isSaving ? 'Menyimpan…' : 'Simpan perubahan' }}</span>
        </button>
      </div>
    </div>

    <!-- Search & Mobile Role Switcher -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
      <div class="relative w-full sm:w-72">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari izin"
          class="w-full h-9 pl-9 pr-8 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors"
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
    <div class="hidden lg:block bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
      <table class="w-full text-left text-sm border-collapse">
        <thead>
          <tr class="bg-slate-100 dark:bg-[#202022] border-b border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-300">
            <th class="py-3 px-4 w-[40%] text-xs font-medium">Perizinan</th>
            <th
              v-for="role in rolesList"
              :key="role.key"
              class="group py-3 px-3 text-center w-[15%] font-normal"
            >
              <span class="block font-medium text-slate-700 dark:text-slate-200 text-xs">{{ role.label }}</span>
              <span v-if="role.key === 'Admin SCM'" class="block text-xs text-slate-400 mt-0.5">Akses penuh</span>
              <!-- Pilih/kosongkan semua: muncul saat kolom diarahkan kursor supaya header tetap bersih -->
              <span v-else class="flex items-center justify-center gap-1 mt-0.5 text-xs opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition-opacity">
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

        <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800">
          <template v-for="group in filteredGroups" :key="group.id">
            <tr>
              <td colspan="5" class="pt-5 pb-2 px-4 text-xs font-medium text-slate-500 dark:text-slate-400">
                {{ group.title }}
              </td>
            </tr>

            <tr
              v-for="item in group.items"
              :key="item.key"
              class="hover:bg-[#eef3fc] dark:hover:bg-[#242428] transition-colors"
            >
              <td class="h-11 px-4 text-slate-800 dark:text-slate-200" :title="item.description">
                {{ item.name }}
              </td>
              <td
                v-for="role in rolesList"
                :key="role.key"
                class="h-11 px-3 text-center align-middle"
              >
                <Check v-if="role.key === 'Admin SCM'" class="w-3.5 h-3.5 text-slate-300 dark:text-slate-600 mx-auto" />
                <button
                  v-else
                  type="button"
                  role="checkbox"
                  :aria-checked="isPermissionChecked(role.key, item.key)"
                  :aria-label="`${item.name} untuk ${role.label}`"
                  class="w-6 h-6 inline-flex items-center justify-center rounded-md align-middle hover:bg-slate-100 dark:hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-slate-400 transition-colors cursor-pointer"
                  @click="togglePermission(role.key, item.key)"
                >
                  <span
                    class="w-4 h-4 rounded border flex items-center justify-center transition-colors"
                    :class="isPermissionChecked(role.key, item.key)
                      ? 'border-slate-400 dark:border-slate-500'
                      : 'border-slate-300 dark:border-slate-600'"
                  >
                    <Check v-if="isPermissionChecked(role.key, item.key)" class="w-3 h-3 text-slate-900 dark:text-slate-100 stroke-[3]" />
                  </span>
                </button>
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
    <div class="lg:hidden bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800">
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
        <template v-for="item in group.items" :key="item.key">
          <div
            v-if="activeMobileRole === 'Admin SCM'"
            class="flex items-center justify-between gap-3 py-2 border-b border-slate-100 dark:border-slate-800/80 last:border-b-0"
            :title="item.description"
          >
            <span class="min-w-0 truncate text-slate-700 dark:text-slate-200">{{ item.name }}</span>
            <Check class="w-3.5 h-3.5 shrink-0 text-slate-300 dark:text-slate-600" />
          </div>
          <button
            v-else
            type="button"
            role="checkbox"
            :aria-checked="isPermissionChecked(activeMobileRole, item.key)"
            class="w-full flex items-center justify-between gap-3 py-2 text-left border-b border-slate-100 dark:border-slate-800/80 last:border-b-0 cursor-pointer"
            :title="item.description"
            @click="togglePermission(activeMobileRole, item.key)"
          >
            <span class="min-w-0 truncate text-slate-700 dark:text-slate-200">{{ item.name }}</span>
            <span
              class="w-4 h-4 shrink-0 rounded border flex items-center justify-center"
              :class="isPermissionChecked(activeMobileRole, item.key)
                ? 'border-slate-400 dark:border-slate-500'
                : 'border-slate-300 dark:border-slate-600'"
            >
              <Check v-if="isPermissionChecked(activeMobileRole, item.key)" class="w-3 h-3 text-slate-900 dark:text-slate-100 stroke-[3]" />
            </span>
          </button>
        </template>
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
    title: 'Visibilitas kolom dan modul',
    items: [
      {
        key: 'view_dashboard',
        name: 'Akses dashboard',
        description: 'Ringkasan metrik statistik dan overview tagihan'
      },
      {
        key: 'view_programs',
        name: 'Akses arsip program',
        description: 'Membuka tabel arsip program SCM'
      },
      {
        key: 'view_master_columns',
        name: 'Tampilkan kolom data master',
        description: 'Kolom Kategori, Brand, Company, Kode Gudang, No. PO/SJ, dan Supplier'
      },
      {
        key: 'view_finance_columns',
        name: 'Tampilkan kolom keuangan',
        description: 'Kolom DPP, PPN, Total Tagihan, Faktur Pajak, dan Status Pembayaran'
      },
    ]
  },
  {
    id: 'programs_actions',
    title: 'Pengelolaan arsip program',
    items: [
      {
        key: 'add_program',
        name: 'Tambah program baru',
        description: 'Formulir input program baru ke arsip'
      },
      {
        key: 'edit_purchase',
        name: 'Ubah data pengadaan, vendor, dan master',
        description: 'Ubah nama program, vendor, NPWP, kategori, brand, kode gudang, PO'
      },
      {
        key: 'edit_finance',
        name: 'Ubah data keuangan dan status pembayaran',
        description: 'Ubah invoice, nominal DPP/PPN, faktur pajak, dan status payment'
      },
      {
        key: 'delete_program',
        name: 'Hapus program',
        description: 'Hapus permanen program dari database'
      },
      {
        key: 'import_program',
        name: 'Impor Excel/CSV',
        description: 'Unggah file spreadsheet untuk sinkronisasi massal'
      },
      {
        key: 'export_program',
        name: 'Ekspor data ke Excel',
        description: 'Unduh seluruh arsip program ke format spreadsheet'
      },
    ]
  },
  {
    id: 'documents_management',
    title: 'Manajemen dokumen',
    items: [
      {
        key: 'upload_memo',
        name: 'Unggah memo/DO (surat jalan)',
        description: 'Lampirkan bukti serah terima atau surat jalan'
      },
      {
        key: 'upload_invoice',
        name: 'Unggah invoice',
        description: 'Lampirkan invoice tagihan resmi vendor'
      },
      {
        key: 'upload_faktur',
        name: 'Unggah faktur pajak',
        description: 'Lampirkan berkas e-Faktur PPN masukan DJP'
      },
      {
        key: 'delete_memo',
        name: 'Hapus memo/DO',
        description: 'Hapus berkas surat jalan/memo terlampir'
      },
      {
        key: 'delete_finance_doc',
        name: 'Hapus invoice dan faktur pajak',
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
