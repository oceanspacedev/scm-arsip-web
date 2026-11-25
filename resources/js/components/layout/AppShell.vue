<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset class="bg-slate-50 dark:bg-graphite-page min-w-0 w-full max-w-full overflow-x-clip">
      <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border bg-white/95 dark:bg-graphite-page/95 backdrop-blur-md px-4 sm:px-6 w-full">
        <div class="flex items-center gap-2 min-w-0">
          <SidebarTrigger class="-ml-1 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white cursor-pointer shrink-0" />
          <Separator orientation="vertical" class="mr-2 h-4 bg-slate-200 dark:bg-slate-800 shrink-0" />

          <nav class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400 select-none truncate">
            <router-link to="/dashboard" class="hover:text-slate-900 dark:hover:text-slate-100 transition-colors shrink-0">
              SCM TaxVault
            </router-link>
            <ChevronRight class="w-4 h-4 text-slate-300 dark:text-slate-600 shrink-0" :stroke-width="1.5" />
            <span class="text-slate-900 dark:text-slate-100 font-medium truncate">{{ currentRouteName }}</span>
          </nav>
        </div>

        <div class="flex items-center gap-1 shrink-0">
          <button
            type="button"
            :class="headerButtonClass"
            :aria-label="isDark ? 'Beralih ke tema terang' : 'Beralih ke tema gelap'"
            :title="isDark ? 'Tema terang' : 'Tema gelap'"
            @click="toggleDark()"
          >
            <Sun v-if="isDark" class="w-4 h-4" :stroke-width="1.75" />
            <Moon v-else class="w-4 h-4" :stroke-width="1.75" />
          </button>
          <button
            type="button"
            :class="headerButtonClass"
            aria-label="Keluar"
            title="Keluar"
            @click="handleLogout"
          >
            <LogOut class="w-4 h-4" :stroke-width="1.75" />
          </button>
        </div>
      </header>

      <div class="flex-1 min-w-0 w-full max-w-full p-4 sm:p-6 lg:p-8 overflow-x-clip">
        <div class="w-full max-w-[1600px] mx-auto min-w-0">
          <router-view />
        </div>
      </div>
    </SidebarInset>

    <ImportExcelModal />
    <UserApprovalModal />
    <DocumentUploadPanel />
  </SidebarProvider>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useDark, useToggle } from '@vueuse/core';
import { ChevronRight, Sun, Moon, LogOut } from 'lucide-vue-next';
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import Separator from '@/components/ui/separator/Separator.vue';
import AppSidebar from './AppSidebar.vue';
import ImportExcelModal from '../programs/ImportExcelModal.vue';
import DocumentUploadPanel from '../programs/DocumentUploadPanel.vue';
import UserApprovalModal from '../auth/UserApprovalModal.vue';
import { useTaxStore } from '../../store/taxStore';

const route = useRoute();
const store = useTaxStore();

const isDark = useDark({
  selector: 'html',
  attribute: 'class',
  valueDark: 'dark',
  valueLight: '',
  storageKey: 'scm_taxvault_theme'
});
const toggleDark = useToggle(isDark);

const router = useRouter();

// Tombol ikon di pojok kanan atas (tema, keluar)
const headerButtonClass = 'h-8 w-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500';

async function handleLogout() {
  store.beginLogout();
  try {
    await router.replace({ name: 'login' });
  } finally {
    store.logout();
  }
}


const currentRouteName = computed(() => {
  const path = route.path;
  if (path === '/dashboard') return 'Dashboard';
  if (path === '/programs') return 'Arsip program';
  if (path.startsWith('/programs/')) return 'Detail program';
  if (path === '/history') return 'History';
  if (path === '/users') return 'Manajemen user';
  if (path === '/permissions') return 'Hak akses & role';
  if (path === '/master-data') return 'Data master';
  if (path === '/settings') return 'Pengaturan sistem';
  return route.name || 'Dokumen Pajak';
});
</script>
