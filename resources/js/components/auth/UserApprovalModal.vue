<template>
  <Dialog
    :open="isOpen"
    title="Persetujuan Akun Pengguna (ACC Admin)"
    description="Verifikasi dan berikan izin akses untuk pengajuan akun baru."
    max-width="max-w-2xl"
    @update:open="onOpenChange"
  >
    <Tabs v-model="activeTab" class="flex flex-col gap-4">
      <TabsList aria-label="Daftar akun">
        <TabsTrigger value="pending">
          Menunggu Persetujuan
          <span :class="['text-xs tabular-nums', pendingUsers.length > 0 ? 'text-warning' : 'text-muted-foreground']">
            {{ pendingUsers.length }}
          </span>
        </TabsTrigger>
        <TabsTrigger value="all">
          Semua Akun
          <span class="text-xs tabular-nums text-muted-foreground">{{ allUsers.length }}</span>
        </TabsTrigger>
      </TabsList>

      <!-- Tab 1: Menunggu persetujuan -->
      <TabsContent value="pending" class="rounded-lg outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50">
        <div v-if="pendingUsers.length === 0" class="flex flex-col items-center justify-center gap-2 py-12 text-center">
          <CheckCircle2 class="size-8 text-muted-foreground" />
          <p class="font-medium">Tidak ada pengajuan tertunda</p>
          <p class="max-w-sm text-muted-foreground">Semua pendaftaran akun baru telah diverifikasi dan diproses.</p>
        </div>

        <ul v-else class="divide-y rounded-lg border">
          <li
            v-for="user in pendingUsers"
            :key="user.id"
            class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
          >
            <div class="min-w-0 space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <p>{{ user.name }}</p>
                <Badge v-if="user.role" variant="secondary">{{ user.role }}</Badge>
              </div>
              <p class="text-xs break-words text-muted-foreground tabular-nums">{{ contactLine(user, true) }}</p>
            </div>

            <div class="flex shrink-0 items-center gap-2 self-end sm:self-center">
              <Button variant="outline" size="sm" :disabled="processingId === user.id" @click="rejectUser(user.id)">
                Tolak
              </Button>
              <Button variant="outline" size="sm" :disabled="processingId === user.id" @click="approveUser(user.id)">
                {{ processingId === user.id ? 'Memproses…' : 'Setujui (ACC)' }}
              </Button>
              <DropdownMenu :modal="false">
                <DropdownMenuTrigger as-child>
                  <Button
                    variant="ghost"
                    size="icon-sm"
                    :disabled="processingId === user.id"
                    :aria-label="`Aksi lain untuk ${user.name}`"
                  >
                    <MoreHorizontal />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-40">
                  <DropdownMenuItem variant="destructive" @select="handleDelete(user)">Hapus</DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </li>
        </ul>
      </TabsContent>

      <!-- Tab 2: Semua akun -->
      <TabsContent value="all" class="rounded-lg outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50">
        <div class="overflow-hidden rounded-lg border">
          <Table>
            <TableHeader>
              <TableRow class="hover:bg-transparent">
                <TableHead>Nama & Kontak</TableHead>
                <TableHead>Role</TableHead>
                <TableHead>Status</TableHead>
                <TableHead class="w-12"><span class="sr-only">Aksi</span></TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              <TableRow v-for="user in allUsers" :key="user.id">
                <TableCell>
                  <p>{{ user.name }}</p>
                  <p class="text-xs text-muted-foreground tabular-nums">{{ contactLine(user) }}</p>
                </TableCell>
                <TableCell>
                  <Badge v-if="user.role" variant="secondary">{{ user.role }}</Badge>
                  <span v-else class="text-muted-foreground">—</span>
                </TableCell>
                <TableCell>
                  <Badge :variant="statusOf(user).variant">{{ statusOf(user).label }}</Badge>
                </TableCell>
                <TableCell class="text-right">
                  <span v-if="user.email === SUPER_ADMIN_EMAIL" class="text-xs text-muted-foreground">Super Admin</span>
                  <DropdownMenu v-else :modal="false">
                    <DropdownMenuTrigger as-child>
                      <Button
                        variant="ghost"
                        size="icon-sm"
                        :disabled="processingId === user.id"
                        :aria-label="`Aksi untuk ${user.name}`"
                      >
                        <MoreHorizontal />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-40">
                      <DropdownMenuItem v-if="user.status !== 'approved'" @select="approveUser(user.id)">
                        Setujui (ACC)
                      </DropdownMenuItem>
                      <DropdownMenuItem v-if="user.status !== 'rejected'" @select="rejectUser(user.id)">Tolak</DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem variant="destructive" @select="handleDelete(user)">Hapus</DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </TableCell>
              </TableRow>

              <TableRow v-if="allUsers.length === 0" class="hover:bg-transparent">
                <TableCell colspan="4" class="h-24 text-center text-muted-foreground">Belum ada akun pengguna.</TableCell>
              </TableRow>
            </TableBody>
          </Table>
        </div>
      </TabsContent>
    </Tabs>

    <template #footer>
      <p class="mr-auto text-xs text-muted-foreground tabular-nums">
        {{ pendingUsers.length }} akun menunggu persetujuan
      </p>
      <Button type="button" variant="outline" @click="store.closeApprovalModal()">Tutup</Button>
    </template>
  </Dialog>

  <!-- Konfirmasi hapus (tanpa alert bawaan browser) -->
  <AlertDialog :open="Boolean(userToDelete)" @update:open="open => !open && cancelDelete()">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>Hapus akun pengguna?</AlertDialogTitle>
        <AlertDialogDescription>
          Apakah Anda yakin ingin menghapus akun
          <span class="font-medium text-foreground">{{ deleteDisplay?.name }}</span>? Data akun ini akan dihapus secara
          permanen dari sistem dan tidak dapat dipulihkan.
        </AlertDialogDescription>
      </AlertDialogHeader>

      <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-1 rounded-lg border p-3 text-sm">
        <dt class="text-muted-foreground">Email</dt>
        <dd class="truncate" :title="deleteDisplay?.email">{{ deleteDisplay?.email || '—' }}</dd>
        <dt class="text-muted-foreground">Role</dt>
        <dd class="truncate">{{ deleteDisplay?.role || '—' }}</dd>
        <template v-if="deleteDisplay?.phone">
          <dt class="text-muted-foreground">WhatsApp</dt>
          <dd class="truncate tabular-nums">{{ deleteDisplay.phone }}</dd>
        </template>
      </dl>

      <AlertDialogFooter>
        <AlertDialogCancel :disabled="isDeleting">Batal</AlertDialogCancel>
        <!-- Tombol biasa (bukan AlertDialogAction) supaya dialog tetap terbuka selama proses hapus -->
        <Button variant="destructive" :disabled="isDeleting" @click="executeDelete">
          {{ isDeleting ? 'Menghapus…' : 'Ya, Hapus' }}
        </Button>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { CheckCircle2, MoreHorizontal } from 'lucide-vue-next';
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { formatTanggal } from '@/lib/format';
import { useTaxStore } from '../../store/taxStore';

const store = useTaxStore();

const isOpen = computed(() => store.isApprovalModalOpen.value);
const activeTab = ref('pending');
const processingId = ref(null);
const userToDelete = ref(null);

const allUsers = computed(() => store.allUsers.value);
const pendingUsers = computed(() => store.pendingUsers.value);

// ---- Tampilan ----

const SUPER_ADMIN_EMAIL = 'admin@scm.corp';

// Label status sama seperti sebelumnya; status lain dianggap ditolak
const STATUS = {
  approved: { label: 'Disetujui', variant: 'success' },
  pending: { label: 'Menunggu', variant: 'warning' },
  rejected: { label: 'Ditolak', variant: 'destructive' },
};
const statusOf = user => STATUS[user.status] || STATUS.rejected;

/** "email · no. WA" (+ tanggal daftar) tanpa pemisah ganda bila ada yang kosong */
function contactLine(user, withDate = false) {
  const date = withDate ? formatTanggal(user.registered_at) : '';
  return [user.email, user.phone, date !== '—' && date].filter(Boolean).join(' · ');
}

// Isi konfirmasi hapus tetap terlihat selama animasi menutup
const deleteDisplay = ref(null);
watch(userToDelete, user => {
  if (user) deleteDisplay.value = user;
});
const isDeleting = computed(() => Boolean(userToDelete.value) && processingId.value === userToDelete.value.id);

// Escape saat konfirmasi hapus terbuka cukup menutup konfirmasinya, bukan jendela ini
function onOpenChange(open) {
  if (!open && !userToDelete.value) store.closeApprovalModal();
}

// ---- Aksi (tidak berubah) ----

async function approveUser(id) {
  if (processingId.value) return;
  processingId.value = id;
  try {
    await store.approveUser(id);
  } finally {
    processingId.value = null;
  }
}

async function rejectUser(id) {
  if (processingId.value) return;
  processingId.value = id;
  try {
    await store.rejectUser(id);
  } finally {
    processingId.value = null;
  }
}

function handleDelete(user) {
  if (processingId.value) return;
  userToDelete.value = user;
}

function cancelDelete() {
  if (processingId.value) return;
  userToDelete.value = null;
}

async function executeDelete() {
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
</script>
