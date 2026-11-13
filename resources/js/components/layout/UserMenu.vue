<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { DropdownMenuRadioGroup, DropdownMenuRadioItem } from 'reka-ui';
import { ArrowLeftStartOnRectangleIcon, ComputerDesktopIcon, MoonIcon, SunIcon } from '@heroicons/vue/24/outline';
import { UserCircleIcon } from '@heroicons/vue/24/solid';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { themeMode } from '@/lib/theme';
import { useTaxStore } from '../../store/taxStore';

// Menu akun di kanan atas (gaya Filament): avatar → nama, pilihan tema, Keluar
const router = useRouter();
const store = useTaxStore();

const user = computed(() => store.currentUser.value || {});
const initials = computed(() => {
  if (user.value.initials) return user.value.initials;
  return String(user.value.name || '?')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map(word => word[0].toUpperCase())
    .join('');
});

const THEMES = [
  { value: 'light', label: 'Mode terang', icon: SunIcon },
  { value: 'dark', label: 'Mode gelap', icon: MoonIcon },
  { value: 'auto', label: 'Ikuti sistem', icon: ComputerDesktopIcon },
];

const selectedTheme = computed({
  get: () => themeMode.value,
  set: value => { themeMode.value = value; },
});

async function logout() {
  store.beginLogout();
  try {
    await router.replace({ name: 'login' });
  } finally {
    store.logout();
  }
}
</script>

<template>
  <DropdownMenu :modal="false">
    <DropdownMenuTrigger as-child>
      <button
        type="button"
        class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground outline-none transition-shadow focus-visible:ring-[3px] focus-visible:ring-ring/50"
        :aria-label="`Menu akun ${user.name || ''}`.trim()"
      >
        {{ initials }}
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" :side-offset="8" class="w-64 p-0">
      <!-- Nama pengguna -->
      <div class="flex items-center gap-2 px-3 py-3">
        <UserCircleIcon class="size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
        <div class="min-w-0">
          <p class="truncate text-sm font-medium">{{ user.name }}</p>
          <p v-if="user.role" class="truncate text-xs text-muted-foreground">{{ user.role }}</p>
        </div>
      </div>

      <DropdownMenuSeparator class="m-0" />

      <!-- Pilihan tema: terang / gelap / ikuti sistem (menu tetap terbuka setelah memilih) -->
      <DropdownMenuRadioGroup v-model="selectedTheme" class="grid grid-cols-3 gap-1 p-1" aria-label="Tema tampilan">
        <DropdownMenuRadioItem
          v-for="theme in THEMES"
          :key="theme.value"
          :value="theme.value"
          :aria-label="theme.label"
          :title="theme.label"
          class="flex h-9 cursor-pointer items-center justify-center rounded-md text-muted-foreground outline-hidden transition-colors select-none data-[highlighted]:bg-accent/60 data-[highlighted]:text-foreground data-[state=checked]:bg-accent data-[state=checked]:text-foreground"
          @select.prevent
        >
          <component :is="theme.icon" class="size-5" aria-hidden="true" />
        </DropdownMenuRadioItem>
      </DropdownMenuRadioGroup>

      <DropdownMenuSeparator class="m-0" />

      <div class="p-1">
        <DropdownMenuItem class="gap-2 px-2 py-2" @select="logout">
          <ArrowLeftStartOnRectangleIcon class="size-5 text-muted-foreground" aria-hidden="true" />
          Keluar
        </DropdownMenuItem>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
