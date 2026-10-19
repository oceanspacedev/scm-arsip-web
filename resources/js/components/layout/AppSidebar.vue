<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  LayoutGrid,
  Archive,
  History as HistoryIcon,
  UsersRound,
  KeyRound,
  Layers,
  Settings,
  LogOut,
  ChevronRight,
} from 'lucide-vue-next'
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubItem,
  SidebarMenuSubButton,
  SidebarRail,
} from '@/components/ui/sidebar'
import { useTaxStore } from '../../store/taxStore'

const route = useRoute()
const router = useRouter()
const store = useTaxStore()

const isAdmin = computed(() => store.isAdmin.value)
const allUsersCount = computed(() => store.allUsers.value.length)
const pendingUsersCount = computed(() => store.pendingUsersCount.value)
const approvedUsersCount = computed(() => store.allUsers.value.filter(u => u.status === 'approved').length)

// State for collapsible Manajemen User submenu (closed by default upon login)
const isUsersSubmenuOpen = ref(false)

watch(
  () => route.path,
  (newPath) => {
    if (newPath.startsWith('/users')) {
      isUsersSubmenuOpen.value = true
    } else {
      isUsersSubmenuOpen.value = false
    }
  },
  { immediate: true }
)

function handleUsersMenuClick() {
  isUsersSubmenuOpen.value = true
  router.push({ name: 'users', query: { tab: 'all' } })
}

function toggleUsersSubmenu(event) {
  event.stopPropagation()
  isUsersSubmenuOpen.value = !isUsersSubmenuOpen.value
}

async function handleLogout() {
  store.beginLogout()
  try {
    await router.replace({ name: 'login' })
  } finally {
    store.logout()
  }
}
</script>

<template>
  <Sidebar collapsible="icon">
    <!-- Header: nama aplikasi, sejajar dengan tinggi top bar (h-14) -->
    <SidebarHeader class="h-14 justify-center border-b border-sidebar-border px-3 py-0 group-data-[collapsible=icon]:px-2">
      <div class="px-1 leading-tight group-data-[collapsible=icon]:hidden">
        <p class="truncate text-sm font-semibold tracking-tight text-sidebar-accent-foreground">Supply Chain Management</p>
        <p class="truncate text-[11px] text-sidebar-foreground/70">Arsip Dokumen Pajak</p>
      </div>
      <div class="hidden group-data-[collapsible=icon]:flex size-8 items-center justify-center rounded-md border border-sidebar-border text-[10px] font-semibold text-sidebar-accent-foreground">
        SCM
      </div>
    </SidebarHeader>

    <!-- Navigation Content -->
    <SidebarContent class="pt-1">
      <SidebarGroup>
        <SidebarGroupLabel>Menu</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                :is-active="route.path === '/dashboard'"
                tooltip="Dashboard"
              >
                <router-link to="/dashboard">
                  <LayoutGrid />
                  <span>Dashboard</span>
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                :is-active="route.path.startsWith('/programs')"
                tooltip="Arsip Program"
              >
                <router-link to="/programs">
                  <Archive />
                  <span>Arsip Program</span>
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                :is-active="route.path === '/history'"
                tooltip="History"
              >
                <router-link to="/history">
                  <HistoryIcon />
                  <span>History</span>
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <!-- Logout untuk non-admin (tidak punya grup Administrasi) -->
            <SidebarMenuItem v-if="!isAdmin">
              <SidebarMenuButton tooltip="Logout" class="cursor-pointer" @click="handleLogout">
                <LogOut />
                <span>Logout</span>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <!-- Group: Administrasi (Visible for Admin) -->
      <SidebarGroup v-if="isAdmin">
        <SidebarGroupLabel>Administrasi</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <!-- Manajemen User (Collapsible Submenu) -->
            <SidebarMenuItem>
              <SidebarMenuButton
                tooltip="Manajemen User"
                :is-active="route.path === '/users'"
                class="cursor-pointer"
                @click="handleUsersMenuClick"
              >
                <!-- Ikon harus anak langsung: mode ikon menyembunyikan semua <div> di dalam tombol -->
                <UsersRound />
                <span class="truncate">Manajemen User</span>
                <div class="flex items-center gap-1.5 ml-auto group-data-[collapsible=icon]:hidden">
                  <span
                    v-if="pendingUsersCount > 0"
                    class="flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-950/70 px-1.5 text-[10px] font-medium text-amber-700 dark:text-amber-300"
                    title="Menunggu ACC"
                  >
                    {{ pendingUsersCount }}
                  </span>
                  <button
                    type="button"
                    class="p-0.5 rounded hover:bg-sidebar-accent cursor-pointer"
                    :aria-expanded="isUsersSubmenuOpen"
                    aria-label="Buka atau tutup submenu Manajemen User"
                    @click.stop="toggleUsersSubmenu"
                  >
                    <ChevronRight
                      class="size-3.5 text-sidebar-foreground/60 transition-transform duration-200"
                      :class="{ 'rotate-90': isUsersSubmenuOpen }"
                    />
                  </button>
                </div>
              </SidebarMenuButton>

              <SidebarMenuSub v-show="isUsersSubmenuOpen">
                <SidebarMenuSubItem>
                  <SidebarMenuSubButton
                    as-child
                    size="sm"
                    :is-active="route.path === '/users' && (!route.query.tab || route.query.tab === 'all')"
                  >
                    <router-link :to="{ name: 'users', query: { tab: 'all' } }" class="flex items-center justify-between w-full">
                      <span class="truncate">Semua User</span>
                      <span class="shrink-0 text-[11px] tabular-nums text-sidebar-foreground/60">{{ allUsersCount }}</span>
                    </router-link>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>

                <SidebarMenuSubItem>
                  <SidebarMenuSubButton
                    as-child
                    size="sm"
                    :is-active="route.path === '/users' && route.query.tab === 'pending'"
                  >
                    <router-link :to="{ name: 'users', query: { tab: 'pending' } }" class="flex items-center justify-between w-full">
                      <span class="truncate">Menunggu ACC</span>
                      <span
                        class="shrink-0 text-[11px] tabular-nums"
                        :class="pendingUsersCount > 0 ? 'font-medium text-amber-600 dark:text-amber-400' : 'text-sidebar-foreground/60'"
                      >
                        {{ pendingUsersCount }}
                      </span>
                    </router-link>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>

                <SidebarMenuSubItem>
                  <SidebarMenuSubButton
                    as-child
                    size="sm"
                    :is-active="route.path === '/users' && route.query.tab === 'approved'"
                  >
                    <router-link :to="{ name: 'users', query: { tab: 'approved' } }" class="flex items-center justify-between w-full">
                      <span class="truncate">User Aktif</span>
                      <span class="shrink-0 text-[11px] tabular-nums text-sidebar-foreground/60">{{ approvedUsersCount }}</span>
                    </router-link>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </SidebarMenuItem>

            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                :is-active="route.path === '/permissions'"
                tooltip="Hak Akses & Role"
              >
                <router-link to="/permissions">
                  <KeyRound />
                  <span>Hak Akses & Role</span>
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                :is-active="route.path === '/master-data'"
                tooltip="Data Master"
              >
                <router-link to="/master-data">
                  <Layers />
                  <span>Data Master</span>
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                :is-active="route.path === '/settings'"
                tooltip="Pengaturan"
              >
                <router-link to="/settings">
                  <Settings />
                  <span>Pengaturan</span>
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <SidebarMenuItem>
              <SidebarMenuButton tooltip="Logout" class="cursor-pointer" @click="handleLogout">
                <LogOut />
                <span>Logout</span>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>

    <!-- SidebarRail for collapsing & dragging -->
    <SidebarRail />
  </Sidebar>
</template>
