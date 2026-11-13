<script setup>
import { computed } from 'vue';
import { MoreHorizontal } from 'lucide-vue-next';
import DataTable from '@/components/data-table/DataTable.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { formatNpwp, formatRupiah, formatTanggal } from '@/lib/format';
import { cn } from '@/lib/utils';
import { useTaxStore } from '../../store/taxStore';
import {
  DOC_TYPES,
  HIDDEN_BY_DEFAULT,
  buildProgramColumns,
  buildProgramFacets,
  findDocument,
  formatPaymentStatus,
  paymentBadgeVariant,
  programBrand,
  programMatchesSearch,
} from './programTable';

defineProps({
  programs: { type: Array, required: true },
  loading: { type: Boolean, default: false },
});
const emit = defineEmits(['open', 'edit', 'delete', 'document', 'filtered']);

const store = useTaxStore();
const permissions = computed(() => ({
  master: store.canViewMasterColumns.value,
  finance: store.canViewFinanceColumns.value,
}));
const columns = computed(() => buildProgramColumns(permissions.value));
const facets = computed(() => buildProgramFacets(permissions.value));
const canEdit = computed(() => store.canEditProgram.value);
const canDelete = computed(() => store.isAdmin.value);
const initialSorting = [{ id: 'program_date', desc: true }];

function docHint(program, doc) {
  if (findDocument(program, doc.type)) return `${doc.label} ada. Klik untuk melihat.`;
  return store.canUploadDoc(doc.type) ? `${doc.label} belum ada. Klik untuk mengunggah.` : `${doc.label} belum ada.`;
}
</script>

<template>
  <DataTable
    :columns="columns"
    :data="programs"
    :loading="loading"
    :get-row-id="program => String(program.id)"
    :initial-sorting="initialSorting"
    :initial-column-visibility="HIDDEN_BY_DEFAULT"
    :facets="facets"
    :date-filter="{ column: 'program_date', title: 'Tanggal' }"
    :global-filter-fn="programMatchesSearch"
    search-placeholder="Cari program, supplier, no. invoice…"
    row-label="program"
    row-class="h-16"
    clickable-rows
    storage-key="scm_arsip_program_kolom_v1"
    empty-title="Tidak ada program"
    empty-description="Coba ubah kata kunci pencarian atau filter."
    @row-click="emit('open', $event)"
    @filtered="emit('filtered', $event)"
  >
    <template #cell-program_date="{ row }">
      <span class="tabular-nums">{{ formatTanggal(row.program_date) }}</span>
    </template>

    <template #cell-po_sj="{ value }">
      <span class="block max-w-[150px] truncate tabular-nums" :title="value">{{ value }}</span>
    </template>

    <!-- Nama program selalu tampil utuh (boleh beberapa baris), tidak dipotong -->
    <template #cell-program_name="{ row }">
      <span class="block leading-5 break-words">{{ row.program_name }}</span>
    </template>

    <template #cell-supplier="{ row }">
      <p class="leading-5 break-words">{{ row.supplier || '—' }}</p>
      <p class="text-xs tabular-nums text-muted-foreground">{{ formatNpwp(row.npwp) || 'NPWP —' }}</p>
    </template>

    <template #cell-brand="{ row }">
      <div class="flex items-center gap-1">
        <Badge variant="secondary">{{ programBrand(row) }}</Badge>
        <Badge v-if="row.kode_gudang" variant="outline" class="tabular-nums">{{ row.kode_gudang }}</Badge>
      </div>
    </template>

    <template #cell-category="{ value }">
      <span class="block max-w-[160px] truncate" :title="value">{{ value || '—' }}</span>
    </template>

    <template #cell-company="{ value }">
      <span class="block max-w-[180px] truncate" :title="value">{{ value || '—' }}</span>
    </template>

    <template #cell-invoice_number="{ value }">
      <span class="tabular-nums">{{ value || '—' }}</span>
    </template>

    <template #cell-faktur="{ row }">
      <div v-if="row.faktur_number" class="max-w-[180px]">
        <p class="truncate tabular-nums">{{ row.faktur_number }}</p>
        <p class="text-xs tabular-nums text-muted-foreground">{{ formatTanggal(row.faktur_date) }}</p>
      </div>
      <span v-else class="text-muted-foreground">—</span>
    </template>

    <template #cell-dpp="{ value }">
      <span class="tabular-nums">{{ formatRupiah(value) }}</span>
    </template>
    <template #cell-ppn="{ value }">
      <span class="tabular-nums">{{ formatRupiah(value) }}</span>
    </template>
    <template #cell-total_invoice="{ value }">
      <span class="tabular-nums">{{ formatRupiah(value) }}</span>
    </template>

    <template #cell-payment_status="{ row }">
      <Badge :variant="paymentBadgeVariant(row.payment_status)">{{ formatPaymentStatus(row.payment_status) }}</Badge>
    </template>

    <template #cell-documents="{ row }">
      <div class="flex items-center justify-center gap-1" @click.stop>
        <Tooltip v-for="doc in DOC_TYPES" :key="doc.type" :delay-duration="300">
          <TooltipTrigger as-child>
            <button
              type="button"
              :aria-label="docHint(row, doc)"
              :class="cn(
                'inline-flex h-6 w-7 cursor-pointer items-center justify-center rounded-md border text-xs font-medium transition-colors outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50',
                findDocument(row, doc.type)
                  ? 'border-transparent bg-secondary text-secondary-foreground hover:bg-accent'
                  : 'border-dashed text-muted-foreground hover:border-ring hover:text-foreground',
              )"
              @click="emit('document', { program: row, type: doc.type })"
            >
              {{ doc.short }}
            </button>
          </TooltipTrigger>
          <TooltipContent>{{ docHint(row, doc) }}</TooltipContent>
        </Tooltip>
      </div>
    </template>

    <template #cell-actions="{ row }">
      <div class="flex justify-end" @click.stop>
        <DropdownMenu :modal="false">
          <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="icon-sm" :aria-label="`Aksi untuk ${row.program_name}`">
              <MoreHorizontal />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuItem @select="emit('open', row)">Lihat</DropdownMenuItem>
            <DropdownMenuItem v-if="canEdit" @select="emit('edit', row)">Edit</DropdownMenuItem>
            <template v-if="canDelete">
              <DropdownMenuSeparator />
              <DropdownMenuItem variant="destructive" @select="emit('delete', row)">Hapus</DropdownMenuItem>
            </template>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </template>
  </DataTable>
</template>
