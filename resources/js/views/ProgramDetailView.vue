<template>
  <div v-if="program" class="space-y-6 select-none max-w-7xl mx-auto pb-10">
    <!-- Judul, ringkasan, dan status -->
    <div>
      <h1 class="text-xl sm:text-2xl font-semibold tracking-tight text-slate-900 dark:text-slate-100">
        {{ program.program_name }}
      </h1>
      <p v-if="canViewMasterColumns" class="text-sm text-slate-500 dark:text-slate-400 mt-1">
        {{ program.supplier }} · {{ program.category }} · {{ program.brand || getProgramBrand(program) }}
      </p>
      <div class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-3 text-xs text-slate-500 dark:text-slate-400">
        <span class="inline-flex items-center gap-1.5 font-medium text-slate-700 dark:text-slate-300">
          <span
            class="w-1.5 h-1.5 rounded-full"
            :class="{
              'bg-emerald-600': completeness.count === 3,
              'bg-amber-500': completeness.count > 0 && completeness.count < 3,
              'bg-slate-400': completeness.count === 0
            }"
          ></span>
          {{ statusBadgeText }}
        </span>
        <span class="text-slate-300 dark:text-slate-600">·</span>
        <span>Diarsipkan oleh {{ uploaderName }} · diperbarui {{ formatDate(program.program_date) }}</span>
      </div>
    </div>

    <!-- Dokumen: tiga kartu berjejer -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <section
        v-for="cat in documentCategories"
        :key="cat.type"
        class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 p-4 flex flex-col"
      >
        <div class="flex items-center justify-between gap-3 mb-3 min-h-[28px]">
          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
            {{ cat.label }}
            <span v-if="getDocs(cat.type).length > 0" class="ml-1 text-xs font-normal text-slate-400 dark:text-slate-500 tabular-nums">
              {{ getDocs(cat.type).length }}
            </span>
          </h3>
          <button
            v-if="getDocs(cat.type).length > 0 && canUploadDoc(cat.type)"
            type="button"
            class="px-2.5 py-1 rounded-md text-xs font-medium border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            title="Unggah berkas tambahan"
            @click="openUpload(cat.type, cat.label)"
          >
            Tambah
          </button>
        </div>

        <!-- Daftar berkas -->
        <ul v-if="getDocs(cat.type).length > 0" class="-mx-2 max-h-[208px] overflow-y-auto">
          <li
            v-for="(doc, dIdx) in getDocs(cat.type)"
            :key="doc.id || doc.file_name || dIdx"
            class="flex items-center gap-2 px-2 py-2 rounded-lg hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
          >
            <div class="min-w-0 flex-1">
              <p class="text-[13px] font-medium text-slate-800 dark:text-slate-200 truncate" :title="doc.file_name">
                {{ doc.file_name }}
              </p>
              <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">
                {{ formatUploadDate(doc.uploaded_at || program.program_date) }}
              </p>
            </div>
            <div class="flex items-center shrink-0">
              <button
                type="button"
                class="p-1.5 rounded-md text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 transition-colors cursor-pointer"
                title="Pratinjau berkas"
                @click="openPreviewDoc(doc)"
              >
                <Eye class="w-4 h-4" />
              </button>
              <button
                type="button"
                class="p-1.5 rounded-md text-slate-400 dark:text-slate-500 hover:text-slate-900 dark:hover:text-slate-100 transition-colors cursor-pointer"
                title="Unduh berkas"
                @click="downloadDocItem(doc)"
              >
                <Download class="w-4 h-4" />
              </button>
              <button
                v-if="canDeleteDoc(cat.type)"
                type="button"
                class="p-1.5 rounded-md text-slate-400 dark:text-slate-500 hover:text-rose-600 dark:hover:text-rose-400 transition-colors cursor-pointer"
                title="Hapus berkas ini"
                @click="deleteDocItem(doc, cat.label)"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
          </li>
        </ul>

        <!-- Belum ada berkas: area unggah, atau keterangan bila tidak berhak mengunggah -->
        <button
          v-else-if="canUploadDoc(cat.type)"
          type="button"
          class="flex-1 min-h-[96px] rounded-lg border border-dashed border-slate-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-500 hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors flex flex-col items-center justify-center gap-1 cursor-pointer"
          @click="openUpload(cat.type, cat.label)"
        >
          <span class="text-xs font-medium text-slate-700 dark:text-slate-300">Unggah {{ cat.label }}</span>
          <span class="text-[11px] text-slate-400 dark:text-slate-500">PDF, JPG, PNG · maks. 15 MB</span>
        </button>
        <div
          v-else
          class="flex-1 min-h-[96px] rounded-lg border border-dashed border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center gap-1 px-3 text-center"
        >
          <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Belum ada berkas</span>
          <span class="text-[11px] text-slate-400 dark:text-slate-500">
            {{ cat.type === 'mou' ? 'Diunggah oleh Tim Gudang / SCM' : 'Diunggah oleh Tim Finance / SCM' }}
          </span>
        </div>
      </section>
    </div>

    <!-- Rincian: nilai & perpajakan, serta data program -->
    <div
      v-if="canViewFinanceColumns || canViewMasterColumns"
      class="grid grid-cols-1 gap-4"
      :class="(canViewFinanceColumns && canViewMasterColumns) ? 'lg:grid-cols-2' : 'lg:grid-cols-1'"
    >
      <section v-if="canViewFinanceColumns" class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 flex flex-col">
        <h3 class="px-5 pt-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Nilai & Perpajakan</h3>

        <dl class="px-5 pt-4 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-4">
          <div v-for="field in invoiceFields" :key="field.label" class="min-w-0">
            <dt class="text-[11px] text-slate-500 dark:text-slate-400">{{ field.label }}</dt>
            <dd class="mt-0.5 text-[13px] font-medium text-slate-800 dark:text-slate-200 truncate" :title="field.value">{{ field.value || '-' }}</dd>
          </div>
        </dl>

        <dl class="mx-5 mt-5 border-t border-slate-100 dark:border-slate-800 divide-y divide-slate-100 dark:divide-slate-800">
          <div v-for="row in amountRows" :key="row.label" class="py-2 flex items-center justify-between text-xs">
            <dt class="text-slate-500 dark:text-slate-400">{{ row.label }}</dt>
            <dd class="font-medium text-slate-800 dark:text-slate-200 tabular-nums">{{ formatRupiah(row.value) }}</dd>
          </div>
        </dl>

        <div class="mt-auto px-5 py-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between gap-3">
          <div class="flex items-center gap-2 min-w-0">
            <span class="text-xs font-medium text-slate-700 dark:text-slate-300" title="DPP + PPN (termasuk pajak)">Total Payment</span>
            <div class="relative inline-flex items-center group">
              <span
                class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md text-[11px] font-medium border transition-colors select-none"
                :class="[
                  getPaymentBadgeClass(program.payment_status),
                  (!canEditFinance && !isAdmin) ? 'opacity-85' : ''
                ]"
              >
                <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getPaymentDotClass(program.payment_status)"></span>
                <span>{{ formatPaymentStatus(program.payment_status) }}</span>
                <ChevronDown v-if="canEditFinance || isAdmin" class="w-3 h-3 text-slate-400 dark:text-slate-500 shrink-0" />
              </span>
              <select
                :value="normalizePayStatus(program.payment_status)"
                :disabled="!canEditFinance && !isAdmin"
                @change="handlePaymentStatusChange($event.target.value)"
                class="absolute inset-0 w-full h-full opacity-0 cursor-pointer disabled:cursor-default"
                title="Ubah Status Payment"
              >
                <option v-for="opt in editPaymentStatusOptions" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>
            </div>
          </div>
          <span class="text-base font-semibold text-slate-900 dark:text-slate-100 tabular-nums">
            {{ formatRupiah(program.total_invoice) }}
          </span>
        </div>
      </section>

      <section v-if="canViewMasterColumns" class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800">
        <h3 class="px-5 pt-4 text-sm font-semibold text-slate-900 dark:text-slate-100">Data Program & Supplier</h3>

        <dl class="px-5 py-4 grid grid-cols-2 gap-x-6 gap-y-4">
          <div v-for="field in programFields" :key="field.label" class="min-w-0">
            <dt class="text-[11px] text-slate-500 dark:text-slate-400">{{ field.label }}</dt>
            <dd class="mt-0.5 text-[13px] font-medium text-slate-800 dark:text-slate-200 truncate" :title="field.value">{{ field.value || '-' }}</dd>
          </div>
        </dl>
      </section>
    </div>

    <!-- Edit Program Modal -->
    <div
      v-if="isEditModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs" @click="isEditModalOpen = false"></div>
      <div class="relative bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 w-full max-w-xl overflow-hidden z-10 animate-in fade-in zoom-in-95 duration-150">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">Ubah Data Program</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Wewenang: {{ store.currentUser.value?.role || 'Pengguna' }}</p>
          </div>
          <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer" @click="isEditModalOpen = false">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveEditProgram" class="p-6 space-y-5 text-xs max-h-[75vh] overflow-y-auto">
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
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:outline-hidden disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
              />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Supplier</label>
                <select
                  v-model="editForm.supplier"
                  required
                  @change="onEditSupplierChange"
                  :disabled="!canEditPurchase"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                >
                  <option v-for="s in masterSuppliers" :key="s.name" :value="s.name">
                    {{ s.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">NPWP Supplier</label>
                <input
                  v-model="editForm.npwp"
                  type="text"
                  :disabled="!canEditPurchase"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-slate-400 focus:outline-hidden disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Company Name</label>
                <select
                  v-model="editForm.company_name"
                  :disabled="!canEditPurchase"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                >
                  <option v-for="c in availableCompanies" :key="c" :value="c">
                    {{ c }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">No. PO / SJ</label>
                <input
                  v-model="editForm.po_sj_number"
                  type="text"
                  placeholder="PO/SCM/2026/001"
                  :disabled="!canEditPurchase"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-slate-400 focus:outline-hidden disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
                <select
                  v-model="editForm.category"
                  :disabled="!canEditPurchase"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                >
                  <option v-for="cat in availableCategories" :key="cat" :value="cat">
                    {{ cat }}
                  </option>
                </select>
              </div>

              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Brand</label>
                <select
                  v-model="editForm.brand"
                  :disabled="!canEditPurchase"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                >
                  <option v-for="b in availableBrands" :key="b" :value="b">
                    {{ b }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <!-- Section 2: Data Nilai & Perpajakan -->
          <div class="space-y-3 pt-2">
            <div class="border-b border-slate-200 dark:border-slate-800 pb-2 flex items-center justify-between">
              <span class="font-semibold text-slate-900 dark:text-slate-100">Data Nilai & Faktur Pajak</span>
              <span v-if="!canEditFinance" class="text-[11px] text-slate-400 dark:text-slate-500 font-medium">Hanya baca</span>
            </div>

            <!-- No. Invoice & Status Payment -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">No. Invoice</label>
                <input
                  v-model="editForm.invoice_number"
                  type="text"
                  placeholder="Contoh: INV/2026/001"
                  :disabled="!canEditFinance"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-slate-400 focus:outline-hidden disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                />
              </div>
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Status Payment</label>
                <select
                  :value="normalizePayStatus(editForm.payment_status)"
                  @change="editForm.payment_status = $event.target.value"
                  :disabled="!canEditFinance && !isAdmin"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                >
                  <option v-for="opt in editPaymentStatusOptions" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </option>
                </select>
              </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nilai DPP (IDR)</label>
                <input
                  v-model.number="editForm.dpp"
                  type="number"
                  required
                  :disabled="!canEditFinance"
                  @input="calculateTaxes"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-slate-400 focus:outline-hidden disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                />
              </div>
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tarif PPN</label>
                <select
                  v-model="editForm.ppn_rate"
                  :disabled="!canEditFinance"
                  @change="calculateTaxes"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                >
                  <option value="0.11">11% (Standar)</option>
                  <option value="0.12">12%</option>
                  <option value="0">0% (Non-PPN)</option>
                </select>
                <p v-if="editForm.ppn_rate === '0'" class="text-[10px] text-emerald-600 dark:text-emerald-400 mt-0.5 font-medium">Non-PPN (0%)</p>
                <p v-else class="text-[10px] text-blue-600 dark:text-blue-400 mt-0.5 font-medium">PPN {{ Number(editForm.ppn_rate) * 100 }}%</p>
              </div>
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Nilai PPN (IDR)</label>
                <input
                  :value="editForm.ppn"
                  type="number"
                  readonly
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 cursor-not-allowed"
                />
              </div>
            </div>
            <!-- Total Invoice -->
            <div>
              <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Total Invoice (IDR)</label>
              <input
                :value="editForm.dpp + editForm.ppn"
                type="number"
                readonly
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 font-mono bg-slate-100 dark:bg-slate-800 font-medium text-slate-700 dark:text-slate-300 cursor-not-allowed"
              />
              <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 font-mono">{{ formatRupiah(editForm.dpp + editForm.ppn) }}</p>
            </div>

            <!-- Jenis PPh & Nilai PPh -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Jenis PPh</label>
                <select
                  v-model="editForm.pph_type"
                  :disabled="!canEditFinance"
                  @change="onPphTypeChange"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:outline-hidden cursor-pointer disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
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
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-hidden disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                />
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-0.5 font-mono">
                  {{ formatRupiah(editForm.pph || 0) }}
                </p>
              </div>
            </div>

            <!-- No. Faktur Pajak & Tanggal Faktur Pajak -->
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">No. Faktur Pajak</label>
                <input
                  v-model="editForm.faktur_number"
                  type="text"
                  placeholder="010.000-25.00000001"
                  :disabled="!canEditFinance"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-slate-400 focus:outline-hidden disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                />
                <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Format: XXX.XXX-XX.XXXXXXXX</p>
              </div>
              <div>
                <label class="block font-medium text-slate-700 dark:text-slate-300 mb-1">Tanggal Faktur Pajak</label>
                <input
                  v-model="editForm.faktur_date"
                  type="date"
                  :disabled="!canEditFinance"
                  class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-slate-400 focus:outline-hidden disabled:bg-slate-50 dark:disabled:bg-slate-800/50 disabled:text-slate-500 dark:disabled:text-slate-400 disabled:cursor-not-allowed"
                />
              </div>
            </div>
          </div>

          <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5">
            <button
              type="button"
              :disabled="isSavingEdit"
              class="px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              @click="isEditModalOpen = false"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="isSavingEdit"
              class="px-5 py-2 rounded-lg font-medium transition-colors cursor-pointer flex items-center gap-2 disabled:opacity-75 disabled:cursor-not-allowed bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700"
            >
              <span v-if="isSavingEdit" class="w-3.5 h-3.5 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"></span>
              <span>{{ isSavingEdit ? 'Menyimpan...' : 'Simpan Perubahan' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Upload Document Modal -->
    <DocumentUploadModal
      v-model:open="isUploadModalOpen"
      :docType="uploadTargetType"
      :docLabel="uploadTargetLabel"
      @uploaded="handleDocumentUploaded"
    />

    <!-- Document Preview Sheet -->
    <DocumentPreviewSheet
      v-model:open="isPreviewSheetOpen"
      :document="selectedPreviewDoc"
      :program="program"
    />

    <!-- Modal Konfirmasi Hapus Berkas Dokumen -->
    <Teleport to="body">
      <div
        v-if="docToDelete"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 dark:bg-slate-950/80 backdrop-blur-xs"
      >
        <div class="bg-white dark:bg-[#111827] rounded-xl max-w-sm w-full p-5 shadow-xl border border-slate-200 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-150">
          <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center mx-auto mb-3 border border-slate-200 dark:border-slate-700">
            <Trash2 class="w-5 h-5 text-red-600 dark:text-rose-400" />
          </div>

          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 text-center mb-1">
            Hapus Berkas Dokumen?
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 text-center leading-relaxed mb-4">
            Apakah Anda yakin ingin menghapus berkas <strong class="text-slate-800 dark:text-slate-200">{{ docToDelete.label }}</strong>? Tindakan ini tidak dapat dibatalkan.
          </p>

          <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-lg border border-slate-200 dark:border-slate-800 mb-4 text-xs">
            <div class="flex items-center justify-between">
              <span class="text-slate-500 dark:text-slate-400">Tipe Dokumen:</span>
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ docToDelete.label }}</span>
            </div>
            <div v-if="docToDelete.fileName" class="flex items-center justify-between mt-1 pt-1 border-t border-slate-200 dark:border-slate-800">
              <span class="text-slate-500 dark:text-slate-400">Nama File:</span>
              <span class="font-mono text-slate-700 dark:text-slate-300 truncate max-w-[160px]">{{ docToDelete.fileName }}</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2.5">
            <button
              type="button"
              :disabled="isDeletingDoc"
              class="w-full py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-xs transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              @click="docToDelete = null"
            >
              Batal
            </button>
            <button
              type="button"
              :disabled="isDeletingDoc"
              class="w-full py-2 rounded-lg font-medium text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-60 disabled:cursor-not-allowed bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40"
              @click="confirmDeleteDoc"
            >
              <Loader2 v-if="isDeletingDoc" class="w-3.5 h-3.5 animate-spin" />
              <Trash2 v-else class="w-3.5 h-3.5" />
              <span>{{ isDeletingDoc ? 'Menghapus...' : 'Ya, Hapus' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Konfirmasi Hapus Program -->
    <Teleport to="body">
      <div
        v-if="isDeleteProgramModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 dark:bg-slate-950/80 backdrop-blur-xs"
      >
        <div class="bg-white dark:bg-[#111827] rounded-xl max-w-sm w-full p-5 shadow-xl border border-slate-200 dark:border-slate-800 animate-in fade-in zoom-in-95 duration-150">
          <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 flex items-center justify-center mx-auto mb-3 border border-slate-200 dark:border-slate-700">
            <Trash2 class="w-5 h-5 text-red-600 dark:text-rose-400" />
          </div>

          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 text-center mb-1">
            Hapus Program Ini?
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 text-center leading-relaxed mb-4">
            Apakah Anda yakin ingin menghapus program <strong class="text-slate-800 dark:text-slate-200">{{ program?.program_name }}</strong>? Seluruh berkas perpajakan terkait akan dihapus.
          </p>

          <div class="grid grid-cols-2 gap-2.5">
            <button
              type="button"
              class="w-full py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-xs transition-colors cursor-pointer"
              @click="isDeleteProgramModalOpen = false"
            >
              Batal
            </button>
            <button
              type="button"
              class="w-full py-2 rounded-lg font-medium text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40"
              @click="executeDeleteProgram"
            >
              <Trash2 class="w-3.5 h-3.5" />
              <span>Ya, Hapus Program</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>

  <!-- Not Found State -->
  <div v-else class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 p-12 text-center space-y-3">
    <FileText class="w-10 h-10 text-slate-300 dark:text-slate-600 mx-auto" />
    <h3 class="text-base font-bold text-slate-800 dark:text-slate-200">Program Tidak Ditemukan</h3>
    <p class="text-xs text-slate-500 dark:text-slate-400">Program mungkin telah dihapus atau URL tidak sesuai.</p>
    <router-link
      to="/programs"
      class="inline-flex items-center gap-2 px-4 py-2 text-xs rounded-lg bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium"
    >
      <ArrowLeft class="w-3.5 h-3.5" />
      <span>Kembali ke Arsip Program</span>
    </router-link>
  </div>
</template>

<script setup>
import { computed, ref, reactive, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  ArrowLeft,
  Trash2,
  FileText,
  Eye,
  Download,
  ChevronDown,
  X,
  Loader2
} from 'lucide-vue-next';
import DocumentUploadModal from '../components/detail/DocumentUploadModal.vue';
import DocumentPreviewSheet from '../components/detail/DocumentPreviewSheet.vue';
import {
  useTaxStore,
  formatRupiah,
  formatDate,
  formatUploadDate,
  getCompleteness,
  getProgramMonth,
  getProgramYear,
  getProgramCompanyName,
  getProgramBrand,
  getProgramPoSjNumber,
  getPphLabel,
  calculatePphAmount,
  PPH_OPTIONS,
  isNonPpnCategory
} from '../store/taxStore';

const route = useRoute();
const router = useRouter();
const store = useTaxStore();

const isAdmin = computed(() => store.isAdmin.value);
const isGudang = computed(() => store.isGudang.value);
const isFinance = computed(() => store.isFinance.value);
const isScm = computed(() => store.isScm.value);
const canEditPurchase = computed(() => store.canEditPurchase.value);
const canEditFinance = computed(() => store.canEditFinance.value);
const canEditProgram = computed(() => store.canEditProgram.value);
const canViewMasterColumns = computed(() => store.canViewMasterColumns.value);
const canViewFinanceColumns = computed(() => store.canViewFinanceColumns.value);

function canUploadDoc(docType) {
  return store.canUploadDoc(docType);
}

function canDeleteDoc(docType) {
  return store.canDeleteDoc(docType);
}

const programId = computed(() => route.params.id);
const program = computed(() => store.getProgramById(programId.value));
const completeness = computed(() => getCompleteness(program.value));

const uploaderName = computed(() => store.currentUser.value?.name || 'Bagas Nugroho');

const documentCategories = [
  { type: 'invoice', label: 'Invoice' },
  { type: 'faktur_pajak', label: 'Faktur Pajak' },
  { type: 'mou', label: 'Memo/DO' }
];

// Rincian kartu bawah: label di atas nilai supaya ringkas
const invoiceFields = computed(() => {
  const p = program.value;
  if (!p) return [];
  return [
    { label: 'No. Invoice', value: p.invoice_number },
    { label: 'Tanggal Invoice', value: formatDate(p.program_date) },
    { label: 'Jatuh Tempo', value: formatDueDate(p.program_date) },
    { label: 'No. Faktur Pajak', value: p.faktur_number },
    { label: 'Tanggal Faktur Pajak', value: p.faktur_date ? formatDate(p.faktur_date) : '' }
  ];
});

const amountRows = computed(() => {
  const p = program.value;
  if (!p) return [];
  return [
    { label: 'DPP', value: p.dpp },
    { label: 'PPN (11%)', value: p.ppn },
    { label: getPphLabel(p.pph_type), value: p.pph || 0 }
  ];
});

const programFields = computed(() => {
  const p = program.value;
  if (!p) return [];
  return [
    { label: 'Company Name', value: p.company_name || getProgramCompanyName(p) },
    { label: 'No. PO / SJ', value: p.po_sj_number || getProgramPoSjNumber(p) },
    { label: 'Nama Supplier', value: p.supplier },
    { label: 'NPWP Supplier', value: p.npwp || '02.010.121.3-071.000' },
    { label: 'Kategori', value: p.category },
    { label: 'Brand', value: p.brand || getProgramBrand(p) },
    { label: 'Bulan / Masa Pajak', value: `${getProgramMonth(p.program_date)} ${getProgramYear(p.program_date)}` },
    { label: 'Nomor Memo/DO', value: p.mou_number || `DO/SCM/2026/${String(p.id).padStart(3, '0')}` },
    { label: 'Periode', value: `${formatDate(p.program_date)} – ${formatDueDate(p.program_date)}` }
  ];
});

function getDocs(docType) {
  return (program.value?.documents || []).filter(d => d.document_type === docType);
}

function getDoc(docType) {
  return (program.value?.documents || []).find(d => d.document_type === docType) || null;
}

// Status Badge text matching requirements
const statusBadgeText = computed(() => {
  const docs = program.value?.documents || [];
  const hasInvoice = docs.some(d => d.document_type === 'invoice');
  const hasFaktur = docs.some(d => d.document_type === 'faktur_pajak');
  const hasMou = docs.some(d => d.document_type === 'mou');

  const count = (hasInvoice ? 1 : 0) + (hasFaktur ? 1 : 0) + (hasMou ? 1 : 0);
  if (count === 3) return 'Dokumen Lengkap';
  if (!hasFaktur && hasInvoice && hasMou) return 'Kurang Faktur Pajak';
  if (!hasInvoice && hasFaktur && hasMou) return 'Kurang Invoice';
  if (!hasMou && hasInvoice && hasFaktur) return 'Kurang Memo/DO';
  if (count === 0) return 'Belum Ada Dokumen';
  return `Kurang ${3 - count} Dokumen`;
});

function formatDueDate(dateStr) {
  if (!dateStr) return '-';
  try {
    const s = String(dateStr).trim();
    let d;
    const dmy = s.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})/);
    if (dmy) {
      d = new Date(parseInt(dmy[3], 10), parseInt(dmy[2], 10) - 1, parseInt(dmy[1], 10));
    } else {
      const ymd = s.match(/^(\d{4})[\/\-\.](\d{1,2})[\/\-\.](\d{1,2})/);
      if (ymd) {
        d = new Date(parseInt(ymd[1], 10), parseInt(ymd[2], 10) - 1, parseInt(ymd[3], 10));
      } else {
        d = new Date(s);
      }
    }
    if (isNaN(d.getTime())) return '-';
    d.setDate(d.getDate() + 30);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}-${month}-${year}`;
  } catch (e) {
    return '-';
  }
}

function goBack() {
  router.push('/programs');
}

// Upload Modal State
const isUploadModalOpen = ref(false);
const uploadTargetType = ref('faktur_pajak');
const uploadTargetLabel = ref('Faktur Pajak');

function openUpload(docType, label) {
  uploadTargetType.value = docType;
  uploadTargetLabel.value = label;
  isUploadModalOpen.value = true;
}

async function handleDocumentUploaded(fileData) {
  if (Array.isArray(fileData)) {
    for (const f of fileData) {
      await store.uploadDocument(program.value.id, f.docType, f);
    }
  } else {
    await store.uploadDocument(program.value.id, fileData.docType, fileData);
  }
}

const isAnalyzingDocId = ref(null);
async function runAiAnalysis(doc) {
  if (!doc?.id || !program.value?.id || isAnalyzingDocId.value) return;
  isAnalyzingDocId.value = doc.id;
  try {
    await store.analyzeDocumentAi(program.value.id, doc.id);
  } finally {
    isAnalyzingDocId.value = null;
  }
}

// Preview Sheet State
const isPreviewSheetOpen = ref(false);
const selectedPreviewDoc = ref(null);

function openPreviewDoc(doc) {
  selectedPreviewDoc.value = doc;
  isPreviewSheetOpen.value = true;
}

function openPreview(docType) {
  const existing = getDoc(docType);
  selectedPreviewDoc.value = existing || {
    document_type: docType,
    file_name: `${docType.toUpperCase()}-${program.value?.invoice_number?.replace(/\//g, '-') || program.value?.id}.pdf`,
    uploaded_at: formatDate(program.value?.program_date),
    uploaded_by: uploaderName.value
  };
  isPreviewSheetOpen.value = true;
}

async function downloadDocItem(doc) {
  if (!doc) return;
  const fileName = doc.file_name || `dokumen-${program.value?.id}.pdf`;

  let downloadUrl = doc.file_data || doc.file_url;
  if (!downloadUrl && doc.id) {
    downloadUrl = await store.loadDocumentContent(doc.id);
  }

  if (downloadUrl) {
    const a = document.createElement('a');
    a.href = downloadUrl;
    a.download = fileName;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    store.notify(`Dokumen ${fileName} berhasil diunduh.`);
    return;
  }

  const blob = new Blob([
    `SCM TaxVault Document Archive\nProgram: ${program.value?.program_name}\nSupplier: ${program.value?.supplier}\nBerkas: ${fileName}`
  ], { type: 'application/pdf' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = fileName;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  store.notify(`Dokumen ${fileName} telah diunduh.`);
}

async function downloadDoc(docType) {
  const doc = getDoc(docType);
  if (doc) {
    await downloadDocItem(doc);
  }
}

const docToDelete = ref(null);
const isDeletingDoc = ref(false);
const isDeleteProgramModalOpen = ref(false);

function deleteDocItem(doc, typeLabel) {
  docToDelete.value = {
    id: doc.id,
    type: doc.document_type || doc.type,
    label: typeLabel || store.getDocTypeLabel(doc.document_type || doc.type),
    fileName: doc.file_name || ''
  };
}

function deleteDoc(docType) {
  const label = store.getDocTypeLabel(docType);
  const existing = getDoc(docType);
  docToDelete.value = {
    id: existing?.id,
    type: docType,
    label: label,
    fileName: existing?.file_name || ''
  };
}

async function confirmDeleteDoc() {
  if (!docToDelete.value || isDeletingDoc.value) return;
  isDeletingDoc.value = true;
  try {
    const targetIdentifier = docToDelete.value.id || docToDelete.value.fileName || docToDelete.value.type;
    await store.deleteDocument(program.value.id, targetIdentifier);
  } finally {
    isDeletingDoc.value = false;
    docToDelete.value = null;
  }
}

// Available Categories for Edit Modal (inclusive of all system categories)
const availableCategories = computed(() => {
  const list = [...store.masterCategories.value];
  if (program.value?.category && !list.includes(program.value.category)) {
    list.unshift(program.value.category);
  }
  return list;
});

const masterBrands = computed(() => store.masterBrands.value);
const masterCompanies = computed(() => store.masterCompanies.value);
const masterSuppliers = computed(() => store.masterSuppliers.value);

const availableBrands = computed(() => {
  const list = [...store.masterBrands.value];
  if (editForm.brand && !list.includes(editForm.brand)) {
    list.unshift(editForm.brand);
  }
  return list;
});

const availableCompanies = computed(() => {
  const list = [...store.masterCompanies.value];
  if (editForm.company_name && !list.includes(editForm.company_name)) {
    list.unshift(editForm.company_name);
  }
  return list;
});

function onEditSupplierChange() {
  const match = masterSuppliers.value.find(s => s.name === editForm.supplier);
  if (match && match.npwp) {
    editForm.npwp = match.npwp;
  }
}

// Edit Modal State & Handling
const isEditModalOpen = ref(false);
const isSavingEdit = ref(false);
const editForm = reactive({
  program_name: '',
  supplier: '',
  company_name: '',
  npwp: '',
  po_sj_number: '',
  invoice_number: '',
  category: 'Logistik',
  brand: 'SCM',
  payment_status: 'WAITING PAYMENT',
  dpp: 0,
  ppn: 0,
  ppn_rate: '0.11',
  pph_type: 'NON_PPH',
  pph: 0,
  faktur_number: '',
  faktur_date: ''
});

// Auto-switch ppn_rate saat kategori di edit form berubah
watch(() => editForm.category, (cat) => {
  if (isNonPpnCategory(cat, editForm.program_name)) {
    editForm.ppn_rate = '0';
  } else {
    editForm.ppn_rate = '0.11';
  }
  calculateTaxes();
});

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

  const seen = new Set();
  return list.filter(item => {
    if (seen.has(item.value)) return false;
    seen.add(item.value);
    return true;
  });
});

async function handlePaymentStatusChange(newStatus) {
  if (!program.value?.id) return;
  await store.updatePaymentStatus(program.value.id, newStatus);
}

function openEditModal() {
  if (!program.value) return;
  // Modal langsung dibuka; data master diperbarui di belakang
  store.fetchMasterData();
  editForm.program_name = program.value.program_name || '';
  editForm.supplier = program.value.supplier || '';
  editForm.company_name = program.value.company_name || getProgramCompanyName(program.value) || '';
  editForm.npwp = program.value.npwp || '';
  editForm.po_sj_number = program.value.po_sj_number || getProgramPoSjNumber(program.value) || '';
  editForm.invoice_number = program.value.invoice_number || '';
  editForm.payment_status = program.value.payment_status || 'WAITING PAYMENT';
  editForm.category = program.value.category || 'Logistik';
  editForm.brand = program.value.brand || getProgramBrand(program.value) || '';
  editForm.dpp = Number(program.value.dpp) || 0;
  // Tentukan ppn_rate berdasar kategori
  const nonPpn = isNonPpnCategory(editForm.category, editForm.program_name);
  editForm.ppn_rate = nonPpn ? '0' : '0.11';
  editForm.ppn = nonPpn ? 0 : (Number(program.value.ppn) || Math.round(editForm.dpp * 0.11));
  editForm.pph_type = program.value.pph_type || 'NON_PPH';
  editForm.pph = Number(program.value.pph) || calculatePphAmount(editForm.dpp, editForm.pph_type);
  editForm.faktur_number = program.value.faktur_number || '';
  editForm.faktur_date = program.value.faktur_date ? String(program.value.faktur_date).slice(0, 10) : '';
  isEditModalOpen.value = true;
}

function calculateTaxes() {
  const rate = Number(editForm.ppn_rate) || 0;
  editForm.ppn = Math.round((Number(editForm.dpp) || 0) * rate);
  if (editForm.pph_type && editForm.pph_type !== 'NON_PPH') {
    editForm.pph = calculatePphAmount(editForm.dpp, editForm.pph_type);
  } else {
    editForm.pph = 0;
  }
}

function onPphTypeChange() {
  editForm.pph = calculatePphAmount(editForm.dpp, editForm.pph_type);
}

async function saveEditProgram() {
  if (isSavingEdit.value) return;
  isSavingEdit.value = true;
  try {
    const res = await store.updateProgram(program.value.id, {
      program_name: editForm.program_name,
      supplier: editForm.supplier,
      company_name: editForm.company_name,
      npwp: editForm.npwp,
      po_sj_number: editForm.po_sj_number,
      invoice_number: editForm.invoice_number || '',
      invoice_no: editForm.invoice_number || '',
      category: editForm.category,
      brand: editForm.brand,
      payment_status: editForm.payment_status,
      dpp: editForm.dpp,
      ppn: editForm.ppn,
      total_invoice: editForm.dpp + editForm.ppn,
      pph_type: editForm.pph_type,
      pph: editForm.pph,
      pph_amount: editForm.pph,
      faktur_number: editForm.faktur_number || '',
      tax_invoice_number: editForm.faktur_number || '',
      faktur_date: editForm.faktur_date || null,
      tax_invoice_date: editForm.faktur_date || null
    });
    if (res?.success) {
      isEditModalOpen.value = false;
    }
  } catch (err) {
    console.error('Gagal menyimpan program:', err);
  } finally {
    isSavingEdit.value = false;
  }
}

function confirmDeleteProgram() {
  isDeleteProgramModalOpen.value = true;
}

async function executeDeleteProgram() {
  isDeleteProgramModalOpen.value = false;
  await store.deleteProgram(program.value.id);
  router.push('/programs');
}
</script>
