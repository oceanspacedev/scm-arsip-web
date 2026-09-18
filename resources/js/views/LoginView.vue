<template>
  <div class="min-h-screen bg-white dark:bg-[#090D16] flex flex-col items-center justify-center p-4 font-sans text-slate-800 dark:text-slate-200 transition-colors relative">
    <!-- Top Right Theme Toggle -->
    <div class="absolute top-4 right-4">
      <button
        type="button"
        @click="toggleDark()"
        class="w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white flex items-center justify-center transition-colors shadow-2xs cursor-pointer"
        :title="isDark ? 'Beralih ke Mode Terang' : 'Beralih ke Mode Gelap'"
      >
        <Sun v-if="isDark" class="w-4 h-4 text-amber-400" />
        <Moon v-else class="w-4 h-4 text-slate-600" />
      </button>
    </div>

    <!-- Main Card Container using shadcn-vue Card components -->
    <Card
      :class="[
        'w-full bg-white dark:bg-[#111827] rounded-2xl border border-slate-200/90 dark:border-slate-800 shadow-sm transition-all my-auto',
        isRegisterMode ? 'max-w-lg' : 'max-w-[420px]'
      ]"
    >
      <!-- ============================================== -->
      <!-- VIEW 1: LOGIN FORM                             -->
      <!-- ============================================== -->
      <template v-if="!isRegisterMode && !isOtpStep">
        <!-- Card Header: Clean Title & Description (Sign Up moved to bottom) -->
        <CardHeader class="pb-4 pt-6 px-6 sm:px-7">
          <CardTitle class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Login ke Akun
          </CardTitle>
          <CardDescription class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ loginTab === 'whatsapp' ? 'Masukkan nomor WhatsApp terdaftar untuk verifikasi OTP' : 'Masukkan email dan kata sandi untuk masuk ke sistem' }}
          </CardDescription>
        </CardHeader>

        <CardContent class="px-6 sm:px-7 pb-7 space-y-4">

          <!-- Error / Pending / Not Registered Alert -->
          <div
            v-if="errorMessage"
            class="p-3.5 rounded-lg border text-xs flex flex-col gap-2 animate-in fade-in duration-150"
            :class="isPendingAlert ? 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-900/60 text-amber-900 dark:text-amber-400' : isNotRegisteredAlert ? 'bg-blue-50 dark:bg-blue-950/40 border-blue-200 dark:border-blue-900/60 text-blue-950 dark:text-blue-300' : 'bg-rose-50 dark:rose-950/40 border-rose-200 dark:border-rose-900/60 text-rose-700 dark:text-rose-400'"
          >
            <div class="flex items-start gap-2">
              <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
              <div class="flex-1 text-xs">
                <p class="font-semibold" v-if="isPendingAlert">Akun Menunggu Persetujuan</p>
                <p class="font-semibold" v-else-if="isNotRegisteredAlert">Nomor Belum Terdaftar</p>
                <p class="mt-0.5 leading-relaxed">{{ errorMessage }}</p>
              </div>
            </div>

            <!-- Direct registration CTA if phone number is not registered in database -->
            <button
              v-if="isNotRegisteredAlert"
              type="button"
              class="w-full py-2 px-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors cursor-pointer flex items-center justify-center gap-1.5 shadow-xs mt-0.5"
              @click="redirectToRegisterWithPhone"
            >
              <span>Registrasi Akun Baru Sekarang</span>
              <ArrowRight class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Tab 1: Email & Password Form -->
          <form v-if="loginTab === 'password'" @submit.prevent="handlePasswordSubmit" class="space-y-4">
            <!-- Email Field -->
            <div class="space-y-1.5">
              <label class="block text-sm font-medium text-slate-900 dark:text-slate-200">
                Email
              </label>
              <input
                v-model="email"
                type="email"
                placeholder="m@example.com"
                required
                class="w-full h-10 px-3.5 text-sm rounded-lg border border-slate-300 dark:border-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
              />
            </div>

            <!-- Password Field (Lupa password removed) -->
            <div class="space-y-1.5">
              <label class="block text-sm font-medium text-slate-900 dark:text-slate-200">
                Password
              </label>
              <div class="relative">
                <input
                  v-model="password"
                  :type="showPassword ? 'text' : 'password'"
                  placeholder="••••••••"
                  required
                  class="w-full h-10 px-3.5 pr-10 text-sm rounded-lg border border-slate-300 dark:border-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                />
                <button
                  type="button"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 cursor-pointer p-0.5"
                  @click="showPassword = !showPassword"
                >
                  <EyeOff v-if="showPassword" class="w-4 h-4" />
                  <Eye v-else class="w-4 h-4" />
                </button>
              </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
              <label class="flex items-center gap-2 cursor-pointer text-xs text-slate-600 dark:text-slate-400">
                <input
                  v-model="rememberMe"
                  type="checkbox"
                  class="w-4 h-4 rounded border-slate-300 dark:border-slate-700 text-blue-600 focus:ring-blue-600 cursor-pointer accent-blue-600"
                />
                <span>Ingat saya di perangkat ini</span>
              </label>
            </div>

            <!-- Main Blue Button: Login -->
            <button
              type="submit"
              class="w-full h-10 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium text-sm transition-colors cursor-pointer flex items-center justify-center gap-2 shadow-xs disabled:opacity-70"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else>Login</span>
            </button>

            <!-- Divider: atau login dengan -->
            <div class="relative flex items-center justify-center pt-1">
              <div class="border-t border-slate-200 dark:border-slate-800 w-full"></div>
              <span class="bg-white dark:bg-[#111827] px-3 text-[11px] text-slate-400 dark:text-slate-500 font-medium shrink-0">
                atau login dengan
              </span>
              <div class="border-t border-slate-200 dark:border-slate-800 w-full"></div>
            </div>

            <!-- WhatsApp button with Authentic WhatsApp Icon -->
            <button
              type="button"
              class="w-full h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-200 font-medium text-xs transition-all flex items-center justify-center gap-2.5 shadow-2xs cursor-pointer group"
              @click="loginTab = 'whatsapp'; errorMessage = ''"
            >
              <svg class="w-4 h-4 shrink-0 transition-transform group-hover:scale-105" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12C2 13.82 2.484 15.522 3.327 16.991L2 22L7.151 20.697C8.583 21.523 10.237 22 12 22C17.523 22 22 17.523 22 12C22 6.477 17.523 2 12 2Z" fill="#25D366" />
                <path fill-rule="evenodd" clip-rule="evenodd" d="M17.472 14.382C17.171 14.232 15.692 13.504 15.416 13.404C15.14 13.304 14.94 13.254 14.739 13.554C14.539 13.854 13.962 14.532 13.787 14.732C13.612 14.932 13.436 14.957 13.135 14.807C12.835 14.657 11.868 14.34 10.721 13.317C9.575 12.295 8.801 11.033 8.575 10.647C8.35 10.261 8.551 10.052 8.701 9.902C8.836 9.767 9.002 9.551 9.152 9.376C9.302 9.201 9.352 9.076 9.453 8.876C9.553 8.676 9.503 8.5 9.428 8.35C9.353 8.2 8.751 6.718 8.501 6.115C8.257 5.527 8.009 5.607 7.824 5.598C7.649 5.59 7.448 5.588 7.247 5.588C7.047 5.588 6.721 5.663 6.445 5.964C6.169 6.264 5.392 6.992 5.392 8.472C5.392 9.952 6.47 11.382 6.621 11.583C6.772 11.783 8.743 14.823 11.762 16.128C12.48 16.438 13.041 16.624 13.478 16.763C14.2 16.993 14.856 16.96 15.375 16.883C15.953 16.796 17.155 16.156 17.406 15.454C17.657 14.752 17.657 14.151 17.582 14.025C17.506 13.899 17.306 13.824 17.005 13.674" fill="white" />
              </svg>
              <span>WhatsApp</span>
            </button>
          </form>

          <!-- Tab 2: WhatsApp OTP Form -->
          <form v-else @submit.prevent="handleWhatsAppSubmit" class="space-y-4">
            <div class="space-y-1.5">
              <label class="block text-sm font-medium text-slate-900 dark:text-slate-200">
                Nomor WhatsApp / Email Terdaftar
              </label>
              <input
                v-model="identifier"
                type="text"
                placeholder="Contoh: 081224290502"
                required
                class="w-full h-10 px-3.5 text-sm rounded-lg border border-slate-300 dark:border-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 font-mono bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
              />
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Kode verifikasi OTP 6-digit akan dikirimkan ke WhatsApp Anda.
              </p>
            </div>

            <button
              type="submit"
              class="w-full h-10 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium text-sm transition-colors cursor-pointer flex items-center justify-center gap-2 shadow-xs disabled:opacity-70"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else>Kirim Kode OTP</span>
            </button>

            <!-- Divider: atau login dengan -->
            <div class="relative flex items-center justify-center pt-1">
              <div class="border-t border-slate-200 dark:border-slate-800 w-full"></div>
              <span class="bg-white dark:bg-[#111827] px-3 text-[11px] text-slate-400 dark:text-slate-500 font-medium shrink-0">
                atau login dengan
              </span>
              <div class="border-t border-slate-200 dark:border-slate-800 w-full"></div>
            </div>

            <!-- Email & Sandi button -->
            <button
              type="button"
              class="w-full h-10 px-4 rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900/80 hover:bg-slate-50 dark:hover:bg-slate-800 hover:border-slate-300 dark:hover:border-slate-700 text-slate-700 dark:text-slate-200 font-medium text-xs transition-all flex items-center justify-center gap-2 shadow-2xs cursor-pointer group"
              @click="loginTab = 'password'; errorMessage = ''"
            >
              <Mail class="w-4 h-4 text-slate-500 dark:text-slate-400" />
              <span>Email & Kata Sandi</span>
            </button>
          </form>

          <!-- Demo Accounts Box (Controlled by Admin settings) -->
          <div v-if="showDemoAccounts" class="pt-4 border-t border-slate-100 dark:border-slate-800 animate-in fade-in duration-200">
            <div class="flex items-center justify-between mb-2">
              <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Akun Cepat / Demo
              </span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
              <button
                v-for="user in demoAccounts"
                :key="user.email"
                type="button"
                class="p-2 rounded-lg border border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/60 hover:bg-blue-50 dark:hover:bg-slate-800 hover:border-blue-300 dark:hover:border-blue-700 transition-all text-left cursor-pointer group"
                :title="`Masuk langsung sebagai ${user.label}`"
                @click="quickLoginDemo(user)"
              >
                <div class="font-bold text-xs text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 leading-tight truncate">
                  {{ user.label }}
                </div>
                <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate mt-0.5 font-mono">
                  {{ user.email.split('@')[0] }}
                </div>
              </button>
            </div>
          </div>
        </CardContent>

        <!-- Card Footer: Sign Up moved to bottom -->
        <CardFooter class="px-6 sm:px-7 pb-6 pt-3 flex items-center justify-center border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
          <span>Belum punya akun?</span>
          <button
            type="button"
            class="ml-1.5 font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline cursor-pointer transition-colors"
            @click="goToRegister"
          >
            Registrasi sekarang
          </button>
        </CardFooter>
      </template>

      <!-- ============================================== -->
      <!-- VIEW 2: OTP VERIFICATION SCREEN (shadcn input-otp) -->
      <!-- ============================================== -->
      <template v-else-if="!isRegisterMode && isOtpStep">
        <CardHeader class="pt-6 px-6 sm:px-7 pb-4">
          <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 text-xs font-semibold mb-2 border border-blue-200 dark:border-blue-900/60 w-fit">
            <Smartphone class="w-3.5 h-3.5" />
            <span>Verifikasi WhatsApp OTP</span>
          </div>
          <CardTitle class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Masukkan Kode OTP
          </CardTitle>
          <CardDescription class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed">
            Kode verifikasi 6-digit telah dikirimkan ke <strong class="text-slate-800 dark:text-slate-200 font-mono">{{ maskedPhone }}</strong>.
          </CardDescription>
        </CardHeader>

        <CardContent class="px-6 sm:px-7 pb-7 space-y-4">
          <div
            v-if="errorMessage"
            class="p-3 bg-rose-50 dark:bg-rose-950/40 rounded-lg border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-400 flex items-center gap-2"
          >
            <AlertCircle class="w-4 h-4 shrink-0" />
            <span>{{ errorMessage }}</span>
          </div>

          <form @submit.prevent="handleOtpVerify" class="space-y-5">
            <div class="space-y-3 flex flex-col items-center">
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 text-center">
                Kode Verifikasi (6 Angka)
              </label>

              <!-- Official shadcn-vue InputOTP Component -->
              <InputOTP
                v-model="otpInput"
                :maxlength="6"
                @complete="handleOtpVerify"
              >
                <InputOTPGroup>
                  <InputOTPSlot :index="0" />
                  <InputOTPSlot :index="1" />
                  <InputOTPSlot :index="2" />
                </InputOTPGroup>
                <InputOTPSeparator />
                <InputOTPGroup>
                  <InputOTPSlot :index="3" />
                  <InputOTPSlot :index="4" />
                  <InputOTPSlot :index="5" />
                </InputOTPGroup>
              </InputOTP>
            </div>

            <button
              type="submit"
              class="w-full h-10 rounded-lg bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-medium text-sm transition-colors cursor-pointer flex items-center justify-center gap-2 shadow-xs disabled:opacity-70"
              :disabled="isLoading || otpInput.length < 6"
            >
              <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else>Verifikasi & Masuk</span>
            </button>
          </form>

          <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              class="hover:text-slate-800 dark:hover:text-slate-200 transition-colors cursor-pointer flex items-center gap-1.5"
              @click="backToLoginForm"
            >
              <ArrowLeft class="w-3.5 h-3.5" />
              <span>Ganti Akun</span>
            </button>

            <button
              type="button"
              class="font-semibold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer disabled:text-slate-400 dark:disabled:text-slate-600 disabled:no-underline"
              :disabled="resendCooldown > 0"
              @click="handleResendOtp"
            >
              <span v-if="resendCooldown > 0">Kirim Ulang ({{ resendCooldown }}s)</span>
              <span v-else>Kirim Ulang OTP</span>
            </button>
          </div>
        </CardContent>
      </template>

      <!-- ============================================== -->
      <!-- VIEW 3: REGISTRATION FORM                       -->
      <!-- ============================================== -->
      <template v-else-if="isRegisterMode">
        <CardHeader class="pb-4 pt-6 px-6 sm:px-7">
          <CardTitle class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            Formulir Pendaftaran
          </CardTitle>
          <CardDescription class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Lengkapi data Anda untuk pengajuan akun baru
          </CardDescription>
        </CardHeader>

        <CardContent class="px-6 sm:px-7 pb-7">
          <!-- Register Success State -->
          <div v-if="registerSuccess" class="space-y-5 text-center py-2">
            <div class="w-12 h-12 rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-900/60 flex items-center justify-center mx-auto">
              <Check class="w-6 h-6 stroke-[2.5]" />
            </div>

            <div class="space-y-1.5">
              <h3 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">
                Pendaftaran Berhasil Diajukan
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto leading-relaxed">
                Pengajuan akun Anda telah tersimpan dan menunggu persetujuan Administrator SCM.
              </p>
            </div>

            <div class="bg-slate-50 dark:bg-slate-900/60 rounded-lg p-3.5 border border-slate-200 dark:border-slate-800 text-xs space-y-2 text-left max-w-sm mx-auto">
              <div class="flex items-center justify-between">
                <span class="text-slate-400 dark:text-slate-500">Nama</span>
                <span class="font-semibold text-slate-900 dark:text-white">{{ registeredData.name }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400 dark:text-slate-500">Email</span>
                <span class="font-mono text-slate-800 dark:text-slate-200">{{ registeredData.email }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-slate-400 dark:text-slate-500">Role Diajukan</span>
                <span class="font-medium text-slate-800 dark:text-slate-200">{{ registeredData.role }}</span>
              </div>
              <div class="flex items-center justify-between pt-1.5 border-t border-slate-200 dark:border-slate-800">
                <span class="text-slate-400 dark:text-slate-500">Status</span>
                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-amber-700 dark:text-amber-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                  <span>Menunggu ACC Admin</span>
                </span>
              </div>
            </div>

            <div class="pt-2 max-w-sm mx-auto">
              <button
                type="button"
                class="w-full h-10 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm transition-colors cursor-pointer shadow-xs"
                @click="backToLoginForm"
              >
                Kembali ke Halaman Login
              </button>
            </div>
          </div>

          <!-- Registration Form Fields -->
          <form v-else @submit.prevent="handleRegisterSubmit" class="space-y-3.5">
            <div
              v-if="errorMessage"
              class="p-3 bg-rose-50 dark:bg-rose-950/40 rounded-lg border border-rose-200 dark:border-rose-900/60 text-xs text-rose-700 dark:text-rose-400 flex items-center gap-2"
            >
              <AlertCircle class="w-4 h-4 shrink-0" />
              <span>{{ errorMessage }}</span>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                Nama Lengkap <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="regForm.name"
                type="text"
                placeholder="Nama sesuai identitas kantor"
                required
                class="w-full h-9 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
              />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Nomor WhatsApp <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="regForm.phone"
                  type="text"
                  placeholder="Contoh: 081234567890"
                  required
                  class="w-full h-9 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 font-mono bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Alamat Email <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="regForm.email"
                  type="email"
                  placeholder="nama@perusahaan.com"
                  required
                  class="w-full h-9 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                />
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                Pengajuan Jabatan / Role <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="regForm.role"
                required
                class="w-full h-9 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden transition-all bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 font-medium"
              >
                <option value="Staff Gudang">Staff Gudang</option>
                <option value="Staff Finance">Staff Finance</option>
                <option value="Staff SCM">Staff SCM</option>
              </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Kata Sandi <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="regForm.password"
                  type="password"
                  placeholder="Minimal 6 karakter"
                  required
                  minlength="6"
                  class="w-full h-9 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">
                  Konfirmasi Kata Sandi <span class="text-rose-500">*</span>
                </label>
                <input
                  v-model="regForm.passwordConfirmation"
                  type="password"
                  placeholder="Ketik ulang kata sandi"
                  required
                  minlength="6"
                  class="w-full h-9 px-3 text-xs rounded-lg border border-slate-300 dark:border-slate-700 focus:border-blue-600 focus:ring-1 focus:ring-blue-600 focus:outline-hidden transition-all placeholder:text-slate-400 dark:placeholder:text-slate-500 bg-white dark:bg-slate-900 text-slate-900 dark:text-white"
                />
              </div>
            </div>

            <div class="p-2.5 rounded-lg bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200/80 dark:border-blue-900/60 text-[11px] text-blue-950 dark:text-blue-300 flex items-start gap-2">
              <AlertCircle class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
              <span>
                Akun baru akan diverifikasi oleh Administrator sebelum dapat login ke sistem.
              </span>
            </div>

            <button
              type="submit"
              class="w-full h-10 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm transition-colors cursor-pointer flex items-center justify-center gap-2 shadow-xs disabled:opacity-70"
              :disabled="isLoading"
            >
              <span v-if="isLoading" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
              <span v-else>Daftar Sekarang</span>
            </button>
          </form>
        </CardContent>

        <!-- Registration Card Footer: Login link at bottom -->
        <CardFooter v-if="!registerSuccess" class="px-6 sm:px-7 pb-6 pt-3 flex items-center justify-center border-t border-slate-100 dark:border-slate-800 text-xs text-slate-500 dark:text-slate-400">
          <span>Sudah memiliki akun?</span>
          <button
            type="button"
            class="ml-1.5 font-semibold text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 hover:underline cursor-pointer transition-colors"
            @click="backToLoginForm"
          >
            Masuk sekarang
          </button>
        </CardFooter>
      </template>
    </Card>

    <!-- Simple Footer -->
    <p class="text-[11px] text-slate-400 dark:text-slate-500 text-center mt-5 select-none">
      &copy; 2026 TaxVault Enterprise System.
    </p>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useDark, useToggle } from '@vueuse/core';
import {
  Check,
  AlertCircle,
  Eye,
  EyeOff,
  ArrowLeft,
  ArrowRight,
  Smartphone,
  Sun,
  Moon,
  Mail,
} from 'lucide-vue-next';
import {
  Card,
  CardHeader,
  CardTitle,
  CardDescription,
  CardContent,
  CardFooter,
} from '@/components/ui/card';
import {
  InputOTP,
  InputOTPGroup,
  InputOTPSlot,
  InputOTPSeparator,
} from '@/components/ui/input-otp';
import { useTaxStore } from '../store/taxStore';

const isDark = useDark({
  storageKey: 'scm_taxvault_theme',
  valueDark: 'dark',
  valueLight: ''
});
const toggleDark = useToggle(isDark);

const router = useRouter();
const route = useRoute();
const store = useTaxStore();

// Navigation states
const isRegisterMode = ref(route.name === 'register' || route.query.mode === 'register');
const loginTab = ref('password'); // 'password' | 'whatsapp'
const isOtpStep = ref(false);

// Form models
const email = ref('');
const password = ref('');
const showPassword = ref(false);
const rememberMe = ref(false);

const identifier = ref('');
const otpInput = ref('');
const activeOtpCode = ref('');
const targetPhone = ref('');
const resendCooldown = ref(0);
let cooldownTimer = null;

const isLoading = ref(false);
const errorMessage = ref('');
const isPendingAlert = ref(false);
const isNotRegisteredAlert = ref(false);

// Registration form
const registerSuccess = ref(false);
const registeredData = ref({
  name: '',
  email: '',
  phone: '',
  role: ''
});

const regForm = reactive({
  name: '',
  phone: '',
  email: '',
  role: 'Staff Gudang',
  password: '',
  passwordConfirmation: ''
});

// Demo accounts list for quick testing
const showDemoAccounts = computed(() => store.showDemoAccounts.value);

onMounted(() => {
  store.fetchSettings();
});

const demoAccounts = [
  { label: 'Admin SCM', email: 'admin@scm.corp', role: 'Admin SCM', phone: '081234567890' },
  { label: 'Staff Gudang', email: 'gudang@scm.corp', role: 'Staff Gudang', phone: '081298765431' },
  { label: 'Staff Finance', email: 'finance@scm.corp', role: 'Staff Finance', phone: '081224290503' },
  { label: 'Staff SCM', email: 'scm@scm.corp', role: 'Staff SCM', phone: '081298765433' },
];

const maskedPhone = computed(() => {
  if (!targetPhone.value) return 'WhatsApp Anda';
  const clean = String(targetPhone.value);
  if (clean.length < 8) return clean;
  return clean.slice(0, 4) + '****' + clean.slice(-4);
});

watch(() => route.path, () => {
  isRegisterMode.value = route.name === 'register' || route.query.mode === 'register';
  errorMessage.value = '';
});

function goToRegister() {
  isRegisterMode.value = true;
  errorMessage.value = '';
  registerSuccess.value = false;
  router.push('/register').catch(() => {});
}

function backToLoginForm() {
  isRegisterMode.value = false;
  isOtpStep.value = false;
  errorMessage.value = '';
  isPendingAlert.value = false;
  isNotRegisteredAlert.value = false;
  router.push('/login').catch(() => {});
}

function fillDemoAccount(acc) {
  email.value = acc.email;
  password.value = 'password123';
  identifier.value = acc.phone;
  errorMessage.value = '';
  isPendingAlert.value = false;
}

// Login instan 1-klik untuk akun demo tanpa OTP
async function quickLoginDemo(acc) {
  isLoading.value = true;
  errorMessage.value = '';
  isPendingAlert.value = false;

  try {
    const credCheck = await store.validatePasswordCredentials(acc.email, 'password123');
    if (credCheck.success && credCheck.user) {
      store.loginDirect(credCheck.user);
      isLoading.value = false;
      router.push('/dashboard');
      return;
    }
  } catch (e) {
    console.warn('Quick demo login fallback', e);
  }

  // Fallback langsung ke store jika API offline
  const found = store.state.users.find(u => u.email.toLowerCase() === acc.email.toLowerCase()) || {
    id: 'usr-' + acc.role.toLowerCase(),
    name: acc.label,
    email: acc.email,
    role: acc.role,
    phone: acc.phone,
    status: 'approved'
  };
  store.loginDirect(found);
  isLoading.value = false;
  router.push('/dashboard');
}

function handleForgotPassword() {
  alert('Untuk mereset kata sandi, silakan hubungi Administrator Sistem atau gunakan metode WhatsApp OTP.');
}

// Handler for Tab 1 (Email & Password)
async function handlePasswordSubmit() {
  errorMessage.value = '';
  isPendingAlert.value = false;
  isLoading.value = true;

  try {
    const credCheck = await store.validatePasswordCredentials(email.value, password.value);
    if (!credCheck.success) {
      errorMessage.value = credCheck.message || 'Email atau kata sandi tidak cocok.';
      isPendingAlert.value = !!credCheck.isPending;
      isLoading.value = false;
      return;
    }

    // Semua akun demo (@scm.corp, demoAccounts, dan Admin SCM) langsung login tanpa OTP
    const cleanEmail = (email.value || '').trim().toLowerCase();
    const isDemoOrAdmin = cleanEmail.includes('@scm.corp') ||
                          cleanEmail === 'auditor@pajak.corp' ||
                          cleanEmail === 'staff@scm.corp' ||
                          demoAccounts.some(d => d.email.toLowerCase() === cleanEmail) ||
                          credCheck.user?.role === 'Admin SCM';

    if (isDemoOrAdmin) {
      store.loginDirect(credCheck.user);
      isLoading.value = false;
      router.push('/dashboard');
      return;
    }

    // Akun reguler diarahkan ke verifikasi WhatsApp OTP
    const otpRes = await store.sendOtp(email.value);
    isLoading.value = false;

    if (otpRes.success) {
      activeOtpCode.value = otpRes.otp;
      targetPhone.value = otpRes.phone;
      isOtpStep.value = true;
      startCooldown();
    } else {
      errorMessage.value = otpRes.message;
      isPendingAlert.value = !!otpRes.isPending;
    }
  } catch (e) {
    isLoading.value = false;
    errorMessage.value = 'Terjadi kesalahan sistem saat memproses login.';
  }
}

// Handler for Tab 2 (WhatsApp/Email OTP)
async function handleWhatsAppSubmit() {
  errorMessage.value = '';
  isPendingAlert.value = false;
  isNotRegisteredAlert.value = false;
  isLoading.value = true;

  // Bypass OTP jika identifier adalah akun demo
  const cleanId = (identifier.value || '').trim().toLowerCase();
  const cleanDigits = cleanId.replace(/[^0-9]/g, '');
  const demoMatch = demoAccounts.find(d => 
    d.email.toLowerCase() === cleanId || 
    (cleanDigits.length >= 8 && d.phone.replace(/[^0-9]/g, '') === cleanDigits)
  );

  if (demoMatch) {
    const found = store.state.users.find(u => u.email.toLowerCase() === demoMatch.email.toLowerCase()) || {
      id: 'usr-' + demoMatch.role.toLowerCase(),
      name: demoMatch.label,
      email: demoMatch.email,
      role: demoMatch.role,
      phone: demoMatch.phone,
      status: 'approved'
    };
    store.loginDirect(found);
    isLoading.value = false;
    router.push('/dashboard');
    return;
  }

  try {
    const otpRes = await store.sendOtp(identifier.value);
    isLoading.value = false;

    if (otpRes.success) {
      activeOtpCode.value = otpRes.otp;
      targetPhone.value = otpRes.phone;
      isOtpStep.value = true;
      startCooldown();
    } else {
      errorMessage.value = otpRes.message;
      isPendingAlert.value = !!otpRes.isPending;
      isNotRegisteredAlert.value = !!otpRes.notRegistered || (otpRes.message && otpRes.message.toLowerCase().includes('belum terdaftar'));
    }
  } catch (e) {
    isLoading.value = false;
    errorMessage.value = 'Gagal menghubungi server WhatsApp Gateway.';
  }
}

function redirectToRegisterWithPhone() {
  const cleanPhone = (identifier.value || '').trim();
  if (cleanPhone) {
    regForm.phone = cleanPhone;
  }
  isNotRegisteredAlert.value = false;
  errorMessage.value = '';
  goToRegister();
}

// Handler for OTP Verification
async function handleOtpVerify() {
  if (!otpInput.value) {
    errorMessage.value = 'Silakan masukkan 6-digit kode OTP.';
    return;
  }

  errorMessage.value = '';
  isLoading.value = true;

  try {
    const result = await store.verifyOtp(otpInput.value);
    isLoading.value = false;

    if (result.success) {
      router.push('/dashboard');
    } else {
      errorMessage.value = result.message || 'Kode OTP salah atau kedaluwarsa.';
    }
  } catch (e) {
    isLoading.value = false;
    errorMessage.value = 'Terjadi kesalahan saat memverifikasi kode OTP.';
  }
}

// Resend OTP
async function handleResendOtp() {
  if (resendCooldown.value > 0) return;
  const currentTarget = targetPhone.value || email.value || identifier.value;
  isLoading.value = true;
  errorMessage.value = '';

  try {
    const res = await store.sendOtp(currentTarget);
    isLoading.value = false;

    if (res.success) {
      activeOtpCode.value = res.otp;
      startCooldown();
    } else {
      errorMessage.value = res.message;
    }
  } catch (e) {
    isLoading.value = false;
    errorMessage.value = 'Gagal mengirim ulang kode OTP.';
  }
}

function startCooldown() {
  resendCooldown.value = 60;
  clearInterval(cooldownTimer);
  cooldownTimer = setInterval(() => {
    if (resendCooldown.value > 0) {
      resendCooldown.value--;
    } else {
      clearInterval(cooldownTimer);
    }
  }, 1000);
}

// Registration Submit Handler
async function handleRegisterSubmit() {
  errorMessage.value = '';

  if (regForm.password !== regForm.passwordConfirmation) {
    errorMessage.value = 'Kata Sandi dan Konfirmasi Kata Sandi tidak cocok.';
    return;
  }

  if (regForm.password.length < 6) {
    errorMessage.value = 'Kata Sandi minimal 6 karakter.';
    return;
  }

  isLoading.value = true;

  try {
    const res = await store.registerUser({
      name: regForm.name,
      email: regForm.email,
      phone: regForm.phone,
      role: regForm.role,
      password: regForm.password
    });

    isLoading.value = false;

    if (res.success) {
      registeredData.value = {
        name: regForm.name,
        email: regForm.email,
        phone: regForm.phone,
        role: regForm.role
      };
      registerSuccess.value = true;
    } else {
      errorMessage.value = res.message || 'Gagal melakukan pendaftaran akun.';
    }
  } catch (e) {
    isLoading.value = false;
    errorMessage.value = 'Terjadi kesalahan sistem saat melakukan pendaftaran.';
  }
}
</script>
