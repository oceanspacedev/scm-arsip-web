<script setup>
import { computed } from 'vue';
import { Search } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import DataTableDateRangeFilter from './DataTableDateRangeFilter.vue';
import DataTableFacetedFilter from './DataTableFacetedFilter.vue';
import DataTableViewOptions from './DataTableViewOptions.vue';

const props = defineProps({
  table: { type: Object, required: true },
  searchPlaceholder: { type: String, default: 'Cari…' },
  facets: { type: Array, default: () => [] },
  dateFilter: { type: Object, default: null },
  isFiltered: { type: Boolean, default: false },
});
const emit = defineEmits(['reset']);

const columnIds = computed(() => new Set(props.table.getAllLeafColumns().map(column => column.id)));
const shownFacets = computed(() => props.facets.filter(facet => columnIds.value.has(facet.column)));

const search = computed({
  get: () => props.table.getState().globalFilter ?? '',
  set: value => props.table.setGlobalFilter(value),
});
</script>

<template>
  <div class="flex flex-col gap-2 lg:flex-row lg:items-start lg:justify-between">
    <div class="flex flex-1 flex-wrap items-center gap-2">
      <div class="relative w-full sm:w-64">
        <Search class="pointer-events-none absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground" />
        <input
          v-model="search"
          type="search"
          :placeholder="searchPlaceholder"
          :aria-label="searchPlaceholder"
          class="h-8 w-full rounded-md border border-input bg-background pr-2 pl-8 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
        />
      </div>
      <DataTableFacetedFilter
        v-for="facet in shownFacets"
        :key="facet.column"
        :column="table.getColumn(facet.column)"
        :title="facet.title"
        :options="facet.options || null"
      />
      <DataTableDateRangeFilter
        v-if="dateFilter && columnIds.has(dateFilter.column)"
        :column="table.getColumn(dateFilter.column)"
        :title="dateFilter.title"
      />
      <Button v-if="isFiltered" variant="ghost" size="sm" class="h-8 px-2" @click="emit('reset')">Reset</Button>
    </div>
    <div class="flex items-center gap-2">
      <DataTableViewOptions :table="table" />
      <slot name="actions" />
    </div>
  </div>
</template>
