<template>
  <div class="flex flex-col gap-6">
    <PageHeader
      title="Pengaturan Sistem"
      description="Konfigurasi parameter perpajakan, audit compliance, dan tampilan tema SCM TaxVault."
    />

    <div class="grid gap-6 xl:grid-cols-2">
      <!-- Tema & tampilan -->
      <Card>
        <CardHeader>
          <CardTitle>Tema & Tampilan Antarmuka</CardTitle>
          <CardDescription>Pilih preferensi mode tampilan yang nyaman untuk mata saat bekerja.</CardDescription>
        </CardHeader>
        <CardContent>
          <div class="flex items-center justify-between gap-4">
            <div class="grid min-w-0 gap-2">
              <Label for="pengaturan-mode-gelap">Mode Gelap</Label>
              <p class="text-xs text-muted-foreground">
                Nuansa gelap kontras tinggi yang ramah mata. Matikan untuk tampilan terang berlatar putih.
              </p>
            </div>
            <Switch id="pengaturan-mode-gelap" :model-value="isDark" @update:model-value="setTheme" />
          </div>
        </CardContent>
      </Card>

      <!-- Tarif PPN & aturan perpajakan -->
      <Card>
        <CardHeader>
          <CardTitle>Parameter Tarif PPN & Aturan Perpajakan</CardTitle>
          <CardDescription>
            Pengaturan default perhitungan otomatis Pajak Pertambahan Nilai pada tagihan program SCM.
          </CardDescription>
        </CardHeader>
        <CardContent class="grid gap-4 md:grid-cols-2">
          <FormField label="Tarif Standar PPN (%)" for="pengaturan-tarif-ppn" helper="Sesuai UU HPP No. 7 Tahun 2021">
            <NativeSelect id="pengaturan-tarif-ppn" v-model="ppnRate">
              <option value="11">11% (Tarif Berlaku Saat Ini)</option>
              <option value="12">12% (Kesiapan Penyesuaian Regulasi Baru)</option>
            </NativeSelect>
          </FormField>

          <FormField label="Validasi Kode Faktur Pajak" helper="Format 16 digit e-Faktur DJP">
            <div class="flex h-9 min-w-0 items-center gap-2">
              <Badge variant="success">Aktif</Badge>
              <span class="truncate text-sm text-muted-foreground tabular-nums">Format 010.xxx-xx.xxxxxxxx</span>
            </div>
          </FormField>
        </CardContent>
      </Card>

      <!-- Dokumen wajib untuk audit -->
      <Card class="xl:col-span-2">
        <CardHeader>
          <CardTitle>Syarat Kepatuhan Dokumen (Audit Checklist)</CardTitle>
          <CardDescription>3 dokumen wajib yang menjadi tolok ukur kesiapan audit eksternal & internal.</CardDescription>
        </CardHeader>
        <CardContent>
          <ul class="grid gap-4 lg:grid-cols-3 lg:gap-6">
            <li v-for="doc in requiredDocuments" :key="doc.name" class="min-w-0">
              <div class="flex items-center justify-between gap-2">
                <p class="truncate text-sm font-medium" :title="doc.name">{{ doc.name }}</p>
                <Badge variant="secondary">Wajib</Badge>
              </div>
              <p class="mt-1 text-xs text-muted-foreground">{{ doc.description }}</p>
            </li>
          </ul>
        </CardContent>
      </Card>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import PageHeader from '../components/layout/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FormField } from '@/components/ui/form-field';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Switch } from '@/components/ui/switch';
import { isDark } from '@/lib/theme';

function setTheme(val) {
  isDark.value = val;
}

// Pilihan tarif PPN belum disimpan ke mana pun (sama seperti sebelumnya); ref ini hanya menjaga pilihan di layar
const ppnRate = ref('11');

// Dokumen wajib kesiapan audit (teks statis)
const requiredDocuments = [
  {
    name: 'Commercial Invoice',
    description: 'Bukti tagihan resmi dari supplier berbadan hukum lengkap dengan rincian termin.',
  },
  {
    name: 'Faktur Pajak (e-Faktur DJP)',
    description: 'Faktur pajak masukan yang telah diapprove dan terekonsiliasi dengan DJP Online.',
  },
  {
    name: 'Memo / DO Perjanjian Kerjasama',
    description: 'Surat perintah kerja, delivery order (DO), purchase order, atau dasar hukum pengadaan barang/jasa SCM.',
  },
];
</script>
