<template>
  <div class="space-y-4 select-none">
    <div>
      <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">History</h1>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Dokumen yang masuk ke arsip beserta hasilnya</p>
    </div>

    <div class="flex flex-col sm:flex-row gap-2">
      <input
        v-model="search"
        type="text"
        placeholder="Cari berkas, program, nomor, atau pengunggah"
        class="h-8 w-full sm:max-w-sm px-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-200 placeholder:text-slate-400 focus:outline-none focus:border-slate-400"
      />
      <select
        v-model="status"
        class="h-8 px-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-slate-400"
      >
        <option value="">Semua status</option>
        <option v-for="(meta, key) in STATUS" :key="key" :value="key">{{ meta.label }}</option>
      </select>
      <select
        v-model="source"
        class="h-8 px-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-xs text-slate-700 dark:text-slate-200 focus:outline-none focus:border-slate-400"
      >
        <option value="">Semua sumber</option>
        <option v-for="(label, key) in SOURCE" :key="key" :value="key">{{ label }}</option>
      </select>
    </div>

    <section class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-xs">
          <thead>
            <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400">
              <th class="py-2.5 px-4 font-medium">Waktu</th>
              <th class="py-2.5 px-4 font-medium">Berkas</th>
              <th class="py-2.5 px-4 font-medium">Program</th>
              <th class="py-2.5 px-4 font-medium">Hasil</th>
              <th class="py-2.5 px-4 font-medium">Oleh</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
            <tr v-for="log in logs" :key="log.id" class="align-top hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
              <td class="py-3 px-4 whitespace-nowrap tabular-nums">
                <p class="text-slate-700 dark:text-slate-300">{{ formatDate(log.created_at) }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">{{ formatClock(log.created_at) }}</p>
              </td>
              <td class="py-3 px-4 max-w-[320px]">
                <!-- Tidak dipotong satu baris: nama berkas sering berawalan sama, bagian pembedanya di ujung -->
                <p class="line-clamp-3 break-words font-medium text-slate-800 dark:text-slate-200" :title="log.file_name">{{ log.file_name }}</p>
                <p class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">
                  {{ [TYPE[log.document_type], SOURCE[log.source]].filter(Boolean).join(' · ') }}
                </p>
              </td>
              <td class="py-3 px-4 max-w-[260px]">
                <router-link
                  v-if="log.program"
                  :to="`/programs/${log.program.id}`"
                  class="block truncate font-medium text-slate-800 dark:text-slate-200 hover:text-slate-950 dark:hover:text-white"
                  :title="log.program.title"
                >
                  {{ log.program.title }}
                </router-link>
                <span v-else class="text-slate-400 dark:text-slate-500">-</span>
                <p v-if="log.program?.company_name" class="mt-0.5 truncate text-[11px] text-slate-400 dark:text-slate-500">{{ log.program.company_name }}</p>
              </td>
              <td class="py-3 px-4 max-w-[300px]">
                <p class="inline-flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
                  <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="STATUS[log.status]?.dot"></span>
                  {{ STATUS[log.status]?.label || log.status }}
                </p>
                <p v-if="detail(log)" class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">{{ detail(log) }}</p>
              </td>
              <td class="py-3 px-4 whitespace-nowrap text-slate-500 dark:text-slate-400">{{ log.uploaded_by || '-' }}</td>
            </tr>

            <tr v-if="logs.length === 0">
              <td colspan="5" class="py-10 text-center text-slate-400 dark:text-slate-500">
                {{ loading ? 'Memuat…' : 'Belum ada dokumen yang masuk.' }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="meta.last_page > 1"
        class="flex items-center justify-between gap-3 px-4 py-2.5 border-t border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400"
      >
        <span>Halaman {{ meta.current_page }} dari {{ meta.last_page }} · {{ meta.total }} catatan</span>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="h-7 px-2.5 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="loading || meta.current_page <= 1"
            @click="load(meta.current_page - 1)"
          >
            Sebelumnya
          </button>
          <button
            type="button"
            class="h-7 px-2.5 rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            :disabled="loading || meta.current_page >= meta.last_page"
            @click="load(meta.current_page + 1)"
          >
            Berikutnya
          </button>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { watchDebounced } from '@vueuse/core';
import { useTaxStore } from '../store/taxStore';
import { useDocumentUploadQueue } from '../store/documentUploadQueue';

const STATUS = {
  matched: { label: 'Masuk', dot: 'bg-emerald-500' },
  duplicate: { label: 'Sudah ada', dot: 'bg-slate-400' },
  review: { label: 'Perlu dipilih', dot: 'bg-amber-500' },
  failed: { label: 'Gagal', dot: 'bg-rose-500' },
  skipped: { label: 'Dilewati', dot: 'bg-slate-300 dark:bg-slate-600' }
};
const SOURCE = { auto: 'Unggah otomatis', manual: 'Unggah manual', whms: 'WHMS' };
const TYPE = { invoice: 'Invoice', faktur_pajak: 'Faktur Pajak', mou: 'Memo/DO' };

const store = useTaxStore();
const { counts } = useDocumentUploadQueue();

const logs = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const search = ref('');
const status = ref('');
const source = ref('');

async function load(page = 1) {
  loading.value = true;
  try {
    const result = await store.fetchDocumentLogs({ status: status.value, source: source.value, q: search.value.trim(), page });
    if (result) {
      logs.value = result.logs;
      meta.value = result.meta;
    }
  } finally {
    loading.value = false;
  }
}

watch([status, source], () => load(1));
watchDebounced(search, () => load(1), { debounce: 300 });
// Unggahan di latar belakang yang selesai langsung terlihat di sini
watch(() => counts.value.finished + (counts.value.review || 0), () => load(meta.value.current_page));

onMounted(() => load(1));

function detail(log) {
  if (log.status !== 'matched') return log.message;
  const number = log.number && `${log.document_type === 'faktur_pajak' ? 'No. Faktur' : 'No. Invoice'} ${log.number}`;
  return [log.reason, number !== log.reason && number].filter(Boolean).join(' · ');
}

function formatDate(value) {
  return value ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';
}

function formatClock(value) {
  return value ? new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : '';
}
</script>
