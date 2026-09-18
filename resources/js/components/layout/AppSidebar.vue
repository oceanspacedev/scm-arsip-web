<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  LayoutDashboard,
  FolderArchive,
  Users,
  Settings2,
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
  SidebarMenuBadge,
  SidebarMenuSub,
  SidebarMenuSubItem,
  SidebarMenuSubButton,
  SidebarRail,
} from '@/components/ui/sidebar'
import { useTaxStore } from '../../store/taxStore'

const route = useRoute()
const router = useRouter()
const store = useTaxStore()

const currentUser = computed(() => store.currentUser.value)
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
    <!-- Header: Workspace / App Title (Clean, no black square icon) -->
    <SidebarHeader class="border-b border-sidebar-border/40 py-3 px-3">
      <div class="flex items-center justify-between px-1 group-data-[collapsible=icon]:hidden">
        <div class="grid flex-1 text-left leading-tight">
          <span class="truncate font-semibold text-sm text-sidebar-foreground tracking-tight">SCM TaxVault</span>
          <span class="truncate text-[11px] text-muted-foreground">{{ currentUser?.name || 'Staff' }} · {{ currentUser?.role || 'Enterprise' }}</span>
        </div>
      </div>
    </SidebarHeader>

    <!-- Navigation Content -->
    <SidebarContent>
      <!-- Group: Platform -->
      <SidebarGroup>
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <!-- Dashboard -->
            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                :is-active="route.path === '/dashboard'"
                tooltip="Dashboard"
              >
                <router-link to="/dashboard">
                  <LayoutDashboard />
                  <span>Dashboard</span>
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <!-- Arsip Program -->
            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                :is-active="route.path.startsWith('/programs')"
                tooltip="Arsip Program"
              >
                <router-link to="/programs">
                  <FolderArchive />
                  <span>Arsip Program</span>
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <!-- Logout (untuk Non-Admin dekat Arsip Program) -->
            <SidebarMenuItem v-if="!isAdmin">
              <SidebarMenuButton
                class="cursor-pointer"
                @click="handleLogout"
              >
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
                class="cursor-pointer justify-between"
                @click="handleUsersMenuClick"
              >
                <div class="flex items-center gap-2 min-w-0">
                  <Users class="size-4 shrink-0" />
                  <span class="truncate">Manajemen User</span>
                </div>
                <div class="flex items-center gap-1.5 ml-auto group-data-[collapsible=icon]:hidden">
                  <span
                    v-if="pendingUsersCount > 0"
                    class="flex h-4.5 min-w-4.5 items-center justify-center rounded-full bg-amber-100 px-1.5 text-[10px] font-bold text-amber-900"
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
                  <!-- Semua User -->
                  <SidebarMenuSubItem>
                    <SidebarMenuSubButton
                      as-child
                      size="sm"
                      :is-active="route.path === '/users' && (!route.query.tab || route.query.tab === 'all')"
                    >
                      <router-link :to="{ name: 'users', query: { tab: 'all' } }" class="flex items-center justify-between w-full">
                        <span class="truncate">Semua User</span>
                        <span class="shrink-0 text-[10px] font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                          {{ allUsersCount }}
                        </span>
                      </router-link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>

                  <!-- Menunggu ACC -->
                  <SidebarMenuSubItem>
                    <SidebarMenuSubButton
                      as-child
                      size="sm"
                      :is-active="route.path === '/users' && route.query.tab === 'pending'"
                    >
                      <router-link :to="{ name: 'users', query: { tab: 'pending' } }" class="flex items-center justify-between w-full">
                        <span class="truncate">Menunggu ACC</span>
                        <span
                          class="shrink-0 text-[10px] font-medium px-1.5 py-0.5 rounded transition-colors"
                          :class="pendingUsersCount > 0 ? 'bg-amber-100 text-amber-900 dark:bg-amber-950/80 dark:text-amber-300 dark:border dark:border-amber-800/60 font-bold' : 'text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800'"
                        >
                          {{ pendingUsersCount }}
                        </span>
                      </router-link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>

                  <!-- User Aktif -->
                  <SidebarMenuSubItem>
                    <SidebarMenuSubButton
                      as-child
                      size="sm"
                      :is-active="route.path === '/users' && route.query.tab === 'approved'"
                    >
                      <router-link :to="{ name: 'users', query: { tab: 'approved' } }" class="flex items-center justify-between w-full">
                        <span class="truncate">User Aktif</span>
                        <span class="shrink-0 text-[10px] font-medium text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 px-1.5 py-0.5 rounded">
                          {{ approvedUsersCount }}
                        </span>
                      </router-link>
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                </SidebarMenuSub>
            </SidebarMenuItem>

            <!-- Pengaturan -->
            <SidebarMenuItem>
              <SidebarMenuButton
                as-child
                :is-active="route.path === '/settings'"
                tooltip="Pengaturan"
              >
                <router-link to="/settings">
                  <Settings2 />
                  <span>Pengaturan</span>
                </router-link>
              </SidebarMenuButton>
            </SidebarMenuItem>

            <!-- Logout (untuk Admin dekat Pengaturan) -->
            <SidebarMenuItem>
              <SidebarMenuButton
                class="cursor-pointer"
                @click="handleLogout"
              >
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
