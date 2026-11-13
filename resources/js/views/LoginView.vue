<template>
  <div class="relative flex min-h-svh items-center justify-center bg-page px-4 py-16">
    <!-- Tema terang / gelap -->
    <Button
      type="button"
      variant="ghost"
      size="icon-sm"
      class="absolute top-4 right-4"
      :aria-label="isDark ? 'Beralih ke mode terang' : 'Beralih ke mode gelap'"
      @click="toggleDark()"
    >
      <Sun v-if="isDark" />
      <Moon v-else />
    </Button>

    <Card :class="cn('w-full', view === 'register' ? 'max-w-lg' : 'max-w-md')">
      <CardHeader class="items-center text-center">
        <AppLogo class="mx-auto mb-2 size-12" />
        <h1 class="text-2xl font-semibold tracking-tight text-balance">{{ headerTitle }}</h1>
        <CardDescription>
          <template v-if="view === 'otp'">
            Kode verifikasi 6-digit telah dikirimkan ke
            <span class="font-medium text-foreground tabular-nums">{{ maskedPhone }}</span>.
          </template>
          <template v-else>{{ headerDescription }}</template>
        </CardDescription>
      </CardHeader>

      <!-- ============================================== -->
      <!-- VIEW 1: LOGIN (Email & kata sandi / WhatsApp)  -->
      <!-- ============================================== -->
      <template v-if="view === 'login'">
        <CardContent>
          <Tabs :model-value="loginTab" class="grid gap-4" @update:model-value="switchLoginTab">
            <TabsList class="grid w-full grid-cols-2">
              <TabsTrigger value="password">Email</TabsTrigger>
              <TabsTrigger value="whatsapp">WhatsApp</TabsTrigger>
            </TabsList>

            <!-- Error / menunggu persetujuan / nomor belum terdaftar -->
            <div v-if="errorMessage" role="alert" :class="cn('grid gap-3 rounded-lg p-3 text-sm', alertTone.container)">
              <div class="flex items-start gap-2">
                <AlertCircle :class="cn('mt-0.5 size-4 shrink-0', alertTone.icon)" />
                <div class="grid min-w-0 gap-1">
                  <p v-if="isPendingAlert" class="font-medium text-warning">Akun Menunggu Persetujuan</p>
                  <p v-else-if="isNotRegisteredAlert" class="font-medium">Nomor Belum Terdaftar</p>
                  <p>{{ errorMessage }}</p>
                </div>
              </div>

              <!-- Nomor belum terdaftar: langsung ke formulir pendaftaran -->
              <Button
                v-if="isNotRegisteredAlert"
                type="button"
                variant="outline"
                size="sm"
                class="w-full"
                @click="redirectToRegisterWithPhone"
              >
                Registrasi Akun Baru Sekarang
              </Button>
            </div>

            <TabsContent value="password" class="rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50">
              <form class="grid gap-4" @submit.prevent="handlePasswordSubmit">
                <FormField label="Email" for="login-email">
                  <Input
                    id="login-email"
                    v-model="email"
                    type="email"
                    placeholder="m@example.com"
                    autocomplete="username"
                    required
                  />
                </FormField>

                <FormField label="Password" for="login-password">
                  <div class="relative">
                    <Input
                      id="login-password"
                      v-model="password"
                      :type="showPassword ? 'text' : 'password'"
                      placeholder="••••••••"
                      autocomplete="current-password"
                      required
                      class="pr-10"
                    />
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon-sm"
                      class="absolute top-1/2 right-0.5 -translate-y-1/2 text-muted-foreground"
                      :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                      @click="showPassword = !showPassword"
                    >
                      <EyeOff v-if="showPassword" />
                      <Eye v-else />
                    </Button>
                  </div>
                </FormField>

                <div class="flex items-center gap-2">
                  <Checkbox id="login-remember" v-model="rememberMe" />
                  <Label for="login-remember" class="font-normal">Ingat saya di perangkat ini</Label>
                </div>

                <Button type="submit" class="w-full" :disabled="isLoading">
                  {{ isLoading ? 'Memproses…' : 'Login' }}
                </Button>
              </form>
            </TabsContent>

            <TabsContent value="whatsapp" class="rounded-md outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50">
              <form class="grid gap-4" @submit.prevent="handleWhatsAppSubmit">
                <FormField
                  label="Nomor WhatsApp / Email Terdaftar"
                  for="login-identifier"
                  helper="Kode verifikasi OTP 6-digit akan dikirimkan ke WhatsApp Anda."
                >
                  <Input
                    id="login-identifier"
                    v-model="identifier"
                    type="text"
                    placeholder="Contoh: 081224290502"
                    required
                    class="tabular-nums"
                  />
                </FormField>

                <Button type="submit" class="w-full" :disabled="isLoading">
                  {{ isLoading ? 'Mengirim…' : 'Kirim Kode OTP' }}
                </Button>
              </form>
            </TabsContent>
          </Tabs>
        </CardContent>

        <CardFooter class="justify-center gap-1 text-sm text-muted-foreground">
          <span>Belum punya akun?</span>
          <Button type="button" variant="link" class="h-auto p-0" @click="goToRegister">Registrasi sekarang</Button>
        </CardFooter>
      </template>

      <!-- ============================================== -->
      <!-- VIEW 2: VERIFIKASI OTP (shadcn input-otp)      -->
      <!-- ============================================== -->
      <template v-else-if="view === 'otp'">
        <CardContent class="grid gap-4">
          <div v-if="errorMessage" role="alert" class="flex items-start gap-2 rounded-lg bg-destructive/10 p-3 text-sm">
            <AlertCircle class="mt-0.5 size-4 shrink-0 text-destructive" />
            <p class="min-w-0">{{ errorMessage }}</p>
          </div>

          <form class="grid gap-4" @submit.prevent="handleOtpVerify">
            <div class="grid justify-items-center gap-2">
              <Label>Kode Verifikasi (6 Angka)</Label>
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

            <Button type="submit" class="w-full" :disabled="isLoading || otpInput.length < 6">
              {{ isLoading ? 'Memverifikasi…' : 'Verifikasi & Masuk' }}
            </Button>
          </form>
        </CardContent>

        <CardFooter class="justify-between gap-2">
          <Button
            type="button"
            variant="link"
            class="h-auto p-0 text-muted-foreground hover:text-foreground"
            @click="backToLoginForm"
          >
            Ganti Akun
          </Button>
          <Button
            type="button"
            variant="link"
            class="h-auto p-0 tabular-nums"
            :disabled="resendCooldown > 0"
            @click="handleResendOtp"
          >
            {{ resendCooldown > 0 ? `Kirim Ulang (${resendCooldown}s)` : 'Kirim Ulang OTP' }}
          </Button>
        </CardFooter>
      </template>

      <!-- ============================================== -->
      <!-- VIEW 3: FORMULIR PENDAFTARAN                   -->
      <!-- ============================================== -->
      <template v-else>
        <CardContent>
          <!-- Pendaftaran berhasil -->
          <div v-if="registerSuccess" class="grid gap-6">
            <div class="grid justify-items-center gap-2 text-center">
              <div class="flex size-10 items-center justify-center rounded-full bg-success/10 text-success">
                <Check class="size-5" />
              </div>
              <h2 class="text-base font-semibold">Pendaftaran Berhasil Diajukan</h2>
              <p class="max-w-sm text-sm text-muted-foreground">
                Pengajuan akun Anda telah tersimpan dan menunggu persetujuan Administrator SCM.
              </p>
            </div>

            <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 rounded-lg border p-4 text-sm">
              <dt class="text-muted-foreground">Nama</dt>
              <dd class="truncate text-right font-medium">{{ registeredData.name }}</dd>
              <dt class="text-muted-foreground">Email</dt>
              <dd class="truncate text-right">{{ registeredData.email }}</dd>
              <dt class="text-muted-foreground">Role Diajukan</dt>
              <dd class="truncate text-right">{{ registeredData.role }}</dd>
              <dt class="self-center text-muted-foreground">Status</dt>
              <dd class="flex justify-end">
                <Badge variant="warning">Menunggu ACC Admin</Badge>
              </dd>
            </dl>

            <Button type="button" variant="outline" class="w-full" @click="backToLoginForm">
              Kembali ke Halaman Login
            </Button>
          </div>

          <!-- Isian pendaftaran -->
          <form v-else class="grid gap-4 md:grid-cols-2" @submit.prevent="handleRegisterSubmit">
            <div
              v-if="errorMessage"
              role="alert"
              class="flex items-start gap-2 rounded-lg bg-destructive/10 p-3 text-sm md:col-span-2"
            >
              <AlertCircle class="mt-0.5 size-4 shrink-0 text-destructive" />
              <p class="min-w-0">{{ errorMessage }}</p>
            </div>

            <FormField label="Nama Lengkap" for="register-name" required class="md:col-span-2">
              <Input
                id="register-name"
                v-model="regForm.name"
                type="text"
                placeholder="Nama sesuai identitas kantor"
                autocomplete="name"
                required
              />
            </FormField>

            <FormField label="Nomor WhatsApp" for="register-phone" required>
              <Input
                id="register-phone"
                v-model="regForm.phone"
                type="text"
                inputmode="tel"
                placeholder="Contoh: 081234567890"
                autocomplete="tel"
                required
                class="tabular-nums"
              />
            </FormField>

            <FormField label="Alamat Email" for="register-email" required>
              <Input
                id="register-email"
                v-model="regForm.email"
                type="email"
                placeholder="nama@perusahaan.com"
                autocomplete="email"
                required
              />
            </FormField>

            <FormField label="Pengajuan Jabatan / Role" for="register-role" required class="md:col-span-2">
              <NativeSelect id="register-role" v-model="regForm.role" required>
                <option value="Staff Gudang">Staff Gudang</option>
                <option value="Staff Finance">Staff Finance</option>
                <option value="Staff SCM">Staff SCM</option>
              </NativeSelect>
            </FormField>

            <FormField label="Kata Sandi" for="register-password" required>
              <Input
                id="register-password"
                v-model="regForm.password"
                type="password"
                placeholder="Minimal 6 karakter"
                autocomplete="new-password"
                required
                minlength="6"
              />
            </FormField>

            <FormField label="Konfirmasi Kata Sandi" for="register-password-confirmation" required>
              <Input
                id="register-password-confirmation"
                v-model="regForm.passwordConfirmation"
                type="password"
                placeholder="Ketik ulang kata sandi"
                autocomplete="new-password"
                required
                minlength="6"
              />
            </FormField>

            <p class="text-xs text-muted-foreground md:col-span-2">
              Akun baru akan diverifikasi oleh Administrator sebelum dapat login ke sistem.
            </p>

            <Button type="submit" class="w-full md:col-span-2" :disabled="isLoading">
              {{ isLoading ? 'Mendaftarkan…' : 'Daftar Sekarang' }}
            </Button>
          </form>
        </CardContent>

        <CardFooter v-if="!registerSuccess" class="justify-center gap-1 text-sm text-muted-foreground">
          <span>Sudah memiliki akun?</span>
          <Button type="button" variant="link" class="h-auto p-0" @click="backToLoginForm">Masuk sekarang</Button>
        </CardFooter>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import {
  AlertCircle,
  Check,
  Eye,
  EyeOff,
  Moon,
  Sun,
} from 'lucide-vue-next';
import {
  Card,
  CardHeader,
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
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { FormField } from '@/components/ui/form-field';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { NativeSelect } from '@/components/ui/native-select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';
import { isDark, toggleDark } from '@/lib/theme';
import AppLogo from '@/components/layout/AppLogo.vue';
import { useTaxStore } from '../store/taxStore';

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

// --- Tampilan kartu ---
// Tampilan aktif, dengan kondisi yang sama seperti sebelumnya: pendaftaran > OTP > login
const view = computed(() => (isRegisterMode.value ? 'register' : isOtpStep.value ? 'otp' : 'login'));

const headerTitle = computed(() => {
  if (view.value === 'register') return 'Formulir Pendaftaran';
  if (view.value === 'otp') return 'Masukkan Kode OTP';
  return 'Supply Chain Management';
});

const headerDescription = computed(() => {
  if (view.value === 'register') return 'Lengkapi data Anda untuk pengajuan akun baru.';
  return loginTab.value === 'whatsapp'
    ? 'Masukkan nomor WhatsApp terdaftar untuk verifikasi OTP.'
    : 'Masukkan email dan kata sandi untuk masuk ke sistem.';
});

// Warna pesan: menunggu persetujuan (warning), nomor belum terdaftar (netral), selain itu error
const alertTone = computed(() => {
  if (isPendingAlert.value) return { container: 'bg-warning/10', icon: 'text-warning' };
  if (isNotRegisteredAlert.value) return { container: 'border bg-muted/50', icon: 'text-muted-foreground' };
  return { container: 'bg-destructive/10', icon: 'text-destructive' };
});

// Pindah metode login (sama seperti tombol "WhatsApp" / "Email & Kata Sandi" sebelumnya)
function switchLoginTab(tab) {
  loginTab.value = tab;
  errorMessage.value = '';
}

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
