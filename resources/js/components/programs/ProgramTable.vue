<template>
  <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200/90 dark:border-slate-800 shadow-2xs overflow-hidden flex flex-col w-full min-w-0">
    <!-- Desktop Table Container (hidden md:block) -->
    <div class="hidden md:block overflow-x-auto w-full max-w-full">
      <table class="w-full text-left text-xs border-collapse min-w-[1240px]">
        <thead>
          <tr class="bg-slate-50/80 dark:bg-slate-900/80 border-b border-slate-200 dark:border-slate-800 text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 font-sans">
            <th class="py-2.5 px-3 font-bold min-w-[95px]">BULAN</th>
            <th class="py-2.5 px-2.5 font-bold min-w-[110px]">KATEGORI</th>
            <th class="py-2.5 px-2.5 font-bold min-w-[100px]">BRAND</th>
            <th class="py-2.5 px-2.5 font-bold min-w-[145px]">COMPANY NAME</th>
            <th class="py-2.5 px-2.5 font-bold min-w-[125px]">NO. PO/SJ</th>
            <th class="py-2.5 px-3 font-bold min-w-[160px]">PROGRAM</th>
            <th class="py-2.5 px-2.5 font-bold min-w-[130px]">SUPPLIER</th>
            <th class="py-2.5 px-2 font-bold min-w-[100px]">NO. INVOICE</th>
            <th class="py-2.5 px-2.5 font-bold min-w-[125px]">TAX INVOICE DATE</th>
            <th class="py-2.5 px-2 font-bold text-right min-w-[90px]">DPP</th>
            <th class="py-2.5 px-2 font-bold text-right min-w-[85px]">PPN</th>
            <th class="py-2.5 px-2.5 font-bold text-right min-w-[105px]">TOTAL INVOICE</th>
            <th class="py-2.5 px-2 font-bold text-center min-w-[95px]">DOKUMEN</th>
            <th class="py-2.5 px-2 font-bold text-center min-w-[100px]">STATUS</th>
            <th class="py-2.5 px-2 font-bold text-center w-20 sticky right-0 bg-slate-50 dark:bg-slate-900 border-l border-slate-200/70 dark:border-slate-800 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.03)] z-10">AKSI</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60">
          <tr
            v-for="program in paginatedPrograms"
            :key="program.id"
            class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors group cursor-pointer"
            @click="goToDetail(program.id)"
          >
            <!-- 1. BULAN -->
            <td class="py-2.5 px-3 text-slate-600 dark:text-slate-400 whitespace-nowrap text-[11px]">
              {{ getProgramMonth(program.program_date) }} {{ getProgramYear(program.program_date) }}
            </td>

            <!-- 2. KATEGORI -->
            <td class="py-2.5 px-2.5 text-slate-600 dark:text-slate-400 whitespace-nowrap text-[11px]">
              {{ program.category || 'Logistik' }}
            </td>

            <!-- 2.5 BRAND -->
            <td class="py-2.5 px-2.5 text-slate-600 dark:text-slate-400 whitespace-nowrap text-[11px]">
              {{ program.brand || getProgramBrand(program) }}
            </td>

            <!-- 3. COMPANY NAME -->
            <td class="py-2.5 px-2.5 max-w-[160px]">
              <div class="truncate text-[11px] text-slate-700 dark:text-slate-300" :title="getProgramCompanyName(program)">
                {{ getProgramCompanyName(program) }}
              </div>
            </td>

            <!-- 4. NO. PO/SJ -->
            <td class="py-2.5 px-2.5 whitespace-nowrap text-[11px] text-slate-600 dark:text-slate-400 tabular-nums">
              {{ getProgramPoSjNumber(program) }}
            </td>

            <!-- 5. PROGRAM -->
            <td class="py-2.5 px-3 max-w-[210px]">
              <router-link
                :to="`/programs/${program.id}`"
                class="font-medium text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate block text-[11px]"
                :title="program.program_name"
                @click.stop
              >
                {{ program.program_name }}
              </router-link>
              <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">
                {{ formatDate(program.program_date) }}
              </div>
            </td>

            <!-- 2. SUPPLIER -->
            <td class="py-2.5 px-2.5 max-w-[150px]">
              <div class="truncate text-[11px] text-slate-700 dark:text-slate-300" :title="program.supplier">
                {{ program.supplier }}
              </div>
              <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate tabular-nums">
                {{ program.npwp || '01.000.000.0-000.000' }}
              </div>
            </td>

            <!-- 3. NO. INVOICE -->
            <td class="py-2.5 px-2 whitespace-nowrap text-[11px] text-slate-600 dark:text-slate-400 tabular-nums">
              {{ program.invoice_number || '-' }}
            </td>

            <!-- TAX INVOICE DATE -->
            <td class="py-2.5 px-2.5 whitespace-nowrap text-[11px] text-slate-600 dark:text-slate-400">
              <div v-if="program.faktur_date">
                {{ formatDate(program.faktur_date) }}
              </div>
              <div v-else class="text-slate-400 dark:text-slate-500">
                -
              </div>
              <div v-if="program.faktur_number" class="text-[10px] text-slate-400 dark:text-slate-500 truncate tabular-nums" :title="program.faktur_number">
                {{ program.faktur_number }}
              </div>
            </td>

            <!-- 4. DPP -->
            <td class="py-2.5 px-2 text-slate-600 dark:text-slate-300 text-right whitespace-nowrap text-[11px] tabular-nums">
              {{ formatRupiah(program.dpp) }}
            </td>

            <!-- 5. PPN -->
            <td class="py-2.5 px-2 text-slate-600 dark:text-slate-300 font-medium text-right whitespace-nowrap text-[11px] tabular-nums">
              {{ formatRupiah(program.ppn) }}
            </td>

            <!-- 6. TOTAL INVOICE -->
            <td class="py-2.5 px-2.5 font-medium text-slate-800 dark:text-slate-200 text-right whitespace-nowrap text-[11px] tabular-nums">
              {{ formatRupiah(program.total_invoice) }}
            </td>

            <!-- 7. DOKUMEN -->
            <td class="py-2.5 px-2 text-center whitespace-nowrap" @click.stop>
              <div class="inline-flex items-center gap-1">
                <!-- INVOICE -->
                <button
                  type="button"
                  class="px-1.5 py-0.5 rounded text-[9px] font-bold transition-all cursor-pointer select-none active:scale-95"
                  :class="hasDoc(program, 'invoice')
                    ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60'
                    : 'bg-slate-50 dark:bg-slate-900/50 text-slate-400 dark:text-slate-500 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300'"
                  :title="hasDoc(program, 'invoice') ? 'Invoice ada - Klik untuk melihat' : 'Invoice belum ada - Klik unggah'"
                  @click="handlePillClick(program, 'invoice', 'Invoice')"
                >
                  <span v-if="!hasDoc(program, 'invoice')" class="mr-0.5 text-[8px] font-normal">+</span>IN
                </button>

                <!-- FAKTUR PAJAK -->
                <button
                  type="button"
                  class="px-1.5 py-0.5 rounded text-[9px] font-bold transition-all cursor-pointer select-none active:scale-95"
                  :class="hasDoc(program, 'faktur_pajak')
                    ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60'
                    : 'bg-slate-50 dark:bg-slate-900/50 text-slate-400 dark:text-slate-500 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300'"
                  :title="hasDoc(program, 'faktur_pajak') ? 'Faktur Pajak ada - Klik untuk melihat' : 'Faktur Pajak belum ada - Klik unggah'"
                  @click="handlePillClick(program, 'faktur_pajak', 'Faktur Pajak')"
                >
                  <span v-if="!hasDoc(program, 'faktur_pajak')" class="mr-0.5 text-[8px] font-normal">+</span>FP
                </button>

                <!-- MEMO / DO -->
                <button
                  type="button"
                  class="px-1.5 py-0.5 rounded text-[9px] font-bold transition-all cursor-pointer select-none active:scale-95"
                  :class="hasDoc(program, 'mou')
                    ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60'
                    : 'bg-slate-50 dark:bg-slate-900/50 text-slate-400 dark:text-slate-500 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300'"
                  :title="hasDoc(program, 'mou') ? 'Memo/DO ada - Klik untuk melihat' : 'Memo/DO belum ada - Klik unggah'"
                  @click="handlePillClick(program, 'mou', 'Memo/DO')"
                >
                  <span v-if="!hasDoc(program, 'mou')" class="mr-0.5 text-[8px] font-normal">+</span>DO
                </button>
              </div>
            </td>

            <!-- 8. STATUS KELENGKAPAN -->
            <td class="py-2.5 px-2 text-center whitespace-nowrap">
              <span
                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                :class="getStatusBadgeClass(program)"
              >
                <span
                  class="w-1.5 h-1.5 rounded-full shrink-0"
                  :class="{
                    'bg-emerald-500': getStatusText(program) === 'Lengkap',
                    'bg-rose-500': getStatusText(program) === 'Belum Lengkap',
                    'bg-amber-500': getStatusText(program).startsWith('Sebagian')
                  }"
                ></span>
                <span>{{ getStatusText(program) }}</span>
              </span>
            </td>

            <!-- 9. AKSI (Compact Icon Buttons - Sticky right) -->
            <td class="py-2.5 px-2 text-center whitespace-nowrap sticky right-0 bg-white dark:bg-[#111827] group-hover:bg-slate-50 dark:group-hover:bg-slate-800/60 transition-colors border-l border-slate-100 dark:border-slate-800 shadow-[-4px_0_6px_-2px_rgba(0,0,0,0.03)] z-10" @click.stop>
              <div class="inline-flex items-center justify-center gap-0.5">
                <button
                  type="button"
                  class="p-1 rounded-md text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition-colors cursor-pointer"
                  title="Lihat Detail Program"
                  @click="goToDetail(program.id)"
                >
                  <Eye class="w-3.5 h-3.5" />
                </button>
                <button
                  v-if="canEditProgram"
                  type="button"
                  class="p-1 rounded-md text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                  title="Edit Data Program"
                  @click="openEditModal(program)"
                >
                  <Pencil class="w-3.5 h-3.5" />
                </button>
                <button
                  v-if="isAdmin"
                  type="button"
                  class="p-1 rounded-md text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors cursor-pointer"
                  title="Hapus Program"
                  @click="openDeleteConfirm(program)"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </td>
          </tr>

          <!-- Empty State Desktop -->
          <tr v-if="filteredPrograms.length === 0">
            <td colspan="14" class="py-14 text-center">
              <div class="flex flex-col items-center justify-center space-y-2">
                <FolderArchive class="w-8 h-8 text-slate-300 dark:text-slate-600" />
                <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Tidak ada program ditemukan</p>
                <p class="text-xs text-slate-400 dark:text-slate-500 max-w-sm">
                  Coba sesuaikan kata kunci pencarian atau reset filter kategori, status, dan supplier.
                </p>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Mobile Cards List (block md:hidden) - NO horizontal scroll required! -->
    <div class="block md:hidden divide-y divide-slate-100 dark:divide-slate-800/60">
      <div
        v-for="program in paginatedPrograms"
        :key="program.id"
        class="p-4 space-y-3 hover:bg-slate-50/50 dark:hover:bg-slate-800/30 transition-colors"
        @click="goToDetail(program.id)"
      >
        <!-- Top Row: Program Name & Status Badge -->
        <div class="flex items-start justify-between gap-2.5">
          <div class="min-w-0 flex-1">
            <router-link
              :to="`/programs/${program.id}`"
              class="font-bold text-slate-900 dark:text-slate-100 hover:text-blue-600 dark:hover:text-blue-400 text-sm leading-snug block break-words"
              @click.stop
            >
              {{ program.program_name }}
            </router-link>
            <div class="flex flex-wrap items-center gap-1.5 mt-1 text-[11px] text-slate-500 dark:text-slate-400">
              <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                {{ getProgramMonth(program.program_date) }} {{ getProgramYear(program.program_date) }}
              </span>
              <span
                class="px-2 py-0.5 rounded text-[10px] font-medium border"
                :class="getCategoryBadgeClass(program.category)"
              >
                {{ program.category }}
              </span>
              <span class="text-slate-500 dark:text-slate-400 font-medium truncate max-w-[140px]">
                {{ getProgramCompanyName(program) }}
              </span>
            </div>
          </div>

          <!-- Status Badge -->
          <span
            :class="[
              'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold border shrink-0',
              getStatusBadgeClass(program)
            ]"
          >
            <span
              class="w-1.5 h-1.5 rounded-full"
              :class="{
                'bg-emerald-500': getStatusText(program) === 'Lengkap',
                'bg-rose-500': getStatusText(program) === 'Belum Lengkap',
                'bg-amber-500': getStatusText(program).startsWith('Sebagian')
              }"
            ></span>
            <span>{{ getStatusText(program) }}</span>
          </span>
        </div>

        <!-- Supplier & Financial Box (Clean compact card) -->
        <div class="bg-slate-50/80 dark:bg-slate-900/60 rounded-xl p-3 border border-slate-100 dark:border-slate-800 space-y-2 text-xs">
          <!-- Supplier -->
          <div class="flex items-center justify-between gap-2">
            <span class="text-slate-500 dark:text-slate-400 text-[11px] shrink-0">Supplier:</span>
            <div class="text-right min-w-0">
              <span class="font-semibold text-slate-800 dark:text-slate-200 block truncate max-w-[210px]">{{ program.supplier }}</span>
              <span class="text-[10px] text-slate-400 dark:text-slate-500 block tabular-nums">{{ program.npwp || '-' }}</span>
            </div>
          </div>

          <!-- No. PO/SJ & Company -->
          <div class="flex items-center justify-between gap-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-800 text-[11px]">
            <div>
              <span class="text-slate-500 dark:text-slate-400 block">No. PO/SJ:</span>
              <span class="font-medium text-slate-700 dark:text-slate-300 block tabular-nums">{{ getProgramPoSjNumber(program) }}</span>
            </div>
            <div class="text-right">
              <span class="text-slate-500 dark:text-slate-400 block">Company:</span>
              <span class="font-semibold text-slate-700 dark:text-slate-300 block truncate max-w-[160px]">{{ getProgramCompanyName(program) }}</span>
            </div>
          </div>

          <!-- No. Invoice & Total -->
          <div class="flex items-center justify-between gap-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-800">
            <div>
              <span class="text-slate-500 dark:text-slate-400 text-[11px] block">No. Invoice</span>
              <span class="text-[11px] font-medium text-slate-700 dark:text-slate-300 block tabular-nums">{{ program.invoice_number || '-' }}</span>
            </div>
            <div class="text-right">
              <span class="text-slate-500 dark:text-slate-400 text-[11px] block">Total Invoice</span>
              <span class="font-bold text-xs sm:text-sm block text-slate-900 dark:text-slate-100 tabular-nums">
                {{ formatRupiah(program.total_invoice) }}
              </span>
            </div>
          </div>

          <!-- Tax Invoice Date & No. FP -->
          <div class="flex items-center justify-between gap-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-800 text-[11px]">
            <div>
              <span class="text-slate-500 dark:text-slate-400 block">Tax Invoice Date:</span>
              <span class="font-medium text-slate-700 dark:text-slate-300 block">
                {{ program.faktur_date ? formatDate(program.faktur_date) : '-' }}
              </span>
            </div>
            <div class="text-right" v-if="program.faktur_number">
              <span class="text-slate-500 dark:text-slate-400 block">No. FP:</span>
              <span class="text-slate-700 dark:text-slate-300 block tabular-nums">{{ program.faktur_number }}</span>
            </div>
          </div>

          <!-- DPP & PPN Breakdown -->
          <div class="grid grid-cols-2 gap-2 pt-1.5 border-t border-slate-200/60 dark:border-slate-800 text-[11px]">
            <div>
              <span class="text-slate-400 dark:text-slate-500 text-[10px]">DPP:</span>
              <span class="text-slate-700 dark:text-slate-300 ml-1 tabular-nums">{{ formatRupiah(program.dpp) }}</span>
            </div>
            <div class="text-right">
              <span class="text-slate-400 dark:text-slate-500 text-[10px]">PPN 11%:</span>
              <span class="text-slate-700 dark:text-slate-300 font-medium ml-1 tabular-nums">{{ formatRupiah(program.ppn) }}</span>
            </div>
          </div>
        </div>

        <!-- Dokumen Lampiran & Action Buttons Row -->
        <div class="flex items-center justify-between gap-2 pt-1" @click.stop>
          <!-- Dokumen Lampiran Interactive Pills -->
          <div class="flex items-center gap-1.5">
            <span class="text-[10px] text-slate-400 dark:text-slate-500 font-semibold uppercase">Dok:</span>
            <!-- INVOICE -->
            <button
              type="button"
              class="px-2 py-1 rounded text-[10px] font-mono font-bold transition-all cursor-pointer active:scale-95"
              :class="hasDoc(program, 'invoice')
                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60'
                : 'bg-slate-50 dark:bg-slate-900/50 text-slate-400 dark:text-slate-500 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300'"
              :title="hasDoc(program, 'invoice') ? 'Invoice ada - Klik untuk melihat' : 'Invoice belum ada - Klik unggah'"
              @click="handlePillClick(program, 'invoice', 'Invoice')"
            >
              <span v-if="!hasDoc(program, 'invoice')" class="mr-0.5 font-normal">+</span>IN
            </button>

            <!-- FAKTUR PAJAK -->
            <button
              type="button"
              class="px-2 py-1 rounded text-[10px] font-mono font-bold transition-all cursor-pointer active:scale-95"
              :class="hasDoc(program, 'faktur_pajak')
                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60'
                : 'bg-slate-50 dark:bg-slate-900/50 text-slate-400 dark:text-slate-500 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300'"
              :title="hasDoc(program, 'faktur_pajak') ? 'Faktur Pajak ada - Klik untuk melihat' : 'Faktur Pajak belum ada - Klik unggah'"
              @click="handlePillClick(program, 'faktur_pajak', 'Faktur Pajak')"
            >
              <span v-if="!hasDoc(program, 'faktur_pajak')" class="mr-0.5 font-normal">+</span>FP
            </button>

            <!-- Memo / DO -->
            <button
              type="button"
              class="px-2 py-1 rounded text-[10px] font-mono font-bold transition-all cursor-pointer active:scale-95"
              :class="hasDoc(program, 'mou')
                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60'
                : 'bg-slate-50 dark:bg-slate-900/50 text-slate-400 dark:text-slate-500 border border-dashed border-slate-300 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-700 dark:hover:text-slate-300'"
              :title="hasDoc(program, 'mou') ? 'Memo/DO ada - Klik untuk melihat' : 'Memo/DO belum ada - Klik unggah'"
              @click="handlePillClick(program, 'mou', 'Memo/DO')"
            >
              <span v-if="!hasDoc(program, 'mou')" class="mr-0.5 font-normal">+</span>DO
            </button>
          </div>

          <!-- Quick Action Buttons -->
          <div class="flex items-center gap-1.5">
            <button
              type="button"
              class="h-8 px-2.5 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700 font-semibold text-xs transition-colors flex items-center gap-1 hover:bg-slate-200 dark:hover:bg-slate-700 cursor-pointer"
              @click="goToDetail(program.id)"
              title="Lihat Detail"
            >
              <Eye class="w-3.5 h-3.5" />
              <span>Detail</span>
            </button>
            <button
              v-if="canEditProgram"
              type="button"
              class="h-8 w-8 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 transition-colors cursor-pointer flex items-center justify-center"
              title="Edit Data Program"
              @click="openEditModal(program)"
            >
              <Pencil class="w-3.5 h-3.5" />
            </button>
            <button
              v-if="isAdmin"
              type="button"
              class="h-8 w-8 rounded-lg text-rose-500 hover:text-rose-700 dark:text-rose-400 dark:hover:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-rose-200 dark:border-rose-900/50 transition-colors cursor-pointer flex items-center justify-center"
              title="Hapus Program"
              @click="openDeleteConfirm(program)"
            >
              <Trash2 class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State Mobile -->
      <div v-if="filteredPrograms.length === 0" class="py-12 px-4 text-center">
        <FolderArchive class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">Tidak ada program ditemukan</p>
        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">Sesuaikan filter atau pencarian.</p>
      </div>
    </div>

    <!-- Table Footer / Summary & Pagination -->
    <div class="px-4 sm:px-5 py-3.5 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
      <div class="flex items-center justify-between w-full sm:w-auto">
        <span v-if="filteredPrograms.length > 0">
          Menampilkan <strong class="text-slate-800 dark:text-slate-200 font-semibold">{{ startIndex + 1 }} - {{ endIndex }}</strong> dari <strong class="text-slate-800 dark:text-slate-200 font-semibold">{{ filteredPrograms.length }}</strong> data
        </span>
        <span v-else>
          Menampilkan <strong class="text-slate-800 dark:text-slate-200 font-semibold">0</strong> data
        </span>

        <!-- Mobile Total Nilai Badge -->
        <span v-if="filteredPrograms.length > 0" class="sm:hidden font-mono text-slate-800 dark:text-slate-200 font-bold text-[11px]">
          {{ formatRupiah(totalSum) }}
        </span>
      </div>

      <!-- Pagination Controls (Sebelumnya / Next) -->
      <div v-if="totalPages > 1" class="flex items-center justify-between sm:justify-end w-full sm:w-auto gap-2">
        <button
          type="button"
          :disabled="currentPage === 1"
          @click="currentPage--"
          class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer flex items-center gap-1 shadow-2xs"
        >
          <ChevronLeft class="w-3.5 h-3.5" />
          <span>Sebelumnya</span>
        </button>

        <!-- Page indicators: Mobile shows 'X / Y', Desktop shows page numbers -->
        <div class="sm:hidden text-xs font-semibold text-slate-700 dark:text-slate-300 px-2">
          {{ currentPage }} / {{ totalPages }}
        </div>

        <div class="hidden sm:flex items-center gap-1">
          <button
            v-for="page in totalPages"
            :key="page"
            type="button"
            @click="currentPage = page"
            :class="[
              'w-7 h-7 rounded-lg text-xs font-semibold flex items-center justify-center transition-colors cursor-pointer',
              currentPage === page
                ? 'bg-blue-600 text-white shadow-2xs'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800'
            ]"
          >
            {{ page }}
          </button>
        </div>

        <button
          type="button"
          :disabled="currentPage >= totalPages"
          @click="currentPage++"
          class="px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition-colors cursor-pointer flex items-center gap-1 shadow-2xs"
        >
          <span>Selanjutnya</span>
          <ChevronRight class="w-3.5 h-3.5" />
        </button>
      </div>

      <div class="hidden sm:flex items-center gap-4 text-xs">
        <span v-if="filteredPrograms.length > 0">
          Total Nilai: <strong class="font-mono text-slate-800 dark:text-slate-200 font-bold ml-1">{{ formatRupiah(totalSum) }}</strong>
        </span>
      </div>
    </div>

    <!-- Quick Edit Modal -->
    <Teleport to="body">
      <div
        v-if="isEditModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/40 dark:bg-slate-950/70 backdrop-blur-xs animate-in fade-in duration-150"
        @click.self="isEditModalOpen = false"
      >
        <div class="bg-white dark:bg-[#111827] rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 w-full max-w-xl overflow-hidden max-h-[92vh] flex flex-col">
          <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between shrink-0">
            <div>
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Edit Data Program</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Wewenang: {{ store.currentUser.value?.role || 'Pengguna' }}</p>
            </div>
            <button
              type="button"
              class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              @click="isEditModalOpen = false"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <form @submit.prevent="saveEdit" class="p-4 sm:p-5 space-y-5 overflow-y-auto flex-1 text-xs">
            <!-- Section 1: Data Purchase & Vendor -->
            <div class="space-y-3">
              <div class="border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center justify-between">
                <span class="font-semibold text-slate-900 dark:text-slate-100">Data Purchase & Pengadaan</span>
                <span v-if="!canEditPurchase" class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Hanya baca</span>
              </div>

              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Program</label>
                <input
                  v-model="editForm.program_name"
                  type="text"
                  required
                  :disabled="!canEditPurchase"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Supplier / Vendor</label>
                  <input
                    v-model="editForm.supplier"
                    type="text"
                    required
                    :disabled="!canEditPurchase"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">NPWP Vendor</label>
                  <input
                    v-model="editForm.npwp"
                    type="text"
                    :disabled="!canEditPurchase"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Company Name</label>
                  <input
                    v-model="editForm.company_name"
                    type="text"
                    placeholder="Contoh: PT SCM Nusantara"
                    :disabled="!canEditPurchase"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">No. PO / SJ</label>
                  <input
                    v-model="editForm.po_sj_number"
                    type="text"
                    placeholder="Contoh: PO/2025/1016 / SJ-1016"
                    :disabled="!canEditPurchase"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
                  <select
                    v-model="editForm.category"
                    :disabled="!canEditPurchase"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  >
                    <option v-for="cat in categoriesList" :key="cat" :value="cat">
                      {{ cat }}
                    </option>
                  </select>
                </div>

                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Brand</label>
                  <input
                    v-model="editForm.brand"
                    type="text"
                    placeholder="Contoh: SCTV, Indosiar, Vidio, dll."
                    :disabled="!canEditPurchase"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
              </div>
            </div>

            <!-- Section 2: Data Nilai & Perpajakan -->
            <div class="space-y-3 pt-2">
              <div class="border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center justify-between">
                <span class="font-semibold text-slate-900 dark:text-slate-100">Data Nilai & Faktur Pajak</span>
                <span v-if="!canEditFinance" class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Hanya baca</span>
              </div>

              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">No. Invoice</label>
                <input
                  v-model="editForm.invoice_number"
                  type="text"
                  :disabled="!canEditFinance"
                  class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                />
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nilai DPP (IDR)</label>
                  <input
                    v-model.number="editForm.dpp"
                    type="number"
                    required
                    :disabled="!canEditFinance"
                    @input="calculateTaxes"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">PPN 11% (IDR)</label>
                  <input
                    v-model.number="editForm.ppn"
                    type="number"
                    :disabled="!canEditFinance"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Total Invoice</label>
                  <input
                    :value="editForm.dpp + editForm.ppn"
                    type="number"
                    readonly
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 font-mono bg-slate-100 dark:bg-slate-800 font-medium text-slate-700 dark:text-slate-300 cursor-not-allowed"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Jenis PPh</label>
                  <select
                    v-model="editForm.pph_type"
                    :disabled="!canEditFinance"
                    @change="onPphTypeChange"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  >
                    <option value="NON_PPH">Non PPh</option>
                    <option value="PPH_21">PPh 21 (2,5%)</option>
                    <option value="PPH_23">PPh 23 (2%)</option>
                    <option value="PPH_4_2">PPh 4 Ayat 2 (10%)</option>
                    <option value="PPH_23_BONUS">PPh 23 Atas Bonus (15%)</option>
                  </select>
                </div>
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nilai PPh (IDR)</label>
                  <input
                    v-model.number="editForm.pph"
                    type="number"
                    :disabled="!canEditFinance"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-none disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">No. Faktur Pajak</label>
                  <input
                    v-model="editForm.faktur_number"
                    type="text"
                    placeholder="010.000-25.00000001"
                    :disabled="!canEditFinance"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
                <div>
                  <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tax Invoice Date</label>
                  <input
                    v-model="editForm.faktur_date"
                    type="date"
                    :disabled="!canEditFinance"
                    class="w-full px-3 py-2 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed font-medium"
                  />
                </div>
              </div>
            </div>

            <div class="pt-3 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-2.5 shrink-0">
              <button
                type="button"
                :disabled="isSavingEdit"
                class="px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer disabled:opacity-50"
                @click="isEditModalOpen = false"
              >
                Batal
              </button>
              <button
                type="submit"
                :disabled="isSavingEdit"
                class="px-4 py-2 rounded-lg bg-blue-600 text-white font-medium text-xs hover:bg-blue-700 transition-colors shadow-2xs cursor-pointer inline-flex items-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed"
              >
                <span v-if="isSavingEdit" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span>{{ isSavingEdit ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div
        v-if="programToDelete"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/40 dark:bg-slate-950/70 backdrop-blur-xs animate-in fade-in duration-150"
        @click.self="programToDelete = null"
      >
        <div class="bg-white dark:bg-[#111827] rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 max-w-sm w-full p-5 text-center">
          <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-3">
            <Trash2 class="w-5 h-5" />
          </div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 mb-1">Hapus Program?</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed mb-5">
            Apakah Anda yakin ingin menghapus <strong class="text-slate-800 dark:text-slate-200">{{ programToDelete.program_name }}</strong>? Data yang dihapus tidak dapat dipulihkan.
          </p>
          <div class="grid grid-cols-2 gap-2.5">
            <button
              type="button"
              class="w-full py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-semibold text-xs hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              @click="programToDelete = null"
            >
              Batal
            </button>
            <button
              type="button"
              class="w-full py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs transition-colors shadow-2xs cursor-pointer"
              @click="executeDelete"
            >
              Ya, Hapus
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Document Preview Sheet -->
    <DocumentPreviewSheet
      v-model:open="isPreviewOpen"
      :document="activeDocument"
      :program="activeProgram"
    />

    <!-- Document Upload Modal -->
    <DocumentUploadModal
      v-model:open="isUploadOpen"
      :docType="uploadDocType"
      :docLabel="uploadDocLabel"
      @uploaded="handleUploaded"
    />
  </div>
</template>

<script setup>
import { computed, ref, reactive, watch } from 'vue';
import { useRouter } from 'vue-router';
import { FolderArchive, Trash2, X, ChevronLeft, ChevronRight, Eye, Pencil } from 'lucide-vue-next';
import {
  useTaxStore,
  formatRupiah,
  formatDate,
  getProgramMonth,
  getProgramYear,
  getProgramCompanyName,
  getProgramBrand,
  getProgramPoSjNumber,
  calculatePphAmount,
  PPH_OPTIONS
} from '../../store/taxStore';
import DocumentPreviewSheet from '../detail/DocumentPreviewSheet.vue';
import DocumentUploadModal from '../detail/DocumentUploadModal.vue';

const router = useRouter();
const store = useTaxStore();

const isAdmin = computed(() => store.isAdmin.value);
const isGudang = computed(() => store.isGudang.value);
const isFinance = computed(() => store.isFinance.value);
const isScm = computed(() => store.isScm.value);
const canEditProgram = computed(() => store.canEditProgram.value);
const canEditPurchase = computed(() => store.canEditPurchase.value);
const canEditFinance = computed(() => store.canEditFinance.value);

const filteredPrograms = computed(() => store.filteredPrograms.value);
const totalCount = computed(() => store.programs.value.length);
const categoriesList = computed(() => store.categoriesList.value.filter(c => c !== 'Semua Kategori'));

// Pagination state (10 data per halaman)
const itemsPerPage = 10;
const currentPage = ref(1);

const totalPages = computed(() => {
  return Math.ceil(filteredPrograms.value.length / itemsPerPage) || 1;
});

const startIndex = computed(() => (currentPage.value - 1) * itemsPerPage);
const endIndex = computed(() => {
  return Math.min(startIndex.value + itemsPerPage, filteredPrograms.value.length);
});

const paginatedPrograms = computed(() => {
  return filteredPrograms.value.slice(startIndex.value, endIndex.value);
});

// Reset ke halaman 1 jika filter berubah
watch(filteredPrograms, () => {
  if (currentPage.value > totalPages.value) {
    currentPage.value = 1;
  }
});

const totalSum = computed(() => {
  return filteredPrograms.value.reduce((acc, p) => acc + (Number(p.total_invoice) || 0), 0);
});

// Document Preview & Upload states
const isPreviewOpen = ref(false);
const activeDocument = ref(null);
const activeProgram = ref(null);

const isUploadOpen = ref(false);
const uploadTargetProgram = ref(null);
const uploadDocType = ref('invoice');
const uploadDocLabel = ref('Invoice');

// Edit Modal State
const isEditModalOpen = ref(false);
const isSavingEdit = ref(false);
const editProgramId = ref(null);
const editForm = reactive({
  program_name: '',
  supplier: '',
  company_name: '',
  po_sj_number: '',
  npwp: '',
  invoice_number: '',
  faktur_number: '',
  faktur_date: '',
  category: 'Logistik',
  brand: 'SCM',
  dpp: 0,
  ppn: 0,
  pph_type: 'NON_PPH',
  pph: 0
});

// Delete Modal State
const programToDelete = ref(null);

function goToDetail(id) {
  router.push(`/programs/${id}`);
}

function hasDoc(program, docType) {
  if (!program.documents || !Array.isArray(program.documents)) return false;
  return program.documents.some((doc) => doc.document_type === docType);
}

function getDoc(program, docType) {
  if (!program.documents || !Array.isArray(program.documents)) return null;
  return program.documents.find((doc) => doc.document_type === docType);
}

function handlePillClick(program, docType, docLabel) {
  const existingDoc = getDoc(program, docType);
  if (existingDoc) {
    activeProgram.value = program;
    activeDocument.value = existingDoc;
    isPreviewOpen.value = true;
  } else {
    uploadTargetProgram.value = program;
    uploadDocType.value = docType;
    uploadDocLabel.value = `${docLabel} (${program.program_name})`;
    isUploadOpen.value = true;
  }
}

async function handleUploaded(fileData) {
  if (uploadTargetProgram.value) {
    if (Array.isArray(fileData)) {
      for (const f of fileData) {
        await store.uploadDocument(uploadTargetProgram.value.id, f.docType, f);
      }
    } else {
      await store.uploadDocument(uploadTargetProgram.value.id, fileData.docType, fileData);
    }
    isUploadOpen.value = false;
  }
}

function openEditModal(program) {
  editProgramId.value = program.id;
  editForm.program_name = program.program_name || '';
  editForm.supplier = program.supplier || '';
  editForm.company_name = program.company_name || getProgramCompanyName(program);
  editForm.po_sj_number = program.po_sj_number || getProgramPoSjNumber(program);
  editForm.npwp = program.npwp || '';
  editForm.invoice_number = program.invoice_number || '';
  editForm.faktur_number = program.faktur_number || '';
  editForm.faktur_date = program.faktur_date ? String(program.faktur_date).slice(0, 10) : '';
  editForm.category = program.category || 'Logistik';
  editForm.brand = program.brand || getProgramBrand(program) || '';
  editForm.dpp = Number(program.dpp) || 0;
  editForm.ppn = Number(program.ppn) || Math.round((Number(program.dpp) || 0) * 0.11);
  editForm.pph_type = program.pph_type || 'NON_PPH';
  editForm.pph = Number(program.pph) || calculatePphAmount(editForm.dpp, editForm.pph_type);
  isEditModalOpen.value = true;
}

function calculateTaxes() {
  editForm.ppn = Math.round((Number(editForm.dpp) || 0) * 0.11);
  if (editForm.pph_type && editForm.pph_type !== 'NON_PPH') {
    editForm.pph = calculatePphAmount(editForm.dpp, editForm.pph_type);
  } else {
    editForm.pph = 0;
  }
}

function onPphTypeChange() {
  editForm.pph = calculatePphAmount(editForm.dpp, editForm.pph_type);
}

async function saveEdit() {
  if (!editProgramId.value || isSavingEdit.value) return;
  isSavingEdit.value = true;
  try {
    const res = await store.updateProgram(editProgramId.value, {
      program_name: editForm.program_name,
      supplier: editForm.supplier,
      company_name: editForm.company_name,
      po_sj_number: editForm.po_sj_number,
      npwp: editForm.npwp,
      invoice_number: editForm.invoice_number,
      faktur_number: editForm.faktur_number,
      faktur_date: editForm.faktur_date || null,
      tax_invoice_number: editForm.faktur_number,
      tax_invoice_date: editForm.faktur_date || null,
      category: editForm.category,
      brand: editForm.brand,
      dpp: editForm.dpp,
      ppn: editForm.ppn,
      total_invoice: editForm.dpp + editForm.ppn,
      pph_type: editForm.pph_type,
      pph: editForm.pph,
      pph_amount: editForm.pph
    });
    if (res?.success) {
      isEditModalOpen.value = false;
    }
  } catch (err) {
    console.error('Error saving program from table:', err);
  } finally {
    isSavingEdit.value = false;
  }
}

function openDeleteConfirm(program) {
  programToDelete.value = program;
}

async function executeDelete() {
  if (programToDelete.value) {
    await store.deleteProgram(programToDelete.value.id);
    programToDelete.value = null;
  }
}

function getCategoryBadgeClass(category) {
  return 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200/80 dark:border-slate-700';
}

function getStatusText(program) {
  const docTypes = new Set((program.documents || []).map(d => d.document_type));
  if (docTypes.size >= 3) return 'Lengkap';
  if (docTypes.size === 0) return 'Belum Lengkap';
  return `Sebagian (${docTypes.size}/3)`;
}

function getStatusBadgeClass(program) {
  const docTypes = new Set((program.documents || []).map(d => d.document_type));
  if (docTypes.size >= 3) {
    return 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border-emerald-200/80 dark:border-emerald-800/60';
  }
  if (docTypes.size === 0) {
    return 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border-rose-200/80 dark:border-rose-800/60';
  }
  return 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border-amber-200/80 dark:border-amber-800/60';
}
</script>
