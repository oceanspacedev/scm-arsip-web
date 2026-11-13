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
    <SheetContent side="right" class="flex w-full flex-col gap-0 p-0 sm:max-w-lg">
      <template v-if="program">
        <SheetHeader class="gap-1 border-b p-6 pr-12 text-left">
          <SheetTitle class="leading-snug text-balance">{{ program.program_name }}</SheetTitle>
          <SheetDescription class="tabular-nums">
            {{ programPoSj(program) }} · {{ formatTanggal(program.program_date) }}
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 space-y-6 overflow-y-auto p-6 text-sm">
          <section v-if="canViewMaster" class="space-y-3">
            <h3 class="text-base font-semibold">Data program</h3>
            <dl class="grid grid-cols-[minmax(0,8rem)_minmax(0,1fr)] gap-x-4 gap-y-2.5">
              <dt class="text-muted-foreground">Supplier</dt>
              <dd class="min-w-0">
                <p class="break-words">{{ program.supplier || '—' }}</p>
                <p class="text-xs tabular-nums text-muted-foreground">NPWP {{ formatNpwp(program.npwp) || '—' }}</p>
              </dd>
              <dt class="text-muted-foreground">Company</dt>
              <dd class="break-words">{{ programCompany(program) || '—' }}</dd>
              <dt class="text-muted-foreground">Kategori</dt>
              <dd class="break-words">{{ program.category || '—' }}</dd>
              <dt class="text-muted-foreground">Brand / Gudang</dt>
              <dd class="flex flex-wrap items-center gap-1">
                <Badge variant="secondary">{{ programBrand(program) }}</Badge>
                <Badge v-if="program.kode_gudang" variant="outline" class="tabular-nums">{{ program.kode_gudang }}</Badge>
              </dd>
            </dl>
          </section>

          <section v-if="canViewFinance" class="space-y-3">
            <h3 class="text-base font-semibold">Nilai & pajak</h3>
            <dl class="grid grid-cols-[minmax(0,8rem)_minmax(0,1fr)] gap-x-4 gap-y-2.5">
              <dt class="text-muted-foreground">DPP</dt>
              <dd class="tabular-nums">{{ formatRupiah(program.dpp) }}</dd>
              <dt class="text-muted-foreground">PPN</dt>
              <dd class="tabular-nums">{{ formatRupiah(program.ppn) }}</dd>
              <dt class="text-muted-foreground">Total</dt>
              <dd class="font-medium tabular-nums">{{ formatRupiah(program.total_invoice) }}</dd>
              <dt class="text-muted-foreground">PPh</dt>
              <dd class="tabular-nums">
                {{ getPphLabel(program.pph_type) }}<template v-if="Number(program.pph)"> · {{ formatRupiah(program.pph) }}</template>
              </dd>
              <dt class="text-muted-foreground">No. Invoice</dt>
              <dd class="tabular-nums break-words">{{ program.invoice_number || '—' }}</dd>
              <dt class="text-muted-foreground">Faktur Pajak</dt>
              <dd class="tabular-nums break-words">
                <template v-if="program.faktur_number">
                  {{ program.faktur_number }}
                  <span class="block text-xs text-muted-foreground">{{ formatTanggal(program.faktur_date) }}</span>
                </template>
                <template v-else>—</template>
              </dd>
              <dt class="self-center text-muted-foreground">Status payment</dt>
              <dd>
                <Select
                  v-if="canEditPayment"
                  :model-value="normalizePayStatus(program.payment_status)"
                  @update:model-value="changePayment"
                >
                  <SelectTrigger size="sm" class="w-full max-w-60" aria-label="Status payment">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem v-for="option in paymentOptions" :key="option.value" :value="option.value">{{ option.label }}</SelectItem>
                  </SelectContent>
                </Select>
                <Badge v-else :variant="paymentBadgeVariant(program.payment_status)">{{ formatPaymentStatus(program.payment_status) }}</Badge>
              </dd>
            </dl>
          </section>

          <section class="space-y-3">
            <h3 class="text-base font-semibold">Dokumen</h3>
            <ul class="divide-y rounded-lg border">
              <li v-for="doc in DOC_TYPES" :key="doc.type" class="flex items-center gap-3 px-3 py-2.5">
                <div class="min-w-0 flex-1">
                  <p class="font-medium">{{ doc.label }}</p>
                  <p class="truncate text-xs text-muted-foreground" :title="findDocument(program, doc.type)?.file_name">
                    {{ findDocument(program, doc.type)?.file_name || 'Belum ada' }}
                  </p>
                </div>
                <Button
                  v-if="findDocument(program, doc.type)"
                  variant="outline"
                  size="sm"
                  @click="emit('document', { program, type: doc.type })"
                >
                  Lihat
                </Button>
                <Button
                  v-else-if="store.canUploadDoc(doc.type)"
                  variant="outline"
                  size="sm"
                  @click="emit('document', { program, type: doc.type })"
                >
                  Unggah
                </Button>
              </li>
            </ul>
          </section>
        </div>

        <SheetFooter class="flex-row justify-end gap-2 border-t p-4">
          <Button variant="outline" @click="openDetailPage">Buka halaman detail</Button>
          <Button v-if="canEdit" @click="emit('edit', program)">Edit</Button>
        </SheetFooter>
      </template>
    </SheetContent>
  </Sheet>
</template>
