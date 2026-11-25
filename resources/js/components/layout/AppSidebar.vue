<script setup>
import { computed, ref, watch, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  LayoutGrid,
  Archive,
  History as HistoryIcon,
  UsersRound,
  KeyRound,
  Layers,
  Settings,
} from 'lucide-vue-next'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuBadge,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
} from '@/components/ui/sidebar'
import { useTaxStore } from '../../store/taxStore'
import { useDocumentUploadQueue } from '../../store/documentUploadQueue'

const route = useRoute()
const store = useTaxStore()
const { counts: uploadCounts } = useDocumentUploadQueue()

const isAdmin = computed(() => store.isAdmin.value)
const pendingUsersCount = computed(() => store.pendingUsersCount.value)
const currentUser = computed(() => store.currentUser.value || {})

// Kelengkapan arsip: berapa program sudah punya ketiga dokumen wajib, dan per jenis dokumen
const DOC_TYPES = [
  { type: 'invoice', label: 'Invoice' },
  { type: 'faktur_pajak', label: 'Faktur pajak' },
  { type: 'mou', label: 'Memo/DO' },
]
const archiveStats = computed(() => {
  const programs = store.programs.value || []
  const docSets = programs.map(p => new Set((p.documents || []).map(d => d.document_type)))
  const complete = docSets.filter(set => DOC_TYPES.every(d => set.has(d.type))).length
  return {
    total: programs.length,
    complete,
    incomplete: programs.length - complete,
    percent: programs.length ? Math.round((complete / programs.length) * 100) : 0,
    perDoc: DOC_TYPES.map(d => ({ ...d, count: docSets.filter(set => set.has(d.type)).length })),
  }
})

// Unggahan otomatis yang menunggu program dipilih (History, hasil "Perlu dipilih")
const reviewCount = ref(0)
async function refreshReviewCount() {
  const result = await store.fetchDocumentLogs({ status: 'review', page: 1, perPage: 10 })
  if (result?.meta) reviewCount.value = result.meta.total
}
onMounted(refreshReviewCount)
// Diperbarui saat pindah halaman (mis. setelah memilih program di History) dan saat antrean unggah selesai
watch(() => route.path, refreshReviewCount)
watch(() => uploadCounts.value.finished + (uploadCounts.value.review || 0), refreshReviewCount)

// Menu utama untuk semua role; badge = jumlah yang perlu ditindaklanjuti
const mainItems = computed(() => [
  { to: '/dashboard', label: 'Dashboard', icon: LayoutGrid, match: path => path === '/dashboard' },
  {
    to: '/programs', label: 'Arsip program', icon: Archive, match: path => path.startsWith('/programs'),
    badge: archiveStats.value.incomplete, badgeTitle: `${archiveStats.value.incomplete} program dokumennya belum lengkap`,
  },
  {
    to: '/history', label: 'History', icon: HistoryIcon, match: path => path === '/history',
    badge: reviewCount.value, badgeTitle: `${reviewCount.value} unggahan menunggu program dipilih`,
  },
])

// Menu khusus Admin SCM
const adminItems = computed(() => [
  {
    to: '/users', label: 'Manajemen user', icon: UsersRound, match: path => path === '/users',
    badge: pendingUsersCount.value, badgeTitle: `${pendingUsersCount.value} pendaftar menunggu ACC`,
  },
  { to: '/permissions', label: 'Hak akses & role', icon: KeyRound, match: path => path === '/permissions' },
  { to: '/master-data', label: 'Data master', icon: Layers, match: path => path === '/master-data' },
  { to: '/settings', label: 'Pengaturan', icon: Settings, match: path => path === '/settings' },
])

const badgeClass = 'top-2 right-2 text-amber-600 dark:text-amber-400 peer-hover/menu-button:text-amber-600 dark:peer-hover/menu-button:text-amber-400 peer-data-[active=true]/menu-button:text-amber-600 dark:peer-data-[active=true]/menu-button:text-amber-400'

const initials = computed(() => {
  const parts = String(currentUser.value.name || '').trim().split(/\s+/).filter(Boolean)
  if (parts.length === 0) return '?'
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase()
})
</script>

<template>
  <Sidebar collapsible="icon">
    <!-- Nama aplikasi, sejajar dengan tinggi top bar (h-14); saat diciutkan cukup tulisan SCM -->
    <SidebarHeader class="h-14 justify-center border-b border-sidebar-border px-4 py-0 group-data-[collapsible=icon]:px-2">
      <router-link to="/dashboard" class="block min-w-0 leading-tight group-data-[collapsible=icon]:hidden">
        <span class="block truncate text-sm font-medium text-sidebar-accent-foreground">SCM TaxVault</span>
        <span class="block truncate text-xs text-sidebar-foreground/80">Arsip dokumen pajak</span>
      </router-link>
      <router-link
        to="/dashboard"
        class="hidden group-data-[collapsible=icon]:flex size-8 items-center justify-center rounded-lg border border-sidebar-border text-[11px] font-medium text-sidebar-accent-foreground"
        aria-label="SCM TaxVault"
      >
        SCM
      </router-link>
    </SidebarHeader>

    <SidebarContent class="gap-0 py-2">
      <SidebarGroup class="px-2 pt-2 pb-1">
        <SidebarGroupLabel class="h-7 px-2.5 text-sidebar-foreground/70">Arsip</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in mainItems" :key="item.to">
              <SidebarMenuButton as-child :is-active="item.match(route.path)" :tooltip="item.label" class="h-9">
                <router-link :to="item.to">
                  <component :is="item.icon" :stroke-width="1.75" />
                  <span>{{ item.label }}</span>
                </router-link>
              </SidebarMenuButton>
              <SidebarMenuBadge v-if="item.badge" :class="badgeClass" :title="item.badgeTitle">{{ item.badge }}</SidebarMenuBadge>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <SidebarGroup v-if="isAdmin" class="px-2 pt-4 pb-1">
        <SidebarGroupLabel class="h-7 px-2.5 text-sidebar-foreground/70">Administrasi</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu class="gap-0.5">
            <SidebarMenuItem v-for="item in adminItems" :key="item.to">
              <SidebarMenuButton as-child :is-active="item.match(route.path)" :tooltip="item.label" class="h-9">
                <router-link :to="item.to">
                  <component :is="item.icon" :stroke-width="1.75" />
                  <span>{{ item.label }}</span>
                </router-link>
              </SidebarMenuButton>
              <SidebarMenuBadge v-if="item.badge" :class="badgeClass" :title="item.badgeTitle">{{ item.badge }}</SidebarMenuBadge>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <!-- Kelengkapan arsip: ringkas, mengisi ruang kosong dengan informasi yang berguna -->
      <router-link
        v-if="archiveStats.total > 0"
        to="/programs"
        class="mt-auto mx-3 mb-2 block rounded-xl border border-sidebar-border p-3 hover:bg-sidebar-accent/60 transition-colors group-data-[collapsible=icon]:hidden"
        title="Buka arsip program"
      >
        <div class="flex items-baseline justify-between gap-2">
          <p class="text-xs text-sidebar-foreground/80">Kelengkapan arsip</p>
          <p class="text-xs tabular-nums text-sidebar-foreground/80">{{ archiveStats.percent }}%</p>
        </div>
        <p class="mt-1 text-sm text-sidebar-accent-foreground tabular-nums">
          {{ archiveStats.complete }} <span class="text-sidebar-foreground/80">dari</span> {{ archiveStats.total }} <span class="text-sidebar-foreground/80">program lengkap</span>
        </p>
        <div class="mt-2 h-1.5 rounded-full bg-sidebar-accent overflow-hidden">
          <div class="h-full rounded-full bg-blue-600 dark:bg-blue-500" :style="{ width: archiveStats.percent + '%' }"></div>
        </div>
        <dl class="mt-3 space-y-1.5">
          <div v-for="doc in archiveStats.perDoc" :key="doc.type" class="flex items-center justify-between gap-2 text-xs">
            <dt class="text-sidebar-foreground/80">{{ doc.label }}</dt>
            <dd class="tabular-nums text-sidebar-accent-foreground">{{ doc.count }}<span class="text-sidebar-foreground/60">/{{ archiveStats.total }}</span></dd>
          </div>
        </dl>
      </router-link>
    </SidebarContent>

    <!-- Pengguna yang sedang masuk (tombol keluar ada di pojok kanan atas) -->
    <SidebarFooter class="border-t border-sidebar-border p-2">
      <div class="flex items-center gap-3 rounded-lg px-2 py-1.5 group-data-[collapsible=icon]:px-0 group-data-[collapsible=icon]:justify-center">
        <span
          class="size-8 shrink-0 inline-flex items-center justify-center rounded-full bg-sidebar-accent text-xs font-medium text-sidebar-accent-foreground"
          :title="`${currentUser.name || 'Pengguna'} · ${currentUser.role || '-'}`"
        >
          {{ initials }}
        </span>
        <div class="min-w-0 flex-1 leading-tight group-data-[collapsible=icon]:hidden">
          <p class="truncate text-sm text-sidebar-accent-foreground" :title="currentUser.name">{{ currentUser.name || 'Pengguna' }}</p>
          <p class="truncate text-xs text-sidebar-foreground/80">{{ currentUser.role || '-' }}</p>
        </div>
      </div>
    </SidebarFooter>

    <SidebarRail />
  </Sidebar>
</template>
