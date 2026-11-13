<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArchiveBoxIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ChevronUpIcon,
  CircleStackIcon,
  ClockIcon,
  Cog6ToothIcon,
  HomeIcon,
  ShieldCheckIcon,
  UsersIcon,
} from '@heroicons/vue/24/outline'
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuBadge,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarRail,
  useSidebar,
} from '@/components/ui/sidebar'
import AppLogo from './AppLogo.vue'
import { useTaxStore } from '../../store/taxStore'

// Sidebar bergaya panel admin Filament: ikon Heroicons (outline), grup berlabel yang bisa dilipat,
// dan tombol di kepala sidebar untuk menciutkannya menjadi deretan ikon.
const route = useRoute()
const store = useTaxStore()
const { state, isMobile, toggleSidebar } = useSidebar()

const isAdmin = computed(() => store.isAdmin.value)
const pendingUsersCount = computed(() => store.pendingUsersCount.value)
const isCollapsed = computed(() => state.value === 'collapsed' && !isMobile.value)

const userTabs = computed(() => [
  { tab: 'all', label: 'Semua User', count: store.allUsers.value.length },
  { tab: 'pending', label: 'Menunggu ACC', count: pendingUsersCount.value, attention: pendingUsersCount.value > 0 },
  { tab: 'approved', label: 'User Aktif', count: store.allUsers.value.filter(u => u.status === 'approved').length },
])

const navGroups = computed(() => [
  {
    id: 'utama',
    items: [{ to: '/dashboard', label: 'Dashboard', icon: HomeIcon, match: path => path === '/dashboard' }],
  },
  {
    id: 'arsip',
    label: 'Arsip',
    items: [
      { to: '/programs', label: 'Arsip Program', icon: ArchiveBoxIcon, match: path => path.startsWith('/programs') },
      { to: '/history', label: 'History', icon: ClockIcon, match: path => path === '/history' },
    ],
  },
  isAdmin.value && {
    id: 'administrasi',
    label: 'Administrasi',
    items: [
      {
        to: { name: 'users', query: { tab: 'all' } },
        label: 'Manajemen User',
        icon: UsersIcon,
        match: path => path === '/users',
        badge: pendingUsersCount.value || null,
        children: userTabs.value,
      },
      { to: '/permissions', label: 'Hak Akses & Role', icon: ShieldCheckIcon, match: path => path === '/permissions' },
      { to: '/master-data', label: 'Data Master', icon: CircleStackIcon, match: path => path === '/master-data' },
    ],
  },
  isAdmin.value && {
    id: 'pengaturan',
    label: 'Pengaturan',
    items: [{ to: '/settings', label: 'Pengaturan Sistem', icon: Cog6ToothIcon, match: path => path === '/settings' }],
  },
].filter(Boolean))

// Grup yang dilipat diingat per browser
const GROUPS_KEY = 'scm_sidebar_grup_terlipat_v1'
function readFolded() {
  try {
    return new Set(JSON.parse(localStorage.getItem(GROUPS_KEY) || '[]'))
  } catch {
    return new Set()
  }
}
const foldedGroups = ref(readFolded())
watch(foldedGroups, value => {
  try {
    localStorage.setItem(GROUPS_KEY, JSON.stringify([...value]))
  } catch {
    // Penyimpanan browser tidak tersedia: lipatan grup hanya berlaku sampai halaman dimuat ulang
  }
}, { deep: true })

function isFolded(group) {
  return Boolean(group.label) && foldedGroups.value.has(group.id) && !isCollapsed.value
}
function toggleGroup(group) {
  const next = new Set(foldedGroups.value)
  if (next.has(group.id)) next.delete(group.id)
  else next.add(group.id)
  foldedGroups.value = next
}

function isUserTabActive(tab) {
  return route.path === '/users' && (route.query.tab || 'all') === tab
}

// Gaya item (mengikuti Filament): ikon abu-abu 24px, label abu gelap; item aktif berlatar abu-abu muda
const itemClass = [
  'h-9 gap-3 rounded-lg px-2 text-sm font-medium text-sidebar-foreground/80',
  '[&>svg]:size-6 [&>svg]:text-sidebar-foreground/40',
  'data-[active=true]:font-medium data-[active=true]:text-sidebar-accent-foreground data-[active=true]:[&>svg]:text-sidebar-accent-foreground',
  'group-data-[collapsible=icon]:!size-10 group-data-[collapsible=icon]:!p-2',
].join(' ')
</script>

<template>
  <Sidebar collapsible="icon">
    <!-- Kepala sidebar: logo A + nama aplikasi utuh, tombol ciutkan di kanan; setinggi top bar -->
    <SidebarHeader
      class="h-16 flex-row items-center gap-2 border-b border-sidebar-border bg-background px-3 py-0 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0"
    >
      <router-link
        v-if="!isCollapsed"
        to="/dashboard"
        class="flex min-w-0 flex-1 items-center gap-2.5 rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-sidebar-ring/50"
      >
        <AppLogo />
        <span class="grid min-w-0 leading-tight">
          <span class="text-sm font-semibold tracking-tight whitespace-nowrap">Supply Chain Management</span>
          <span class="truncate text-xs text-sidebar-foreground/60">Arsip Dokumen Pajak</span>
        </span>
      </router-link>
      <button
        v-if="!isMobile"
        type="button"
        class="flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-lg text-sidebar-foreground/50 transition-colors outline-none hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:ring-[3px] focus-visible:ring-sidebar-ring/50"
        :aria-label="isCollapsed ? 'Lebarkan menu' : 'Ciutkan menu'"
        :title="isCollapsed ? 'Lebarkan menu' : 'Ciutkan menu'"
        @click="toggleSidebar"
      >
        <ChevronRightIcon v-if="isCollapsed" class="size-5" />
        <ChevronLeftIcon v-else class="size-5" />
      </button>
    </SidebarHeader>

    <SidebarContent class="gap-6 px-3 py-6 group-data-[collapsible=icon]:gap-3 group-data-[collapsible=icon]:px-3">
      <SidebarGroup v-for="group in navGroups" :key="group.id" class="gap-1 p-0">
        <!-- Label grup + tombol lipat (disembunyikan saat sidebar diciutkan) -->
        <div v-if="group.label && !isCollapsed" class="flex h-8 items-center justify-between pr-1 pl-2">
          <span class="text-sm font-medium text-sidebar-foreground/60">{{ group.label }}</span>
          <button
            type="button"
            class="flex size-6 cursor-pointer items-center justify-center rounded-md text-sidebar-foreground/40 transition-colors outline-none hover:bg-sidebar-accent hover:text-sidebar-foreground focus-visible:ring-[3px] focus-visible:ring-sidebar-ring/50"
            :aria-expanded="!isFolded(group)"
            :aria-label="`${isFolded(group) ? 'Buka' : 'Lipat'} grup ${group.label}`"
            @click="toggleGroup(group)"
          >
            <ChevronUpIcon class="size-4 transition-transform duration-200" :class="{ 'rotate-180': isFolded(group) }" />
          </button>
        </div>
        <!-- Pemisah tipis antar grup saat sidebar diciutkan -->
        <div v-else-if="group.label" class="mx-2 border-t border-sidebar-border" aria-hidden="true" />

        <SidebarGroupContent v-show="!isFolded(group)">
          <SidebarMenu class="gap-1">
            <SidebarMenuItem v-for="item in group.items" :key="item.label">
              <SidebarMenuButton
                as-child
                :is-active="item.match(route.path)"
                :tooltip="item.label"
                :class="[itemClass, item.badge && 'pr-10']"
              >
                <router-link :to="item.to">
                  <component :is="item.icon" aria-hidden="true" />
                  <span>{{ item.label }}</span>
                </router-link>
              </SidebarMenuButton>

              <!-- Lencana jumlah yang menunggu ACC -->
              <SidebarMenuBadge
                v-if="item.badge"
                class="right-2 rounded-md bg-warning/10 px-1.5 text-warning ring-1 ring-warning/25 ring-inset peer-data-[size=default]/menu-button:top-2"
                :aria-label="`${item.badge} menunggu ACC`"
              >
                {{ item.badge }}
              </SidebarMenuBadge>

              <!-- Submenu (seperti item bertingkat Filament): tampil saat halamannya dibuka -->
              <ul
                v-if="item.children && item.match(route.path) && !isCollapsed"
                class="mt-1 flex flex-col gap-1"
                :aria-label="`Submenu ${item.label}`"
              >
                <li v-for="(child, index) in item.children" :key="child.tab">
                  <router-link
                    :to="{ name: 'users', query: { tab: child.tab } }"
                    class="flex h-9 items-center gap-3 rounded-lg px-2 text-sm font-medium outline-none transition-colors hover:bg-sidebar-accent focus-visible:ring-[3px] focus-visible:ring-sidebar-ring/50"
                    :class="isUserTabActive(child.tab) ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-sidebar-foreground/70'"
                    :aria-current="isUserTabActive(child.tab) ? 'page' : undefined"
                  >
                    <!-- Garis vertikal + titik penanda di kolom ikon -->
                    <span class="relative flex h-full w-6 shrink-0 items-center justify-center" aria-hidden="true">
                      <span
                        class="absolute w-px bg-sidebar-border"
                        :class="[index === 0 ? 'top-1/2' : '-top-1', index === item.children.length - 1 ? 'bottom-1/2' : '-bottom-1']"
                      />
                      <span
                        class="relative size-1.5 rounded-full"
                        :class="isUserTabActive(child.tab) ? 'bg-sidebar-accent-foreground' : 'bg-sidebar-foreground/30'"
                      />
                    </span>
                    <span class="min-w-0 flex-1 truncate">{{ child.label }}</span>
                    <span
                      class="shrink-0 text-xs tabular-nums"
                      :class="child.attention ? 'font-medium text-warning' : 'text-sidebar-foreground/50'"
                    >
                      {{ child.count }}
                    </span>
                  </router-link>
                </li>
              </ul>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>

    <!-- Akun, tema, dan Keluar ada di menu avatar kanan atas (seperti Filament) -->
    <SidebarRail />
  </Sidebar>
</template>
