<template>
  <Card class="overflow-hidden">
    <CardHeader class="flex-row flex-wrap items-start justify-between gap-x-4 gap-y-3">
      <div class="flex min-w-0 flex-col gap-1.5">
        <CardTitle>
          Dokumen belum lengkap
          <span v-if="alertList.length > 0" class="ml-1 font-normal text-muted-foreground tabular-nums">{{ alertList.length }}</span>
        </CardTitle>
        <CardDescription>Diurutkan dari dokumen paling sedikit.</CardDescription>
      </div>
      <Button variant="outline" size="sm" as-child>
        <router-link to="/programs">Lihat semua</router-link>
      </Button>
    </CardHeader>

    <div class="border-t">
      <Table>
        <TableHeader>
          <TableRow class="hover:bg-transparent">
            <TableHead class="pl-6">Program</TableHead>
            <TableHead class="hidden md:table-cell">Dokumen kurang</TableHead>
            <TableHead class="hidden md:table-cell">Tanggal</TableHead>
            <TableHead class="pr-6"><span class="sr-only">Aksi</span></TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          <TableRow v-if="alertList.length === 0" class="hover:bg-transparent">
            <TableCell colspan="4" class="h-24 text-center whitespace-normal text-muted-foreground">
              Semua program sudah lengkap dokumennya.
            </TableCell>
          </TableRow>
          <TableRow v-for="item in alertList" :key="item.id">
            <TableCell class="w-full pl-6 whitespace-normal">
              <!-- Nama program tampil utuh, tidak dipotong -->
              <router-link
                :to="`/programs/${item.id}`"
                class="block break-words underline-offset-4 hover:underline"
              >
                {{ item.name }}
              </router-link>
              <p v-if="item.supplier" class="mt-0.5 text-xs text-muted-foreground">{{ item.supplier }}</p>
              <!-- Layar kecil: dokumen kurang & tanggal pindah ke bawah nama program -->
              <p class="mt-1 text-xs text-muted-foreground md:hidden">
                Kurang: {{ item.missingDoc }} · <span class="tabular-nums">{{ formatTanggal(item.date) }}</span>
              </p>
            </TableCell>
            <TableCell class="hidden md:table-cell">
              <!-- Satu baris: kolom program yang menyesuaikan lebar, bukan lencana yang turun ke bawah -->
              <div class="flex gap-1">
                <Badge v-for="doc in item.missingDoc.split(', ')" :key="doc" variant="outline">{{ doc }}</Badge>
              </div>
            </TableCell>
            <TableCell class="hidden text-muted-foreground tabular-nums md:table-cell">
              {{ formatTanggal(item.date) }}
            </TableCell>
            <TableCell class="pr-6 text-right">
              <Button variant="ghost" size="sm" as-child>
                <router-link :to="`/programs/${item.id}`">Lihat</router-link>
              </Button>
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>
    </div>
  </Card>
</template>

<script setup>
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatTanggal } from '@/lib/format';
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
