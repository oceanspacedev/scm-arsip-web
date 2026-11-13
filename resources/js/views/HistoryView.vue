<template>
  <div class="flex flex-col gap-6">
    <PageHeader title="History" description="Dokumen yang masuk ke arsip beserta hasilnya." />

    <DataTable
      :columns="columns"
      :data="logs"
      :loading="loading && logs.length === 0"
      :get-row-id="log => String(log.id)"
      :initial-sorting="[{ id: 'created_at', desc: true }]"
      :facets="facets"
      :date-filter="{ column: 'created_at', title: 'Tanggal' }"
      :global-filter-fn="matchesSearch"
      :page-sizes="[25, 50, 100]"
      search-placeholder="Cari berkas, program, nomor…"
      row-label="catatan"
      empty-title="Belum ada catatan"
      empty-description="Dokumen yang diunggah akan tercatat di sini."
    >
      <template #cell-created_at="{ row }">
        <p class="tabular-nums">{{ formatTanggal(row.created_at) }}</p>
        <p class="text-xs tabular-nums text-muted-foreground">{{ formatClock(row.created_at) }}</p>
      </template>

      <!-- Semua teks tampil utuh (boleh beberapa baris), tidak dipotong -->
      <template #cell-file_name="{ row }">
        <p class="leading-5 break-words">{{ row.file_name }}</p>
        <p class="mt-0.5 text-xs text-muted-foreground">{{ [TYPE[row.document_type], SOURCE[row.source]].filter(Boolean).join(' · ') }}</p>
      </template>

      <template #cell-program="{ row }">
        <router-link
          v-if="row.program"
          :to="`/programs/${row.program.id}`"
          class="block leading-5 break-words hover:underline"
        >
          {{ row.program.title }}
        </router-link>
        <span v-else class="text-muted-foreground">—</span>
        <p v-if="row.program?.company_name" class="mt-0.5 text-xs text-muted-foreground">{{ row.program.company_name }}</p>
      </template>

      <template #cell-status="{ row }">
        <Badge :variant="STATUS[row.status]?.variant || 'outline'">{{ STATUS[row.status]?.label || row.status }}</Badge>
        <p v-for="line in detailLines(row)" :key="line" class="mt-1 text-xs tabular-nums break-words text-muted-foreground">{{ line }}</p>
      </template>

      <template #cell-uploaded_by="{ value }">
        <span class="text-muted-foreground">{{ value || '—' }}</span>
      </template>
    </DataTable>

    <p v-if="truncated" class="text-xs text-muted-foreground">Menampilkan {{ formatNumber(logs.length) }} catatan terbaru.</p>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import DataTable from '@/components/data-table/DataTable.vue';
import { inDateRange, inSelection } from '@/components/data-table/utils';
import PageHeader from '@/components/layout/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { formatNumber, formatTanggal } from '@/lib/format';
import { useTaxStore } from '../store/taxStore';
import { useDocumentUploadQueue } from '../store/documentUploadQueue';

const STATUS = {
  matched: { label: 'Masuk', variant: 'success' },
  duplicate: { label: 'Sudah ada', variant: 'secondary' },
  review: { label: 'Perlu dipilih', variant: 'warning' },
  failed: { label: 'Gagal', variant: 'destructive' },
  skipped: { label: 'Dilewati', variant: 'outline' }
};
const SOURCE = { auto: 'Unggah otomatis', manual: 'Unggah manual', whms: 'WHMS' };
const TYPE = { invoice: 'Invoice', faktur_pajak: 'Faktur Pajak', mou: 'Memo/DO' };

// Semua halaman riwayat dimuat (maks. 20 halaman = 1.000 catatan terbaru), lalu dicari & difilter di tabel
const MAX_PAGES = 20;

const store = useTaxStore();
const { counts } = useDocumentUploadQueue();

const logs = ref([]);
const loading = ref(false);
const truncated = ref(false);

async function load() {
  loading.value = true;
  try {
    const first = await store.fetchDocumentLogs({ page: 1 });
    if (!first) return;
    const all = [...first.logs];
    const lastPage = Math.min(first.meta?.last_page || 1, MAX_PAGES);
    // Satu per satu: server lokal melayani satu request sekaligus
    for (let page = 2; page <= lastPage; page++) {
      const next = await store.fetchDocumentLogs({ page });
      if (!next) break;
      all.push(...next.logs);
    }
    logs.value = all;
    truncated.value = (first.meta?.last_page || 1) > MAX_PAGES;
  } finally {
    loading.value = false;
  }
}

// Unggahan di latar belakang yang selesai langsung terlihat di sini
watch(() => counts.value.finished + (counts.value.review || 0), () => load());

onMounted(load);

const timestamp = value => (value ? new Date(value).getTime() : 0);

const columns = [
  { id: 'created_at', accessorFn: log => timestamp(log.created_at), filterFn: inDateRange, sortDescFirst: true, meta: { label: 'Waktu', sticky: 'left' } },
  // Tanpa kolom w-full: sisa lebar dibagi ke Berkas, Program, dan Hasil sesuai panjang isinya
  { id: 'file_name', accessorFn: log => log.file_name || '', sortingFn: 'text', meta: { label: 'Berkas', cellClass: 'min-w-[240px] whitespace-normal' } },
  { id: 'program', accessorFn: log => log.program?.title || '', sortingFn: 'text', meta: { label: 'Program', cellClass: 'min-w-[200px] whitespace-normal' } },
  { id: 'status', accessorFn: log => log.status, filterFn: inSelection, meta: { label: 'Hasil', cellClass: 'min-w-[180px] max-w-[300px] whitespace-normal' } },
  { id: 'uploaded_by', accessorFn: log => log.uploaded_by || '', filterFn: inSelection, meta: { label: 'Oleh' } },
  { id: 'source', accessorFn: log => log.source, filterFn: inSelection, enableHiding: false, meta: { label: 'Sumber', filterOnly: true } },
  { id: 'document_type', accessorFn: log => log.document_type || '', filterFn: inSelection, enableHiding: false, meta: { label: 'Jenis', filterOnly: true } },
];

const facets = computed(() => [
  { column: 'status', title: 'Hasil', options: Object.entries(STATUS).map(([value, meta]) => ({ value, label: meta.label })) },
  { column: 'source', title: 'Sumber', options: Object.entries(SOURCE).map(([value, label]) => ({ value, label })) },
  { column: 'document_type', title: 'Jenis', options: Object.entries(TYPE).map(([value, label]) => ({ value, label })) },
  { column: 'uploaded_by', title: 'Oleh' },
]);

// Keterangan hasil per baris: pesan (gagal/perlu dipilih), atau dasar pencocokan + nomor dokumen (masuk)
function detailLines(log) {
  if (log.status !== 'matched') return log.message ? [log.message] : [];
  const number = log.number && `${log.document_type === 'faktur_pajak' ? 'No. Faktur' : 'No. Invoice'} ${log.number}`;
  return [log.reason, number !== log.reason && number].filter(Boolean);
}

function matchesSearch(row, _columnId, query) {
  const text = String(query || '').toLowerCase().trim();
  if (!text) return true;
  const log = row.original;
  return [log.file_name, log.program?.title, log.program?.company_name, log.number, log.uploaded_by, log.reason, log.message]
    .filter(Boolean)
    .join(' ')
    .toLowerCase()
    .includes(text);
}

function formatClock(value) {
  return value ? new Intl.DateTimeFormat('id-ID', { hour: '2-digit', minute: '2-digit' }).format(new Date(value)) : '';
}
</script>
