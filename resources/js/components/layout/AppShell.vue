<template>
  <SidebarProvider>
    <AppSidebar />
    <SidebarInset class="bg-[#F8FAFC] dark:bg-[#090D16] min-w-0 w-full max-w-full overflow-x-clip transition-colors">
      <header class="sticky top-0 z-20 flex h-14 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border bg-white/95 dark:bg-[#0F172A]/95 backdrop-blur-md px-4 w-full transition-[width,height,background-color,border-color] ease-linear">
        <div class="flex items-center gap-2 min-w-0">
          <SidebarTrigger class="-ml-1 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white cursor-pointer shrink-0" />
          <Separator orientation="vertical" class="mr-2 h-4 bg-slate-200 dark:bg-slate-800 shrink-0" />

          <nav class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 font-medium select-none truncate">
            <router-link to="/dashboard" class="hover:text-slate-800 dark:hover:text-slate-200 transition-colors shrink-0">
              TaxVault
            </router-link>
            <ChevronRight class="w-3.5 h-3.5 text-slate-400 dark:text-slate-600 shrink-0" />
            <span class="text-slate-900 dark:text-slate-100 font-semibold truncate">{{ currentRouteName }}</span>
          </nav>
        </div>

        <div class="flex items-center gap-2 shrink-0">
          <button
            type="button"
            class="h-8 w-8 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 hover:bg-slate-100 dark:hover:bg-slate-700/80 text-slate-600 dark:text-slate-300 flex items-center justify-center transition-colors cursor-pointer focus:outline-hidden"
            :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
            @click="toggleDark()"
          >
            <Sun v-if="isDark" class="w-4 h-4 text-amber-400 transition-transform duration-200" />
            <Moon v-else class="w-4 h-4 text-slate-600 transition-transform duration-200" />
          </button>

          <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 select-none">
            <span>{{ userRole }}</span>
          </div>
        </div>
      </header>

      <div class="flex-1 min-w-0 w-full max-w-full p-4 sm:p-6 lg:p-7 overflow-x-clip">
        <div class="w-full max-w-[1600px] mx-auto min-w-0">
          <router-view />
        </div>
      </div>
    </SidebarInset>

    <ImportExcelModal />
    <UserApprovalModal />
  </SidebarProvider>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { useDark, useToggle } from '@vueuse/core';
import { ChevronRight, Sun, Moon } from 'lucide-vue-next';
import { SidebarProvider, SidebarInset, SidebarTrigger } from '@/components/ui/sidebar';
import Separator from '@/components/ui/separator/Separator.vue';
import AppSidebar from './AppSidebar.vue';
import ImportExcelModal from '../programs/ImportExcelModal.vue';
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

const userRole = computed(() => store.currentUser.value?.role || 'Tamu');

const currentRouteName = computed(() => {
  const path = route.path;
  if (path === '/dashboard') return 'Dashboard';
  if (path === '/programs') return 'Arsip Program';
  if (path.startsWith('/programs/')) return 'Detail Program';
  if (path === '/users') return 'Manajemen User';
  if (path === '/settings') return 'Pengaturan Sistem';
  return route.name || 'Dokumen Pajak';
});
</script>
