<script setup>
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const props = defineProps({
  table: { type: Object, required: true },
});

const columns = computed(() =>
  props.table.getAllLeafColumns().filter(column => column.getCanHide() && !column.columnDef.meta?.filterOnly),
);
</script>

<template>
  <!-- Tabel dengan satu kolom saja tidak butuh menu Kolom -->
  <DropdownMenu v-if="columns.length > 1">
    <DropdownMenuTrigger as-child>
      <Button variant="outline" size="sm" class="h-8">Kolom</Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-52">
      <DropdownMenuLabel>Tampilkan kolom</DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuCheckboxItem
        v-for="column in columns"
        :key="column.id"
        :model-value="column.getIsVisible()"
        @update:model-value="value => column.toggleVisibility(!!value)"
        @select.prevent
      >
        {{ column.columnDef.meta?.label || column.id }}
      </DropdownMenuCheckboxItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
