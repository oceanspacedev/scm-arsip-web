<template>
  <div class="space-y-6 max-w-4xl">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-[#17201E] dark:text-slate-100">
        Pengaturan Sistem
      </h1>
      <p class="text-sm text-[#66736F] dark:text-slate-400 mt-1">
        Konfigurasi parameter perpajakan, audit compliance, tampilan tema, dan pengelolaan data SCM TaxVault.
      </p>
    </div>

    <!-- Section: Tampilan & Tema (Dark Mode / Light Mode) -->
    <div class="bg-white dark:bg-[#111827] rounded-xl border border-[#DDE4E1] dark:border-slate-800 shadow-2xs overflow-hidden">
      <div class="p-5 border-b border-[#DDE4E1] dark:border-slate-800">
        <h3 class="text-sm font-semibold text-[#17201E] dark:text-slate-100">
          Tema & Tampilan Antarmuka
        </h3>
        <p class="text-xs text-[#66736F] dark:text-slate-400 mt-0.5">
          Pilih preferensi mode tampilan yang nyaman untuk mata saat bekerja
        </p>
      </div>
      <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Light Mode Option -->
          <button
            type="button"
            class="p-4 rounded-xl border text-left transition-all cursor-pointer flex items-center gap-3.5"
            :class="!isDark
              ? 'border-blue-600 bg-blue-50/40 dark:bg-blue-950/20 ring-1 ring-blue-600'
              : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 bg-slate-50/50 dark:bg-slate-900/40'"
            @click="setTheme(false)"
          >
            <div class="w-10 h-10 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center shrink-0">
              <Sun class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between">
                <span class="font-semibold text-xs text-slate-900 dark:text-slate-100">Mode Terang (Light)</span>
                <span v-if="!isDark" class="w-2 h-2 rounded-full bg-blue-600"></span>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Tampilan bersih dengan latar belakang putih elegan
              </p>
            </div>
          </button>

          <!-- Dark Mode Option -->
          <button
            type="button"
            class="p-4 rounded-xl border text-left transition-all cursor-pointer flex items-center gap-3.5"
            :class="isDark
              ? 'border-blue-600 bg-blue-50/40 dark:bg-blue-950/40 ring-1 ring-blue-600'
              : 'border-slate-200 dark:border-slate-700 hover:border-slate-300 dark:hover:border-slate-600 bg-slate-50/50 dark:bg-slate-900/40'"
            @click="setTheme(true)"
          >
            <div class="w-10 h-10 rounded-lg bg-slate-800 text-blue-400 flex items-center justify-center shrink-0 border border-slate-700">
              <Moon class="w-5 h-5" />
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex items-center justify-between">
                <span class="font-semibold text-xs text-slate-900 dark:text-slate-100">Mode Gelap (Dark)</span>
                <span v-if="isDark" class="w-2 h-2 rounded-full bg-blue-600"></span>
              </div>
              <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Nuansa slate gelap kontras tinggi yang ramah mata
              </p>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Section 1: Tax Rate Configuration -->
    <div class="bg-white dark:bg-[#111827] rounded-xl border border-[#DDE4E1] dark:border-slate-800 shadow-2xs overflow-hidden">
      <div class="p-5 border-b border-[#DDE4E1] dark:border-slate-800">
        <h3 class="text-sm font-semibold text-[#17201E] dark:text-slate-100">
          Parameter Tarif PPN & Aturan Perpajakan
        </h3>
        <p class="text-xs text-[#66736F] dark:text-slate-400 mt-0.5">
          Pengaturan default perhitungan otomatis Pajak Pertambahan Nilai pada tagihan program SCM
        </p>
      </div>
      <div class="p-5 space-y-4 text-xs">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
          <div>
            <label class="font-semibold text-[#17201E] dark:text-slate-100 block">Tarif Standar PPN (%)</label>
            <span class="text-[#66736F] dark:text-slate-400">Sesuai UU HPP No. 7 Tahun 2021</span>
          </div>
          <div class="sm:col-span-2 max-w-xs">
            <select class="w-full bg-white dark:bg-slate-900 text-[#17201E] dark:text-slate-100 text-xs rounded-md border border-[#DDE4E1] dark:border-slate-700 px-3 py-2 focus:outline-none">
              <option value="11">11% (Tarif Berlaku Saat Ini)</option>
              <option value="12">12% (Kesiapan Penyesuaian Regulasi Baru)</option>
            </select>
          </div>
        </div>

        <div class="border-t border-[#EBEFEF] dark:border-slate-800 pt-4 grid grid-cols-1 sm:grid-cols-3 gap-4 items-center">
          <div>
            <label class="font-semibold text-[#17201E] dark:text-slate-100 block">Validasi Kode Faktur Pajak</label>
            <span class="text-[#66736F] dark:text-slate-400">Format 16 digit e-Faktur DJP</span>
          </div>
          <div class="sm:col-span-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-[#F0FDF4] dark:bg-emerald-950/60 text-[#15803D] dark:text-emerald-300 rounded border border-[#BBF7D0] dark:border-emerald-800/60 font-medium">
              Aktif - Format 010.xxx-xx.xxxxxxxx
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Section 2: Document Compliance Rules -->
    <div class="bg-white dark:bg-[#111827] rounded-xl border border-[#DDE4E1] dark:border-slate-800 shadow-2xs overflow-hidden">
      <div class="p-5 border-b border-[#DDE4E1] dark:border-slate-800">
        <h3 class="text-sm font-semibold text-[#17201E] dark:text-slate-100">
          Syarat Kepatuhan Dokumen (Audit Checklist)
        </h3>
        <p class="text-xs text-[#66736F] dark:text-slate-400 mt-0.5">
          3 Dokumen wajib yang menjadi tolok ukur kesiapan audit eksternal & internal
        </p>
      </div>
      <div class="p-5 divide-y divide-[#EBEFEF] dark:divide-slate-800 text-xs">
        <div class="py-2.5 flex items-center justify-between">
          <div>
            <p class="font-semibold text-[#17201E] dark:text-slate-100">1. Commercial Invoice</p>
            <p class="text-[#66736F] dark:text-slate-400">Bukti tagihan resmi dari supplier berbadan hukum lengkap dengan rincian termin.</p>
          </div>
          <span class="px-2 py-0.5 rounded bg-[#F4F6F5] dark:bg-slate-800 text-[#17201E] dark:text-slate-300 border border-[#DDE4E1] dark:border-slate-700 font-mono">Wajib</span>
        </div>
        <div class="py-2.5 flex items-center justify-between">
          <div>
            <p class="font-semibold text-[#17201E] dark:text-slate-100">2. Faktur Pajak (e-Faktur DJP)</p>
            <p class="text-[#66736F] dark:text-slate-400">Faktur pajak masukan yang telah diapprove dan terekonsiliasi dengan DJP Online.</p>
          </div>
          <span class="px-2 py-0.5 rounded bg-[#F4F6F5] dark:bg-slate-800 text-[#17201E] dark:text-slate-300 border border-[#DDE4E1] dark:border-slate-700 font-mono">Wajib</span>
        </div>
        <div class="py-2.5 flex items-center justify-between">
          <div>
            <p class="font-semibold text-[#17201E] dark:text-slate-100">3. Memo / DO Perjanjian Kerjasama</p>
            <p class="text-[#66736F] dark:text-slate-400">Surat perintah kerja, delivery order (DO), purchase order, atau dasar hukum pengadaan barang/jasa SCM.</p>
          </div>
          <span class="px-2 py-0.5 rounded bg-[#F4F6F5] dark:bg-slate-800 text-[#17201E] dark:text-slate-300 border border-[#DDE4E1] dark:border-slate-700 font-mono">Wajib</span>
        </div>
      </div>
    </div>

    <!-- Section 3: Data Management & Demo Settings -->
    <div class="bg-white dark:bg-[#111827] rounded-xl border border-[#DDE4E1] dark:border-slate-800 shadow-2xs overflow-hidden">
      <div class="p-5 border-b border-[#DDE4E1] dark:border-slate-800">
        <h3 class="text-sm font-semibold text-[#17201E] dark:text-slate-100">
          Manajemen Data Uji Coba & Akses Demo
        </h3>
        <p class="text-xs text-[#66736F] dark:text-slate-400 mt-0.5">
          Pengaturan akses akun demo login cepat dan reset dataset program SCM
        </p>
      </div>

      <div class="p-5 divide-y divide-[#EBEFEF] dark:divide-slate-800 text-xs space-y-4">
        <!-- Setting 1: Tampilkan Akun Demo ON / OFF -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-2">
              <span class="font-semibold text-[#17201E] dark:text-slate-100">Tampilkan Akun Demo di Login</span>
              <span
                class="px-2 py-0.5 rounded-full text-[10px] font-semibold transition-colors"
                :class="showDemoAccounts ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60' : 'bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700'"
              >
                {{ showDemoAccounts ? 'Aktif (ON)' : 'Nonaktif (OFF)' }}
              </span>
            </div>
            <p class="text-[#66736F] dark:text-slate-400 mt-0.5">
              Tampilkan tombol pemilihan cepat akun simulasi (Admin SCM, Tim Pajak, Staf SCM) pada form login.
            </p>
          </div>
          <button
            type="button"
            role="switch"
            :aria-checked="showDemoAccounts"
            @click="toggleDemoAccounts"
            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden"
            :class="showDemoAccounts ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'"
            title="Nyalakan / Matikan Akun Demo di Login"
          >
            <span
              aria-hidden="true"
              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-sm ring-0 transition duration-200 ease-in-out"
              :class="showDemoAccounts ? 'translate-x-5' : 'translate-x-0'"
            />
          </button>
        </div>

        <!-- Setting 2: Reset Data (Hapus Semua) -->
        <div class="pt-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div>
            <p class="font-semibold text-[#17201E] dark:text-slate-100">Hapus & Kosongkan Semua Data</p>
            <p class="text-[#66736F] dark:text-slate-400 mt-0.5">
              Hapus seluruh data arsip program, berkas lampiran, dan data hasil impor secara total (data menjadi kosong).
            </p>
          </div>
          <button
            type="button"
            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-md border border-rose-200 dark:border-rose-900/60 bg-rose-50/60 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-950/60 text-rose-700 dark:text-rose-300 font-semibold text-xs transition-colors cursor-pointer shrink-0"
            @click="isResetModalOpen = true"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Hapus Semua Data</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Reset Data -->
    <Teleport to="body">
      <div
        v-if="isResetModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="!isResetting && (isResetModalOpen = false)"
      >
        <div class="bg-white dark:bg-[#111827] rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-100 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-150">
          <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-4 border border-rose-100 dark:border-rose-900/50">
            <RotateCcw class="w-6 h-6" :class="{ 'animate-spin': isResetting }" />
          </div>

          <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 text-center mb-1">
            Hapus Semua Data?
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 text-center leading-relaxed mb-5">
            Apakah Anda yakin ingin menghapus <strong class="text-slate-700 dark:text-slate-200">SELURUH data</strong> arsip program, berkas lampiran, dan riwayat dokumen secara permanen? Data akan dikosongkan total dari sistem.
          </p>

          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              :disabled="isResetting"
              class="w-full py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs transition-colors cursor-pointer disabled:opacity-50"
              @click="isResetModalOpen = false"
            >
              Batal
            </button>
            <button
              type="button"
              :disabled="isResetting"
              class="w-full py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs transition-colors cursor-pointer shadow-sm flex items-center justify-center gap-1.5 disabled:opacity-75"
              @click="executeReset"
            >
              <RotateCcw class="w-3.5 h-3.5" :class="{ 'animate-spin': isResetting }" />
              <span>{{ isResetting ? 'Menghapus...' : 'Ya, Hapus Semua' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { RotateCcw, Sun, Moon } from 'lucide-vue-next';
import { useDark } from '@vueuse/core';
import { useTaxStore } from '../store/taxStore';

const isDark = useDark({
  storageKey: 'scm_taxvault_theme',
  valueDark: 'dark',
  valueLight: ''
});

function setTheme(val) {
  isDark.value = val;
}

const store = useTaxStore();
const isResetModalOpen = ref(false);

const showDemoAccounts = computed(() => store.showDemoAccounts.value);
const isResetting = computed(() => store.isResetting.value);

async function toggleDemoAccounts() {
  await store.setDemoAccountsVisibility(!showDemoAccounts.value);
}

async function executeReset() {
  const res = await store.resetEntireSystemData();
  if (res && res.success) {
    isResetModalOpen.value = false;
  }
}
</script>
