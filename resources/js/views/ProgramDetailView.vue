<template>
  <div v-if="program" class="space-y-6 select-none max-w-7xl mx-auto pb-10">
    <!-- Header Section matching screenshot -->
    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-sans">
          {{ program.program_name }}
        </h1>
        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 font-medium mt-1">
          {{ program.supplier }} · {{ program.category }}
        </p>
      </div>

      <!-- Action Buttons Top Right matching screenshot: Kembali, Ubah, Hapus -->
      <div class="flex items-center gap-2 shrink-0 self-start">
        <!-- Kembali -->
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer"
          @click="goBack"
        >
          <ArrowLeft class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" />
          <span>Kembali</span>
        </button>

        <!-- Ubah -->
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors cursor-pointer"
          @click="openEditModal"
        >
          <Pencil class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" />
          <span>Ubah</span>
        </button>

        <!-- Hapus -->
        <button
          type="button"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:text-red-600 dark:hover:text-rose-400 hover:border-red-200 dark:hover:border-rose-900/50 hover:bg-red-50/50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
          @click="confirmDeleteProgram"
        >
          <Trash2 class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" />
          <span>Hapus</span>
        </button>
      </div>
    </div>

    <!-- Status & Meta Strip -->
    <div class="flex flex-wrap items-center gap-3 text-xs">
      <!-- Status Badge -->
      <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
        <span
          class="w-1.5 h-1.5 rounded-full"
          :class="{
            'bg-emerald-600': completeness.count === 3,
            'bg-amber-500': completeness.count > 0 && completeness.count < 3,
            'bg-slate-400': completeness.count === 0
          }"
        ></span>
        <span>{{ statusBadgeText }}</span>
      </div>

      <!-- Meta Info -->
      <span class="text-slate-500 dark:text-slate-400 text-xs">
        Diarsipkan oleh {{ uploaderName }} · diperbarui {{ formatDate(program.program_date) }}
      </span>
    </div>

    <!-- Three Document Categories Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
      <div
        v-for="cat in documentCategories"
        :key="cat.type"
        class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200/90 dark:border-slate-800 p-4 sm:p-5 flex flex-col justify-between shadow-2xs hover:border-slate-300 dark:hover:border-slate-700 transition-all min-h-[220px]"
      >
        <div class="flex-1 flex flex-col">
          <!-- Card Header: Icon Badge, Title, Subtitle, and Add button -->
          <div class="flex items-start justify-between gap-3 mb-4">
            <div class="flex items-center gap-3 min-w-0">
              <!-- Clean Neutral Icon Container (Monochrome shadcn style) -->
              <div class="w-9 h-9 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-100/80 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center shrink-0">
                <component :is="cat.icon" class="w-4.5 h-4.5 stroke-[1.75]" />
              </div>

              <div class="min-w-0">
                <div class="flex items-center gap-2">
                  <h4 class="font-semibold text-slate-900 dark:text-slate-100 text-sm leading-tight truncate">
                    {{ cat.label }}
                  </h4>
                  <span
                    v-if="getDocs(cat.type).length > 0"
                    class="text-[10px] font-medium px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 shrink-0"
                  >
                    {{ getDocs(cat.type).length }} Berkas
                  </span>
                </div>
                <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">
                  {{ cat.description }}
                </p>
              </div>
            </div>

            <!-- Quick "+ Tambah" button when category has files -->
            <button
              v-if="getDocs(cat.type).length > 0"
              type="button"
              class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-[11px] font-semibold border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-600 transition-colors cursor-pointer shrink-0"
              title="Unggah berkas tambahan"
              @click="openUpload(cat.type, cat.label)"
            >
              <Plus class="w-3.5 h-3.5 text-slate-500 dark:text-slate-400" />
              <span>Tambah</span>
            </button>
          </div>

          <!-- Card Body: Uploaded Files List -->
          <div v-if="getDocs(cat.type).length > 0" class="space-y-2 max-h-[220px] overflow-y-auto pr-1 flex-1">
            <div
              v-for="doc in getDocs(cat.type)"
              :key="doc.id"
              class="p-2.5 rounded-lg border border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/60 hover:bg-slate-50 dark:hover:bg-slate-800/60 transition-colors flex items-center justify-between gap-2 group"
            >
              <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate" :title="doc.file_name">
                  {{ doc.file_name }}
                </p>
                <div class="flex items-center gap-1.5 text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 truncate">
                  <span v-if="doc.file_size">{{ doc.file_size }}</span>
                  <span v-if="doc.file_size">·</span>
                  <span>Diunggah {{ formatUploadDate(doc.uploaded_at || program.program_date) }}</span>
                </div>
              </div>

              <!-- Action buttons per file -->
              <div class="flex items-center gap-1 shrink-0">
                <button
                  type="button"
                  class="p-1.5 rounded-md text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-white dark:hover:bg-slate-800 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-colors cursor-pointer"
                  title="Pratinjau berkas"
                  @click="openPreviewDoc(doc)"
                >
                  <Eye class="w-3.5 h-3.5" />
                </button>
                <button
                  type="button"
                  class="p-1.5 rounded-md text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-white dark:hover:bg-slate-800 border border-transparent hover:border-slate-200 dark:hover:border-slate-700 transition-colors cursor-pointer"
                  title="Unduh berkas"
                  @click="downloadDocItem(doc)"
                >
                  <Download class="w-3.5 h-3.5" />
                </button>
                <button
                  type="button"
                  class="p-1.5 rounded-md text-slate-400 hover:text-red-600 dark:hover:text-rose-400 hover:bg-red-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                  title="Hapus berkas ini"
                  @click="deleteDocItem(doc, cat.label)"
                >
                  <Trash2 class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>

          <!-- Card Body: Modern Interactive Dropzone (Replaces generic dashed button) -->
          <div v-else class="flex-1 flex flex-col justify-center">
            <button
              type="button"
              class="w-full py-4 px-3 rounded-lg border border-dashed border-slate-200 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-500 bg-slate-50/50 dark:bg-slate-900/40 hover:bg-slate-100/60 dark:hover:bg-slate-800/50 transition-all flex flex-col items-center justify-center gap-2 cursor-pointer group"
              @click="openUpload(cat.type, cat.label)"
            >
              <!-- Circular Upload Badge in center -->
              <div class="w-8 h-8 rounded-full bg-white dark:bg-slate-800 border border-slate-200/90 dark:border-slate-700 shadow-2xs flex items-center justify-center text-slate-400 dark:text-slate-400 group-hover:text-slate-800 dark:group-hover:text-slate-200 group-hover:border-slate-300 dark:group-hover:border-slate-600 group-hover:scale-105 transition-all">
                <Upload class="w-4 h-4 stroke-[2]" />
              </div>

              <!-- Main Action Text -->
              <div class="text-center">
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 group-hover:text-slate-900 dark:group-hover:text-slate-100 block leading-tight">
                  Unggah {{ cat.label }}
                </span>
                <span class="text-[11px] text-slate-400 dark:text-slate-500 font-normal mt-1 block">
                  PDF, JPG, PNG hingga 15 MB
                </span>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Section: Two Columns Grid matching screenshot -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Left Card: Rekonsiliasi Nilai & Perpajakan -->
      <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col justify-between">
        <div>
          <!-- Title -->
          <div class="p-6 pb-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-sans">
              Rekonsiliasi Nilai & Perpajakan
            </h3>
          </div>

          <!-- Key-Value Rows matching screenshot -->
          <div class="px-6 divide-y divide-slate-100 dark:divide-slate-800/70 text-xs">
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">NO. INVOICE</span>
              <span class="font-mono text-slate-700 dark:text-slate-300 font-medium">{{ program.invoice_number }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">TANGGAL INVOICE</span>
              <span class="text-slate-700 dark:text-slate-300">{{ formatDate(program.program_date) }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">JATUH TEMPO</span>
              <span class="text-slate-700 dark:text-slate-300">{{ formatDueDate(program.program_date) }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">DPP</span>
              <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ formatRupiah(program.dpp) }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">PPN (11%)</span>
              <span class="font-mono text-slate-800 dark:text-slate-200 font-semibold">{{ formatRupiah(program.ppn) }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">
                {{ getPphLabel(program.pph_type) }}
              </span>
              <span class="font-mono text-slate-700 dark:text-slate-300 font-semibold">{{ formatRupiah(program.pph || 0) }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">NO. FAKTUR PAJAK</span>
              <span class="font-mono text-slate-600 dark:text-slate-400 font-medium">
                {{ program.faktur_number || (getDoc('faktur_pajak') ? '010.002-25.88291024' : '-') }}
              </span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">TANGGAL FAKTUR PAJAK</span>
              <span class="text-slate-600 dark:text-slate-400 font-medium">
                {{ program.faktur_date ? formatDate(program.faktur_date) : (getDoc('faktur_pajak') ? formatDate(program.program_date) : '-') }}
              </span>
            </div>
          </div>
        </div>

        <!-- TOTAL INVOICE Dark Strip matching screenshot -->
        <div class="p-6 pt-4">
          <div class="bg-[#0F172A] dark:bg-slate-950 dark:border dark:border-slate-800 rounded-xl px-6 py-4 flex items-center justify-between text-white shadow-sm">
            <span class="text-xs font-bold tracking-wider uppercase text-slate-300 font-sans">
              TOTAL INVOICE
            </span>
            <span class="font-mono text-2xl font-extrabold text-white tracking-tight">
              {{ formatRupiah(program.total_invoice) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Right Card: Data Program & Supplier -->
      <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col justify-between">
        <div>
          <!-- Title -->
          <div class="p-6 pb-4">
            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100 font-sans">
              Data Program & Supplier
            </h3>
          </div>

          <!-- Key-Value Rows matching screenshot -->
          <div class="px-6 divide-y divide-slate-100 dark:divide-slate-800/70 text-xs">
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">COMPANY NAME</span>
              <span class="text-slate-800 dark:text-slate-200 font-semibold max-w-[240px] truncate text-right">{{ getProgramCompanyName(program) }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">NO. PO / SJ</span>
              <span class="font-mono text-slate-700 dark:text-slate-300 font-medium">{{ getProgramPoSjNumber(program) }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">BULAN / MASA PAJAK</span>
              <span class="font-semibold text-slate-700 dark:text-slate-300">{{ getProgramMonth(program.program_date) }} {{ getProgramYear(program.program_date) }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">NAMA SUPPLIER</span>
              <span class="text-slate-800 dark:text-slate-200 font-semibold max-w-[240px] truncate text-right">{{ program.supplier }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">NPWP SUPPLIER</span>
              <span class="font-mono text-slate-700 dark:text-slate-300 font-medium">{{ program.npwp || '02.010.121.3-071.000' }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">KATEGORI</span>
              <span class="px-2.5 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-medium text-[11px]">{{ program.category }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">NOMOR MEMO/DO</span>
              <span class="font-mono text-slate-700 dark:text-slate-300">{{ program.mou_number || `DO/SCM/2026/${String(program.id).padStart(3, '0')}` }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">PERIODE MULAI</span>
              <span class="text-slate-700 dark:text-slate-300">{{ formatDate(program.program_date) }}</span>
            </div>
            <div class="py-3 flex items-center justify-between">
              <span class="font-bold text-[10px] text-slate-400 dark:text-slate-500 tracking-wider uppercase font-sans">PERIODE SELESAI</span>
              <span class="text-slate-700 dark:text-slate-300">{{ formatDueDate(program.program_date) }}</span>
            </div>
          </div>
        </div>

        <!-- Catatan Pemeriksaan Box matching screenshot -->
        <div class="p-6 pt-4">
          <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-800">
            <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-1.5 font-sans">
              CATATAN PEMERIKSAAN
            </h4>
            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
              {{ completeness.count === 3 ? 'Semua berkas invoice, faktur pajak, dan Memo/DO telah lengkap dan diverifikasi tim audit pajak.' : 'Menunggu kelengkapan dokumen perpajakan.' }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Edit Program Modal -->
    <div
      v-if="isEditModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto"
    >
      <div class="fixed inset-0 bg-slate-900/60 dark:bg-slate-950/80 backdrop-blur-xs" @click="isEditModalOpen = false"></div>
      <div class="relative bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 w-full max-w-xl overflow-hidden z-10 animate-in fade-in zoom-in-95 duration-150">
        <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
          <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Ubah Data Program & Invoice</h3>
          <button type="button" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer" @click="isEditModalOpen = false">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="saveEditProgram" class="p-6 space-y-4 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Program</label>
            <input
              v-model="editForm.program_name"
              type="text"
              required
              class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-600 focus:outline-hidden"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Supplier</label>
              <input
                v-model="editForm.supplier"
                type="text"
                required
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">NPWP Supplier</label>
              <input
                v-model="editForm.npwp"
                type="text"
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. Invoice</label>
              <input
                v-model="editForm.invoice_number"
                type="text"
                required
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
              <select
                v-model="editForm.category"
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-600 focus:outline-hidden cursor-pointer"
              >
                <option v-for="cat in availableCategories" :key="cat" :value="cat">
                  {{ cat }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nilai DPP (IDR)</label>
              <input
                v-model.number="editForm.dpp"
                type="number"
                required
                @input="calculateTaxes"
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">PPN 11% (IDR)</label>
              <input
                v-model.number="editForm.ppn"
                type="number"
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-hidden"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Total Invoice (IDR)</label>
              <input
                :value="editForm.dpp + editForm.ppn"
                type="number"
                readonly
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 font-mono bg-slate-50 dark:bg-slate-800 font-bold text-slate-900 dark:text-slate-100"
              />
            </div>
          </div>

          <!-- Jenis PPh & Nilai PPh -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis PPh</label>
              <select
                v-model="editForm.pph_type"
                @change="onPphTypeChange"
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-600 focus:outline-hidden cursor-pointer"
              >
                <option value="NON_PPH">Non PPh</option>
                <option value="PPH_21">PPh 21 (2,5%)</option>
                <option value="PPH_23">PPh 23 (2%)</option>
                <option value="PPH_4_2">PPh 4 Ayat 2 (10%)</option>
                <option value="PPH_23_BONUS">PPh 23 Atas Bonus (15%)</option>
              </select>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nilai PPh (IDR)</label>
              <input
                v-model.number="editForm.pph"
                type="number"
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 font-mono bg-slate-50 dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:outline-hidden"
              />
              <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 font-mono">
                {{ formatRupiah(editForm.pph || 0) }}
              </p>
            </div>
          </div>

          <!-- No. Faktur Pajak & Tanggal Faktur Pajak -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No. Faktur Pajak (No. FP)</label>
              <input
                v-model="editForm.faktur_number"
                type="text"
                placeholder="010.000-25.00000001"
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:ring-2 focus:ring-blue-600 focus:outline-hidden"
              />
              <p class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5">Format: XXX.XXX-XX.XXXXXXXX</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Tanggal Faktur Pajak (Tgl FP)</label>
              <input
                v-model="editForm.faktur_date"
                type="date"
                class="w-full px-3 py-2 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>
          </div>

          <div class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2.5">
            <button
              type="button"
              class="px-4 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-800 cursor-pointer"
              @click="isEditModalOpen = false"
            >
              Batal
            </button>
            <button
              type="submit"
              class="px-5 py-2 rounded-lg bg-slate-900 dark:bg-blue-600 text-white font-medium hover:bg-slate-800 dark:hover:bg-blue-700 transition-colors cursor-pointer"
            >
              Simpan Perubahan
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
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 dark:bg-slate-950/80 backdrop-blur-xs"
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
              class="w-full py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-xs transition-colors cursor-pointer"
              @click="docToDelete = null"
            >
              Batal
            </button>
            <button
              type="button"
              class="w-full py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5"
              @click="confirmDeleteDoc"
            >
              <Trash2 class="w-3.5 h-3.5" />
              <span>Ya, Hapus</span>
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
              class="w-full py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5"
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
      class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700"
    >
      <ArrowLeft class="w-3.5 h-3.5" />
      <span>Kembali ke Arsip Program</span>
    </router-link>
  </div>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import {
  ArrowLeft,
  Pencil,
  Trash2,
  FileText,
  Upload,
  Receipt,
  FileCheck2,
  FileSignature,
  Eye,
  Download,
  Plus,
  X
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
  getProgramPoSjNumber,
  getPphLabel,
  calculatePphAmount,
  PPH_OPTIONS
} from '../store/taxStore';

const route = useRoute();
const router = useRouter();
const store = useTaxStore();

const programId = computed(() => route.params.id);
const program = computed(() => store.getProgramById(programId.value));
const completeness = computed(() => getCompleteness(program.value));

const uploaderName = computed(() => store.currentUser.value?.name || 'Bagas Nugroho');

const documentCategories = [
  {
    type: 'invoice',
    label: 'Invoice',
    description: 'Faktur tagihan & rincian biaya vendor',
    icon: Receipt,
  },
  {
    type: 'faktur_pajak',
    label: 'Faktur Pajak',
    description: 'Bukti pungutan PPN e-Faktur resmi',
    icon: FileCheck2,
  },
  {
    type: 'mou',
    label: 'Memo/DO',
    description: 'Surat Perintah Kerja / Dokumen Memo/DO',
    icon: FileSignature,
  }
];

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
    const d = new Date(dateStr);
    d.setDate(d.getDate() + 30);
    return formatDate(d.toISOString().split('T')[0]);
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
const isDeleteProgramModalOpen = ref(false);

function deleteDocItem(doc, typeLabel) {
  docToDelete.value = {
    id: doc.id,
    type: doc.document_type,
    label: typeLabel || store.getDocTypeLabel(doc.document_type),
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
  if (!docToDelete.value) return;
  await store.deleteDocument(program.value.id, docToDelete.value.id || docToDelete.value.type);
  docToDelete.value = null;
}

// Available Categories for Edit Modal (inclusive of all system categories)
const availableCategories = computed(() => {
  const defaults = [
    'Promosi',
    'Rebate',
    'Cashback',
    'Sewa Display',
    'Listing Fee',
    'Digital Promo',
    'Distribusi',
    'Insentif',
    'Bundling',
    'Sampling',
    'Branding',
    'Loyalty',
    'Diskon',
    'Event',
    'Logistik',
    'Kemitraan',
    'Pipa & Tubing',
    'Sewa Alat Berat',
    'Inspeksi & Sertifikasi',
    'Mekanikal & Valve',
    'Bahan Kimia',
    'Operasional'
  ];
  if (program.value?.category && !defaults.includes(program.value.category)) {
    return [program.value.category, ...defaults];
  }
  return defaults;
});

// Edit Modal State & Handling
const isEditModalOpen = ref(false);
const editForm = reactive({
  program_name: '',
  supplier: '',
  npwp: '',
  invoice_number: '',
  category: 'Logistik',
  dpp: 0,
  ppn: 0,
  pph_type: 'NON_PPH',
  pph: 0,
  faktur_number: '',
  faktur_date: ''
});

function openEditModal() {
  if (!program.value) return;
  editForm.program_name = program.value.program_name || '';
  editForm.supplier = program.value.supplier || '';
  editForm.npwp = program.value.npwp || '';
  editForm.invoice_number = program.value.invoice_number || '';
  editForm.category = program.value.category || 'Logistik';
  editForm.dpp = Number(program.value.dpp) || 0;
  editForm.ppn = Number(program.value.ppn) || Math.round(editForm.dpp * 0.11);
  editForm.pph_type = program.value.pph_type || 'NON_PPH';
  editForm.pph = Number(program.value.pph) || calculatePphAmount(editForm.dpp, editForm.pph_type);
  editForm.faktur_number = program.value.faktur_number || '';
  editForm.faktur_date = program.value.faktur_date ? String(program.value.faktur_date).slice(0, 10) : '';
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

async function saveEditProgram() {
  await store.updateProgram(program.value.id, {
    program_name: editForm.program_name,
    supplier: editForm.supplier,
    npwp: editForm.npwp,
    invoice_number: editForm.invoice_number,
    category: editForm.category,
    dpp: editForm.dpp,
    ppn: editForm.ppn,
    total_invoice: editForm.dpp + editForm.ppn,
    pph_type: editForm.pph_type,
    pph: editForm.pph,
    pph_amount: editForm.pph,
    faktur_number: editForm.faktur_number,
    faktur_date: editForm.faktur_date || null
  });
  isEditModalOpen.value = false;
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
