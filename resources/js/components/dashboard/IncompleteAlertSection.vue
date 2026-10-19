<template>
  <section class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
    <div class="px-5 py-3.5 flex items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800">
      <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
        Dokumen Belum Lengkap
        <span v-if="alertList.length > 0" class="ml-1 text-xs font-normal text-slate-400 dark:text-slate-500 tabular-nums">
          {{ alertList.length }}
        </span>
      </h3>
      <router-link
        to="/programs"
        class="px-2.5 py-1 rounded-md border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors whitespace-nowrap"
      >
        Lihat semua
      </router-link>
    </div>

    <!-- Desktop -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead>
          <tr class="border-b border-slate-100 dark:border-slate-800 text-[11px] text-slate-500 dark:text-slate-400">
            <th class="py-2.5 px-5 font-medium">Program</th>
            <th class="py-2.5 px-5 font-medium">Dokumen Kurang</th>
            <th class="py-2.5 px-5 font-medium">Tanggal</th>
            <th class="py-2.5 px-5"><span class="sr-only">Aksi</span></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          <tr v-if="alertList.length === 0">
            <td colspan="4" class="py-8 text-center text-slate-400 dark:text-slate-500">
              Semua program sudah lengkap dokumennya.
            </td>
          </tr>
          <tr
            v-for="item in alertList"
            :key="item.id"
            class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
          >
            <td class="py-2.5 px-5">
              <router-link
                :to="`/programs/${item.id}`"
                class="block max-w-[28rem] truncate font-medium text-slate-800 dark:text-slate-200 hover:text-slate-950 dark:hover:text-white"
              >
                {{ item.name }}
              </router-link>
              <span v-if="item.supplier" class="block max-w-[28rem] truncate text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                {{ item.supplier }}
              </span>
            </td>
            <td class="py-2.5 px-5 whitespace-nowrap text-slate-700 dark:text-slate-300">
              <span class="inline-flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="item.dotColor === 'red' ? 'bg-rose-500' : 'bg-amber-500'"></span>
                {{ item.missingDoc }}
              </span>
            </td>
            <td class="py-2.5 px-5 whitespace-nowrap text-slate-500 dark:text-slate-400 tabular-nums">
              {{ item.date }}
            </td>
            <td class="py-2.5 px-5 text-right">
              <router-link
                :to="`/programs/${item.id}`"
                class="text-xs font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 transition-colors"
              >
                Lihat
              </router-link>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile: satu baris per program, seluruh baris bisa diklik -->
    <div class="md:hidden divide-y divide-slate-100 dark:divide-slate-800">
      <p v-if="alertList.length === 0" class="py-8 px-4 text-center text-xs text-slate-400 dark:text-slate-500">
        Semua program sudah lengkap dokumennya.
      </p>
      <router-link
        v-for="item in alertList"
        :key="item.id"
        :to="`/programs/${item.id}`"
        class="block px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors"
      >
        <div class="flex items-start justify-between gap-3">
          <p class="min-w-0 text-xs font-medium text-slate-800 dark:text-slate-200 leading-snug">{{ item.name }}</p>
          <span class="shrink-0 text-[11px] text-slate-400 dark:text-slate-500 tabular-nums">{{ item.date }}</span>
        </div>
        <p v-if="item.supplier" class="text-[11px] text-slate-400 dark:text-slate-500 truncate mt-0.5">{{ item.supplier }}</p>
        <p class="mt-1.5 flex items-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-300">
          <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="item.dotColor === 'red' ? 'bg-rose-500' : 'bg-amber-500'"></span>
          Kurang: {{ item.missingDoc }}
        </p>
      </router-link>
    </div>
  </section>
</template>

<script setup>
import { computed } from 'vue';
import { useTaxStore, formatDate, getMissingDocuments, getCompleteness } from '../../store/taxStore';

const store = useTaxStore();

const alertList = computed(() => {
  const all = store.dashboardPrograms.value || [];
  return all
    .filter(p => {
      const docs = p.documents || [];
      return docs.length < 3;
    })
    .sort((a, b) => {
      // Show programs with 0 docs or 1-2 docs
      const countA = a.documents?.length || 0;
      const countB = b.documents?.length || 0;
      if (countA !== countB) return countA - countB;
      const dateA = new Date(a.program_date || 0);
      const dateB = new Date(b.program_date || 0);
      return dateB - dateA;
    })
    .slice(0, 10)
    .map(p => {
      const missing = getMissingDocuments(p);
      const completeness = getCompleteness(p);
      const dotColor = completeness.count === 0 ? 'red' : 'orange';

      return {
        id: p.id,
        name: p.program_name,
        supplier: p.supplier,
        missingDoc: missing.join(', ') || 'Belum Ada Dokumen',
        date: formatDate(p.program_date),
        dotColor: dotColor
      };
    });
});
</script>
