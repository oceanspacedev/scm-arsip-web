<template>
  <div class="bg-white dark:bg-graphite-card rounded-xl border border-slate-200/90 dark:border-slate-800 overflow-hidden flex flex-col w-full min-w-0">
    <!--
      Tabel desktop: satu data per kolom, satu baris per sel. Teks panjang dipotong dan lengkapnya muncul saat kursor diarahkan.
      Kolom Program menempel di kiri dan Aksi di kanan saat tabel digeser ke samping. Baris berselang warna agar mudah diikuti.
    -->
    <div ref="tableScrollRef" class="hidden md:block overflow-x-auto w-full max-w-full" @scroll.passive="updateScrollEdges">
      <table class="w-full text-left text-[13px] border-collapse whitespace-nowrap">
        <thead>
          <tr class="border-b border-slate-300 dark:border-slate-700 text-xs font-medium text-slate-600 dark:text-slate-300">
            <th :class="[thClass, stickyLeftClass, 'z-10 bg-[#e8eaee]! dark:bg-[#28282b]!']">Program</th>
            <th v-if="show('date')" :class="thClass">Tanggal</th>
            <template v-if="canViewMasterColumns">
              <th v-if="show('category')" :class="thClass">Kategori</th>
              <th v-if="show('brand')" :class="thClass">Brand</th>
              <th v-if="show('company')" :class="thClass">Company</th>
              <th v-if="show('supplier')" :class="thClass">Supplier</th>
              <th v-if="show('po_sj')" :class="thClass">No. PO/SJ</th>
              <th v-if="show('warehouse')" :class="thClass">Kode gudang</th>
            </template>
            <template v-if="canViewFinanceColumns">
              <th v-if="show('invoice_number')" :class="thClass">No. invoice</th>
              <th v-if="show('faktur_number')" :class="thClass">No. faktur pajak</th>
              <th v-if="show('dpp')" :class="[thClass, 'text-right']">DPP</th>
              <th v-if="show('ppn')" :class="[thClass, 'text-right']">PPN</th>
              <th v-if="show('total')" :class="[thClass, 'text-right']">Total</th>
              <th v-if="show('payment')" :class="thClass">Pembayaran</th>
            </template>
            <th v-for="doc in visibleDocTypes" :key="doc.type" :class="thClass">{{ doc.label }}</th>
            <th v-if="show('completeness')" :class="thClass">Kelengkapan</th>
            <th :class="[thClass, stickyRightClass, 'z-10 bg-[#e8eaee]! dark:bg-[#28282b]!']"><span class="sr-only">Aksi</span></th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="(program, rowIdx) in paginatedPrograms"
            :key="program.id"
            class="group cursor-pointer border-b border-slate-200 dark:border-slate-800 text-slate-800 dark:text-slate-200"
            :class="rowBg(rowIdx)"
            @click="goToDetail(program.id)"
          >
            <td :class="[tdClass, stickyLeftClass, 'z-[1]', stickyBg(rowIdx)]">
              <router-link
                :to="`/programs/${program.id}`"
                class="block max-w-[300px] truncate font-medium text-slate-900 dark:text-slate-50 hover:underline underline-offset-2"
                :title="program.program_name"
                @click.stop
              >
                {{ program.program_name }}
              </router-link>
            </td>
            <td v-if="show('date')" :class="[tdClass, 'tabular-nums']">{{ formatDate(program.program_date) }}</td>

            <template v-if="canViewMasterColumns">
              <td v-if="show('category')" :class="tdClass">{{ program.category || '-' }}</td>
              <td v-if="show('brand')" :class="tdClass">{{ program.brand || getProgramBrand(program) || '-' }}</td>
              <td v-if="show('company')" :class="tdClass"><span class="block max-w-[200px] truncate" :title="getProgramCompanyName(program)">{{ getProgramCompanyName(program) }}</span></td>
              <td v-if="show('supplier')" :class="tdClass"><span class="block max-w-[220px] truncate" :title="program.supplier">{{ program.supplier || '-' }}</span></td>
              <td v-if="show('po_sj')" :class="[tdClass, 'tabular-nums']">{{ getProgramPoSjNumber(program) }}</td>
              <td v-if="show('warehouse')" :class="tdClass">{{ program.kode_gudang || '-' }}</td>
            </template>

            <template v-if="canViewFinanceColumns">
              <td v-if="show('invoice_number')" :class="[tdClass, 'tabular-nums']">{{ program.invoice_number || '-' }}</td>
              <td v-if="show('faktur_number')" :class="[tdClass, 'tabular-nums']">{{ program.faktur_number || '-' }}</td>
              <td v-if="show('dpp')" :class="[tdClass, 'text-right tabular-nums']">{{ formatRupiah(program.dpp) }}</td>
              <td v-if="show('ppn')" :class="[tdClass, 'text-right tabular-nums']">{{ formatRupiah(program.ppn) }}</td>
              <td v-if="show('total')" :class="[tdClass, 'text-right tabular-nums font-medium text-slate-900 dark:text-slate-50']">{{ formatRupiah(program.total_invoice) }}</td>
              <td v-if="show('payment')" :class="tdClass" @click.stop>
                <button
                  type="button"
                  class="-ml-2 inline-flex items-center gap-2 h-8 px-2 rounded-lg transition-colors"
                  :class="[
                    canChangePayment ? 'cursor-pointer hover:bg-slate-200/70 dark:hover:bg-slate-700/60' : 'cursor-default',
                    paymentMenu?.programId === program.id ? 'bg-slate-200/70 dark:bg-slate-700/60' : ''
                  ]"
                  :disabled="!canChangePayment"
                  :aria-haspopup="canChangePayment ? 'menu' : undefined"
                  :aria-expanded="paymentMenu?.programId === program.id"
                  aria-label="Status pembayaran"
                  @click="togglePaymentMenu($event, program)"
                >
                  <span class="w-2 h-2 rounded-full shrink-0" :class="getPaymentDotClass(program.payment_status)"></span>
                  <span>{{ formatPaymentStatus(program.payment_status) }}</span>
                  <ChevronDown v-if="canChangePayment" class="w-3.5 h-3.5 text-slate-400" :stroke-width="1.75" />
                </button>
              </td>
            </template>

            <!-- Tiga dokumen wajib: Ada (klik untuk lihat) / Unggah / Belum ada -->
            <td v-for="doc in visibleDocTypes" :key="doc.type" :class="tdClass" @click.stop>
              <button
                v-if="hasDoc(program, doc.type)"
                type="button"
                class="-ml-2 inline-flex items-center gap-1.5 h-8 px-2 rounded-lg text-emerald-700 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors cursor-pointer"
                :title="`Lihat ${doc.label}`"
                @click="handlePillClick(program, doc.type, doc.label)"
              >
                <CircleCheck class="w-4 h-4" :stroke-width="2" />
                <span>Ada</span>
              </button>
              <button
                v-else-if="store.canUploadDoc(doc.type)"
                type="button"
                class="-ml-2 inline-flex items-center gap-1.5 h-8 px-2 rounded-lg border border-dashed border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 hover:border-slate-500 hover:text-slate-900 dark:hover:border-slate-400 dark:hover:text-slate-100 transition-colors cursor-pointer"
                :title="`Unggah ${doc.label}`"
                @click="handlePillClick(program, doc.type, doc.label)"
              >
                <Upload class="w-3.5 h-3.5" :stroke-width="2" />
                <span>Unggah</span>
              </button>
              <span v-else class="text-slate-400 dark:text-slate-500">Belum ada</span>
            </td>

            <td v-if="show('completeness')" :class="tdClass">
              <span class="inline-flex items-center gap-2">
                <span class="w-2 h-2 rounded-full shrink-0" :class="docStatus(program).dot"></span>
                {{ docStatus(program).label }}
              </span>
            </td>

            <td :class="['px-2 py-2 z-[1]', stickyRightClass, stickyBg(rowIdx)]" @click.stop>
              <div class="flex items-center justify-end gap-0.5">
                <button
                  v-if="canEditProgram"
                  type="button"
                  class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-200/70 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-700/60 transition-colors cursor-pointer"
                  aria-label="Ubah program"
                  title="Ubah"
                  @click="openEditModal(program)"
                >
                  <Pencil class="w-4 h-4" :stroke-width="1.75" />
                </button>
                <button
                  v-if="isAdmin"
                  type="button"
                  class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:text-slate-400 dark:hover:text-rose-400 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                  aria-label="Hapus program"
                  title="Hapus"
                  @click="openDeleteConfirm(program)"
                >
                  <Trash2 class="w-4 h-4" :stroke-width="1.75" />
                </button>
                <ChevronRight v-if="!canEditProgram && !isAdmin" class="w-4 h-4 text-slate-400" />
              </div>
            </td>
          </tr>

          <tr v-if="filteredPrograms.length === 0">
            <td colspan="30" class="py-16 text-center whitespace-normal">
              <FolderArchive class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600" :stroke-width="1.5" />
              <p class="mt-3 text-sm text-slate-800 dark:text-slate-200">Tidak ada program yang cocok</p>
              <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ubah kata kunci atau reset filter</p>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Kartu program untuk layar kecil: ketuk kartu untuk membuka detail -->
    <div class="block md:hidden divide-y divide-slate-200 dark:divide-slate-800">
      <div
        v-for="program in paginatedPrograms"
        :key="program.id"
        class="p-4 active:bg-slate-50 dark:active:bg-slate-800/40 transition-colors cursor-pointer"
        @click="goToDetail(program.id)"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="font-medium text-slate-900 dark:text-slate-100 leading-5">{{ program.program_name }}</p>
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
              <span class="tabular-nums">{{ formatDate(program.program_date) }}</span>
              <template v-if="canViewMasterColumns">
                <span class="mx-1 text-slate-300 dark:text-slate-600">·</span>{{ program.category || '-' }}
              </template>
            </p>
          </div>
          <span class="shrink-0 inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300">
            <span class="w-2 h-2 rounded-full" :class="docStatus(program).dot"></span>
            {{ docStatus(program).label }}
          </span>
        </div>

        <dl v-if="canViewMasterColumns || canViewFinanceColumns" class="mt-3 space-y-1.5 text-sm">
          <div v-if="canViewMasterColumns" class="flex justify-between gap-4">
            <dt class="text-slate-500 dark:text-slate-400 shrink-0">Supplier</dt>
            <dd class="text-right text-slate-800 dark:text-slate-200 truncate">{{ program.supplier || '-' }}</dd>
          </div>
          <div v-if="canViewMasterColumns" class="flex justify-between gap-4">
            <dt class="text-slate-500 dark:text-slate-400 shrink-0">No. PO/SJ</dt>
            <dd class="text-right text-slate-800 dark:text-slate-200 tabular-nums truncate">{{ getProgramPoSjNumber(program) }}</dd>
          </div>
          <div v-if="canViewFinanceColumns" class="flex justify-between gap-4">
            <dt class="text-slate-500 dark:text-slate-400 shrink-0">No. invoice</dt>
            <dd class="text-right text-slate-800 dark:text-slate-200 tabular-nums truncate">{{ program.invoice_number || '-' }}</dd>
          </div>
          <div v-if="canViewFinanceColumns" class="flex justify-between items-center gap-4">
            <dt class="text-slate-500 dark:text-slate-400 shrink-0">Total</dt>
            <dd class="text-right">
              <span class="font-medium text-slate-900 dark:text-slate-100 tabular-nums">{{ formatRupiah(program.total_invoice) }}</span>
              <span class="ml-2 inline-flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400">
                <span class="w-1.5 h-1.5 rounded-full" :class="getPaymentDotClass(program.payment_status)"></span>
                {{ formatPaymentStatus(program.payment_status) }}
              </span>
            </dd>
          </div>
        </dl>

        <!-- Dokumen + aksi -->
        <div class="mt-3 flex items-center justify-between gap-2" @click.stop>
          <div class="flex flex-wrap items-center gap-1">
            <button
              v-for="doc in DOC_TYPES"
              :key="doc.type"
              type="button"
              class="h-7 px-2 inline-flex items-center gap-1 rounded-md text-xs transition-colors"
              :class="hasDoc(program, doc.type)
                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 cursor-pointer'
                : store.canUploadDoc(doc.type)
                  ? 'border border-dashed border-slate-300 dark:border-slate-600 text-slate-600 dark:text-slate-300 cursor-pointer'
                  : 'border border-dashed border-slate-200 dark:border-slate-800 text-slate-400 dark:text-slate-500 cursor-not-allowed'"
              :title="docChipTitle(program, doc)"
              @click="handlePillClick(program, doc.type, doc.label)"
            >
              <CircleCheck v-if="hasDoc(program, doc.type)" class="w-3.5 h-3.5" :stroke-width="2" />
              <span v-else aria-hidden="true">+</span>
              {{ doc.type === 'faktur_pajak' ? 'Faktur' : doc.label }}
            </button>
          </div>
          <div class="flex items-center shrink-0">
            <button
              v-if="canEditProgram"
              type="button"
              class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 cursor-pointer"
              aria-label="Ubah program"
              @click="openEditModal(program)"
            >
              <Pencil class="w-4 h-4" :stroke-width="1.75" />
            </button>
            <button
              v-if="isAdmin"
              type="button"
              class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:text-slate-400 dark:hover:text-rose-400 dark:hover:bg-rose-950/40 cursor-pointer"
              aria-label="Hapus program"
              @click="openDeleteConfirm(program)"
            >
              <Trash2 class="w-4 h-4" :stroke-width="1.75" />
            </button>
          </div>
        </div>
      </div>

      <div v-if="filteredPrograms.length === 0" class="py-16 px-4 text-center">
        <FolderArchive class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600" :stroke-width="1.5" />
        <p class="mt-3 text-sm text-slate-800 dark:text-slate-200">Tidak ada program yang cocok</p>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Ubah kata kunci atau reset filter</p>
      </div>
    </div>

    <TablePagination
      v-model:page="currentPage"
      v-model:page-size="pageSize"
      :total="filteredPrograms.length"
      item-label="program"
    />

    <!-- Dialog ubah program -->
    <Teleport to="body">
      <div v-if="isEditModalOpen" :class="f.dialogOverlay" @click.self="isEditModalOpen = false">
        <form :class="f.dialogPanel" role="dialog" aria-labelledby="edit-program-title" @submit.prevent="saveEdit">
          <div :class="f.dialogHeader">
            <div class="min-w-0">
              <h2 id="edit-program-title" class="text-base font-medium text-slate-900 dark:text-slate-100">Ubah data program</h2>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Sebagai {{ store.currentUser.value?.role || 'pengguna' }}</p>
            </div>
            <button type="button" :class="f.closeButton" aria-label="Tutup" @click="isEditModalOpen = false">
              <X class="w-4 h-4" :stroke-width="1.75" />
            </button>
          </div>

          <div :class="f.dialogBody">
            <!-- Pengadaan -->
            <section>
              <div :class="f.sectionHead">
                <h3 :class="f.sectionTitle">Pengadaan</h3>
                <span v-if="!canEditPurchase" :class="f.readOnlyBadge"><Lock class="w-3.5 h-3.5" :stroke-width="1.75" />Hanya baca</span>
              </div>
              <div :class="f.fieldGrid">
                <div class="sm:col-span-2">
                  <label for="te-name" :class="f.fieldLabel">Nama program</label>
                  <input id="te-name" v-model="editForm.program_name" type="text" required :disabled="!canEditPurchase" :class="f.fieldInput" />
                </div>

                <div>
                  <label id="te-supplier" :class="f.fieldLabel">Supplier</label>
                  <SearchSelect
                    :model-value="editForm.supplier"
                    labelledby="te-supplier"
                    search-placeholder="Cari supplier"
                    :options="supplierEditOptions"
                    :disabled="!canEditPurchase"
                    @update:model-value="(v) => { editForm.supplier = v; onEditSupplierChange(); }"
                  />
                  <input v-if="editForm.supplier === '__custom__'" v-model="customEditSupplier" type="text" required placeholder="Nama supplier baru" :class="[f.fieldInput, 'mt-2']" />
                </div>
                <div>
                  <label for="te-npwp" :class="f.fieldLabel">NPWP supplier</label>
                  <input id="te-npwp" v-model="editForm.npwp" type="text" :disabled="!canEditPurchase" :class="[f.fieldInput, 'tabular-nums']" />
                </div>

                <div>
                  <label id="te-company" :class="f.fieldLabel">Company</label>
                  <SearchSelect v-model="editForm.company_name" labelledby="te-company" search-placeholder="Cari company" :options="companyEditOptions" :disabled="!canEditPurchase" />
                  <input v-if="editForm.company_name === '__custom__'" v-model="customEditCompany" type="text" placeholder="Nama company baru" :class="[f.fieldInput, 'mt-2']" />
                </div>
                <div>
                  <label for="te-po" :class="f.fieldLabel">No. PO/SJ</label>
                  <input id="te-po" v-model="editForm.po_sj_number" type="text" placeholder="PO/2025/1016" :disabled="!canEditPurchase" :class="[f.fieldInput, 'tabular-nums']" />
                </div>

                <div>
                  <label id="te-category" :class="f.fieldLabel">Kategori</label>
                  <SearchSelect v-model="editForm.category" labelledby="te-category" search-placeholder="Cari kategori" :options="categoryEditOptions" :disabled="!canEditPurchase" />
                  <input v-if="editForm.category === '__custom__'" v-model="customEditCategory" type="text" required placeholder="Kategori baru" :class="[f.fieldInput, 'mt-2']" />
                </div>
                <div>
                  <label id="te-brand" :class="f.fieldLabel">Brand</label>
                  <SearchSelect v-model="editForm.brand" labelledby="te-brand" search-placeholder="Cari brand" :options="brandEditOptions" :disabled="!canEditPurchase" />
                  <input v-if="editForm.brand === '__custom__'" v-model="customEditBrand" type="text" placeholder="Brand baru" :class="[f.fieldInput, 'mt-2']" />
                </div>

                <div>
                  <label id="te-warehouse" :class="f.fieldLabel">Kode gudang</label>
                  <SearchSelect v-model="editForm.kode_gudang" labelledby="te-warehouse" placeholder="Belum dipilih" search-placeholder="Cari kode gudang" :options="warehouseEditOptions" :disabled="!canEditPurchase" />
                  <input v-if="editForm.kode_gudang === '__custom__'" v-model="customEditWarehouse" type="text" placeholder="GDG-JKT-03" :class="[f.fieldInput, 'mt-2 uppercase']" />
                </div>
              </div>
            </section>

            <!-- Nilai & pajak -->
            <section>
              <div :class="f.sectionHead">
                <h3 :class="f.sectionTitle">Nilai dan pajak</h3>
                <span v-if="!canEditFinance" :class="f.readOnlyBadge"><Lock class="w-3.5 h-3.5" :stroke-width="1.75" />Hanya baca</span>
              </div>
              <div :class="f.fieldGrid">
                <div>
                  <label for="te-invoice" :class="f.fieldLabel">No. invoice</label>
                  <input id="te-invoice" v-model="editForm.invoice_number" type="text" :disabled="!canEditFinance" :class="[f.fieldInput, 'tabular-nums']" />
                </div>
                <div>
                  <label id="te-payment" :class="f.fieldLabel">Status pembayaran</label>
                  <SearchSelect
                    :model-value="normalizePayStatus(editForm.payment_status)"
                    labelledby="te-payment"
                    :options="editPaymentStatusOptions"
                    :disabled="!canEditFinance && !isAdmin"
                    @update:model-value="(v) => { editForm.payment_status = v; }"
                  />
                </div>

                <div>
                  <label for="te-faktur" :class="f.fieldLabel">No. faktur pajak</label>
                  <input id="te-faktur" v-model="editForm.faktur_number" type="text" placeholder="010.000-25.00000001" :disabled="!canEditFinance" :class="[f.fieldInput, 'tabular-nums']" />
                </div>
                <div>
                  <label for="te-faktur-date" :class="f.fieldLabel">Tanggal faktur pajak</label>
                  <input id="te-faktur-date" v-model="editForm.faktur_date" type="date" :disabled="!canEditFinance" :class="f.fieldInput" />
                </div>

                <div>
                  <label for="te-dpp" :class="f.fieldLabel">DPP</label>
                  <input id="te-dpp" v-model.number="editForm.dpp" type="number" required :disabled="!canEditFinance" :class="f.fieldNumber" @input="calculateTaxes" />
                </div>
                <div>
                  <label for="te-ppn" :class="f.fieldLabel">PPN</label>
                  <input id="te-ppn" v-model.number="editForm.ppn" type="number" :disabled="!canEditFinance" :class="f.fieldNumber" />
                  <p :class="f.fieldHint">Otomatis 11% dari DPP, bisa diubah</p>
                </div>

                <div>
                  <label id="te-pph-type" :class="f.fieldLabel">Jenis PPh</label>
                  <SearchSelect
                    :model-value="editForm.pph_type"
                    labelledby="te-pph-type"
                    :options="PPH_TYPE_OPTIONS"
                    :disabled="!canEditFinance"
                    @update:model-value="(v) => { editForm.pph_type = v; onPphTypeChange(); }"
                  />
                </div>
                <div>
                  <label for="te-pph" :class="f.fieldLabel">Nilai PPh</label>
                  <input id="te-pph" v-model.number="editForm.pph" type="number" :disabled="!canEditFinance" :class="f.fieldNumber" />
                </div>
              </div>

              <dl :class="f.summaryBox">
                <div :class="f.summaryRow">
                  <dt class="text-slate-500 dark:text-slate-400">DPP</dt>
                  <dd class="tabular-nums text-slate-800 dark:text-slate-200">{{ formatRupiah(editForm.dpp || 0) }}</dd>
                </div>
                <div :class="f.summaryRow">
                  <dt class="text-slate-500 dark:text-slate-400">PPN</dt>
                  <dd class="tabular-nums text-slate-800 dark:text-slate-200">{{ formatRupiah(editForm.ppn || 0) }}</dd>
                </div>
                <div :class="f.summaryRow">
                  <dt class="font-medium text-slate-900 dark:text-slate-100">Total invoice</dt>
                  <dd class="tabular-nums font-medium text-slate-900 dark:text-slate-100">{{ formatRupiah((editForm.dpp || 0) + (editForm.ppn || 0)) }}</dd>
                </div>
                <div v-if="editForm.pph_type !== 'NON_PPH'" :class="f.summaryRow">
                  <dt class="text-slate-500 dark:text-slate-400">PPh dipotong</dt>
                  <dd class="tabular-nums text-slate-800 dark:text-slate-200">{{ formatRupiah(editForm.pph || 0) }}</dd>
                </div>
              </dl>
            </section>
          </div>

          <div :class="f.dialogFooter">
            <button type="button" :class="f.buttonSecondary" :disabled="isSavingEdit" @click="isEditModalOpen = false">Batal</button>
            <button type="submit" :class="f.buttonPrimary" :disabled="isSavingEdit">
              <span v-if="isSavingEdit" class="w-4 h-4 rounded-full border-2 border-current/30 border-t-current animate-spin"></span>
              {{ isSavingEdit ? 'Menyimpan…' : 'Simpan perubahan' }}
            </button>
          </div>
        </form>
      </div>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div
        v-if="programToDelete"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/40 dark:bg-slate-950/70 backdrop-blur-xs animate-in fade-in duration-150"
        @click.self="programToDelete = null"
      >
        <div class="bg-white dark:bg-graphite-card rounded-2xl shadow-xl border border-slate-200 dark:border-slate-800 max-w-sm w-full p-5 text-center">
          <div class="w-10 h-10 rounded-full bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center mx-auto mb-3">
            <Trash2 class="w-5 h-5" />
          </div>
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-1">Hapus Program?</h3>
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
              class="w-full py-2 rounded-lg text-xs transition-colors cursor-pointer bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 font-medium"
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

    <!-- Menu status pembayaran (di body supaya tidak terpotong oleh tabel yang bisa digeser) -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition-[opacity,transform] duration-150 ease-[cubic-bezier(0.22,1,0.36,1)]"
        enter-from-class="opacity-0 -translate-y-1"
        leave-active-class="transition-opacity duration-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="paymentMenu"
          ref="paymentMenuRef"
          role="menu"
          aria-label="Ubah status pembayaran"
          class="fixed z-50 w-60 p-1 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 shadow-[0_8px_24px_rgba(0,0,0,0.10)] dark:shadow-[0_8px_24px_rgba(0,0,0,0.5)]"
          :style="{ top: paymentMenu.top + 'px', left: paymentMenu.left + 'px' }"
        >
          <p class="px-2.5 pt-1.5 pb-1 text-xs text-slate-500 dark:text-slate-400">Status pembayaran</p>
          <button
            v-for="opt in paymentMenuOptions"
            :key="opt.value"
            type="button"
            role="menuitemradio"
            :aria-checked="opt.value === paymentMenu.current"
            class="w-full h-9 px-2.5 flex items-center gap-2.5 rounded-lg text-left text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 focus-visible:bg-slate-100 dark:focus-visible:bg-slate-700 outline-none transition-colors cursor-pointer"
            @click="choosePaymentStatus(opt.value)"
          >
            <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getPaymentDotClass(opt.value)"></span>
            <span class="flex-1 truncate">{{ opt.label }}</span>
            <Check v-if="opt.value === paymentMenu.current" class="w-4 h-4 text-slate-900 dark:text-slate-100" :stroke-width="2" />
          </button>
        </div>
      </Transition>
    </Teleport>

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
import { computed, ref, reactive, watch, nextTick } from 'vue';
import { onClickOutside, onKeyStroke, useEventListener, useResizeObserver } from '@vueuse/core';
import { useProgramColumns } from '../../store/programColumns';
import { useRouter } from 'vue-router';
import { FolderArchive, Trash2, X, ChevronLeft, ChevronRight, Eye, Pencil, ChevronDown, Check, CircleCheck, Upload, Lock } from 'lucide-vue-next';
import {
  useTaxStore,
  formatRupiah,
  formatDate,
  getProgramCompanyName,
  getProgramBrand,
  getProgramPoSjNumber,
  calculatePphAmount,
  PPH_OPTIONS
} from '../../store/taxStore';
import DocumentPreviewSheet from '../detail/DocumentPreviewSheet.vue';
import DocumentUploadModal from '../detail/DocumentUploadModal.vue';
import TablePagination from '../ui/TablePagination.vue';
import SearchSelect from '../ui/SearchSelect.vue';
import * as f from './formStyles';
import { PPH_TYPE_OPTIONS } from './formStyles';

const router = useRouter();
const store = useTaxStore();

const isAdmin = computed(() => store.isAdmin.value);
const isGudang = computed(() => store.isGudang.value);
const isFinance = computed(() => store.isFinance.value);
const isScm = computed(() => store.isScm.value);
const canEditProgram = computed(() => store.canEditProgram.value);
const canEditPurchase = computed(() => store.canEditPurchase.value);
const canEditFinance = computed(() => store.canEditFinance.value);
const canViewMasterColumns = computed(() => store.canViewMasterColumns.value);
const canViewFinanceColumns = computed(() => store.canViewFinanceColumns.value);

// Payment Status Helpers
function normalizePayStatus(status) {
  if (!status) return 'WAITING PAYMENT';
  const s = String(status).trim();
  const lower = s.toLowerCase();
  if (lower === 'cbd' || lower === 'cbd (cash before delivery)') return 'cbd';
  if (lower === 'tempo') return 'tempo';
  if (lower === 'paid' || lower === 'lunas' || lower === 'paid (lunas)') return 'PAID';
  if (lower === 'waiting payment') return 'WAITING PAYMENT';
  return s;
}

function formatPaymentStatus(status) {
  if (!status) return 'Waiting Payment';
  const s = String(status).trim();
  const lower = s.toLowerCase();
  if (lower === 'cbd' || lower === 'cbd (cash before delivery)') return 'CBD';
  if (lower === 'tempo') return 'Tempo';
  if (lower === 'paid' || lower === 'lunas' || lower === 'paid (lunas)') return 'Paid';
  if (lower === 'waiting payment') return 'Waiting Payment';
  return s;
}

function getPaymentBadgeClass(status) {
  return 'bg-slate-50/90 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-slate-200/90 dark:border-slate-700/80 hover:bg-slate-100/90 dark:hover:bg-slate-700/70 hover:border-slate-300 dark:hover:border-slate-600';
}

function getPaymentDotClass(status) {
  const norm = normalizePayStatus(status);
  const s = String(norm).toLowerCase();
  if (s === 'cbd') return 'bg-amber-500';
  if (s === 'tempo') return 'bg-blue-500';
  if (s === 'paid' || s === 'lunas') return 'bg-emerald-500';
  if (s === 'waiting payment' || s.includes('waiting')) return 'bg-rose-500';
  return 'bg-blue-500';
}

const masterPaymentStatuses = computed(() => store.masterPaymentStatuses.value);

const editPaymentStatusOptions = computed(() => {
  const list = [];
  const master = masterPaymentStatuses.value.length > 0
    ? masterPaymentStatuses.value
    : ['Waiting Payment', 'CBD (Cash Before Delivery)', 'Tempo', 'Paid (Lunas)'];

  master.forEach(m => {
    list.push({
      value: normalizePayStatus(m),
      label: m
    });
  });

  if (editForm.payment_status) {
    const curNorm = normalizePayStatus(editForm.payment_status);
    if (!list.some(item => item.value === curNorm)) {
      list.unshift({
        value: curNorm,
        label: formatPaymentStatus(editForm.payment_status)
      });
    }
  }

  // Deduplicate by value
  const seen = new Set();
  return list.filter(item => {
    if (seen.has(item.value)) return false;
    seen.add(item.value);
    return true;
  });
});

async function handleSelectPaymentStatus(programId, newStatus) {
  await store.updatePaymentStatus(programId, newStatus);
}

// Menu status pembayaran di tabel desktop
const canChangePayment = computed(() => canEditFinance.value || isAdmin.value);
const paymentMenu = ref(null); // { programId, current, top, left }
const paymentMenuRef = ref(null);
const MENU_WIDTH = 240;

const paymentMenuOptions = computed(() => {
  const master = masterPaymentStatuses.value.length > 0
    ? masterPaymentStatuses.value
    : ['Waiting Payment', 'CBD (Cash Before Delivery)', 'Tempo', 'Paid (Lunas)'];
  const seen = new Set();
  return master
    .map(label => ({ value: normalizePayStatus(label), label }))
    .filter(opt => !seen.has(opt.value) && seen.add(opt.value));
});

function togglePaymentMenu(event, program) {
  if (!canChangePayment.value) return;
  if (paymentMenu.value?.programId === program.id) {
    paymentMenu.value = null;
    return;
  }
  paymentTrigger = event.currentTarget;
  paymentMenu.value = {
    programId: program.id,
    current: normalizePayStatus(program.payment_status),
    ...menuPosition(paymentTrigger.getBoundingClientRect()),
  };
  nextTick(() => paymentMenuRef.value?.querySelector('[aria-checked="true"]')?.focus({ preventScroll: true }));
}

let paymentTrigger = null;

// Di bawah tombol, rata kanan; naik ke atas tombol bila tidak cukup ruang di bawah
function menuPosition(rect) {
  const menuHeight = 40 + paymentMenuOptions.value.length * 36;
  const opensUp = rect.bottom + 4 + menuHeight > window.innerHeight - 8;
  return {
    top: opensUp ? rect.top - menuHeight - 4 : rect.bottom + 4,
    left: Math.min(Math.max(8, rect.right - MENU_WIDTH), window.innerWidth - MENU_WIDTH - 8),
  };
}

// Saat halaman/tabel digulir, menu ikut tombolnya; tutup bila tombol keluar dari layar
function followPaymentTrigger() {
  if (!paymentMenu.value || !paymentTrigger) return;
  const rect = paymentTrigger.getBoundingClientRect();
  if (rect.bottom < 0 || rect.top > window.innerHeight || !paymentTrigger.isConnected) {
    paymentMenu.value = null;
    return;
  }
  Object.assign(paymentMenu.value, menuPosition(rect));
}

async function choosePaymentStatus(value) {
  const menu = paymentMenu.value;
  paymentMenu.value = null;
  if (menu && value !== menu.current) {
    await handleSelectPaymentStatus(menu.programId, value);
  }
}

// Tutup saat klik di luar atau Esc
onClickOutside(paymentMenuRef, (event) => {
  if (!event.target.closest?.('[aria-label="Status pembayaran"]')) paymentMenu.value = null;
});
onKeyStroke('Escape', () => { paymentMenu.value = null; });
useEventListener(window, 'scroll', followPaymentTrigger, { capture: true, passive: true });
useEventListener(window, 'resize', followPaymentTrigger);

const filteredPrograms = computed(() => store.filteredPrograms.value);
const totalCount = computed(() => store.programs.value.length);
const categoriesList = computed(() => store.categoriesList.value.filter(c => c !== 'Semua Kategori'));

// Pagination (jumlah baris per halaman bisa dipilih di bawah tabel)
const pageSize = ref(10);
const currentPage = ref(1);

const paginatedPrograms = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return filteredPrograms.value.slice(start, start + pageSize.value);
});

// Filter atau pencarian berubah: kembali ke halaman 1
watch(
  () => [
    store.state.searchQuery, store.state.selectedStatus, store.state.selectedPaymentStatus,
    store.state.selectedSupplier, store.state.selectedCategory, store.state.selectedBrand,
    store.state.selectedMonth, store.state.selectedCompany,
  ],
  () => { currentPage.value = 1; }
);

// Data berkurang (mis. program dihapus): jangan tertinggal di halaman kosong
watch(filteredPrograms, (list) => {
  const lastPage = Math.max(1, Math.ceil(list.length / pageSize.value));
  if (currentPage.value > lastPage) currentPage.value = lastPage;
});

// Gaya sel tabel desktop. Baris berselang warna; sel yang menempel (Program, Aksi) memakai warna baris yang sama
const thClass = 'h-11 px-4 bg-slate-100 dark:bg-[#202022]';
const tdClass = 'h-12 px-4';
function rowBase(idx) {
  return idx % 2 ? 'bg-slate-50 dark:bg-[#1d1d1f]' : 'bg-white dark:bg-graphite-card';
}
// Baris yang disorot kursor diberi warna sama di semua sel, termasuk sel yang menempel
function rowBg(idx) {
  return `${rowBase(idx)} hover:bg-[#eef3fc] dark:hover:bg-[#242428]`;
}
// Kolom yang menempel (Program, Aksi) sedikit lebih gelap dari kolom yang bergeser, supaya terlihat terpisah
function stickyBg(idx) {
  const base = idx % 2 ? 'bg-[#eef0f3] dark:bg-[#252528]' : 'bg-[#f3f4f6] dark:bg-[#222225]';
  return `${base} group-hover:bg-[#e4ecfb] dark:group-hover:bg-[#2b2b30]`;
}

// Bayangan di tepi kolom yang menempel hanya saat ada isi tabel yang tergeser di bawahnya
const tableScrollRef = ref(null);
const scrollEdges = reactive({ left: false, right: false });

function updateScrollEdges() {
  const el = tableScrollRef.value;
  if (!el) return;
  scrollEdges.left = el.scrollLeft > 1;
  scrollEdges.right = el.scrollLeft + el.clientWidth < el.scrollWidth - 1;
}

const stickyLeftClass = computed(() => [
  'sticky left-0 border-r border-slate-300 dark:border-slate-700',
  scrollEdges.left ? 'shadow-[6px_0_10px_-6px_rgba(15,23,42,0.25)] dark:shadow-[6px_0_10px_-6px_rgba(0,0,0,0.8)]' : '',
]);
const stickyRightClass = computed(() => [
  'sticky right-0 border-l border-slate-300 dark:border-slate-700',
  scrollEdges.right ? 'shadow-[-6px_0_10px_-6px_rgba(15,23,42,0.25)] dark:shadow-[-6px_0_10px_-6px_rgba(0,0,0,0.8)]' : '',
]);

useResizeObserver(tableScrollRef, updateScrollEdges);

// Kolom yang dipilih pengguna (tombol "Kolom" di toolbar)
const { isVisible: isColumnVisible, hiddenKeys } = useProgramColumns();
function show(key) {
  return isColumnVisible(key);
}
const visibleDocTypes = computed(() => DOC_TYPES.filter(doc => show(`doc_${doc.type}`)));
watch([hiddenKeys, paginatedPrograms], () => nextTick(updateScrollEdges));

// Tiga dokumen wajib per program
const DOC_TYPES = [
  { type: 'invoice', short: 'IN', label: 'Invoice' },
  { type: 'faktur_pajak', short: 'FP', label: 'Faktur pajak' },
  { type: 'mou', short: 'DO', label: 'Memo/DO' },
];

function docStatus(program) {
  const count = new Set((program.documents || []).map(d => d.document_type)).size;
  if (count >= 3) return { label: 'Lengkap', dot: 'bg-emerald-500' };
  if (count === 0) return { label: 'Belum ada dokumen', dot: 'bg-rose-500' };
  return { label: `Kurang ${3 - count} dokumen`, dot: 'bg-amber-500' };
}

function docChipClass(program, docType) {
  const base = 'h-6 min-w-8 px-1.5 rounded-md text-[11px] font-medium transition-colors select-none';
  if (hasDoc(program, docType)) {
    return `${base} bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 cursor-pointer`;
  }
  if (store.canUploadDoc(docType)) {
    return `${base} border border-dashed border-slate-300 text-slate-400 hover:border-slate-500 hover:text-slate-700 dark:border-slate-600 dark:text-slate-500 dark:hover:text-slate-300 cursor-pointer`;
  }
  return `${base} border border-dashed border-slate-200 text-slate-300 dark:border-slate-800 dark:text-slate-600 cursor-not-allowed`;
}

function docChipTitle(program, doc) {
  if (hasDoc(program, doc.type)) return `${doc.label} sudah ada, klik untuk melihat`;
  return store.canUploadDoc(doc.type) ? `${doc.label} belum ada, klik untuk mengunggah` : `${doc.label} belum ada`;
}

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
  kode_gudang: '',
  po_sj_number: '',
  npwp: '',
  invoice_number: '',
  faktur_number: '',
  faktur_date: '',
  category: 'Logistik',
  brand: 'SCM',
  payment_status: 'WAITING PAYMENT',
  dpp: 0,
  ppn: 0,
  pph_type: 'NON_PPH',
  pph: 0
});

const masterCategories = computed(() => store.masterCategories.value);
const masterBrands = computed(() => store.masterBrands.value);
const masterCompanies = computed(() => store.masterCompanies.value);
const masterWarehouses = computed(() => store.masterWarehouses.value);
const masterSuppliers = computed(() => store.masterSuppliers.value);

const customEditCategory = ref('');
const customEditBrand = ref('');
const customEditCompany = ref('');
const customEditWarehouse = ref('');
const customEditSupplier = ref('');

const editCategoryOptions = computed(() => {
  const set = new Set(masterCategories.value);
  if (editForm.category && editForm.category !== '__custom__') {
    set.add(editForm.category);
  }
  return Array.from(set);
});

const editBrandOptions = computed(() => {
  const set = new Set(masterBrands.value);
  if (editForm.brand && editForm.brand !== '__custom__') {
    set.add(editForm.brand);
  }
  return Array.from(set);
});

const editCompanyOptions = computed(() => {
  const set = new Set(masterCompanies.value);
  if (editForm.company_name && editForm.company_name !== '__custom__') {
    set.add(editForm.company_name);
  }
  return Array.from(set);
});

const editWarehouseOptions = computed(() => {
  const list = [...masterWarehouses.value];
  if (editForm.kode_gudang && editForm.kode_gudang !== '__custom__') {
    if (!list.some(w => w.code === editForm.kode_gudang)) {
      list.unshift({ code: editForm.kode_gudang, name: editForm.kode_gudang });
    }
  }
  return list;
});

const editSupplierOptions = computed(() => {
  const list = [...masterSuppliers.value];
  if (editForm.supplier && editForm.supplier !== '__custom__') {
    if (!list.some(s => s.name === editForm.supplier)) {
      list.unshift({ name: editForm.supplier, npwp: editForm.npwp || '' });
    }
  }
  return list;
});

// Opsi dropdown dialog ubah ({ value, label }); "__custom__" membuka isian manual di bawahnya
const supplierEditOptions = computed(() => [
  ...editSupplierOptions.value.map(s => ({ value: s.name, label: s.name, hint: s.npwp || '' })),
  { value: '__custom__', label: '+ Supplier lain (ketik manual)' },
]);
const companyEditOptions = computed(() => [
  ...editCompanyOptions.value.map(c => ({ value: c, label: c })),
  { value: '__custom__', label: '+ Company lain (ketik manual)' },
]);
const categoryEditOptions = computed(() => [
  ...editCategoryOptions.value.map(c => ({ value: c, label: c })),
  { value: '__custom__', label: '+ Kategori lain (ketik manual)' },
]);
const brandEditOptions = computed(() => [
  ...editBrandOptions.value.map(b => ({ value: b, label: b })),
  { value: '__custom__', label: '+ Brand lain (ketik manual)' },
]);
const warehouseEditOptions = computed(() => [
  { value: '', label: 'Belum dipilih' },
  ...editWarehouseOptions.value.map(w => ({ value: w.code, label: w.code, hint: w.name !== w.code ? w.name : '' })),
  { value: '__custom__', label: '+ Kode gudang lain (ketik manual)' },
]);

function onEditSupplierChange() {
  if (editForm.supplier === '__custom__') return;
  const match = masterSuppliers.value.find(s => s.name === editForm.supplier);
  if (match && match.npwp) {
    editForm.npwp = match.npwp;
  }
}

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
    if (!store.canUploadDoc(docType)) {
      const roleName = store.currentUser.value?.role || 'Staff Gudang';
      store.notify(`Role ${roleName} hanya diizinkan mengunggah dokumen Memo/DO.`, 'warning');
      return;
    }
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
  // Modal langsung dibuka; data master diperbarui di belakang dan dropdown ikut terisi saat datanya tiba
  store.fetchMasterData();
  editProgramId.value = program.id;
  editForm.program_name = program.program_name || '';
  editForm.supplier = program.supplier || '';
  editForm.company_name = program.company_name || getProgramCompanyName(program);
  editForm.kode_gudang = program.kode_gudang || '';
  editForm.po_sj_number = program.po_sj_number || getProgramPoSjNumber(program);
  editForm.npwp = program.npwp || '';
  editForm.invoice_number = program.invoice_number || '';
  editForm.faktur_number = program.faktur_number || '';
  editForm.faktur_date = program.faktur_date ? String(program.faktur_date).slice(0, 10) : '';
  editForm.payment_status = program.payment_status || 'WAITING PAYMENT';
  editForm.category = program.category || masterCategories.value[0] || 'Promosi';
  editForm.brand = program.brand || getProgramBrand(program) || masterBrands.value[0] || '';
  editForm.dpp = Number(program.dpp) || 0;
  editForm.ppn = Number(program.ppn) || Math.round((Number(program.dpp) || 0) * 0.11);
  editForm.pph_type = program.pph_type || 'NON_PPH';
  editForm.pph = Number(program.pph) || calculatePphAmount(editForm.dpp, editForm.pph_type);

  customEditCategory.value = '';
  customEditBrand.value = '';
  customEditCompany.value = '';
  customEditWarehouse.value = '';
  customEditSupplier.value = '';

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

  const finalCategory = editForm.category === '__custom__' ? customEditCategory.value.trim() : editForm.category;
  const finalBrand = editForm.brand === '__custom__' ? customEditBrand.value.trim() : editForm.brand;
  const finalCompany = editForm.company_name === '__custom__' ? customEditCompany.value.trim() : editForm.company_name;
  const finalWarehouse = editForm.kode_gudang === '__custom__' ? customEditWarehouse.value.trim() : editForm.kode_gudang;
  const finalSupplier = editForm.supplier === '__custom__' ? customEditSupplier.value.trim() : editForm.supplier;

  isSavingEdit.value = true;
  try {
    if (editForm.brand === '__custom__' && finalBrand && !masterBrands.value.includes(finalBrand)) {
      store.addMasterItem('brands', finalBrand);
    }
    if (editForm.category === '__custom__' && finalCategory && !masterCategories.value.includes(finalCategory)) {
      store.addMasterItem('categories', finalCategory);
    }
    if (editForm.company_name === '__custom__' && finalCompany && !masterCompanies.value.includes(finalCompany)) {
      store.addMasterItem('companies', finalCompany);
    }
    if (editForm.supplier === '__custom__' && finalSupplier && !masterSuppliers.value.some(s => s.name === finalSupplier)) {
      store.addMasterItem('suppliers', { name: finalSupplier, npwp: editForm.npwp || '' });
    }

    const res = await store.updateProgram(editProgramId.value, {
      program_name: editForm.program_name,
      supplier: finalSupplier,
      company_name: finalCompany,
      kode_gudang: finalWarehouse,
      po_sj_number: editForm.po_sj_number,
      invoice_number: editForm.invoice_number || '',
      invoice_no: editForm.invoice_number || '',
      faktur_number: editForm.faktur_number || '',
      tax_invoice_number: editForm.faktur_number || '',
      faktur_date: editForm.faktur_date || null,
      tax_invoice_date: editForm.faktur_date || null,
      category: finalCategory,
      brand: finalBrand,
      payment_status: editForm.payment_status,
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

function getStatusText(program) {
  const docTypes = new Set((program.documents || []).map(d => d.document_type));
  if (docTypes.size >= 3) return 'Lengkap';
  if (docTypes.size === 0) return 'Belum Lengkap';
  return `Sebagian (${docTypes.size}/3)`;
}

function getStatusBadgeClass(program) {
  return 'bg-slate-50/90 dark:bg-slate-800/60 text-slate-700 dark:text-slate-300 border-slate-200/90 dark:border-slate-700/80';
}
</script>
