<script setup>
import { computed } from 'vue';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatNumber } from '@/lib/format';

const props = defineProps({
  table: { type: Object, required: true },
  pageSizes: { type: Array, default: () => [10, 25, 50] },
  rowLabel: { type: String, default: 'data' },
});

const total = computed(() => props.table.getFilteredRowModel().rows.length);
const pagination = computed(() => props.table.getState().pagination);
const from = computed(() => (total.value ? pagination.value.pageIndex * pagination.value.pageSize + 1 : 0));
const to = computed(() => Math.min((pagination.value.pageIndex + 1) * pagination.value.pageSize, total.value));
const pageCount = computed(() => Math.max(props.table.getPageCount(), 1));
</script>

<template>
  <div class="flex flex-col gap-3 px-1 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
    <p class="tabular-nums">
      Menampilkan {{ formatNumber(from) }}–{{ formatNumber(to) }} dari {{ formatNumber(total) }} {{ rowLabel }}
    </p>
    <div class="flex flex-wrap items-center gap-x-6 gap-y-2">
      <div class="flex items-center gap-2">
        <span>Baris per halaman</span>
        <Select :model-value="String(pagination.pageSize)" @update:model-value="value => table.setPageSize(Number(value))">
          <SelectTrigger size="sm" class="w-[72px] tabular-nums" aria-label="Baris per halaman">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="size in pageSizes" :key="size" :value="String(size)" class="tabular-nums">{{ size }}</SelectItem>
          </SelectContent>
        </Select>
      </div>
      <span class="tabular-nums">Halaman {{ pagination.pageIndex + 1 }} dari {{ pageCount }}</span>
      <div class="flex items-center gap-1">
        <Button variant="outline" size="icon-sm" class="hidden lg:inline-flex" :disabled="!table.getCanPreviousPage()" aria-label="Halaman pertama" @click="table.setPageIndex(0)">
          <ChevronsLeft />
        </Button>
        <Button variant="outline" size="icon-sm" :disabled="!table.getCanPreviousPage()" aria-label="Halaman sebelumnya" @click="table.previousPage()">
          <ChevronLeft />
        </Button>
        <Button variant="outline" size="icon-sm" :disabled="!table.getCanNextPage()" aria-label="Halaman berikutnya" @click="table.nextPage()">
          <ChevronRight />
        </Button>
        <Button variant="outline" size="icon-sm" class="hidden lg:inline-flex" :disabled="!table.getCanNextPage()" aria-label="Halaman terakhir" @click="table.setPageIndex(table.getPageCount() - 1)">
          <ChevronsRight />
        </Button>
      </div>
    </div>
  </div>
</template>
