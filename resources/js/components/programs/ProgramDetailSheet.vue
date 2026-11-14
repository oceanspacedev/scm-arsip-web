<script setup>
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { formatNpwp, formatRupiah, formatTanggal } from '@/lib/format';
import { getPphLabel, useTaxStore } from '../../store/taxStore';
import {
  DOC_TYPES,
  documentCount,
  findDocument,
  formatPaymentStatus,
  normalizePayStatus,
  paymentBadgeVariant,
  programBrand,
  programCompany,
  programPoSj,
} from './programTable';

// Ringkasan satu program (klik baris di Arsip Program). Data diambil dari store supaya selalu terbaru.
const props = defineProps({
  open: { type: Boolean, default: false },
  programId: { type: String, default: null },
});
const emit = defineEmits(['update:open', 'edit', 'document']);

const router = useRouter();
const store = useTaxStore();

const program = computed(() => store.programs.value.find(p => String(p.id) === props.programId) || null);
const canViewMaster = computed(() => store.canViewMasterColumns.value);
const canViewFinance = computed(() => store.canViewFinanceColumns.value);
const canEdit = computed(() => store.canEditProgram.value);
const canEditPayment = computed(() => store.canEditFinance.value || store.isAdmin.value);

// Pilihan status payment dari Data Master, sama seperti di form edit
const paymentOptions = computed(() => {
  const master = store.masterPaymentStatuses.value.length
    ? store.masterPaymentStatuses.value
    : ['Waiting Payment', 'CBD (Cash Before Delivery)', 'Tempo', 'Paid (Lunas)'];
  const options = master.map(label => ({ value: normalizePayStatus(label), label }));
  const current = normalizePayStatus(program.value?.payment_status);
  if (program.value && !options.some(option => option.value === current)) {
    options.unshift({ value: current, label: formatPaymentStatus(current) });
  }
  return options.filter((option, index) => options.findIndex(o => o.value === option.value) === index);
});

function changePayment(value) {
  if (program.value && value) store.updatePaymentStatus(program.value.id, value);
}

function openDetailPage() {
  emit('update:open', false);
  router.push(`/programs/${program.value.id}`);
}
</script>

<template>
  <Sheet :open="open" @update:open="value => emit('update:open', value)">
    <SheetContent side="right" class="flex w-full flex-col gap-0 p-0 sm:max-w-md md:max-w-lg lg:max-w-xl">
      <template v-if="program">
        <!-- Header -->
        <SheetHeader class="gap-1 border-b px-6 py-4 pr-12 text-left">
          <div class="flex flex-wrap items-center gap-1.5 pb-0.5">
            <Badge variant="secondary" class="font-medium text-xs">{{ programBrand(program) }}</Badge>
            <Badge v-if="program.kode_gudang" variant="outline" class="tabular-nums font-mono text-xs">{{ program.kode_gudang }}</Badge>
          </div>
          <SheetTitle class="leading-snug text-balance text-base font-semibold sm:text-lg">{{ program.program_name }}</SheetTitle>
          <SheetDescription class="tabular-nums text-xs text-muted-foreground">
            {{ programPoSj(program) }} · {{ formatTanggal(program.program_date) }}
          </SheetDescription>
        </SheetHeader>

        <!-- Body: 3 kartu terstruktur rapi -->
        <div class="flex-1 overflow-y-auto p-5 text-sm space-y-3.5">
          <!-- Card 1: Ringkasan Nilai & Status Pembayaran -->
          <section v-if="canViewFinance" class="rounded-xl border bg-muted/30 p-4 space-y-3">
            <div class="flex items-center justify-between gap-3">
              <div>
                <span class="text-[11px] font-medium uppercase tracking-wider text-muted-foreground">Total Nilai</span>
                <div class="text-xl font-bold tracking-tight text-foreground tabular-nums">
                  {{ formatRupiah(program.total_invoice) }}
                </div>
              </div>
              <div class="w-44 shrink-0">
                <Select
                  v-if="canEditPayment"
                  :model-value="normalizePayStatus(program.payment_status)"
                  @update:model-value="changePayment"
                >
                  <SelectTrigger size="sm" class="h-8 bg-background text-xs" aria-label="Status payment">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="option in paymentOptions" :key="option.value" :value="option.value" class="text-xs">
                      {{ option.label }}
                    </SelectItem>
                  </SelectContent>
                </Select>
                <Badge v-else :variant="paymentBadgeVariant(program.payment_status)" class="w-full justify-center">
                  {{ formatPaymentStatus(program.payment_status) }}
                </Badge>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-2 pt-2.5 border-t border-border/60 text-xs">
              <div>
                <span class="text-[11px] text-muted-foreground block">DPP</span>
                <span class="font-semibold tabular-nums text-foreground">{{ formatRupiah(program.dpp) }}</span>
              </div>
              <div>
                <span class="text-[11px] text-muted-foreground block">PPN</span>
                <span class="font-semibold tabular-nums text-foreground">{{ formatRupiah(program.ppn) }}</span>
              </div>
              <div>
                <span class="text-[11px] text-muted-foreground block">PPh</span>
                <span class="font-semibold tabular-nums text-foreground">
                  {{ getPphLabel(program.pph_type) }}<template v-if="Number(program.pph)"> · {{ formatRupiah(program.pph) }}</template>
                </span>
              </div>
            </div>
          </section>

          <!-- Card 2: Informasi Program & Pajak -->
          <section v-if="canViewMaster" class="rounded-xl border bg-card p-4 space-y-3">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
              Informasi Program
            </div>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2.5 text-xs">
              <div class="space-y-0.5">
                <dt class="text-muted-foreground">Supplier</dt>
                <dd class="font-medium text-foreground break-words">{{ program.supplier || '—' }}</dd>
                <dd class="text-[11px] tabular-nums text-muted-foreground">NPWP {{ formatNpwp(program.npwp) || '—' }}</dd>
              </div>
              <div class="space-y-0.5">
                <dt class="text-muted-foreground">Company</dt>
                <dd class="font-medium text-foreground break-words">{{ programCompany(program) || '—' }}</dd>
              </div>
              <div class="space-y-0.5">
                <dt class="text-muted-foreground">Kategori</dt>
                <dd class="font-medium text-foreground break-words">{{ program.category || '—' }}</dd>
              </div>
              <div class="space-y-0.5">
                <dt class="text-muted-foreground">No. Invoice</dt>
                <dd class="font-medium tabular-nums text-foreground break-words">{{ program.invoice_number || '—' }}</dd>
              </div>
              <div class="col-span-2 space-y-0.5 pt-1.5 border-t border-border/40">
                <dt class="text-muted-foreground">Faktur Pajak</dt>
                <dd class="font-medium tabular-nums text-foreground break-words flex items-center gap-2">
                  <span>{{ program.faktur_number || '—' }}</span>
                  <span v-if="program.faktur_date" class="text-muted-foreground font-normal">({{ formatTanggal(program.faktur_date) }})</span>
                </dd>
              </div>
            </dl>
          </section>

          <!-- Card 3: Dokumen Lampiran (3 kartu mini sejajar) -->
          <section class="rounded-xl border bg-card p-4 space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">Dokumen Lampiran</span>
              <span class="text-[11px] text-muted-foreground tabular-nums">{{ documentCount(program) }} dari 3 terunggah</span>
            </div>
            <div class="grid grid-cols-3 gap-2.5">
              <div
                v-for="doc in DOC_TYPES"
                :key="doc.type"
                class="flex flex-col justify-between rounded-lg border p-2.5 bg-background transition-colors hover:border-foreground/20"
              >
                <div class="min-w-0 space-y-1">
                  <div class="flex items-center justify-between gap-1">
                    <span class="text-xs font-semibold text-foreground truncate">{{ doc.label }}</span>
                    <span
                      class="size-2 rounded-full shrink-0"
                      :class="findDocument(program, doc.type) ? 'bg-emerald-500' : 'bg-amber-400'"
                      :title="findDocument(program, doc.type) ? 'Sudah terunggah' : 'Belum ada berkas'"
                    />
                  </div>
                  <p
                    class="truncate text-[11px] text-muted-foreground"
                    :title="findDocument(program, doc.type)?.file_name || 'Belum ada'"
                  >
                    {{ findDocument(program, doc.type)?.file_name || 'Belum ada' }}
                  </p>
                </div>

                <div class="pt-2.5">
                  <Button
                    v-if="findDocument(program, doc.type)"
                    variant="outline"
                    size="sm"
                    class="h-7 w-full text-xs font-medium cursor-pointer"
                    @click="emit('document', { program, type: doc.type })"
                  >
                    Lihat
                  </Button>
                  <Button
                    v-else-if="store.canUploadDoc(doc.type)"
                    variant="outline"
                    size="sm"
                    class="h-7 w-full text-xs font-medium cursor-pointer"
                    @click="emit('document', { program, type: doc.type })"
                  >
                    Unggah
                  </Button>
                </div>
              </div>
            </div>
          </section>
        </div>

        <!-- Footer -->
        <SheetFooter class="flex-row justify-end gap-2 border-t px-6 py-3.5 bg-muted/20">
          <Button variant="outline" size="sm" @click="openDetailPage">Buka halaman detail</Button>
          <Button v-if="canEdit" size="sm" @click="emit('edit', program)">Edit</Button>
        </SheetFooter>
      </template>
    </SheetContent>
  </Sheet>
</template>
