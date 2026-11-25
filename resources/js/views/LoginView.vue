<template>
  <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-16 bg-slate-50 dark:bg-graphite-page text-slate-900 dark:text-slate-100">
    <button
      type="button"
      class="absolute top-4 right-4 h-8 w-8 rounded-lg flex items-center justify-center text-slate-500 hover:text-slate-900 hover:bg-slate-200/60 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500"
      :aria-label="isDark ? 'Beralih ke tema terang' : 'Beralih ke tema gelap'"
      :title="isDark ? 'Tema terang' : 'Tema gelap'"
      @click="toggleDark()"
    >
      <Sun v-if="isDark" class="w-4 h-4" :stroke-width="1.75" />
      <Moon v-else class="w-4 h-4" :stroke-width="1.75" />
    </button>

    <div :class="['w-full', isRegisterMode && !registerSuccess ? 'max-w-[480px]' : 'max-w-[400px]']">
      <!-- Nama produk dan tiga dokumen yang diarsipkan (tanpa logo) -->
      <div class="text-center mb-8">
        <p class="text-lg font-medium leading-none">SCM TaxVault</p>
        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Invoice · Faktur pajak · Memo/DO</p>
      </div>

      <div class="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-graphite-card shadow-[0_1px_2px_rgba(0,0,0,0.04)] dark:shadow-none">
        <!-- ============================================== -->
        <!-- MASUK: email & kata sandi / WhatsApp           -->
        <!-- ============================================== -->
        <template v-if="!isRegisterMode && !isOtpStep">
          <div class="p-6 sm:p-8">
            <h1 class="text-xl font-medium leading-7">Masuk</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              {{ loginTab === 'whatsapp' ? 'Kode OTP dikirim ke WhatsApp yang terdaftar' : 'Gunakan email dan kata sandi akun Anda' }}
            </p>

            <div v-if="errorMessage" role="alert" :class="['mt-6 rounded-lg border px-4 py-3 text-sm', alertClass]">
              <div class="flex items-start gap-3">
                <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" :stroke-width="1.75" />
                <div class="flex-1 leading-5">
                  <p v-if="isPendingAlert" class="font-medium">Akun menunggu persetujuan</p>
                  <p v-else-if="isNotRegisteredAlert" class="font-medium">Nomor belum terdaftar</p>
                  <p :class="{ 'mt-1 opacity-90': isPendingAlert || isNotRegisteredAlert }">{{ errorMessage }}</p>
                </div>
              </div>
              <button
                v-if="isNotRegisteredAlert"
                type="button"
                :class="[secondaryButtonClass, 'mt-3 h-9']"
                @click="redirectToRegisterWithPhone"
              >
                <span>Daftar akun baru</span>
                <ArrowRight class="w-4 h-4" :stroke-width="1.75" />
              </button>
            </div>

            <!-- Email & kata sandi -->
            <form v-if="loginTab === 'password'" class="mt-6" @submit.prevent="handlePasswordSubmit">
              <div class="space-y-4">
                <div>
                  <label for="login-email" :class="labelClass">Email</label>
                  <input
                    id="login-email"
                    v-model="email"
                    type="email"
                    autocomplete="username"
                    placeholder="nama@perusahaan.com"
                    required
                    :class="inputClass"
                  />
                </div>

                <div>
                  <label for="login-password" :class="labelClass">Kata sandi</label>
                  <div class="relative">
                    <input
                      id="login-password"
                      v-model="password"
                      :type="showPassword ? 'text' : 'password'"
                      autocomplete="current-password"
                      required
                      :class="[inputClass, 'pr-10']"
                    />
                    <button
                      type="button"
                      class="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8 rounded-md flex items-center justify-center text-slate-400 hover:text-slate-700 dark:text-slate-500 dark:hover:text-slate-200 transition-colors cursor-pointer"
                      :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                      @click="showPassword = !showPassword"
                    >
                      <EyeOff v-if="showPassword" class="w-4 h-4" :stroke-width="1.75" />
                      <Eye v-else class="w-4 h-4" :stroke-width="1.75" />
                    </button>
                  </div>
                </div>

                <label class="flex items-center gap-2 w-fit cursor-pointer text-sm text-slate-600 dark:text-slate-400">
                  <input
                    v-model="rememberMe"
                    type="checkbox"
                    class="w-4 h-4 rounded border-slate-300 dark:border-slate-600 accent-blue-600 cursor-pointer"
                  />
                  <span>Ingat saya di perangkat ini</span>
                </label>
              </div>

              <button type="submit" :class="[primaryButtonClass, 'mt-6']" :disabled="isLoading">
                <span v-if="isLoading" :class="spinnerClass"></span>
                <span v-else>Masuk</span>
              </button>

              <div class="my-4 flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500">
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                <span>atau</span>
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
              </div>

              <button type="button" :class="secondaryButtonClass" @click="loginTab = 'whatsapp'; errorMessage = ''">
                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.477 2 12C2 13.82 2.484 15.522 3.327 16.991L2 22L7.151 20.697C8.583 21.523 10.237 22 12 22C17.523 22 22 17.523 22 12C22 6.477 17.523 2 12 2Z" fill="#25D366" />
                  <path fill-rule="evenodd" clip-rule="evenodd" d="M17.472 14.382C17.171 14.232 15.692 13.504 15.416 13.404C15.14 13.304 14.94 13.254 14.739 13.554C14.539 13.854 13.962 14.532 13.787 14.732C13.612 14.932 13.436 14.957 13.135 14.807C12.835 14.657 11.868 14.34 10.721 13.317C9.575 12.295 8.801 11.033 8.575 10.647C8.35 10.261 8.551 10.052 8.701 9.902C8.836 9.767 9.002 9.551 9.152 9.376C9.302 9.201 9.352 9.076 9.453 8.876C9.553 8.676 9.503 8.5 9.428 8.35C9.353 8.2 8.751 6.718 8.501 6.115C8.257 5.527 8.009 5.607 7.824 5.598C7.649 5.59 7.448 5.588 7.247 5.588C7.047 5.588 6.721 5.663 6.445 5.964C6.169 6.264 5.392 6.992 5.392 8.472C5.392 9.952 6.47 11.382 6.621 11.583C6.772 11.783 8.743 14.823 11.762 16.128C12.48 16.438 13.041 16.624 13.478 16.763C14.2 16.993 14.856 16.96 15.375 16.883C15.953 16.796 17.155 16.156 17.406 15.454C17.657 14.752 17.657 14.151 17.582 14.025C17.506 13.899 17.306 13.824 17.005 13.674" fill="white" />
                </svg>
                <span>Masuk dengan WhatsApp</span>
              </button>
            </form>

            <!-- WhatsApp OTP -->
            <form v-else class="mt-6" @submit.prevent="handleWhatsAppSubmit">
              <div>
                <label for="login-identifier" :class="labelClass">Nomor WhatsApp atau email</label>
                <input
                  id="login-identifier"
                  v-model="identifier"
                  type="text"
                  inputmode="email"
                  autocomplete="username"
                  placeholder="0812 3456 7890"
                  required
                  :class="[inputClass, 'tabular-nums']"
                />
                <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">Kode 6 digit dikirim lewat WhatsApp, berlaku 5 menit</p>
              </div>

              <button type="submit" :class="[primaryButtonClass, 'mt-6']" :disabled="isLoading">
                <span v-if="isLoading" :class="spinnerClass"></span>
                <span v-else>Kirim kode OTP</span>
              </button>

              <div class="my-4 flex items-center gap-3 text-xs text-slate-400 dark:text-slate-500">
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
                <span>atau</span>
                <div class="h-px flex-1 bg-slate-200 dark:bg-slate-800"></div>
              </div>

              <button type="button" :class="secondaryButtonClass" @click="loginTab = 'password'; errorMessage = ''">
                <Mail class="w-4 h-4 text-slate-500 dark:text-slate-400" :stroke-width="1.75" />
                <span>Masuk dengan email</span>
              </button>
            </form>
          </div>

          <div :class="footerClass">
            <span>Belum punya akun?</span>
            <button type="button" :class="linkClass" @click="goToRegister">Daftar</button>
          </div>
        </template>

        <!-- ============================================== -->
        <!-- VERIFIKASI OTP                                 -->
        <!-- ============================================== -->
        <template v-else-if="!isRegisterMode && isOtpStep">
          <div class="p-6 sm:p-8">
            <h1 class="text-xl font-medium leading-7">Masukkan kode OTP</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Kode 6 digit dikirim ke <span class="text-slate-900 dark:text-slate-100 tabular-nums">{{ maskedPhone }}</span>
            </p>

            <div v-if="errorMessage" role="alert" :class="['mt-6 rounded-lg border px-4 py-3 text-sm flex items-start gap-3', alertClass]">
              <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" :stroke-width="1.75" />
              <span class="leading-5">{{ errorMessage }}</span>
            </div>

            <form class="mt-6" @submit.prevent="handleOtpVerify">
              <label class="sr-only">Kode verifikasi</label>
              <div class="flex justify-center">
                <InputOTP v-model="otpInput" :maxlength="6" @complete="handleOtpVerify">
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

              <button type="submit" :class="[primaryButtonClass, 'mt-6']" :disabled="isLoading || otpInput.length < 6">
                <span v-if="isLoading" :class="spinnerClass"></span>
                <span v-else>Verifikasi dan masuk</span>
              </button>
            </form>
          </div>

          <div class="flex items-center justify-between px-6 sm:px-8 py-4 border-t border-slate-200 dark:border-slate-800 text-sm">
            <button
              type="button"
              class="flex items-center gap-2 text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100 transition-colors cursor-pointer"
              @click="backToLoginForm"
            >
              <ArrowLeft class="w-4 h-4" :stroke-width="1.75" />
              <span>Ganti akun</span>
            </button>
            <button
              type="button"
              :class="[linkClass, 'tabular-nums disabled:text-slate-400 dark:disabled:text-slate-500 disabled:no-underline disabled:cursor-default']"
              :disabled="resendCooldown > 0"
              @click="handleResendOtp"
            >
              {{ resendCooldown > 0 ? `Kirim ulang dalam ${resendCooldown} dtk` : 'Kirim ulang kode' }}
            </button>
          </div>
        </template>

        <!-- ============================================== -->
        <!-- PENDAFTARAN                                    -->
        <!-- ============================================== -->
        <template v-else-if="isRegisterMode">
          <!-- Pendaftaran terkirim -->
          <div v-if="registerSuccess" class="p-6 sm:p-8">
            <div class="h-10 w-10 rounded-full bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
              <Check class="w-5 h-5" :stroke-width="2" />
            </div>
            <h1 class="mt-4 text-xl font-medium leading-7">Pendaftaran terkirim</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
              Akun Anda aktif setelah disetujui admin SCM. Pemberitahuan dikirim lewat WhatsApp.
            </p>

            <dl class="mt-6 rounded-lg border border-slate-200 dark:border-slate-800 divide-y divide-slate-200 dark:divide-slate-800 text-sm">
              <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Nama</dt>
                <dd class="text-right">{{ registeredData.name }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Email</dt>
                <dd class="text-right truncate">{{ registeredData.email }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Role</dt>
                <dd class="text-right">{{ registeredData.role }}</dd>
              </div>
              <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Status</dt>
                <dd class="flex items-center gap-2 text-amber-700 dark:text-amber-400">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                  <span>Menunggu persetujuan</span>
                </dd>
              </div>
            </dl>

            <button type="button" :class="[secondaryButtonClass, 'mt-6']" @click="backToLoginForm">
              Kembali ke halaman masuk
            </button>
          </div>

          <template v-else>
            <div class="p-6 sm:p-8">
              <h1 class="text-xl font-medium leading-7">Daftar akun</h1>
              <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Akun baru aktif setelah disetujui admin SCM
              </p>

              <div v-if="errorMessage" role="alert" :class="['mt-6 rounded-lg border px-4 py-3 text-sm flex items-start gap-3', alertClass]">
                <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" :stroke-width="1.75" />
                <span class="leading-5">{{ errorMessage }}</span>
              </div>

              <form class="mt-6" @submit.prevent="handleRegisterSubmit">
                <div class="space-y-4">
                  <div>
                    <label for="reg-name" :class="labelClass">Nama lengkap</label>
                    <input id="reg-name" v-model="regForm.name" type="text" autocomplete="name" placeholder="Nama sesuai identitas kantor" required :class="inputClass" />
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label for="reg-phone" :class="labelClass">Nomor WhatsApp</label>
                      <input id="reg-phone" v-model="regForm.phone" type="tel" autocomplete="tel" placeholder="0812 3456 7890" required :class="[inputClass, 'tabular-nums']" />
                    </div>
                    <div>
                      <label for="reg-email" :class="labelClass">Email</label>
                      <input id="reg-email" v-model="regForm.email" type="email" autocomplete="email" placeholder="nama@perusahaan.com" required :class="inputClass" />
                    </div>
                  </div>

                  <div>
                    <label for="reg-role" :class="labelClass">Role yang diajukan</label>
                    <select id="reg-role" v-model="regForm.role" required :class="[inputClass, 'cursor-pointer']">
                      <option value="Staff Gudang">Staff Gudang</option>
                      <option value="Staff Finance">Staff Finance</option>
                      <option value="Staff SCM">Staff SCM</option>
                    </select>
                  </div>

                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                      <label for="reg-password" :class="labelClass">Kata sandi</label>
                      <input id="reg-password" v-model="regForm.password" type="password" autocomplete="new-password" placeholder="Minimal 6 karakter" required minlength="6" :class="inputClass" />
                    </div>
                    <div>
                      <label for="reg-password2" :class="labelClass">Ulangi kata sandi</label>
                      <input id="reg-password2" v-model="regForm.passwordConfirmation" type="password" autocomplete="new-password" required minlength="6" :class="inputClass" />
                    </div>
                  </div>
                </div>

                <button type="submit" :class="[primaryButtonClass, 'mt-6']" :disabled="isLoading">
                  <span v-if="isLoading" :class="spinnerClass"></span>
                  <span v-else>Kirim pendaftaran</span>
                </button>
              </form>
            </div>

            <div :class="footerClass">
              <span>Sudah punya akun?</span>
              <button type="button" :class="linkClass" @click="backToLoginForm">Masuk</button>
            </div>
          </template>
        </template>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useDark, useToggle } from '@vueuse/core';
import {
  Check,
  AlertCircle,
  Eye,
  EyeOff,
  ArrowLeft,
  ArrowRight,
  Sun,
  Moon,
  Mail,
} from 'lucide-vue-next';
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

// Gaya kontrol bersama untuk semua langkah (masuk, OTP, daftar)
const labelClass = 'block mb-2 text-sm font-medium text-slate-700 dark:text-slate-300';
const inputClass = 'w-full h-10 px-3 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-500 outline-none focus:border-blue-500 dark:focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20 transition-colors';
const buttonBase = 'w-full h-10 px-4 rounded-lg text-sm font-medium flex items-center justify-center gap-2 cursor-pointer transition-[background-color,border-color,color,transform] duration-150 active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-500';
// Aksi utama: isi warna teks utama (gelap di tema terang, putih tulang di tema gelap)
const primaryButtonClass = `${buttonBase} bg-slate-900 text-white hover:bg-slate-800 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white`;
const secondaryButtonClass = `${buttonBase} border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700`;
const spinnerClass = 'w-4 h-4 rounded-full border-2 border-current/30 border-t-current animate-spin';
const linkClass = 'font-medium text-blue-600 dark:text-blue-400 hover:underline underline-offset-4 cursor-pointer';
const footerClass = 'flex items-center justify-center gap-1 px-6 sm:px-8 py-4 border-t border-slate-200 dark:border-slate-800 text-sm text-slate-500 dark:text-slate-400';

const alertClass = computed(() => {
  if (isPendingAlert.value) {
    return 'bg-amber-50 border-amber-200 text-amber-900 dark:bg-amber-950/30 dark:border-amber-900/50 dark:text-amber-300';
  }
  if (isNotRegisteredAlert.value) {
    return 'bg-blue-50 border-blue-200 text-blue-900 dark:bg-blue-950/30 dark:border-blue-900/50 dark:text-blue-300';
  }
  return 'bg-rose-50 border-rose-200 text-rose-800 dark:bg-rose-950/30 dark:border-rose-900/50 dark:text-rose-300';
});

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

    // Login dengan Email & Password langsung masuk tanpa OTP!
    store.loginDirect(credCheck.user);
    isLoading.value = false;
    router.push('/dashboard');
    return;
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
