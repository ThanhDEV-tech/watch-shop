<script setup>
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const avatarInput = ref(null)
const avatarPreview = ref('')
const profileForm = reactive({ name: '', phone: '', bio: '' })
const passwordForm = reactive({ current_password: '', new_password: '', new_password_confirmation: '' })
const savingProfile = ref(false)
const uploadingAvatar = ref(false)
const savingPassword = ref(false)
const profileError = ref('')
const profileSuccess = ref('')
const passwordError = ref('')
const passwordSuccess = ref('')

const displayedAvatar = computed(() => avatarPreview.value || authStore.user?.avatar_url || authStore.user?.avatar || '')
const userInitial = computed(() => authStore.user?.name?.charAt(0)?.toUpperCase() || 'U')

const apiError = (error, fallback) => {
  const errors = error.response?.data?.data?.errors
  return errors ? Object.values(errors).flat()[0] : (error.response?.data?.message ?? fallback)
}

watch(
  () => authStore.user,
  (user) => {
    profileForm.name = user?.name ?? ''
    profileForm.phone = user?.phone ?? ''
    profileForm.bio = user?.bio ?? ''
  },
  { immediate: true },
)

const saveProfile = async () => {
  savingProfile.value = true
  profileError.value = ''
  profileSuccess.value = ''
  try {
    const response = await authStore.updateProfile({
      name: profileForm.name.trim(),
      phone: profileForm.phone.trim() || null,
      bio: profileForm.bio.trim() || null,
    })
    profileSuccess.value = response.message
  } catch (error) {
    profileError.value = apiError(error, 'Không thể cập nhật thông tin cá nhân.')
  } finally {
    savingProfile.value = false
  }
}

const selectAvatar = async (event) => {
  const file = event.target.files?.[0]
  event.target.value = ''
  if (!file) return

  profileError.value = ''
  profileSuccess.value = ''
  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    profileError.value = 'Ảnh đại diện phải là file JPEG, PNG hoặc WebP.'
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    profileError.value = 'Ảnh đại diện không được vượt quá 2MB.'
    return
  }

  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
  avatarPreview.value = URL.createObjectURL(file)
  uploadingAvatar.value = true
  try {
    const response = await authStore.uploadAvatar(file)
    profileSuccess.value = response.message
    URL.revokeObjectURL(avatarPreview.value)
    avatarPreview.value = ''
  } catch (error) {
    profileError.value = apiError(error, 'Không thể cập nhật ảnh đại diện.')
  } finally {
    uploadingAvatar.value = false
  }
}

const savePassword = async () => {
  passwordError.value = ''
  passwordSuccess.value = ''
  if (passwordForm.new_password.length < 8) {
    passwordError.value = 'Mật khẩu mới phải có ít nhất 8 ký tự.'
    return
  }
  if (passwordForm.new_password !== passwordForm.new_password_confirmation) {
    passwordError.value = 'Xác nhận mật khẩu mới không khớp.'
    return
  }

  savingPassword.value = true
  try {
    const response = await authStore.changePassword({ ...passwordForm })
    passwordSuccess.value = response.message
    passwordForm.current_password = ''
    passwordForm.new_password = ''
    passwordForm.new_password_confirmation = ''
  } catch (error) {
    passwordError.value = apiError(error, 'Không thể đổi mật khẩu.')
  } finally {
    savingPassword.value = false
  }
}

onBeforeUnmount(() => {
  if (avatarPreview.value) URL.revokeObjectURL(avatarPreview.value)
})
</script>

<template>
  <main class="mx-auto min-h-[calc(100vh-5rem)] w-full max-w-4xl px-margin-mobile py-lg md:px-gutter">
    <div class="w-full min-w-0">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Account settings</p>
      <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">My Profile</h1>
      <p class="mt-xs w-full text-body-md text-on-surface-variant">Quản lý thông tin cá nhân và bảo mật tài khoản.</p>
    </div>

    <section class="mt-lg w-full min-w-0 rounded-xl border border-surface-variant bg-surface p-md md:p-lg">
      <div class="flex w-full min-w-0 flex-col items-center gap-md border-b border-surface-variant pb-lg sm:flex-row sm:items-center">
        <button class="group relative h-28 w-28 shrink-0 overflow-hidden rounded-full border-2 border-primary/50 bg-surface-container-highest" type="button" :disabled="uploadingAvatar" aria-label="Đổi ảnh đại diện" @click="avatarInput?.click()">
          <img v-if="displayedAvatar" :src="displayedAvatar" :alt="authStore.user?.name" class="h-full w-full object-cover" />
          <span v-else class="flex h-full w-full items-center justify-center font-display text-4xl font-bold text-primary">{{ userInitial }}</span>
          <span class="absolute inset-0 flex items-center justify-center bg-black/60 text-white opacity-0 transition-opacity group-hover:opacity-100"><span class="material-symbols-outlined">photo_camera</span></span>
        </button>
        <input ref="avatarInput" class="hidden" type="file" accept="image/jpeg,image/png,image/webp" @change="selectAvatar" />
        <div class="w-full min-w-0 text-center sm:text-left">
          <h2 class="w-full font-display text-headline-sm font-semibold text-on-surface">Ảnh đại diện</h2>
          <p class="mt-xs w-full text-body-sm text-on-surface-variant">JPEG, PNG hoặc WebP, tối đa 2MB. Nhấn vào ảnh để thay đổi.</p>
          <p v-if="uploadingAvatar" class="mt-xs w-full text-body-sm text-primary">Đang tải ảnh lên...</p>
        </div>
      </div>

      <form class="mt-lg w-full min-w-0 space-y-md" @submit.prevent="saveProfile">
        <div class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2">
          <div class="w-full min-w-0"><label class="block w-full text-body-sm font-medium text-on-surface" for="profile-name">Họ và tên</label><input id="profile-name" v-model="profileForm.name" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary" required maxlength="255" /></div>
          <div class="w-full min-w-0"><label class="block w-full text-body-sm font-medium text-on-surface" for="profile-phone">Số điện thoại</label><input id="profile-phone" v-model="profileForm.phone" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary" maxlength="30" placeholder="Chưa cập nhật" /></div>
        </div>
        <div class="w-full min-w-0"><label class="block w-full text-body-sm font-medium text-on-surface" for="profile-email">Email</label><input id="profile-email" :value="authStore.user?.email" class="mt-xs w-full min-w-0 cursor-not-allowed rounded-lg border border-surface-variant bg-surface-container-highest px-md py-3 text-on-surface-variant" disabled /><p class="mt-xs w-full text-xs text-on-surface-variant">Email không thể thay đổi tại trang này.</p></div>
        <p v-if="profileError" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ profileError }}</p>
        <p v-if="profileSuccess" class="w-full rounded-lg border border-[var(--accent-success)]/40 bg-[var(--accent-success)]/10 p-sm text-body-sm text-[var(--accent-success)]">{{ profileSuccess }}</p>
        <button class="w-full rounded-lg bg-primary px-md py-3 font-display text-button-text font-semibold text-white hover:bg-[var(--accent-primary-hover)] disabled:opacity-50 sm:w-auto" type="submit" :disabled="savingProfile || uploadingAvatar">{{ savingProfile ? 'Đang lưu...' : 'Lưu thông tin' }}</button>
      </form>
    </section>

    <section class="mt-md w-full min-w-0 rounded-xl border border-surface-variant bg-surface p-md md:p-lg">
      <div class="w-full min-w-0"><h2 class="w-full font-display text-headline-sm font-semibold text-on-surface">Đổi mật khẩu</h2><p class="mt-xs w-full text-body-sm text-on-surface-variant">Mật khẩu mới phải có ít nhất 8 ký tự.</p></div>
      <form class="mt-md w-full min-w-0 space-y-md" @submit.prevent="savePassword">
        <div class="w-full min-w-0"><label class="block w-full text-body-sm font-medium text-on-surface" for="current-password">Mật khẩu hiện tại</label><input id="current-password" v-model="passwordForm.current_password" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary" type="password" required autocomplete="current-password" /></div>
        <div class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2">
          <div class="w-full min-w-0"><label class="block w-full text-body-sm font-medium text-on-surface" for="new-password">Mật khẩu mới</label><input id="new-password" v-model="passwordForm.new_password" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary" type="password" required minlength="8" autocomplete="new-password" /></div>
          <div class="w-full min-w-0"><label class="block w-full text-body-sm font-medium text-on-surface" for="confirm-password">Xác nhận mật khẩu mới</label><input id="confirm-password" v-model="passwordForm.new_password_confirmation" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary" type="password" required minlength="8" autocomplete="new-password" /></div>
        </div>
        <p v-if="passwordError" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ passwordError }}</p>
        <p v-if="passwordSuccess" class="w-full rounded-lg border border-[var(--accent-success)]/40 bg-[var(--accent-success)]/10 p-sm text-body-sm text-[var(--accent-success)]">{{ passwordSuccess }}</p>
        <button class="w-full rounded-lg border border-primary bg-primary px-md py-3 font-display text-button-text font-semibold text-white hover:bg-[var(--accent-primary-hover)] disabled:opacity-50 sm:w-auto" type="submit" :disabled="savingPassword">{{ savingPassword ? 'Đang đổi...' : 'Đổi mật khẩu' }}</button>
      </form>
    </section>
  </main>
</template>
