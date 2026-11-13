import { computed } from 'vue';
import { useColorMode } from '@vueuse/core';

// Satu sumber status tema untuk seluruh aplikasi: 'light' | 'dark' | 'auto' (ikuti pengaturan sistem).
// Disimpan di localStorage dengan kunci yang sama seperti sebelumnya, jadi pilihan lama tetap berlaku.
const mode = useColorMode({
    selector: 'html',
    attribute: 'class',
    modes: { light: '', dark: 'dark' },
    storageKey: 'scm_taxvault_theme',
});

/** Pilihan pengguna: 'light' | 'dark' | 'auto' */
export const themeMode = computed({
    get: () => mode.store.value,
    set: value => {
        mode.value = value;
    },
});

/** true bila tampilan sedang gelap (termasuk 'auto' saat sistem memakai mode gelap) */
export const isDark = computed({
    get: () => mode.state.value === 'dark',
    set: value => {
        mode.value = value ? 'dark' : 'light';
    },
});

export function toggleDark() {
    isDark.value = !isDark.value;
}
