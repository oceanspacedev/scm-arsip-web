<script setup>
import { computed, ref } from 'vue';
import { Check } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

// Filter multi-select untuk satu kolom. options: [{ label, value }]; bila kosong diambil dari isi kolom.
const props = defineProps({
  column: { type: Object, required: true },
  title: { type: String, required: true },
  options: { type: Array, default: null },
});

const query = ref('');
const counts = computed(() => props.column.getFacetedUniqueValues());
const selected = computed(() => new Set(props.column.getFilterValue() || []));

const allOptions = computed(() => {
  if (props.options) return props.options;
  return [...counts.value.keys()]
    .filter(value => value !== '' && value !== null && value !== undefined)
    .sort((a, b) => String(a).localeCompare(String(b), 'id'))
    .map(value => ({ label: String(value), value }));
});

const shownOptions = computed(() => {
  const q = query.value.trim().toLowerCase();
  return q ? allOptions.value.filter(option => option.label.toLowerCase().includes(q)) : allOptions.value;
});

function toggle(value) {
  const next = new Set(selected.value);
  if (next.has(value)) next.delete(value);
  else next.add(value);
  props.column.setFilterValue(next.size ? [...next] : undefined);
}
</script>

<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button variant="outline" size="sm" class="h-8 border-dashed">
        {{ title }}
        <template v-if="selected.size > 0">
          <span class="mx-0.5 h-4 w-px bg-border" aria-hidden="true" />
          <Badge variant="secondary" class="rounded-sm px-1.5 font-medium tabular-nums">{{ selected.size }}</Badge>
        </template>
      </Button>
    </PopoverTrigger>
    <PopoverContent class="w-64 p-0">
      <div v-if="allOptions.length > 7" class="border-b p-1">
        <input
          v-model="query"
          type="text"
          :placeholder="`Cari ${title.toLowerCase()}…`"
          class="h-8 w-full bg-transparent px-2 text-sm outline-none placeholder:text-muted-foreground"
        />
      </div>
      <div role="listbox" aria-multiselectable="true" :aria-label="title" class="max-h-72 overflow-y-auto p-1">
        <button
          v-for="option in shownOptions"
          :key="String(option.value)"
          type="button"
          role="option"
          :aria-selected="selected.has(option.value)"
          class="flex w-full cursor-pointer items-center gap-2 rounded-sm px-2 py-1.5 text-left text-sm outline-none hover:bg-accent focus-visible:bg-accent"
          @click="toggle(option.value)"
        >
          <span
            :class="cn(
              'flex size-4 shrink-0 items-center justify-center rounded-[4px] border shadow-xs',
              selected.has(option.value) ? 'border-primary text-primary' : 'border-input',
            )"
          >
            <Check v-if="selected.has(option.value)" class="size-3" />
          </span>
          <span class="min-w-0 flex-1 truncate">{{ option.label }}</span>
          <span class="shrink-0 text-xs tabular-nums text-muted-foreground">{{ counts.get(option.value) || 0 }}</span>
        </button>
        <p v-if="shownOptions.length === 0" class="px-2 py-4 text-center text-sm text-muted-foreground">Tidak ada pilihan.</p>
      </div>
      <div v-if="selected.size > 0" class="border-t p-1">
        <button
          type="button"
          class="w-full cursor-pointer rounded-sm px-2 py-1.5 text-center text-sm hover:bg-accent"
          @click="column.setFilterValue(undefined)"
        >
          Hapus filter
        </button>
      </div>
    </PopoverContent>
  </Popover>
</template>
