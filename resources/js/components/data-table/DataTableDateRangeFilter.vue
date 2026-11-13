<script setup>
import { computed, ref, watch } from 'vue';
import { CalendarDate } from '@internationalized/date';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { RangeCalendar } from '@/components/ui/range-calendar';
import { formatTanggal } from '@/lib/format';

// Filter rentang tanggal; nilai filter kolom { from: Date, to: Date } (lihat inDateRange)
const props = defineProps({
  column: { type: Object, required: true },
  title: { type: String, default: 'Tanggal' },
});

const open = ref(false);
const range = computed(() => props.column.getFilterValue());
const draft = ref(null);

const toCalendarDate = date => new CalendarDate(date.getFullYear(), date.getMonth() + 1, date.getDate());
const toDate = calendarDate => new Date(calendarDate.year, calendarDate.month - 1, calendarDate.day);

watch(open, isOpen => {
  if (isOpen) {
    draft.value = range.value ? { start: toCalendarDate(range.value.from), end: toCalendarDate(range.value.to) } : undefined;
  }
});

function onSelect(value) {
  draft.value = value;
  if (value?.start && value?.end) {
    props.column.setFilterValue({ from: toDate(value.start), to: toDate(value.end) });
    open.value = false;
  }
}

function clear() {
  props.column.setFilterValue(undefined);
  draft.value = undefined;
  open.value = false;
}

const label = computed(() => {
  if (!range.value) return '';
  return `${formatTanggal(range.value.from)} – ${formatTanggal(range.value.to)}`;
});
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <Button variant="outline" size="sm" class="h-8 border-dashed">
        {{ title }}
        <template v-if="range">
          <span class="mx-0.5 h-4 w-px bg-border" aria-hidden="true" />
          <span class="text-xs tabular-nums text-foreground">{{ label }}</span>
        </template>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-auto p-0">
      <RangeCalendar :model-value="draft" @update:model-value="onSelect" />
      <div class="flex items-center justify-between gap-2 border-t px-3 py-2">
        <p class="text-xs text-muted-foreground">Pilih tanggal awal dan akhir.</p>
        <Button v-if="range" variant="ghost" size="sm" class="h-7" @click="clear">Hapus</Button>
      </div>
    </PopoverContent>
  </Popover>
</template>
