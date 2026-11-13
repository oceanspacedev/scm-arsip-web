<template>
  <SidebarProvider :default-open="sidebarDefaultOpen">
    <AppSidebar />
    <SidebarInset class="min-w-0 bg-page">
      <!-- Top bar setinggi kepala sidebar (h-16). Tombol menu hanya di layar kecil; di desktop ada di kepala sidebar. -->
      <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-2 border-b bg-background/95 px-4 backdrop-blur supports-[backdrop-filter]:bg-background/80 md:px-6">
        <SidebarTrigger class="-ml-1 md:hidden" />
        <Separator orientation="vertical" class="mr-1 h-4 md:hidden" />
        <Breadcrumb :items="breadcrumbs" class="flex-1" />
        <!-- Menu akun (gaya Filament): tema terang/gelap/sistem & Keluar -->
        <UserMenu />
      </header>

      <div class="mx-auto w-full max-w-screen-2xl min-w-0 flex-1 p-4 md:p-6">
        <router-view />
      </div>
    </SidebarInset>

    <ImportExcelModal />
    <UserApprovalModal />
    <DocumentUploadPanel />
  </SidebarProvider>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { SidebarInset, SidebarProvider, SidebarTrigger } from '@/components/ui/sidebar';
import { SIDEBAR_COOKIE_NAME } from '@/components/ui/sidebar/utils';
import Separator from '@/components/ui/separator/Separator.vue';
import { Breadcrumb } from '@/components/ui/breadcrumb';
import AppSidebar from './AppSidebar.vue';
import UserMenu from './UserMenu.vue';
import ImportExcelModal from '../programs/ImportExcelModal.vue';
import DocumentUploadPanel from '../programs/DocumentUploadPanel.vue';
import UserApprovalModal from '../auth/UserApprovalModal.vue';

const route = useRoute();

// Sidebar terbuka penuh di layar ≥1366px; di bawahnya mulai sebagai deretan ikon supaya tabel muat.
// Pilihan terakhir pengguna (tombol < / > di kepala sidebar, atau Ctrl+B) diingat lewat cookie.
function initialSidebarOpen() {
  if (typeof window === 'undefined') return true;
  const saved = document.cookie.match(new RegExp(`(?:^|;\\s*)${SIDEBAR_COOKIE_NAME}=(true|false)`));
  if (saved) return saved[1] === 'true';
  return window.innerWidth >= 1366;
}
const sidebarDefaultOpen = initialSidebarOpen();

const PAGE_TITLES = {
  '/dashboard': 'Dashboard',
  '/programs': 'Arsip Program',
  '/history': 'History',
  '/users': 'Manajemen User',
  '/permissions': 'Hak Akses & Role',
  '/master-data': 'Data Master',
  '/settings': 'Pengaturan',
};

const breadcrumbs = computed(() => {
  const items = [{ label: 'TaxVault', to: '/dashboard' }];
  if (route.path.startsWith('/programs/')) {
    items.push({ label: 'Arsip Program', to: '/programs' }, { label: 'Detail Program' });
  } else {
    items.push({ label: PAGE_TITLES[route.path] || String(route.name || 'Dokumen Pajak') });
  }
  return items;
});
</script>
