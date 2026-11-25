<template>
  <div class="space-y-6 max-w-4xl">
    <div>
      <h1 class="text-xl sm:text-2xl font-medium text-slate-900 dark:text-slate-100">Pengaturan sistem</h1>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        Tema tampilan, tarif PPN, dan dokumen wajib untuk kesiapan audit
      </p>
    </div>

    <!-- Tema -->
    <section :class="sectionClass">
      <div :class="sectionHeaderClass">
        <h2 :class="sectionTitleClass">Tema tampilan</h2>
        <p :class="sectionDescClass">Berlaku di perangkat ini</p>
      </div>
      <div class="p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" role="radiogroup" aria-label="Tema tampilan">
          <button
            v-for="option in themeOptions"
            :key="option.value"
            type="button"
            role="radio"
            :aria-checked="isDark === option.value"
            class="flex items-center gap-3 p-4 rounded-lg border text-left cursor-pointer transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
            :class="isDark === option.value
              ? 'border-slate-900 dark:border-slate-300 bg-slate-50 dark:bg-slate-800'
              : 'border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800/60'"
            @click="setTheme(option.value)"
          >
            <component :is="option.icon" class="w-4 h-4 shrink-0 text-slate-500 dark:text-slate-400" :stroke-width="1.75" />
            <div class="min-w-0 flex-1">
              <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ option.label }}</p>
              <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ option.desc }}</p>
            </div>
            <span
              class="size-4 shrink-0 rounded-full border flex items-center justify-center"
              :class="isDark === option.value ? 'border-blue-600 dark:border-blue-400' : 'border-slate-300 dark:border-slate-600'"
            >
              <span v-if="isDark === option.value" class="size-2 rounded-full bg-blue-600 dark:bg-blue-400"></span>
            </span>
          </button>
        </div>
      </div>
    </section>

    <!-- Tarif PPN -->
    <section :class="sectionClass">
      <div :class="sectionHeaderClass">
        <h2 :class="sectionTitleClass">Tarif PPN dan aturan pajak</h2>
        <p :class="sectionDescClass">Nilai awal untuk menghitung PPN pada tagihan program SCM</p>
      </div>
      <div class="divide-y divide-slate-200 dark:divide-slate-800">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center px-5 py-4">
          <div>
            <label for="ppn-rate" class="block text-sm font-medium text-slate-900 dark:text-slate-100">Tarif standar PPN</label>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Sesuai UU HPP No. 7 Tahun 2021</p>
          </div>
          <div class="sm:col-span-2 max-w-xs">
            <select
              id="ppn-rate"
              class="w-full h-9 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 cursor-pointer"
            >
              <option value="11">11% (berlaku saat ini)</option>
              <option value="12">12% (persiapan regulasi baru)</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-center px-5 py-4">
          <div>
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">Validasi nomor faktur pajak</p>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Format 16 digit e-Faktur DJP</p>
          </div>
          <div class="sm:col-span-2 flex items-center gap-2 text-sm text-slate-700 dark:text-slate-300">
            <span class="size-1.5 rounded-full bg-emerald-500"></span>
            <span>Aktif</span>
            <span class="text-slate-400 dark:text-slate-500">·</span>
            <span class="tabular-nums text-slate-500 dark:text-slate-400">010.xxx-xx.xxxxxxxx</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Dokumen wajib -->
    <section :class="sectionClass">
      <div :class="sectionHeaderClass">
        <h2 :class="sectionTitleClass">Dokumen wajib audit</h2>
        <p :class="sectionDescClass">Program dianggap lengkap bila ketiga dokumen ini sudah diunggah</p>
      </div>
      <ol class="divide-y divide-slate-200 dark:divide-slate-800">
        <li v-for="(doc, idx) in requiredDocs" :key="doc.title" class="flex items-start gap-4 px-5 py-4">
          <span class="w-4 shrink-0 text-sm tabular-nums text-slate-400 dark:text-slate-500">{{ idx + 1 }}</span>
          <div class="min-w-0 flex-1">
            <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ doc.title }}</p>
            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ doc.desc }}</p>
          </div>
          <span class="shrink-0 text-xs text-slate-500 dark:text-slate-400">Wajib</span>
        </li>
      </ol>
    </section>
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

const sectionClass = 'bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden';
const sectionHeaderClass = 'px-5 py-4 border-b border-slate-200 dark:border-slate-800';
const sectionTitleClass = 'text-sm font-medium text-slate-900 dark:text-slate-100';
const sectionDescClass = 'mt-1 text-xs text-slate-500 dark:text-slate-400';

const themeOptions = [
  { value: false, icon: Sun, label: 'Terang', desc: 'Latar putih, cocok untuk ruangan terang' },
  { value: true, icon: Moon, label: 'Gelap', desc: 'Latar abu gelap, nyaman untuk kerja lama' },
];

const requiredDocs = [
  { title: 'Invoice', desc: 'Tagihan resmi dari supplier berbadan hukum, lengkap dengan rincian termin' },
  { title: 'Faktur pajak (e-Faktur DJP)', desc: 'Faktur pajak masukan yang sudah disetujui dan terekonsiliasi dengan DJP Online' },
  { title: 'Memo / DO', desc: 'Surat perintah kerja, delivery order, purchase order, atau dasar pengadaan barang/jasa SCM' },
];
</script>
