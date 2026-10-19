<template>
  <div class="space-y-6 max-w-4xl">
    <!-- Header -->
    <div>
      <h1 class="text-2xl font-bold tracking-tight text-[#17201E] dark:text-slate-100">
        Pengaturan Sistem
      </h1>
      <p class="text-sm text-[#66736F] dark:text-slate-400 mt-1">
        Konfigurasi parameter perpajakan, audit compliance, dan tampilan tema SCM TaxVault.
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
  </div>
</template>

<script setup>
import { Sun, Moon } from 'lucide-vue-next';
import { useDark } from '@vueuse/core';

const isDark = useDark({
  storageKey: 'scm_taxvault_theme',
  valueDark: 'dark',
  valueLight: ''
});

function setTheme(val) {
  isDark.value = val;
}
</script>
