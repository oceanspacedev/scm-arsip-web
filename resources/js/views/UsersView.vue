<template>
  <div class="space-y-4">
    <!-- Judul + aksi -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
      <div>
        <h1 class="text-xl sm:text-2xl font-medium text-slate-900 dark:text-slate-100">Manajemen user</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Kelola akses personel divisi SCM dan Tim Pajak</p>
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto">
        <router-link
          to="/permissions"
          class="h-9 px-3 flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
        >
          <ShieldCheck class="w-4 h-4 text-slate-500 dark:text-slate-400" :stroke-width="1.75" />
          <span>Atur hak akses role</span>
        </router-link>
        <button
          type="button"
          class="h-9 px-4 flex-1 sm:flex-initial inline-flex items-center justify-center gap-2 rounded-lg text-sm font-medium bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white transition-colors cursor-pointer"
          @click="openAddModal"
        >
          <Plus class="w-4 h-4" :stroke-width="1.75" />
          <span>Tambah user</span>
        </button>
      </div>
    </div>

    <!-- Tab status + pencarian -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 border-b border-slate-200 dark:border-slate-800">
      <div class="flex gap-6 overflow-x-auto -mb-px no-scrollbar text-sm">
        <button
          v-for="tab in tabs"
          :key="tab.value"
          type="button"
          class="pb-3 border-b-2 inline-flex items-center gap-2 shrink-0 whitespace-nowrap transition-colors cursor-pointer"
          :class="activeFilter === tab.value
            ? 'border-slate-900 text-slate-900 dark:border-slate-100 dark:text-slate-100 font-medium'
            : 'border-transparent text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100'"
          @click="setActiveFilter(tab.value)"
        >
          <span>{{ tab.label }}</span>
          <span
            class="tabular-nums text-xs"
            :class="tab.value === 'pending' && tab.count > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500'"
          >{{ tab.count }}</span>
        </button>
      </div>

      <div class="relative w-full sm:w-72 pb-3">
        <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 pointer-events-none" :stroke-width="1.75" />
        <input
          v-model="searchQuery"
          type="search"
          aria-label="Cari user"
          placeholder="Cari nama, email, atau nomor WA"
          class="w-full h-9 pl-9 pr-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-colors"
        />
      </div>
    </div>

    <!-- Tabel user (desktop) -->
    <div class="hidden md:block bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-left text-[13px] border-collapse">
          <thead>
            <tr class="bg-slate-50 dark:bg-[#171719] border-b border-slate-200 dark:border-slate-800 text-xs font-medium text-slate-500 dark:text-slate-400 whitespace-nowrap">
              <th class="py-3 px-4">Nama</th>
              <th class="py-3 px-4">No. WhatsApp</th>
              <th class="py-3 px-4">Peran</th>
              <th class="py-3 px-4">Terdaftar</th>
              <th class="py-3 px-4">Status</th>
              <th class="py-3 px-4"><span class="sr-only">Aksi</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800">
            <tr
              v-for="user in paginatedUsers"
              :key="user.id"
              class="hover:bg-slate-50 dark:hover:bg-[#1e1e20] transition-colors"
            >
              <td class="py-3 px-4">
                <div class="flex items-center gap-3">
                  <span class="w-8 h-8 rounded-full inline-flex items-center justify-center shrink-0 bg-slate-100 dark:bg-slate-800 text-xs font-medium text-slate-600 dark:text-slate-300">
                    {{ getInitials(user.name) }}
                  </span>
                  <div class="min-w-0">
                    <p class="font-medium text-slate-900 dark:text-slate-100 leading-5 truncate">{{ user.name }}</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">{{ user.email }}</p>
                  </div>
                </div>
              </td>
              <td class="py-3 px-4 whitespace-nowrap tabular-nums text-slate-700 dark:text-slate-300">{{ user.phone || '-' }}</td>
              <td class="py-3 px-4 whitespace-nowrap text-slate-700 dark:text-slate-300">{{ user.role }}</td>
              <td class="py-3 px-4 whitespace-nowrap tabular-nums text-slate-500 dark:text-slate-400">{{ formatDate(user.registered_at) }}</td>
              <td class="py-3 px-4 whitespace-nowrap">
                <span class="inline-flex items-center gap-2 text-slate-700 dark:text-slate-300">
                  <span class="w-1.5 h-1.5 rounded-full" :class="statusDot(user.status)"></span>
                  {{ statusLabel(user.status) }}
                </span>
              </td>
              <td class="py-3 px-4 whitespace-nowrap">
                <div v-if="user.email !== 'admin@scm.corp'" class="flex items-center justify-end gap-1">
                  <button
                    type="button"
                    :disabled="processingId === user.id"
                    class="h-8 px-3 mr-1 rounded-lg border text-xs font-medium transition-colors cursor-pointer disabled:opacity-50 inline-flex items-center gap-1.5"
                    :class="user.status === 'pending'
                      ? 'border-transparent bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white'
                      : 'border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800'"
                    @click="user.status === 'approved' ? handleReject(user) : handleApprove(user)"
                  >
                    <span v-if="processingId === user.id" class="w-3 h-3 rounded-full border-2 border-current/30 border-t-current animate-spin"></span>
                    {{ user.status === 'pending' ? 'ACC' : user.status === 'approved' ? 'Nonaktifkan' : 'Aktifkan' }}
                  </button>
                  <button
                    type="button"
                    :disabled="processingId === user.id"
                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer disabled:opacity-50"
                    aria-label="Ubah user"
                    title="Ubah"
                    @click="openEditModal(user)"
                  >
                    <Pencil class="w-4 h-4" :stroke-width="1.75" />
                  </button>
                  <button
                    type="button"
                    :disabled="processingId === user.id"
                    class="h-8 w-8 inline-flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:text-rose-400 dark:hover:bg-rose-950/40 transition-colors cursor-pointer disabled:opacity-50"
                    aria-label="Hapus user"
                    title="Hapus"
                    @click="promptDelete(user)"
                  >
                    <Trash2 class="w-4 h-4" :stroke-width="1.75" />
                  </button>
                </div>
                <p v-else class="text-right text-xs text-slate-400 dark:text-slate-500">Super admin</p>
              </td>
            </tr>

            <tr v-if="filteredList.length === 0">
              <td colspan="6" class="py-16 text-center">
                <Users class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600" :stroke-width="1.5" />
                <p class="mt-3 text-sm text-slate-700 dark:text-slate-300">Tidak ada user yang cocok</p>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <TablePagination
        v-model:page="currentPage"
        v-model:page-size="pageSize"
        :total="filteredList.length"
        item-label="user"
      />
    </div>

    <!-- Mobile Cards View (block md:hidden) -->
    <div class="block md:hidden space-y-3">
      <div
        v-for="user in paginatedUsers"
        :key="user.id"
        class="bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800 p-3.5 sm:p-4 space-y-3 transition-colors"
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
        <div class="rounded-lg p-2.5 space-y-1.5 text-xs text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800">
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
                class="h-7 px-3 rounded-md text-xs font-medium transition-colors cursor-pointer disabled:opacity-60 flex items-center gap-1 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700"
                @click="handleApprove(user)"
              >
                <span v-if="processingId === user.id" class="w-3 h-3 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"></span>
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
        class="bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-400 dark:text-slate-500"
      >
        <Users class="w-8 h-8 text-slate-300 dark:text-slate-600 mx-auto mb-2" />
        <p class="font-medium text-slate-600 dark:text-slate-300 text-xs">Tidak ada data pengguna yang sesuai.</p>
      </div>
      <TablePagination
        v-if="filteredList.length > 0"
        v-model:page="currentPage"
        v-model:page-size="pageSize"
        :total="filteredList.length"
        item-label="user"
        class="bg-white dark:bg-graphite-card rounded-xl border border-slate-200 dark:border-slate-800"
      />
    </div>

    <!-- Modal: + Tambah User Baru -->
    <Teleport to="body">
      <div
        v-if="isAddModalOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 dark:bg-slate-950/80 backdrop-blur-xs"
        @click.self="isAddModalOpen = false"
      >
        <div class="w-full max-w-md bg-white dark:bg-graphite-card rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden max-h-[92vh] flex flex-col">
          <!-- Modal Header -->
          <div class="px-5 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-graphite-card shrink-0">
            <div>
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                Tambah user baru
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
                class="px-4 py-2 rounded-lg text-xs font-medium transition-colors cursor-pointer flex items-center gap-1.5 disabled:opacity-60 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700"
                :disabled="isSubmitting"
              >
                <span v-if="isSubmitting" class="w-3.5 h-3.5 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"></span>
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
        <div class="w-full max-w-md bg-white dark:bg-graphite-card rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 overflow-hidden max-h-[92vh] flex flex-col">
          <!-- Modal Header -->
          <div class="px-5 sm:px-6 py-3.5 sm:py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-white dark:bg-graphite-card shrink-0">
            <div>
              <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100">
                Ubah data pengguna
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
                class="px-4 py-2 rounded-lg text-xs font-medium transition-colors cursor-pointer flex items-center gap-1.5 disabled:opacity-60 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-slate-700"
                :disabled="isEditing"
              >
                <span v-if="isEditing" class="w-3.5 h-3.5 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"></span>
                <span v-else>Simpan perubahan</span>
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
        <div class="w-full max-w-sm bg-white dark:bg-graphite-card rounded-xl shadow-xl border border-slate-200 dark:border-slate-800 p-5 text-center space-y-3.5">
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
              class="w-full py-2 rounded-lg font-medium text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 bg-white dark:bg-slate-800 border border-rose-200 dark:border-rose-900/60 text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40"
              @click="confirmDelete"
              :disabled="processingId === userToDelete.id"
            >
              <span v-if="processingId === userToDelete.id" class="w-3.5 h-3.5 border-2 border-slate-300 border-t-slate-600 rounded-full animate-spin"></span>
              <span v-else>Ya, Hapus</span>
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
import { Plus, Search, Trash2, X, AlertCircle, Users, Eye, EyeOff, Mail, Phone, Calendar, Check, Pencil, ShieldCheck } from 'lucide-vue-next';
import { useTaxStore } from '../store/taxStore';
import TablePagination from '../components/ui/TablePagination.vue';

const route = useRoute();
const router = useRouter();
const store = useTaxStore();

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

const tabs = computed(() => [
  { value: 'all', label: 'Semua user', count: allUsers.value.length },
  { value: 'pending', label: 'Menunggu ACC', count: pendingUsers.value.length },
  { value: 'approved', label: 'Aktif', count: approvedUsers.value.length },
]);

// Pagination
const pageSize = ref(10);
const currentPage = ref(1);
const paginatedUsers = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return filteredList.value.slice(start, start + pageSize.value);
});
watch([activeFilter, searchQuery], () => { currentPage.value = 1; });
watch(filteredList, (list) => {
  const lastPage = Math.max(1, Math.ceil(list.length / pageSize.value));
  if (currentPage.value > lastPage) currentPage.value = lastPage;
});

function statusLabel(status) {
  if (status === 'approved') return 'Aktif';
  if (status === 'pending') return 'Menunggu ACC';
  return 'Nonaktif';
}

function statusDot(status) {
  if (status === 'approved') return 'bg-emerald-500';
  if (status === 'pending') return 'bg-amber-500';
  return 'bg-slate-400';
}

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
