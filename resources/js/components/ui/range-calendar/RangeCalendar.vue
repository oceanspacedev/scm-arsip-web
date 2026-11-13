<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import {
  RangeCalendarCell,
  RangeCalendarCellTrigger,
  RangeCalendarGrid,
  RangeCalendarGridBody,
  RangeCalendarGridHead,
  RangeCalendarGridRow,
  RangeCalendarHeadCell,
  RangeCalendarHeader,
  RangeCalendarHeading,
  RangeCalendarNext,
  RangeCalendarPrev,
  RangeCalendarRoot,
} from 'reka-ui';
import { cn } from '@/lib/utils';
import { buttonVariants } from '@/components/ui/button';

// v-model: { start: CalendarDate, end: CalendarDate } dari @internationalized/date
defineOptions({ inheritAttrs: false });

const props = defineProps({ class: { type: null, default: undefined } });

const navButton = cn(buttonVariants({ variant: 'outline', size: 'icon-sm' }), 'absolute size-7 bg-transparent p-0 opacity-60 hover:opacity-100');
</script>

<template>
  <RangeCalendarRoot
    v-slot="{ grid, weekDays }"
    locale="id-ID"
    weekday-format="short"
    calendar-label="Pilih rentang tanggal"
    v-bind="$attrs"
    :class="cn('p-3', props.class)"
  >
    <RangeCalendarHeader class="relative flex w-full items-center justify-center pt-1">
      <RangeCalendarPrev :class="cn(navButton, 'left-0')" aria-label="Bulan sebelumnya">
        <ChevronLeft class="size-4" />
      </RangeCalendarPrev>
      <RangeCalendarHeading class="text-sm font-medium" />
      <RangeCalendarNext :class="cn(navButton, 'right-0')" aria-label="Bulan berikutnya">
        <ChevronRight class="size-4" />
      </RangeCalendarNext>
    </RangeCalendarHeader>

    <div class="mt-4 flex flex-col gap-4 sm:flex-row">
      <RangeCalendarGrid v-for="month in grid" :key="month.value.toString()" class="w-full border-collapse select-none">
        <RangeCalendarGridHead>
          <RangeCalendarGridRow class="flex">
            <RangeCalendarHeadCell v-for="day in weekDays" :key="day" class="w-8 rounded-md text-xs font-normal text-muted-foreground">
              {{ day }}
            </RangeCalendarHeadCell>
          </RangeCalendarGridRow>
        </RangeCalendarGridHead>
        <RangeCalendarGridBody>
          <RangeCalendarGridRow v-for="(weekDates, index) in month.rows" :key="`week-${index}`" class="mt-1 flex w-full">
            <RangeCalendarCell
              v-for="weekDate in weekDates"
              :key="weekDate.toString()"
              :date="weekDate"
              class="relative p-0 text-center text-sm focus-within:relative focus-within:z-20 [&:has([data-selected])]:bg-accent first:[&:has([data-selected])]:rounded-l-md last:[&:has([data-selected])]:rounded-r-md [&:has([data-selected][data-selection-end])]:rounded-r-md [&:has([data-selected][data-selection-start])]:rounded-l-md"
            >
              <RangeCalendarCellTrigger
                :day="weekDate"
                :month="month.value"
                :class="cn(
                  buttonVariants({ variant: 'ghost' }),
                  'size-8 p-0 font-normal tabular-nums data-[selected]:opacity-100',
                  '[&[data-today]:not([data-selected])]:bg-accent [&[data-today]:not([data-selected])]:text-accent-foreground',
                  'data-[selection-start]:bg-primary data-[selection-start]:text-primary-foreground data-[selection-start]:hover:bg-primary data-[selection-start]:hover:text-primary-foreground',
                  'data-[selection-end]:bg-primary data-[selection-end]:text-primary-foreground data-[selection-end]:hover:bg-primary data-[selection-end]:hover:text-primary-foreground',
                  'data-[outside-view]:text-muted-foreground data-[outside-view]:opacity-50 data-[disabled]:text-muted-foreground data-[disabled]:opacity-50',
                )"
              />
            </RangeCalendarCell>
          </RangeCalendarGridRow>
        </RangeCalendarGridBody>
      </RangeCalendarGrid>
    </div>
  </RangeCalendarRoot>
</template>
