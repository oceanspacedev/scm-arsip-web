<template>
  <div class="space-y-4 sm:space-y-6">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
      <div>
        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100 font-sans">
          Manajemen User
        </h1>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 sm:mt-1">
          Kelola akses personel divisi SCM dan Tim Pajak
        </p>
      </div>

      <!-- Action: + Tambah User Button -->
      <div class="flex items-center gap-3 w-full sm:w-auto">
        <button
          type="button"
          class="h-8.5 px-3.5 w-full sm:w-auto justify-center rounded-lg bg-slate-900 dark:bg-blue-600 hover:bg-slate-800 dark:hover:bg-blue-700 text-white font-medium text-xs transition-colors cursor-pointer flex items-center gap-1.5"
          @click="openAddModal"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah User</span>
        </button>
      </div>
    </div>

    <!-- Admin Controls Bar: Demo Account Toggle & Reset Data -->
    <div class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 sm:p-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
      <!-- Left: Demo Accounts Toggle -->
      <div class="flex items-start sm:items-center gap-3">
        <div class="min-w-0">
          <div class="flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold text-slate-900 dark:text-slate-100">Tampilkan Akun Demo di Login</span>
            <span
              class="px-2 py-0.5 rounded text-[10px] font-medium transition-colors"
              :class="showDemoAccounts ? 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700' : 'bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700'"
            >
              {{ showDemoAccounts ? 'Aktif' : 'Nonaktif' }}
            </span>
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
            Menampilkan tombol cepat akun demo (Admin SCM, Tim Pajak, Staf SCM) di form masuk
          </p>
        </div>
      </div>

      <!-- Right: Actions (Toggle Switch & Reset Data Button) -->
      <div class="flex items-center justify-between md:justify-end gap-3 pt-3 md:pt-0 border-t border-slate-100 dark:border-slate-800 md:border-t-0 shrink-0 w-full md:w-auto">
        <!-- Switch Toggle Button -->
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-600 dark:text-slate-400 font-medium md:hidden">Akun Demo:</span>
          <button
            type="button"
            role="switch"
            :aria-checked="showDemoAccounts"
            @click="handleToggleDemoAccounts"
            class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-hidden"
            :class="showDemoAccounts ? 'bg-slate-900 dark:bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'"
            title="Nyalakan / Matikan Akun Demo di Login"
          >
            <span
              aria-hidden="true"
              class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs ring-0 transition duration-200 ease-in-out"
              :class="showDemoAccounts ? 'translate-x-4' : 'translate-x-0'"
            />
          </button>
        </div>

        <div class="h-5 w-px bg-slate-200 dark:bg-slate-700 hidden md:block"></div>

        <!-- Reset Data Button -->
        <button
          type="button"
          class="h-8 px-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 hover:text-red-600 dark:hover:text-rose-400 hover:border-red-200 dark:hover:border-rose-900/50 text-slate-700 dark:text-slate-300 text-xs font-medium transition-colors cursor-pointer flex items-center gap-1.5"
          @click="isResetModalOpen = true"
        >
          <RotateCcw class="w-3.5 h-3.5 text-slate-400" />
          <span>Reset Data</span>
        </button>
      </div>
    </div>

    <!-- Filter Tabs & Stats -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 text-xs">
      <div class="flex gap-4 sm:gap-6 overflow-x-auto pb-2 -mb-px scrollbar-none">
        <button
          type="button"
          class="pb-2 border-b-2 font-medium transition-colors cursor-pointer flex items-center gap-1.5 shrink-0 whitespace-nowrap"
          :class="activeFilter === 'all' ? 'border-slate-900 dark:border-slate-100 text-slate-900 dark:text-slate-100 font-semibold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
          @click="setActiveFilter('all')"
        >
          <span>Semua User</span>
          <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium">
            {{ allUsers.length }}
          </span>
        </button>

        <button
          type="button"
          class="pb-2 border-b-2 font-medium transition-colors cursor-pointer flex items-center gap-1.5 shrink-0 whitespace-nowrap"
          :class="activeFilter === 'pending' ? 'border-slate-900 dark:border-slate-100 text-slate-900 dark:text-slate-100 font-semibold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
          @click="setActiveFilter('pending')"
        >
          <span>Menunggu ACC</span>
          <span
            class="px-1.5 py-0.5 rounded text-[10px] font-medium"
            :class="pendingUsers.length > 0 ? 'bg-amber-100 dark:bg-amber-950/70 text-amber-900 dark:text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
          >
            {{ pendingUsers.length }}
          </span>
        </button>

        <button
          type="button"
          class="pb-2 border-b-2 font-medium transition-colors cursor-pointer flex items-center gap-1.5 shrink-0 whitespace-nowrap"
          :class="activeFilter === 'approved' ? 'border-slate-900 dark:border-slate-100 text-slate-900 dark:text-slate-100 font-semibold' : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200'"
          @click="setActiveFilter('approved')"
        >
          <span>Aktif</span>
          <span class="px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-medium">
            {{ approvedUsers.length }}
          </span>
        </button>
      </div>

      <!-- Search Input -->
      <div class="relative w-full sm:w-64 pb-2">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Cari nama, email, atau no. WA..."
          class="w-full h-8.5 pl-8.5 pr-3 text-xs rounded-lg border border-slate-200 dark:border-slate-700 focus:border-slate-400 dark:focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors placeholder:text-slate-400 dark:placeholder:text-slate-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100"
        />
        <Search class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5 pointer-events-none" />
      </div>
    </div>

    <!-- Desktop Users Table (hidden md:block) -->
    <div class="hidden md:block bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs border-collapse min-w-[640px]">
          <thead>
            <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 text-[11px] font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
              <th class="py-3 px-4">NAMA</th>
              <th class="py-3 px-4">EMAIL</th>
              <th class="py-3 px-4">NO. WHATSAPP</th>
              <th class="py-3 px-4">PERAN</th>
              <th class="py-3 px-4">DIBUAT</th>
              <th class="py-3 px-4">STATUS</th>
              <th class="py-3 px-4 text-right">AKSI</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
            <tr
              v-for="user in filteredList"
              :key="user.id"
              class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
            >
              <!-- NAMA -->
              <td class="py-3 px-4 font-semibold text-slate-900 dark:text-slate-100">
                <div class="flex items-center gap-2.5">
                  <div
                    class="w-7 h-7 rounded-full flex items-center justify-center font-semibold text-[11px] shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
                  >
                    {{ getInitials(user.name) }}
                  </div>
                  <span class="truncate max-w-[180px] lg:max-w-none">{{ user.name }}</span>
                </div>
              </td>

              <!-- EMAIL -->
              <td class="py-3 px-4 font-mono text-slate-500 dark:text-slate-400 text-xs">
                {{ user.email }}
              </td>

              <!-- NO. WHATSAPP -->
              <td class="py-3 px-4 font-mono text-slate-700 dark:text-slate-300 text-xs whitespace-nowrap font-medium">
                {{ user.phone || '-' }}
              </td>

              <!-- PERAN -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                  {{ user.role }}
                </span>
              </td>

              <!-- DIBUAT -->
              <td class="py-3 px-4 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                {{ formatDate(user.registered_at) }}
              </td>

              <!-- STATUS -->
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 font-medium">
                  <span
                    class="w-1.5 h-1.5 rounded-full"
                    :class="{
                      'bg-emerald-500': user.status === 'approved',
                      'bg-amber-500': user.status === 'pending',
                      'bg-slate-400': user.status === 'rejected'
                    }"
                  ></span>
                  <span>{{ user.status === 'approved' ? 'Aktif' : user.status === 'pending' ? 'Menunggu ACC' : 'Nonaktif' }}</span>
                </span>
              </td>

              <!-- AKSI -->
              <td class="py-3 px-4 text-right whitespace-nowrap">
                <div class="inline-flex items-center gap-1.5" v-if="user.email !== 'admin@scm.corp'">
                  <!-- Pending -> ACC -->
                  <button
                    v-if="user.status === 'pending'"
                    type="button"
                    :disabled="processingId === user.id"
                    class="h-7 px-2.5 rounded-md bg-slate-900 dark:bg-blue-600 hover:bg-slate-800 dark:hover:bg-blue-700 text-white text-[11px] font-medium transition-colors cursor-pointer disabled:opacity-60 flex items-center gap-1"
                    @click="handleApprove(user)"
                  >
                    <span v-if="processingId === user.id" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                    <span v-else>ACC</span>
                  </button>

                  <!-- Active -> Nonaktifkan -->
                  <button
                    v-else-if="user.status === 'approved'"
                    type="button"
                    :disabled="processingId === user.id"
                    class="h-7 px-2.5 rounded-md text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[11px] font-medium transition-colors cursor-pointer disabled:opacity-50"
                    @click="handleReject(user)"
                  >
                    Nonaktifkan
                  </button>

                  <!-- Inactive -> Aktifkan -->
                  <button
                    v-else
                    type="button"
                    :disabled="processingId === user.id"
                    class="h-7 px-2.5 rounded-md text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 text-[11px] font-medium transition-colors cursor-pointer disabled:opacity-50"
                    @click="handleApprove(user)"
                  >
                    Aktifkan
                  </button>

                  <!-- Edit Icon Button -->
                  <button
                    type="button"
                    :disabled="processingId === user.id"
                    class="p-1.5 rounded-md text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition-colors cursor-pointer flex items-center justify-center disabled:opacity-50"
                    @click="openEditModal(user)"
                    title="Ubah Pengguna"
                  >
                    <Pencil class="w-3.5 h-3.5" />
                  </button>

                  <!-- Trash Delete Icon Button -->
                  <button
                    type="button"
                    :disabled="processingId === user.id"
                    class="p-1.5 rounded-md text-slate-400 hover:text-red-600 dark:hover:text-rose-400 hover:bg-red-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer flex items-center justify-center disabled:opacity-50"
                    @click="promptDelete(user)"
                    title="Hapus Akun"
                  >
                    <Trash2 class="w-3.5 h-3.5" />
                  </button>
                </div>
                <span v-else class="text-[11px] text-slate-400 font-medium">
                  Super Admin
                </span>
              </td>
            </tr>

            <!-- Empty State Desktop -->
            <tr v-if="filteredList.length === 0">
              <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
                <Users class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
                <p class="font-medium text-slate-600 dark:text-slate-300">Tidak ada data pengguna yang sesuai.</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Mobile Cards View (block md:hidden) -->
    <div class="block md:hidden space-y-3">
      <div
        v-for="user in filteredList"
        :key="user.id"
        class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 sm:p-4 space-y-3 transition-colors"
      >
        <!-- Card Top: Avatar, Name, Role & Status -->
        <div class="flex items-start justify-between gap-2.5">
          <div class="flex items-center gap-2.5 min-w-0">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center font-semibold text-xs shrink-0 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700"
            >
              {{ getInitials(user.name) }}
            </div>
            <div class="min-w-0">
              <h3 class="font-semibold text-sm text-slate-900 dark:text-slate-100 truncate leading-tight">
                {{ user.name }}
              </h3>
              <div class="flex items-center gap-1.5 mt-0.5">
                <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300">
                  {{ user.role }}
                </span>
              </div>
            </div>
          </div>

          <!-- Status Badge -->
          <span class="inline-flex items-center gap-1.5 text-xs text-slate-700 dark:text-slate-300 font-medium shrink-0">
            <span
              class="w-1.5 h-1.5 rounded-full"
              :class="{
                'bg-emerald-500': user.status === 'approved',
                'bg-amber-500': user.status === 'pending',
                'bg-slate-400': user.status === 'rejected'
              }"
            ></span>
            <span>{{ user.status === 'approved' ? 'Aktif' : user.status === 'pending' ? 'Menunggu' : 'Nonaktif' }}</span>
          </span>
        </div>

        <!-- Card Middle: Contact & Info -->
        <div class="bg-slate-50/70 dark:bg-slate-900/60 rounded-lg p-2.5 space-y-1.5 text-xs text-slate-600 dark:text-slate-400 border border-slate-200/80 dark:border-slate-800">
          <div class="flex items-center gap-2 truncate">
            <Mail class="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <span class="font-mono text-[11px] truncate select-all text-slate-700 dark:text-slate-300">{{ user.email }}</span>
          </div>

          <div v-if="user.phone" class="flex items-center gap-2 truncate">
            <Phone class="w-3.5 h-3.5 text-slate-400 shrink-0" />
            <span class="font-mono text-[11px] text-slate-700 dark:text-slate-300">{{ user.phone }}</span>
          </div>

          <div class="flex items-center gap-2 text-slate-400 dark:text-slate-500 text-[10px]">
            <Calendar class="w-3.5 h-3.5 shrink-0" />
            <span>Terdaftar: {{ formatDate(user.registered_at) }}</span>
          </div>
        </div>

        <!-- Card Bottom: Action Buttons -->
        <div class="pt-2 flex items-center justify-between gap-2 border-t border-slate-100 dark:border-slate-800">
          <div v-if="user.email === 'admin@scm.corp'">
            <span class="text-[11px] text-slate-400 font-medium">
              Super Admin Sistem
            </span>
          </div>

          <template v-else>
            <!-- Action Buttons (left aligned: Edit & Delete) -->
            <div class="flex items-center gap-1.5">
              <button
                type="button"
                :disabled="processingId === user.id"
                class="h-7 px-2 rounded-md border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 text-xs font-medium transition-colors cursor-pointer flex items-center gap-1 disabled:opacity-50"
                @click="openEditModal(user)"
                title="Ubah Pengguna"
              >
                <Pencil class="w-3.5 h-3.5" />
                <span>Ubah</span>
              </button>

              <button
                type="button"
                :disabled="processingId === user.id"
                class="h-7 px-2 rounded-md border border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 hover:text-red-600 dark:hover:text-rose-400 hover:bg-red-50 dark:hover:bg-rose-950/40 text-xs font-medium transition-colors cursor-pointer flex items-center gap-1 disabled:opacity-50"
                @click="promptDelete(user)"
                title="Hapus Akun"
              >
                <Trash2 class="w-3.5 h-3.5" />
                <span>Hapus</span>
              </button>
            </div>

            <div class="flex items-center gap-1.5">
              <!-- Pending -> ACC -->
              <button
                v-if="user.status === 'pending'"
                type="button"
                :disabled="processingId === user.id"
                class="h-7 px-3 rounded-md bg-slate-900 dark:bg-blue-600 hover:bg-slate-800 dark:hover:bg-blue-700 text-white text-xs font-medium transition-colors cursor-pointer disabled:opacity-60 flex items-center gap-1"
                @click="handleApprove(user)"
              >
                <span v-if="processingId === user.id" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <Check v-else class="w-3.5 h-3.5" />
                <span>ACC Akun</span>
              </button>

              <!-- Active -> Nonaktifkan -->
              <button
                v-else-if="user.status === 'approved'"
                type="button"
                :disabled="processingId === user.id"
                class="h-7 px-2.5 rounded-md text-slate-700 dark:text-slate-300 hover:text-slate-900 dark:hover:text-slate-100 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium transition-colors cursor-pointer disabled:opacity-50"
                @click="handleReject(user)"
              >
                Nonaktifkan
              </button>

              <!-- Inactive -> Aktifkan -->
              <button
                v-else
                type="button"
                :disabled="processingId === user.id"
                class="h-7 px-3 rounded-md text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-medium transition-colors cursor-pointer disabled:opacity-50 flex items-center gap-1"
                @click="handleApprove(user)"
              >
                <Check class="w-3.5 h-3.5" />
                <span>Aktifkan</span>
              </button>
            </div>
          </template>
        </div>
      </div>

      <!-- Empty State Mobile -->
      <div
        v-if="filteredList.length === 0"
        class="bg-white dark:bg-[#111827] rounded-xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-400 dark:text-slate-500"
      >
        <Users class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
        <p class="font-medium text-slate-600 dark:text-slate-300 text-xs">Tidak ada data pengguna yang sesuai.</p>
      </div>
    </div>

    <!-- Modal: + Tambah User Baru -->
    <Teleport to="body">
      <div
        v-if="isAddModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="isAddModalOpen = false"
      >
        <div class="w-full max-w-md bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden max-h-[92vh] flex flex-col">
          <!-- Modal Header -->
          <div class="px-5 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-[#111827] shrink-0">
            <div>
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                Tambah User Baru
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Tambahkan personel internal untuk akses SCM TaxVault
              </p>
            </div>
            <button
              type="button"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              @click="isAddModalOpen = false"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Form Body -->
          <form @submit.prevent="submitAddUser" class="p-5 sm:p-6 space-y-3.5 overflow-y-auto flex-1 text-xs">
            <div
              v-if="addError"
              class="p-2.5 rounded-lg bg-red-50 dark:bg-rose-950/60 border border-red-200 dark:border-rose-900 text-xs text-red-700 dark:text-rose-300 flex items-center gap-2"
            >
              <AlertCircle class="w-4 h-4 shrink-0" />
              <span>{{ addError }}</span>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                Nama Lengkap <span class="text-red-500">*</span>
              </label>
              <input
                v-model="addForm.name"
                type="text"
                placeholder="Contoh: Budi Santoso"
                required
                class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors placeholder:text-slate-400 dark:placeholder:text-slate-500"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                  Email <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="addForm.email"
                  type="email"
                  placeholder="nama@perusahaan.com"
                  required
                  class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors placeholder:text-slate-400 dark:placeholder:text-slate-500 font-mono"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                  Nomor WhatsApp
                </label>
                <input
                  v-model="addForm.phone"
                  type="text"
                  placeholder="081234567890"
                  class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors placeholder:text-slate-400 dark:placeholder:text-slate-500 font-mono"
                />
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                Peran / Role <span class="text-red-500">*</span>
              </label>
              <select
                v-model="addForm.role"
                required
                class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-medium"
              >
                <option value="Admin SCM">Admin SCM</option>
                <option value="Staff Gudang">Staff Gudang</option>
                <option value="Staff Finance">Staff Finance</option>
                <option value="Staff SCM">Staff SCM</option>
              </select>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                Kata Sandi <span class="text-red-500">*</span>
              </label>
              <input
                v-model="addForm.password"
                type="password"
                placeholder="Minimal 6 karakter"
                required
                minlength="6"
                class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors placeholder:text-slate-400 dark:placeholder:text-slate-500"
              />
            </div>

            <!-- Footer Buttons -->
            <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800 shrink-0">
              <button
                type="button"
                class="px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                @click="isAddModalOpen = false"
              >
                Batal
              </button>

              <button
                type="submit"
                class="px-4 py-2 rounded-lg bg-slate-900 dark:bg-blue-600 hover:bg-slate-800 dark:hover:bg-blue-700 text-white text-xs font-medium transition-colors cursor-pointer flex items-center gap-1.5"
                :disabled="isSubmitting"
              >
                <span v-if="isSubmitting" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span v-else>Simpan & Buat Akun</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Modal: Ubah User -->
    <Teleport to="body">
      <div
        v-if="isEditModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="isEditModalOpen = false"
      >
        <div class="w-full max-w-md bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden max-h-[92vh] flex flex-col">
          <!-- Modal Header -->
          <div class="px-5 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-[#111827] shrink-0">
            <div>
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                Ubah Data Pengguna
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Perbarui informasi akun, hak akses, dan status pengguna
              </p>
            </div>
            <button
              type="button"
              class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              @click="isEditModalOpen = false"
            >
              <X class="w-4 h-4" />
            </button>
          </div>

          <!-- Form Body -->
          <form @submit.prevent="submitEditUser" class="p-5 sm:p-6 space-y-3.5 overflow-y-auto flex-1 text-xs">
            <div
              v-if="editError"
              class="p-2.5 rounded-lg bg-red-50 dark:bg-rose-950/60 border border-red-200 dark:border-rose-900 text-xs text-red-700 dark:text-rose-300 flex items-center gap-2"
            >
              <AlertCircle class="w-4 h-4 shrink-0" />
              <span>{{ editError }}</span>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                Nama Lengkap <span class="text-red-500">*</span>
              </label>
              <input
                v-model="editForm.name"
                type="text"
                required
                class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                  Email <span class="text-red-500">*</span>
                </label>
                <input
                  v-model="editForm.email"
                  type="email"
                  required
                  class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors font-mono"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                  Nomor WhatsApp
                </label>
                <input
                  v-model="editForm.phone"
                  type="text"
                  placeholder="081234567890"
                  class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors font-mono"
                />
              </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                  Peran / Role <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="editForm.role"
                  required
                  class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-medium"
                >
                  <option value="Admin SCM">Admin SCM</option>
                  <option value="Staff Gudang">Staff Gudang</option>
                  <option value="Staff Finance">Staff Finance</option>
                  <option value="Staff SCM">Staff SCM</option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                  Status Akun <span class="text-red-500">*</span>
                </label>
                <select
                  v-model="editForm.status"
                  required
                  class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-medium"
                >
                  <option value="approved">Aktif (Approved)</option>
                  <option value="pending">Menunggu ACC (Pending)</option>
                  <option value="rejected">Nonaktif (Rejected)</option>
                </select>
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300">
                Ganti Kata Sandi (Opsional)
              </label>
              <input
                v-model="editForm.password"
                type="password"
                placeholder="Kosongkan jika tidak ingin mengubah sandi"
                minlength="6"
                class="w-full h-8.5 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:border-slate-500 focus:ring-1 focus:ring-slate-300 dark:focus:ring-slate-600 focus:outline-hidden transition-colors placeholder:text-slate-400 dark:placeholder:text-slate-500"
              />
            </div>

            <!-- Footer Buttons -->
            <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100 dark:border-slate-800 shrink-0">
              <button
                type="button"
                :disabled="isEditing"
                class="px-3.5 py-2 rounded-lg border border-slate-200 dark:border-slate-700 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors cursor-pointer disabled:opacity-50"
                @click="isEditModalOpen = false"
              >
                Batal
              </button>

              <button
                type="submit"
                class="px-4 py-2 rounded-lg bg-slate-900 dark:bg-blue-600 hover:bg-slate-800 dark:hover:bg-blue-700 text-white text-xs font-medium transition-colors cursor-pointer flex items-center gap-1.5 disabled:opacity-60"
                :disabled="isEditing"
              >
                <span v-if="isEditing" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                <span v-else>Simpan Perubahan</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Custom In-App Confirmation Modal for Delete -->
    <Teleport to="body">
      <div
        v-if="userToDelete"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/40 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="userToDelete = null"
      >
        <div class="w-full max-w-sm bg-white dark:bg-[#111827] rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 p-5 text-center space-y-3.5">
          <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 flex items-center justify-center mx-auto">
            <Trash2 class="w-5 h-5 text-red-600 dark:text-rose-400" />
          </div>

          <div class="space-y-1">
            <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
              Hapus Akun Pengguna?
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Apakah Anda yakin ingin menghapus akun <strong class="text-slate-800 dark:text-slate-200">{{ userToDelete.name }}</strong>?
            </p>
          </div>

          <div class="p-3 bg-slate-50 dark:bg-slate-900/60 rounded-lg border border-slate-200 dark:border-slate-800 text-left text-xs space-y-1 text-slate-600 dark:text-slate-300">
            <div class="flex justify-between">
              <span class="text-slate-500 dark:text-slate-400">Email:</span>
              <span class="font-mono text-slate-800 dark:text-slate-200 text-[11px] truncate max-w-[180px]">{{ userToDelete.email }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500 dark:text-slate-400">Peran:</span>
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ userToDelete.role }}</span>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-2.5 pt-1">
            <button
              type="button"
              class="w-full py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-xs transition-colors cursor-pointer"
              @click="userToDelete = null"
              :disabled="processingId === userToDelete.id"
            >
              Batal
            </button>

            <button
              type="button"
              class="w-full py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5"
              @click="confirmDelete"
              :disabled="processingId === userToDelete.id"
            >
              <span v-if="processingId === userToDelete.id" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else>Ya, Hapus</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Modal Konfirmasi Reset Data -->
    <Teleport to="body">
      <div
        v-if="isResetModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/40 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="!isResetting && (isResetModalOpen = false)"
      >
        <div class="bg-white dark:bg-[#111827] rounded-xl max-w-sm w-full p-5 shadow-xl border border-slate-200 dark:border-slate-800 text-center space-y-3.5 animate-in fade-in zoom-in-95 duration-150">
          <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-700 flex items-center justify-center mx-auto">
            <Trash2 class="w-5 h-5 text-red-600 dark:text-rose-400" :class="{ 'animate-spin': isResetting }" />
          </div>

          <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-1">
            Hapus Semua Data?
          </h3>
          <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
            Apakah Anda yakin ingin menghapus <strong class="text-slate-800 dark:text-slate-200">SELURUH data</strong> arsip program, berkas lampiran, dan riwayat dokumen secara permanen? Data akan dikosongkan total dari sistem.
          </p>

          <div class="grid grid-cols-2 gap-2.5 pt-1">
            <button
              type="button"
              :disabled="isResetting"
              class="w-full py-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 font-medium text-xs transition-colors cursor-pointer disabled:opacity-50"
              @click="isResetModalOpen = false"
            >
              Batal
            </button>
            <button
              type="button"
              :disabled="isResetting"
              class="w-full py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 disabled:opacity-75"
              @click="handleResetData"
            >
              <RotateCcw class="w-3.5 h-3.5" :class="{ 'animate-spin': isResetting }" />
              <span>{{ isResetting ? 'Menghapus...' : 'Ya, Hapus Semua' }}</span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { Plus, Search, Trash2, X, AlertCircle, Users, RotateCcw, Eye, EyeOff, Mail, Phone, Calendar, Check, Pencil } from 'lucide-vue-next';
import { useTaxStore } from '../store/taxStore';

const route = useRoute();
const router = useRouter();
const store = useTaxStore();

const showDemoAccounts = computed(() => store.showDemoAccounts.value);
const isResetting = computed(() => store.isResetting.value);
const isResetModalOpen = ref(false);

async function handleToggleDemoAccounts() {
  await store.setDemoAccountsVisibility(!showDemoAccounts.value);
}

async function handleResetData() {
  const res = await store.resetEntireSystemData();
  if (res && res.success) {
    isResetModalOpen.value = false;
  }
}

onMounted(() => {
  const role = store.currentUser.value?.role || '';
  const isAdmin = role === 'Admin SCM' || role.toLowerCase().includes('admin');
  if (!isAdmin) {
    router.replace('/dashboard');
    return;
  }
  store.fetchUsers();
});

const activeFilter = ref('all');

// Synchronize tab filter with route query param (?tab=all|pending|approved)
watch(
  () => route.query.tab,
  (tab) => {
    const value = Array.isArray(tab) ? tab[0] : tab;
    if (value === 'pending') {
      activeFilter.value = 'pending';
    } else if (value === 'approved') {
      activeFilter.value = 'approved';
    } else {
      activeFilter.value = 'all';
    }
  },
  { immediate: true }
);

function setActiveFilter(tab) {
  activeFilter.value = tab;
  router.replace({
    query: {
      ...route.query,
      tab: tab === 'all' ? undefined : tab
    }
  });
}
const searchQuery = ref('');
const processingId = ref(null);
const userToDelete = ref(null);

const isAddModalOpen = ref(false);
const isSubmitting = ref(false);
const addError = ref('');

const addForm = reactive({
  name: '',
  email: '',
  phone: '',
  role: 'Staff Gudang',
  password: ''
});

const isEditModalOpen = ref(false);
const isEditing = ref(false);
const editError = ref('');

const editForm = reactive({
  id: null,
  name: '',
  email: '',
  phone: '',
  role: 'Staff Gudang',
  status: 'approved',
  password: ''
});

const allUsers = computed(() => store.allUsers.value);
const pendingUsers = computed(() => store.pendingUsers.value);
const approvedUsers = computed(() => allUsers.value.filter(u => u.status === 'approved'));

const filteredList = computed(() => {
  let list = allUsers.value;

  if (activeFilter.value === 'pending') {
    list = list.filter(u => u.status === 'pending');
  } else if (activeFilter.value === 'approved') {
    list = list.filter(u => u.status === 'approved');
  }

  const query = searchQuery.value.trim().toLowerCase();
  if (query) {
    list = list.filter(u =>
      (u.name && u.name.toLowerCase().includes(query)) ||
      (u.email && u.email.toLowerCase().includes(query)) ||
      (u.phone && u.phone.includes(query)) ||
      (u.role && u.role.toLowerCase().includes(query))
    );
  }

  return list;
});

function getAvatarColorClass(role) {
  return 'bg-slate-100 text-slate-700 border border-slate-200';
}

function getInitials(name) {
  if (!name) return 'U';
  const parts = name.trim().split(/\s+/);
  if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

function getRoleBadgeClass(role) {
  const r = (role || '').toLowerCase();
  if (r.includes('admin')) {
    return 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-950/40 dark:text-purple-300 dark:border-purple-800';
  }
  if (r.includes('gudang')) {
    return 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-800';
  }
  if (r.includes('finance') || r.includes('pajak')) {
    return 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-800';
  }
  return 'bg-sky-50 text-sky-700 border border-sky-200 dark:bg-sky-950/40 dark:text-sky-300 dark:border-sky-800';
}

function formatDate(dateStr) {
  if (!dateStr || dateStr === '-') return '-';
  try {
    const d = new Date(dateStr);
    if (isNaN(d.getTime())) return dateStr;
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
  } catch (e) {
    return dateStr;
  }
}

async function handleApprove(user) {
  if (processingId.value) return;
  processingId.value = user.id;
  try {
    await store.approveUser(user.id);
  } finally {
    processingId.value = null;
  }
}

async function handleReject(user) {
  if (processingId.value) return;
  processingId.value = user.id;
  try {
    await store.rejectUser(user.id);
  } finally {
    processingId.value = null;
  }
}

function promptDelete(user) {
  if (processingId.value) return;
  userToDelete.value = user;
}

async function confirmDelete() {
  if (!userToDelete.value || processingId.value) return;
  const id = userToDelete.value.id;
  processingId.value = id;
  try {
    await store.deleteUser(id);
    userToDelete.value = null;
  } finally {
    processingId.value = null;
  }
}

function openAddModal() {
  addForm.name = '';
  addForm.email = '';
  addForm.phone = '';
  addForm.role = 'Staff Gudang';
  addForm.password = '';
  addError.value = '';
  isAddModalOpen.value = true;
}

async function submitAddUser() {
  if (isSubmitting.value) return;
  isSubmitting.value = true;
  addError.value = '';

  try {
    const res = await store.createUser({ ...addForm });
    if (res.success) {
      isAddModalOpen.value = false;
    } else {
      addError.value = res.message || 'Gagal menambahkan user.';
    }
  } catch (e) {
    addError.value = 'Terjadi kesalahan sistem.';
  } finally {
    isSubmitting.value = false;
  }
}

function openEditModal(user) {
  editForm.id = user.id;
  editForm.name = user.name || '';
  editForm.email = user.email || '';
  editForm.phone = user.phone || '';
  editForm.role = user.role || 'Staff Gudang';
  editForm.status = user.status || 'approved';
  editForm.password = '';
  editError.value = '';
  isEditModalOpen.value = true;
}

async function submitEditUser() {
  if (isEditing.value) return;
  isEditing.value = true;
  editError.value = '';

  try {
    const payload = {
      name: editForm.name,
      email: editForm.email,
      phone: editForm.phone,
      role: editForm.role,
      status: editForm.status
    };
    if (editForm.password && editForm.password.trim().length >= 6) {
      payload.password = editForm.password.trim();
    }
    const res = await store.updateUser(editForm.id, payload);
    if (res.success) {
      isEditModalOpen.value = false;
    } else {
      editError.value = res.message || 'Gagal memperbarui pengguna.';
    }
  } catch (e) {
    editError.value = 'Terjadi kesalahan sistem.';
  } finally {
    isEditing.value = false;
  }
}
</script>
