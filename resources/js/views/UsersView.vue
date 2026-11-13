<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="Manajemen User" description="Kelola akses personel divisi SCM dan Tim Pajak.">
      <template #actions>
        <Button variant="outline" as-child>
          <router-link to="/permissions">Atur Hak Akses Role</router-link>
        </Button>
        <Button @click="openAddModal">Tambah User</Button>
      </template>
    </PageHeader>

    <div class="flex flex-col gap-4">
      <!-- Tab mengikuti ?tab= di URL; submenu sidebar menautkan ke URL yang sama -->
      <Tabs :model-value="activeFilter" @update:model-value="setActiveFilter">
        <TabsList aria-label="Saring user menurut status">
          <TabsTrigger v-for="tab in tabs" :key="tab.value" :value="tab.value">
            {{ tab.label }}
            <span :class="['text-xs tabular-nums', tab.attention ? 'text-warning' : 'text-muted-foreground']">
              {{ formatNumber(tab.count) }}
            </span>
          </TabsTrigger>
        </TabsList>
      </Tabs>

      <DataTable
        :columns="columns"
        :data="filteredList"
        :loading="loading"
        :get-row-id="user => String(user.id)"
        :initial-sorting="initialSorting"
        :facets="facets"
        :global-filter-fn="userMatchesSearch"
        search-placeholder="Cari nama, email, atau no. WA…"
        row-label="user"
        row-class="h-16"
        storage-key="scm_users_kolom_v1"
        :empty-title="emptyState.title"
        :empty-description="emptyState.description"
      >
        <template #cell-name="{ row }">
          <div class="flex items-center gap-3">
            <Avatar :initials="getInitials(row.name)" />
            <div class="min-w-0">
              <p class="break-words">{{ row.name }}</p>
              <p class="text-xs break-all text-muted-foreground">{{ row.email || '—' }}</p>
            </div>
          </div>
        </template>

        <template #cell-phone="{ row }">
          <span v-if="row.phone" class="tabular-nums">{{ row.phone }}</span>
          <span v-else class="text-muted-foreground">—</span>
        </template>

        <template #cell-role="{ row }">
          <div class="flex items-center gap-1.5">
            <Badge v-if="row.role" variant="secondary">{{ row.role }}</Badge>
            <span v-else class="text-muted-foreground">—</span>
            <!-- Akun utama: tidak bisa diubah/dihapus, jadi kolom aksinya kosong -->
            <Badge v-if="isSuperAdmin(row)" variant="outline">Super Admin</Badge>
          </div>
        </template>

        <template #cell-registered_at="{ row }">
          <span class="tabular-nums">{{ formatTanggal(row.registered_at) }}</span>
        </template>

        <template #cell-status="{ row }">
          <Badge :variant="statusOf(row).variant">{{ statusOf(row).label }}</Badge>
        </template>

        <template #cell-actions="{ row }">
          <div v-if="!isSuperAdmin(row)" class="flex items-center justify-end gap-2">
            <!-- Persetujuan tetap satu klik untuk akun yang menunggu ACC -->
            <Button
              v-if="row.status === 'pending'"
              variant="outline"
              size="sm"
              :disabled="processingId === row.id"
              @click="handleApprove(row)"
            >
              ACC
            </Button>
            <DropdownMenu :modal="false">
              <DropdownMenuTrigger as-child>
                <Button
                  variant="ghost"
                  size="icon-sm"
                  :disabled="processingId === row.id"
                  :aria-label="`Aksi untuk ${row.name}`"
                >
                  <MoreHorizontal />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" class="w-40">
                <DropdownMenuItem @select="openEditModal(row)">Ubah</DropdownMenuItem>
                <DropdownMenuItem v-if="row.status === 'pending'" @select="handleReject(row)">Tolak</DropdownMenuItem>
                <DropdownMenuItem v-else-if="row.status === 'approved'" @select="handleReject(row)">Nonaktifkan</DropdownMenuItem>
                <DropdownMenuItem v-else @select="handleApprove(row)">Aktifkan</DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem variant="destructive" @select="promptDelete(row)">Hapus</DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </template>
      </DataTable>
    </div>

    <!-- Tambah user -->
    <Dialog
      v-model:open="isAddModalOpen"
      title="Tambah User Baru"
      description="Tambahkan personel internal untuk akses SCM TaxVault."
      max-width="max-w-lg"
    >
      <form id="add-user-form" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitAddUser">
        <p v-if="addError" role="alert" class="rounded-md bg-destructive/10 px-3 py-2 text-destructive md:col-span-2">
          {{ addError }}
        </p>

        <FormField label="Nama Lengkap" for="add-user-name" required class="md:col-span-2">
          <Input id="add-user-name" v-model="addForm.name" type="text" placeholder="Contoh: Budi Santoso" required />
        </FormField>

        <FormField label="Email" for="add-user-email" required>
          <Input id="add-user-email" v-model="addForm.email" type="email" placeholder="nama@perusahaan.com" required />
        </FormField>

        <FormField label="Nomor WhatsApp" for="add-user-phone">
          <Input id="add-user-phone" v-model="addForm.phone" type="text" placeholder="081234567890" class="tabular-nums" />
        </FormField>

        <FormField label="Peran / Role" for="add-user-role" required>
          <NativeSelect id="add-user-role" v-model="addForm.role" required>
            <option v-for="role in ROLE_OPTIONS" :key="role" :value="role">{{ role }}</option>
          </NativeSelect>
        </FormField>

        <FormField label="Kata Sandi" for="add-user-password" required>
          <Input
            id="add-user-password"
            v-model="addForm.password"
            type="password"
            placeholder="Minimal 6 karakter"
            autocomplete="new-password"
            minlength="6"
            required
          />
        </FormField>
      </form>

      <template #footer>
        <Button type="button" variant="outline" @click="isAddModalOpen = false">Batal</Button>
        <Button type="submit" form="add-user-form" :disabled="isSubmitting">
          {{ isSubmitting ? 'Menyimpan…' : 'Simpan & Buat Akun' }}
        </Button>
      </template>
    </Dialog>

    <!-- Ubah user -->
    <Dialog
      v-model:open="isEditModalOpen"
      title="Ubah Data Pengguna"
      description="Perbarui informasi akun, hak akses, dan status pengguna."
      max-width="max-w-lg"
    >
      <form id="edit-user-form" class="grid gap-4 md:grid-cols-2" @submit.prevent="submitEditUser">
        <p v-if="editError" role="alert" class="rounded-md bg-destructive/10 px-3 py-2 text-destructive md:col-span-2">
          {{ editError }}
        </p>

        <FormField label="Nama Lengkap" for="edit-user-name" required class="md:col-span-2">
          <Input id="edit-user-name" v-model="editForm.name" type="text" required />
        </FormField>

        <FormField label="Email" for="edit-user-email" required>
          <Input id="edit-user-email" v-model="editForm.email" type="email" required />
        </FormField>

        <FormField label="Nomor WhatsApp" for="edit-user-phone">
          <Input id="edit-user-phone" v-model="editForm.phone" type="text" placeholder="081234567890" class="tabular-nums" />
        </FormField>

        <FormField label="Peran / Role" for="edit-user-role" required>
          <NativeSelect id="edit-user-role" v-model="editForm.role" required>
            <option v-for="role in ROLE_OPTIONS" :key="role" :value="role">{{ role }}</option>
          </NativeSelect>
        </FormField>

        <FormField label="Status Akun" for="edit-user-status" required>
          <NativeSelect id="edit-user-status" v-model="editForm.status" required>
            <option value="approved">Aktif (Approved)</option>
            <option value="pending">Menunggu ACC (Pending)</option>
            <option value="rejected">Nonaktif (Rejected)</option>
          </NativeSelect>
        </FormField>

        <FormField
          label="Ganti Kata Sandi (Opsional)"
          for="edit-user-password"
          helper="Minimal 6 karakter."
          class="md:col-span-2"
        >
          <Input
            id="edit-user-password"
            v-model="editForm.password"
            type="password"
            placeholder="Kosongkan jika tidak ingin mengubah sandi"
            autocomplete="new-password"
            minlength="6"
          />
        </FormField>
      </form>

      <template #footer>
        <Button type="button" variant="outline" :disabled="isEditing" @click="isEditModalOpen = false">Batal</Button>
        <Button type="submit" form="edit-user-form" :disabled="isEditing">
          {{ isEditing ? 'Menyimpan…' : 'Simpan Perubahan' }}
        </Button>
      </template>
    </Dialog>

    <!-- Konfirmasi hapus -->
    <AlertDialog :open="Boolean(userToDelete)" @update:open="open => !open && (userToDelete = null)">
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Hapus akun pengguna?</AlertDialogTitle>
          <AlertDialogDescription>
            Apakah Anda yakin ingin menghapus akun
            <span class="font-medium text-foreground">{{ deleteDisplay?.name }}</span>? Data yang dihapus tidak dapat dipulihkan.
          </AlertDialogDescription>
        </AlertDialogHeader>

        <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 rounded-lg border p-3 text-sm">
          <dt class="text-muted-foreground">Email</dt>
          <dd class="truncate" :title="deleteDisplay?.email">{{ deleteDisplay?.email || '—' }}</dd>
          <dt class="text-muted-foreground">Peran</dt>
          <dd class="truncate">{{ deleteDisplay?.role || '—' }}</dd>
        </dl>

        <AlertDialogFooter>
          <AlertDialogCancel :disabled="isDeleting">Batal</AlertDialogCancel>
          <!-- Tombol biasa (bukan AlertDialogAction) supaya dialog tetap terbuka selama proses hapus -->
          <Button variant="destructive" :disabled="isDeleting" @click="confirmDelete">
            {{ isDeleting ? 'Menghapus…' : 'Ya, Hapus' }}
          </Button>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { MoreHorizontal } from 'lucide-vue-next';
import DataTable from '@/components/data-table/DataTable.vue';
import { inSelection } from '@/components/data-table/utils';
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
import { Avatar } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
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
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatNumber, formatTanggal, parseDate } from '@/lib/format';
import { useTaxStore } from '../store/taxStore';

const route = useRoute();
const router = useRouter();
const store = useTaxStore();

const allUsers = computed(() => store.allUsers.value);
const pendingUsers = computed(() => store.pendingUsers.value);
const approvedUsers = computed(() => allUsers.value.filter(u => u.status === 'approved'));

// Skeleton hanya saat belum ada data sama sekali dan daftar user sedang diambil
const loading = ref(false);

onMounted(async () => {
  const role = store.currentUser.value?.role || '';
  const isAdmin = role === 'Admin SCM' || role.toLowerCase().includes('admin');
  if (!isAdmin) {
    router.replace('/dashboard');
    return;
  }
  loading.value = allUsers.value.length === 0;
  try {
    await store.fetchUsers();
  } finally {
    loading.value = false;
  }
});

const activeFilter = ref('all');

// Synchronize tab filter with route query param (?tab=all|pending|approved)
watch(
  () => route.query.tab,
  (tab) => {
    const value = Array.isArray(tab) ? tab[0] : tab;
    if (value === 'pending') {
      activeFilter.value = 'pending';
    } else if (value === 'approved') {
      activeFilter.value = 'approved';
    } else {
      activeFilter.value = 'all';
    }
  },
  { immediate: true }
);

function setActiveFilter(tab) {
  activeFilter.value = tab;
  router.replace({
    query: {
      ...route.query,
      tab: tab === 'all' ? undefined : tab
    }
  });
}
const processingId = ref(null);
const userToDelete = ref(null);

const isAddModalOpen = ref(false);
const isSubmitting = ref(false);
const addError = ref('');

const addForm = reactive({
  name: '',
  email: '',
  phone: '',
  role: 'Staff Gudang',
  password: ''
});

const isEditModalOpen = ref(false);
const isEditing = ref(false);
const editError = ref('');

const editForm = reactive({
  id: null,
  name: '',
  email: '',
  phone: '',
  role: 'Staff Gudang',
  status: 'approved',
  password: ''
});

// Daftar sesuai tab. Pencarian (nama, email, no. WA, peran) & filter peran ada di toolbar tabel.
const filteredList = computed(() => {
  let list = allUsers.value;

  if (activeFilter.value === 'pending') {
    list = list.filter(u => u.status === 'pending');
  } else if (activeFilter.value === 'approved') {
    list = list.filter(u => u.status === 'approved');
  }

  return list;
});

// ---- Tampilan ----

const ROLE_OPTIONS = ['Admin SCM', 'Staff Gudang', 'Staff Finance', 'Staff SCM'];
const SUPER_ADMIN_EMAIL = 'admin@scm.corp';
const isSuperAdmin = user => user.email === SUPER_ADMIN_EMAIL;

// Label status sama seperti sebelumnya; status lain dianggap nonaktif
const STATUS = {
  approved: { label: 'Aktif', variant: 'success' },
  pending: { label: 'Menunggu ACC', variant: 'warning' },
  rejected: { label: 'Nonaktif', variant: 'destructive' },
};
const statusOf = user => STATUS[user.status] || STATUS.rejected;

const tabs = computed(() => [
  { value: 'all', label: 'Semua User', count: allUsers.value.length },
  { value: 'pending', label: 'Menunggu ACC', count: pendingUsers.value.length, attention: pendingUsers.value.length > 0 },
  { value: 'approved', label: 'Aktif', count: approvedUsers.value.length },
]);

// Urutan bawaan sama dengan urutan dari server (terbaru dulu)
const initialSorting = [{ id: 'registered_at', desc: true }];

const columns = [
  {
    id: 'name',
    accessorFn: user => user.name || '',
    sortingFn: 'text',
    enableHiding: false,
    // Tanpa w-full: sisa lebar dibagi rata ke semua kolom, jadi tidak ada celah besar setelah nama
    meta: { label: 'Nama', cellClass: 'min-w-[240px]' },
  },
  {
    id: 'phone',
    accessorFn: user => user.phone || '',
    enableSorting: false,
    meta: { label: 'No. WhatsApp' },
  },
  {
    id: 'role',
    accessorFn: user => user.role || '',
    sortingFn: 'text',
    filterFn: inSelection,
    meta: { label: 'Peran' },
  },
  {
    id: 'registered_at',
    accessorFn: user => parseDate(user.registered_at)?.getTime() || 0,
    sortDescFirst: true,
    meta: { label: 'Terdaftar' },
  },
  {
    id: 'status',
    accessorFn: user => user.status || '',
    meta: { label: 'Status' },
  },
  {
    id: 'actions',
    enableSorting: false,
    enableHiding: false,
    meta: { label: 'Aksi', srOnlyHeader: true, align: 'right', sticky: 'right', cellClass: 'w-12' },
  },
];

const facets = [{ column: 'role', title: 'Peran' }];

// Pencarian sama seperti sebelumnya: nama, email, no. WhatsApp, atau peran
function userMatchesSearch(row, _columnId, value) {
  const query = String(value || '').trim().toLowerCase();
  if (!query) return true;
  const u = row.original;
  return Boolean(
    (u.name && u.name.toLowerCase().includes(query)) ||
    (u.email && u.email.toLowerCase().includes(query)) ||
    (u.phone && String(u.phone).includes(query)) ||
    (u.role && u.role.toLowerCase().includes(query))
  );
}

const emptyState = computed(() => {
  if (filteredList.value.length) {
    return { title: 'Tidak ada data pengguna yang sesuai', description: 'Coba ubah kata kunci pencarian atau filter.' };
  }
  if (activeFilter.value === 'pending') {
    return { title: 'Tidak ada pengajuan tertunda', description: 'Semua pendaftaran akun baru telah diverifikasi dan diproses.' };
  }
  if (activeFilter.value === 'approved') {
    return { title: 'Belum ada user aktif', description: 'User yang sudah di-ACC akan tampil di sini.' };
  }
  return { title: 'Belum ada user', description: 'Tambahkan user baru lewat tombol Tambah User.' };
});

// Isi dialog hapus tetap terlihat selama animasi menutup
const deleteDisplay = ref(null);
watch(userToDelete, user => {
  if (user) deleteDisplay.value = user;
});
const isDeleting = computed(() => Boolean(userToDelete.value) && processingId.value === userToDelete.value.id);

function getInitials(name) {
  if (!name) return 'U';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

// ---- Aksi (tidak berubah) ----

async function handleApprove(user) {
  if (processingId.value) return;
  processingId.value = user.id;
  try {
    await store.approveUser(user.id);
  } finally {
    processingId.value = null;
  }
}

async function handleReject(user) {
  if (processingId.value) return;
  processingId.value = user.id;
  try {
    await store.rejectUser(user.id);
  } finally {
    processingId.value = null;
  }
}

function promptDelete(user) {
  if (processingId.value) return;
  userToDelete.value = user;
}

async function confirmDelete() {
  if (!userToDelete.value || processingId.value) return;
  const id = userToDelete.value.id;
  processingId.value = id;
  try {
    await store.deleteUser(id);
    userToDelete.value = null;
  } finally {
    processingId.value = null;
  }
}

function openAddModal() {
  addForm.name = '';
  addForm.email = '';
  addForm.phone = '';
  addForm.role = 'Staff Gudang';
  addForm.password = '';
  addError.value = '';
  isAddModalOpen.value = true;
}

async function submitAddUser() {
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  addError.value = '';

  try {
    const res = await store.createUser({ ...addForm });
    if (res.success) {
      isAddModalOpen.value = false;
    } else {
      addError.value = res.message || 'Gagal menambahkan user.';
    }
  } catch (e) {
    addError.value = 'Terjadi kesalahan sistem.';
  } finally {
    isSubmitting.value = false;
  }
}

function openEditModal(user) {
  editForm.id = user.id;
  editForm.name = user.name || '';
  editForm.email = user.email || '';
  editForm.phone = user.phone || '';
  editForm.role = user.role || 'Staff Gudang';
  editForm.status = user.status || 'approved';
  editForm.password = '';
  editError.value = '';
  isEditModalOpen.value = true;
}

async function submitEditUser() {
  if (isEditing.value) return;
  isEditing.value = true;
  editError.value = '';

  try {
    const payload = {
      name: editForm.name,
      email: editForm.email,
      phone: editForm.phone,
      role: editForm.role,
      status: editForm.status
    };
    if (editForm.password && editForm.password.trim().length >= 6) {
      payload.password = editForm.password.trim();
    }
    const res = await store.updateUser(editForm.id, payload);
    if (res.success) {
      isEditModalOpen.value = false;
    } else {
      editError.value = res.message || 'Gagal memperbarui pengguna.';
    }
  } catch (e) {
    editError.value = 'Terjadi kesalahan sistem.';
  } finally {
    isEditing.value = false;
  }
}
</script>
