<template>
  <div class="space-y-4">
    <div>
      <h1 class="text-xl sm:text-2xl font-medium text-slate-900 dark:text-slate-100">History</h1>
      <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
        <span class="text-slate-900 dark:text-slate-100 tabular-nums">{{ meta.total }}</span> dokumen masuk ke arsip beserta hasilnya
      </p>
    </div>

    <!-- Cari + filter hasil dan sumber -->
    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
      <div class="relative flex-1 sm:max-w-md">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" :stroke-width="1.75" />
        <input
          v-model="search"
          type="search"
          aria-label="Cari riwayat"
          placeholder="Cari berkas, program, nomor, atau pengunggah"
          class="w-full h-9 pl-9 pr-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors"
        />
      </div>
      <div class="grid grid-cols-2 sm:flex gap-2">
        <div class="sm:w-44">
          <SearchSelect v-model="status" aria-label="Hasil" :options="statusOptions" :active="!!status" border-class="border-slate-200 dark:border-slate-700" />
        </div>
        <div class="sm:w-44">
          <SearchSelect v-model="source" aria-label="Sumber" :options="sourceOptions" :active="!!source" border-class="border-slate-200 dark:border-slate-700" />
        </div>
      </div>
      <button
        v-if="search || status || source"
        type="button"
        class="h-9 px-2 self-start sm:self-auto text-sm text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 transition-colors cursor-pointer"
        @click="resetFilters"
      >
        Reset
      </button>
    </div>

    <!--
      Tabel: satu baris per sel dengan lebar kolom tetap, jadi muat selebar layar tanpa digeser.
      Teks yang terpotong tampil lengkap saat kursor diarahkan.
    -->
    <section class="bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[960px] table-fixed text-left text-[13px] border-collapse">
          <colgroup>
            <col class="w-[156px]" />
            <col />
            <col class="w-[112px]" />
            <col />
            <col class="w-[132px]" />
            <col />
            <col class="w-[148px]" />
          </colgroup>
          <thead>
            <tr class="bg-slate-100 dark:bg-[#202022] border-b border-slate-300 dark:border-slate-700 text-xs font-medium text-slate-600 dark:text-slate-300">
              <th :class="thClass">Waktu</th>
              <th :class="thClass">Berkas</th>
              <th :class="thClass">Jenis</th>
              <th :class="thClass">Program</th>
              <th :class="thClass">Hasil</th>
              <th :class="thClass">Keterangan</th>
              <th :class="thClass">Oleh</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(log, idx) in logs"
              :key="log.id"
              class="border-b border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200 transition-colors hover:bg-[#eef3fc] dark:hover:bg-[#242428]"
              :class="idx % 2 ? 'bg-slate-50 dark:bg-[#1d1d1f]' : 'bg-white dark:bg-graphite-card'"
            >
              <td :class="[tdClass, 'tabular-nums']" :title="formatFull(log.created_at)">
                {{ formatDate(log.created_at) }}<span class="text-slate-500 dark:text-slate-400">, {{ formatClock(log.created_at) }}</span>
              </td>
              <td :class="tdClass">
                <span class="block truncate font-medium text-slate-900 dark:text-slate-50" :title="`${log.file_name} · ${SOURCE_FULL[log.source] || log.source}`">{{ log.file_name }}</span>
              </td>
              <td :class="tdClass">
                <span class="block truncate">{{ TYPE[log.document_type] || '-' }}</span>
              </td>
              <td :class="tdClass">
                <router-link
                  v-if="log.program"
                  :to="`/programs/${log.program.id}`"
                  class="block truncate hover:underline underline-offset-2"
                  :title="log.program.title"
                >
                  {{ log.program.title }}
                </router-link>
                <span v-else class="text-slate-400 dark:text-slate-500">Belum ada program</span>
              </td>
              <td :class="tdClass">
                <span class="flex items-center gap-2 min-w-0">
                  <span class="w-2 h-2 rounded-full shrink-0" :class="STATUS[log.status]?.dot"></span>
                  <span class="truncate">{{ STATUS[log.status]?.label || log.status }}</span>
                </span>
              </td>
              <td :class="tdClass">
                <span class="block truncate text-slate-600 dark:text-slate-300" :title="detail(log) || ''">{{ detail(log) || '-' }}</span>
              </td>
              <td :class="tdClass">
                <span class="block truncate text-slate-600 dark:text-slate-300" :title="log.uploaded_by || ''">{{ log.uploaded_by || '-' }}</span>
              </td>
            </tr>

            <tr v-if="logs.length === 0">
              <td colspan="7" class="py-16 text-center text-sm text-slate-500 dark:text-slate-400">
                {{ loading ? 'Memuat…' : (search || status || source ? 'Tidak ada catatan yang cocok' : 'Belum ada dokumen yang masuk') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <TablePagination
        :page="meta.current_page"
        :page-size="pageSize"
        :total="meta.total"
        item-label="catatan"
        @update:page="load"
        @update:page-size="(size) => { pageSize = size; }"
      />
    </section>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { watchDebounced } from '@vueuse/core';
import { Search } from 'lucide-vue-next';
import { useTaxStore } from '../store/taxStore';
import { useDocumentUploadQueue } from '../store/documentUploadQueue';
import TablePagination from '../components/ui/TablePagination.vue';
import SearchSelect from '../components/ui/SearchSelect.vue';

const STATUS = {
  matched: { label: 'Masuk', dot: 'bg-emerald-500' },
  duplicate: { label: 'Sudah ada', dot: 'bg-slate-400' },
  review: { label: 'Perlu dipilih', dot: 'bg-amber-500' },
  failed: { label: 'Gagal', dot: 'bg-rose-500' },
  skipped: { label: 'Dilewati', dot: 'bg-slate-300 dark:bg-slate-600' }
};
const SOURCE_FULL = { auto: 'Unggah otomatis', manual: 'Unggah manual', whms: 'Dari WHMS' };
const TYPE = { invoice: 'Invoice', faktur_pajak: 'Faktur pajak', mou: 'Memo/DO' };

const statusOptions = [{ value: '', label: 'Semua hasil' }, ...Object.entries(STATUS).map(([value, s]) => ({ value, label: s.label }))];
const sourceOptions = [
  { value: '', label: 'Semua sumber' },
  { value: 'auto', label: 'Unggah otomatis' },
  { value: 'manual', label: 'Unggah manual' },
  { value: 'whms', label: 'WHMS' },
];

const thClass = 'h-11 px-3 font-medium';
const tdClass = 'h-12 px-3 whitespace-nowrap overflow-hidden';

const store = useTaxStore();
const { counts } = useDocumentUploadQueue();

const logs = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0 });
const loading = ref(false);
const search = ref('');
const status = ref('');
const source = ref('');
const pageSize = ref(25);

async function load(page = 1) {
  loading.value = true;
  try {
    const result = await store.fetchDocumentLogs({
      status: status.value,
      source: source.value,
      q: search.value.trim(),
      page,
      perPage: pageSize.value,
    });
    if (result) {
      logs.value = result.logs;
      meta.value = result.meta;
    }
  } finally {
    loading.value = false;
  }
}

// Ganti jumlah baris: TablePagination mengirim update:page-size lalu update:page(1), jadi load(1) sudah terpanggil
watch([status, source], () => load(1));
watchDebounced(search, () => load(1), { debounce: 300 });
// Unggahan di latar belakang yang selesai langsung terlihat di sini
watch(() => counts.value.finished + (counts.value.review || 0), () => load(meta.value.current_page));

onMounted(() => load(1));

function resetFilters() {
  search.value = '';
  status.value = '';
  source.value = '';
}

function formatFull(value) {
  return value ? new Intl.DateTimeFormat('id-ID', { dateStyle: 'full', timeStyle: 'short' }).format(new Date(value)) : '';
}

function detail(log) {
  if (log.status !== 'matched') return log.message;
  const NUMBER_LABEL = { faktur_pajak: 'No. Faktur', invoice: 'No. Invoice', mou: 'No. Memo/DO' };
  const number = log.number && `${NUMBER_LABEL[log.document_type] || 'No.'} ${log.number}`;
  return [log.reason, number !== log.reason && number].filter(Boolean).join(' · ');
}

function formatDate(value) {
  return value ? new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(value)) : '-';
}

function formatClock(value) {
  return value ? new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : '';
}
</script>
