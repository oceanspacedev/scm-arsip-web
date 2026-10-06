<template>
  <div class="space-y-4 font-sans text-xs select-none">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-sans">
          Data Master
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
          Kelola referensi Kategori Program, Brand, Company, Kode Gudang, Supplier, Status Payment, dan Aturan PPN
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          class="h-8.5 px-3 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium text-xs transition-colors cursor-pointer flex items-center gap-1.5 shadow-2xs"
          @click="openResetModal"
          title="Reset semua data master ke pengaturan default sistem"
        >
          <RotateCcw class="w-3.5 h-3.5 text-slate-400" />
          <span>Reset Default</span>
        </button>
      </div>
    </div>

    <!-- Horizontal Tabs Navigation -->
    <div class="border-b border-slate-200 dark:border-slate-800 overflow-x-auto no-scrollbar">
      <nav class="flex items-center gap-1 -mb-px">
        <button
          v-for="tab in tabs"
          :key="tab.id"
          type="button"
          @click="changeTab(tab.id)"
          class="group inline-flex items-center gap-2 px-3.5 py-2.5 text-xs font-medium border-b-2 transition-all cursor-pointer whitespace-nowrap"
          :class="activeTab === tab.id
            ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold'
            : 'border-transparent text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-200 hover:border-slate-300 dark:hover:border-slate-700'"
        >
          <span>{{ tab.label }}</span>
          <span
            class="px-1.5 py-0.5 rounded-full text-[10px] font-mono tabular-nums transition-colors"
            :class="activeTab === tab.id
              ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-semibold'
              : 'bg-slate-100 dark:bg-slate-800 text-slate-500 group-hover:bg-slate-200/70 dark:group-hover:bg-slate-700'"
          >
            {{ getTabCount(tab.id) }}
          </span>
        </button>
      </nav>
    </div>

    <!-- Main Card Container -->
    <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden shadow-2xs">
      <!-- Toolbar Bar (Title + Search + Action Button) -->
      <div class="px-4 py-3 sm:px-5 sm:py-3.5 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/50 dark:bg-slate-900/30 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
          <h2 class="text-xs font-semibold text-slate-900 dark:text-slate-100">
            {{ currentTabLabel }}
          </h2>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
            <span v-if="searchQuery && activeTab !== 'ppn_rules'">
              Menampilkan <strong>{{ filteredList.length }}</strong> dari total {{ getTabCount(activeTab) }} data
            </span>
            <span v-else>
              Total <strong>{{ getTabCount(activeTab) }}</strong> referensi terdaftar
            </span>
          </p>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto">
          <!-- Search Input -->
          <div class="relative w-full sm:w-64">
            <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="searchQuery"
              type="text"
              :placeholder="`Cari ${currentTabLabel.toLowerCase()}...`"
              class="w-full h-8 pl-8 pr-7 text-xs rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 transition-colors"
            />
            <button
              v-if="searchQuery"
              type="button"
              @click="searchQuery = ''"
              class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer p-0.5"
            >
              <X class="w-3 h-3" />
            </button>
          </div>

          <button
            v-if="activeTab !== 'ppn_rules'"
            type="button"
            class="h-8 px-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors cursor-pointer flex items-center gap-1.5 shrink-0 shadow-2xs whitespace-nowrap"
            @click="openAddModal"
          >
            <Plus class="w-3.5 h-3.5" />
            <span>Tambah {{ currentTabLabel }}</span>
          </button>
        </div>
      </div>

      <!-- 1. DATA TABLE (Categories, Brands, Companies, Payment Statuses) -->
      <div v-if="isGridTab" class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse min-w-[560px]">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              <th class="py-3 px-4 w-14 text-center">NO</th>
              <th class="py-3 px-4">{{ getColumnTitle(activeTab) }}</th>
              <th v-if="activeTab === 'payment_statuses'" class="py-3 px-4 w-44">TIPE STATUS</th>
              <th class="py-3 px-4 w-28 text-right">AKSI</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
            <tr
              v-for="(item, idx) in filteredList"
              :key="item"
              class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group"
            >
              <td class="py-3 px-4 text-slate-400 font-mono text-[11px] text-center">{{ idx + 1 }}</td>
              <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">
                {{ item }}
              </td>
              <td v-if="activeTab === 'payment_statuses'" class="py-3 px-4">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700">
                  <span class="w-1.5 h-1.5 rounded-full shrink-0" :class="getPaymentDotClass(item)"></span>
                  {{ item }}
                </span>
              </td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    type="button"
                    class="p-1.5 rounded-md text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 dark:hover:text-blue-400 transition-colors cursor-pointer"
                    title="Edit"
                    @click="openEditModal(activeTab, item)"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button
                    type="button"
                    class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-rose-950/50 dark:hover:text-rose-400 transition-colors cursor-pointer"
                    title="Hapus"
                    @click="promptDelete(activeTab, item)"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 2. KODE GUDANG TABLE -->
      <div v-else-if="activeTab === 'warehouses'" class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse min-w-[560px]">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              <th class="py-3 px-4 w-14 text-center">NO</th>
              <th class="py-3 px-4 w-44">KODE GUDANG</th>
              <th class="py-3 px-4">NAMA GUDANG</th>
              <th class="py-3 px-4 w-48">LOKASI / KOTA</th>
              <th class="py-3 px-4 w-28 text-right">AKSI</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
            <tr
              v-for="(w, idx) in filteredList"
              :key="w.code"
              class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group"
            >
              <td class="py-3 px-4 text-slate-400 font-mono text-[11px] text-center">{{ idx + 1 }}</td>
              <td class="py-3 px-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded font-mono font-medium text-[11px] bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                  {{ w.code }}
                </span>
              </td>
              <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">{{ w.name }}</td>
              <td class="py-3 px-4 text-slate-500 dark:text-slate-400">{{ w.location || '-' }}</td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    type="button"
                    class="p-1.5 rounded-md text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 dark:hover:text-blue-400 transition-colors cursor-pointer"
                    title="Edit"
                    @click="openEditModal('warehouses', w)"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button
                    type="button"
                    class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-rose-950/50 dark:hover:text-rose-400 transition-colors cursor-pointer"
                    title="Hapus"
                    @click="promptDelete('warehouses', w)"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 3. SUPPLIER & NPWP TABLE -->
      <div v-else-if="activeTab === 'suppliers'" class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse min-w-[620px]">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              <th class="py-3 px-4 w-14 text-center">NO</th>
              <th class="py-3 px-4">NAMA SUPPLIER</th>
              <th class="py-3 px-4 w-52">NPWP SUPPLIER</th>
              <th class="py-3 px-4 w-40">KONTAK / TELP</th>
              <th class="py-3 px-4 w-28 text-right">AKSI</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
            <tr
              v-for="(s, idx) in filteredList"
              :key="s.name"
              class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group"
            >
              <td class="py-3 px-4 text-slate-400 font-mono text-[11px] text-center">{{ idx + 1 }}</td>
              <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">{{ s.name }}</td>
              <td class="py-3 px-4 font-mono text-slate-600 dark:text-slate-300 text-[11px]">{{ s.npwp || '-' }}</td>
              <td class="py-3 px-4 font-mono text-slate-500 dark:text-slate-400 text-[11px]">{{ s.phone || '-' }}</td>
              <td class="py-3 px-4 text-right">
                <div class="flex items-center justify-end gap-1">
                  <button
                    type="button"
                    class="p-1.5 rounded-md text-slate-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 dark:hover:text-blue-400 transition-colors cursor-pointer"
                    title="Edit"
                    @click="openEditModal('suppliers', s)"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>
                  <button
                    type="button"
                    class="p-1.5 rounded-md text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-rose-950/50 dark:hover:text-rose-400 transition-colors cursor-pointer"
                    title="Hapus"
                    @click="promptDelete('suppliers', s)"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 4. ATURAN PPN TABLE -->
      <div v-else-if="activeTab === 'ppn_rules'" class="overflow-x-auto">
        <div class="px-4 py-2.5 border-b border-slate-100 dark:border-slate-800/80 bg-slate-50/40 dark:bg-slate-900/20">
          <p class="text-[11px] text-slate-500 dark:text-slate-400">
            Atur tarif PPN per kategori. Klik tombol aksi untuk beralih antara 0% (Non-PPN) dan 11% PPN.
          </p>
        </div>
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              <th class="py-3 px-4 w-14 text-center">NO</th>
              <th class="py-3 px-4">KATEGORI PROGRAM</th>
              <th class="py-3 px-4 w-36 text-center">TARIF PPN</th>
              <th class="py-3 px-4 w-32 text-center">AKSI</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-slate-700 dark:text-slate-300">
            <tr
              v-for="(cat, idx) in masterCategoriesForPpn"
              :key="cat"
              class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
            >
              <td class="py-3 px-4 text-slate-400 font-mono text-[11px] text-center">{{ idx + 1 }}</td>
              <td class="py-3 px-4 font-medium text-slate-800 dark:text-slate-200">{{ cat }}</td>
              <td class="py-3 px-4 text-center">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-mono font-medium border"
                  :class="isCategoryNonPpn(cat)
                    ? 'bg-slate-50 dark:bg-slate-900 text-slate-500 dark:text-slate-400 border-slate-200/80 dark:border-slate-800'
                    : 'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border-slate-200 dark:border-slate-700'"
                >
                  {{ isCategoryNonPpn(cat) ? '0% Non-PPN' : '11% PPN' }}
                </span>
              </td>
              <td class="py-3 px-4 text-center">
                <button
                  type="button"
                  @click="togglePpnRule(cat)"
                  :disabled="togglingCategory === cat"
                  class="h-7 px-2.5 text-xs font-medium rounded-md border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:border-slate-300 dark:hover:border-slate-600 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="togglingCategory === cat" class="text-slate-400">Memproses...</span>
                  <span v-else>{{ isCategoryNonPpn(cat) ? 'Ubah ke 11%' : 'Ubah ke 0%' }}</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Empty State -->
      <div v-if="filteredList.length === 0 && activeTab !== 'ppn_rules'" class="py-12 px-4 text-center">
        <p class="font-medium text-slate-700 dark:text-slate-300 text-xs">
          <span v-if="searchQuery">Tidak ditemukan data untuk kata kunci "{{ searchQuery }}"</span>
          <span v-else>Belum ada data {{ currentTabLabel.toLowerCase() }}.</span>
        </p>
        <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-1">
          <span v-if="searchQuery">Periksa kembali kata kunci pencarian Anda.</span>
          <span v-else>Tambahkan data baru dengan tombol di bawah.</span>
        </p>
        <div class="mt-3.5 flex items-center justify-center gap-2">
          <button
            v-if="searchQuery"
            type="button"
            @click="searchQuery = ''"
            class="px-3.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-xs transition-colors cursor-pointer"
          >
            Bersihkan Pencarian
          </button>
          <button
            type="button"
            @click="openAddModal"
            class="px-3.5 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors cursor-pointer shadow-2xs"
          >
            + Tambah {{ currentTabLabel }}
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: Tambah Data Master -->
    <Teleport to="body">
      <div
        v-if="isAddModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="isAddModalOpen = false"
      >
        <div class="w-full max-w-md bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col">
          <!-- Modal Header -->
          <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-[#111827]">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              Tambah {{ currentTabLabel }} Baru
            </h3>
            <button
              type="button"
              class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              @click="isAddModalOpen = false"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Form Body -->
          <form @submit.prevent="submitAdd" class="p-5 space-y-3.5 text-xs">
            <!-- Form: Kategori -->
            <div v-if="activeTab === 'categories'" class="space-y-1">
              <label class="block font-medium text-slate-700 dark:text-slate-300">
                Nama Kategori Program <span class="text-red-500">*</span>
              </label>
              <input
                v-model="addForm.name"
                type="text"
                placeholder="Contoh: Digital Promo"
                required
                autofocus
                class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>

            <!-- Form: Brand -->
            <div v-if="activeTab === 'brands'" class="space-y-1">
              <label class="block font-medium text-slate-700 dark:text-slate-300">
                Nama Brand <span class="text-red-500">*</span>
              </label>
              <input
                v-model="addForm.name"
                type="text"
                placeholder="Contoh: SCM"
                required
                autofocus
                class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>

            <!-- Form: Company Name -->
            <div v-if="activeTab === 'companies'" class="space-y-1">
              <label class="block font-medium text-slate-700 dark:text-slate-300">
                Nama Perusahaan (Company Name) <span class="text-red-500">*</span>
              </label>
              <input
                v-model="addForm.name"
                type="text"
                placeholder="Contoh: PT SCM Nusantara"
                required
                autofocus
                class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>

            <!-- Form: Payment Status -->
            <div v-if="activeTab === 'payment_statuses'" class="space-y-1">
              <label class="block font-medium text-slate-700 dark:text-slate-300">
                Nama Status Payment <span class="text-red-500">*</span>
              </label>
              <input
                v-model="addForm.name"
                type="text"
                placeholder="Contoh: Waiting Payment, Tempo 30 Hari, Paid (Lunas)"
                required
                autofocus
                class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>

            <!-- Form: Warehouse -->
            <div v-if="activeTab === 'warehouses'" class="space-y-3">
              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Kode Gudang <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="addForm.code"
                  type="text"
                  placeholder="Contoh: GDG-JKT-03"
                  required
                  autofocus
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden uppercase"
                />
              </div>

              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Nama Gudang <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="addForm.name"
                  type="text"
                  placeholder="Contoh: Gudang Daan Mogot"
                  required
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>

              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Lokasi / Kota
                </label>
                <input
                  v-model="addForm.location"
                  type="text"
                  placeholder="Contoh: Jakarta Barat"
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>
            </div>

            <!-- Form: Supplier -->
            <div v-if="activeTab === 'suppliers'" class="space-y-3">
              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Nama Supplier <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="addForm.name"
                  type="text"
                  placeholder="Contoh: PT Unilever Indonesia Tbk"
                  required
                  autofocus
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>

              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  NPWP Supplier
                </label>
                <input
                  v-model="addForm.npwp"
                  type="text"
                  placeholder="01.234.567.8-901.000"
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>

              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Nomor Telepon / Kontak
                </label>
                <input
                  v-model="addForm.phone"
                  type="text"
                  placeholder="Contoh: 021-52995299"
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>
            </div>

            <!-- Footer Buttons -->
            <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                class="px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                @click="isAddModalOpen = false"
              >
                Batal
              </button>
              <button
                type="submit"
                class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium transition-colors cursor-pointer shadow-2xs"
              >
                Simpan
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- MODAL: Edit Data Master -->
    <Teleport to="body">
      <div
        v-if="isEditModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="isEditModalOpen = false"
      >
        <div class="w-full max-w-md bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden flex flex-col">
          <!-- Modal Header -->
          <div class="px-5 py-3.5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-[#111827]">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              Edit {{ currentTabLabel }}
            </h3>
            <button
              type="button"
              class="p-1 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              @click="isEditModalOpen = false"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Form Body -->
          <form @submit.prevent="submitEdit" class="p-5 space-y-3.5 text-xs">
            <!-- Edit: Single String (Category, Brand, Company, Payment Status) -->
            <div v-if="isGridTab" class="space-y-1">
              <label class="block font-medium text-slate-700 dark:text-slate-300">
                Nama {{ currentTabLabel }} <span class="text-red-500">*</span>
              </label>
              <input
                v-model="editForm.name"
                type="text"
                required
                autofocus
                class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
              />
            </div>

            <!-- Edit: Warehouse -->
            <div v-if="activeTab === 'warehouses'" class="space-y-3">
              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Kode Gudang <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editForm.code"
                  type="text"
                  required
                  autofocus
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden uppercase"
                />
              </div>

              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Nama Gudang <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editForm.name"
                  type="text"
                  required
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>

              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Lokasi / Kota
                </label>
                <input
                  v-model="editForm.location"
                  type="text"
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>
            </div>

            <!-- Edit: Supplier -->
            <div v-if="activeTab === 'suppliers'" class="space-y-3">
              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Nama Supplier <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editForm.name"
                  type="text"
                  required
                  autofocus
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>

              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  NPWP Supplier
                </label>
                <input
                  v-model="editForm.npwp"
                  type="text"
                  placeholder="01.234.567.8-901.000"
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>

              <div class="space-y-1">
                <label class="block font-medium text-slate-700 dark:text-slate-300">
                  Nomor Telepon / Kontak
                </label>
                <input
                  v-model="editForm.phone"
                  type="text"
                  placeholder="Contoh: 021-52995299"
                  class="w-full h-8.5 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 font-mono focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden"
                />
              </div>
            </div>

            <!-- Footer Buttons -->
            <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800">
              <button
                type="button"
                class="px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                @click="isEditModalOpen = false"
              >
                Batal
              </button>
              <button
                type="submit"
                class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium transition-colors cursor-pointer shadow-2xs"
              >
                Simpan Perubahan
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- MODAL: Konfirmasi Hapus Data Master -->
    <Teleport to="body">
      <div
        v-if="itemToDelete"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/40 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="itemToDelete = null"
      >
        <div class="w-full max-w-sm bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 p-5 text-center space-y-3.5">
          <div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              Hapus Item Data Master?
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              Apakah Anda yakin ingin menghapus <strong class="text-slate-800 dark:text-slate-200">"{{ itemToDelete.label }}"</strong> dari referensi {{ currentTabLabel.toLowerCase() }}?
            </p>
          </div>

          <div class="grid grid-cols-2 gap-2.5 pt-2">
            <button
              type="button"
              class="w-full py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-xs transition-colors cursor-pointer"
              @click="itemToDelete = null"
            >
              Batal
            </button>
            <button
              type="button"
              class="w-full py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium text-xs transition-colors cursor-pointer shadow-2xs"
              @click="confirmDelete"
            >
              Ya, Hapus
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- MODAL: Konfirmasi Reset Default -->
    <Teleport to="body">
      <div
        v-if="isResetModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/40 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="isResetModalOpen = false"
      >
        <div class="w-full max-w-sm bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 p-5 text-center space-y-3.5">
          <div>
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              Reset ke Default Sistem?
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed">
              Seluruh daftar kategori, brand, company, gudang, supplier, dan status payment akan dikembalikan ke data awal sistem.
            </p>
          </div>

          <div class="grid grid-cols-2 gap-2.5 pt-2">
            <button
              type="button"
              class="w-full py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-xs transition-colors cursor-pointer"
              @click="isResetModalOpen = false"
            >
              Batal
            </button>
            <button
              type="button"
              class="w-full py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors cursor-pointer shadow-2xs"
              @click="confirmReset"
            >
              Ya, Reset
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted } from 'vue';
import { Search, Plus, Pencil, Trash2, RotateCcw, X } from 'lucide-vue-next';
import { useTaxStore, isNonPpnCategory } from '../store/taxStore';

const store = useTaxStore();

const activeTab = ref('categories');
const searchQuery = ref('');
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const itemToEdit = ref(null);
const itemToDelete = ref(null);
const isResetModalOpen = ref(false);

const tabs = [
  { id: 'categories', label: 'Kategori Program' },
  { id: 'brands', label: 'Brand' },
  { id: 'companies', label: 'Company Name' },
  { id: 'warehouses', label: 'Kode Gudang' },
  { id: 'suppliers', label: 'Supplier & NPWP' },
  { id: 'payment_statuses', label: 'Status Payment' },
  { id: 'ppn_rules', label: 'Aturan PPN' },
];

const addForm = reactive({
  name: '',
  code: '',
  location: '',
  npwp: '',
  phone: '',
});

const editForm = reactive({
  name: '',
  code: '',
  location: '',
  npwp: '',
  phone: '',
});

const currentTab = computed(() => {
  return tabs.find(t => t.id === activeTab.value) || tabs[0];
});

const currentTabLabel = computed(() => currentTab.value.label);

const isGridTab = computed(() => {
  return (
    activeTab.value === 'categories' ||
    activeTab.value === 'brands' ||
    activeTab.value === 'companies' ||
    activeTab.value === 'payment_statuses'
  );
});

function changeTab(tabId) {
  activeTab.value = tabId;
  searchQuery.value = '';
}

function getColumnTitle(tabId) {
  if (tabId === 'categories') return 'NAMA KATEGORI PROGRAM';
  if (tabId === 'brands') return 'NAMA BRAND';
  if (tabId === 'companies') return 'NAMA PERUSAHAAN (COMPANY)';
  if (tabId === 'payment_statuses') return 'NAMA STATUS PAYMENT';
  return 'NAMA REFERENSI';
}

function getTabCount(tabId) {
  const data = store.masterData.value || {};
  if (tabId === 'ppn_rules') {
    return (data.categories || []).length;
  }
  const list = data[tabId] || [];
  return list.length;
}

function getPaymentDotClass(status) {
  const s = String(status || '').toLowerCase().trim();
  if (s === 'cbd' || s.includes('cbd')) return 'bg-amber-500';
  if (s === 'tempo' || s.includes('tempo')) return 'bg-blue-500';
  if (s === 'paid' || s === 'lunas' || s.includes('paid') || s.includes('lunas')) return 'bg-emerald-500';
  if (s === 'waiting payment' || s.includes('waiting')) return 'bg-rose-500';
  return 'bg-blue-500';
}

const filteredList = computed(() => {
  const q = searchQuery.value.toLowerCase().trim();
  const data = store.masterData.value || {};
  const list = data[activeTab.value] || [];

  if (activeTab.value === 'ppn_rules') return [];

  if (!q) return list;

  if (isGridTab.value) {
    return list.filter(item => String(item).toLowerCase().includes(q));
  }

  if (activeTab.value === 'warehouses') {
    return list.filter(w =>
      (w.code || '').toLowerCase().includes(q) ||
      (w.name || '').toLowerCase().includes(q) ||
      (w.location || '').toLowerCase().includes(q)
    );
  }

  if (activeTab.value === 'suppliers') {
    return list.filter(s =>
      (s.name || '').toLowerCase().includes(q) ||
      (s.npwp || '').toLowerCase().includes(q) ||
      (s.phone || '').toLowerCase().includes(q)
    );
  }

  return list;
});

// Computed: semua kategori untuk tabel Aturan PPN (gunakan dari masterData.categories)
const masterCategoriesForPpn = computed(() => {
  const data = store.masterData.value || {};
  const cats = data.categories || [];
  const q = searchQuery.value.toLowerCase().trim();
  if (!q) return cats;
  return cats.filter(c => c.toLowerCase().includes(q));
});

// Cek apakah kategori ini Non-PPN (reactive langsung terhadap store.masterData)
function isCategoryNonPpn(categoryName) {
  const rules = store.masterData.value?.ppn_rules;
  if (Array.isArray(rules)) {
    const cLower = String(categoryName || '').toLowerCase().trim();
    return rules.some(r => r.toLowerCase().trim() === cLower);
  }
  return isNonPpnCategory(categoryName);
}

// State loading per-kategori saat klik toggle
const togglingCategory = ref(null);

// Toggle PPN rule untuk kategori
async function togglePpnRule(categoryName) {
  if (togglingCategory.value) return;
  togglingCategory.value = categoryName;
  try {
    const currentlyNonPpn = isCategoryNonPpn(categoryName);
    await store.setPpnRule(categoryName, !currentlyNonPpn);
  } finally {
    togglingCategory.value = null;
  }
}

function openAddModal() {
  addForm.name = '';
  addForm.code = '';
  addForm.location = '';
  addForm.npwp = '';
  addForm.phone = '';
  isAddModalOpen.value = true;
}

async function submitAdd() {
  let itemPayload = null;

  if (isGridTab.value) {
    itemPayload = addForm.name.trim();
  } else if (activeTab.value === 'warehouses') {
    itemPayload = {
      code: addForm.code.trim().toUpperCase(),
      name: addForm.name.trim(),
      location: addForm.location.trim(),
    };
  } else if (activeTab.value === 'suppliers') {
    itemPayload = {
      name: addForm.name.trim(),
      npwp: addForm.npwp.trim(),
      phone: addForm.phone.trim(),
    };
  }

  if (itemPayload) {
    const res = await store.addMasterItem(activeTab.value, itemPayload);
    if (res && res.success !== false) {
      isAddModalOpen.value = false;
    }
  }
}

function openEditModal(type, item) {
  itemToEdit.value = { type, raw: item };
  if (type === 'warehouses') {
    editForm.code = item.code || '';
    editForm.name = item.name || '';
    editForm.location = item.location || '';
  } else if (type === 'suppliers') {
    editForm.name = item.name || '';
    editForm.npwp = item.npwp || '';
    editForm.phone = item.phone || '';
  } else {
    editForm.name = String(item || '');
  }
  isEditModalOpen.value = true;
}

async function submitEdit() {
  if (!itemToEdit.value) return;
  const type = itemToEdit.value.type;
  let oldValue = null;
  let newPayload = null;

  if (
    type === 'categories' ||
    type === 'brands' ||
    type === 'companies' ||
    type === 'payment_statuses'
  ) {
    oldValue = itemToEdit.value.raw;
    newPayload = editForm.name.trim();
  } else if (type === 'warehouses') {
    oldValue = itemToEdit.value.raw.code;
    newPayload = {
      code: editForm.code.trim().toUpperCase(),
      name: editForm.name.trim(),
      location: editForm.location.trim(),
    };
  } else if (type === 'suppliers') {
    oldValue = itemToEdit.value.raw.name;
    newPayload = {
      name: editForm.name.trim(),
      npwp: editForm.npwp.trim(),
      phone: editForm.phone.trim(),
    };
  }

  if (newPayload) {
    const res = await store.updateMasterItem(type, oldValue, newPayload);
    if (res && res.success !== false) {
      isEditModalOpen.value = false;
      itemToEdit.value = null;
    }
  }
}

function promptDelete(type, item) {
  let val = '';
  let label = '';
  if (type === 'warehouses') {
    val = item.code;
    label = `${item.name} (${item.code})`;
  } else if (type === 'suppliers') {
    val = item.name;
    label = item.name;
  } else {
    val = item;
    label = item;
  }
  itemToDelete.value = { type, value: val, label };
}

async function confirmDelete() {
  if (!itemToDelete.value) return;
  await store.deleteMasterItem(itemToDelete.value.type, itemToDelete.value.value);
  itemToDelete.value = null;
}

function openResetModal() {
  isResetModalOpen.value = true;
}

async function confirmReset() {
  await store.resetMasterData();
  isResetModalOpen.value = false;
}

onMounted(() => {
  store.fetchMasterData();
});
</script>
