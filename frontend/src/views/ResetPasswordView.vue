<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { resetPassword } from '../services/api'

const route = useRoute()
const router = useRouter()

const form = reactive({
  password: '',
  passwordConfirmation: '',
})
const errors = reactive({
  password: '',
  passwordConfirmation: '',
})
const apiError = ref('')
const successMessage = ref('')
const isLoading = ref(false)

const token = computed(() => String(route.query.token ?? ''))
const email = computed(() => String(route.query.email ?? ''))
const hasResetParams = computed(() => Boolean(token.value && email.value))

const validate = () => {
  errors.password = form.password.length >= 8 ? '' : 'Password must contain at least 8 characters.'
  errors.passwordConfirmation = form.passwordConfirmation === form.password ? '' : 'Passwords do not match.'

  return !Object.values(errors).some(Boolean)
}

const submit = async () => {
  if (!hasResetParams.value || !validate()) return

  apiError.value = ''
  successMessage.value = ''
  isLoading.value = true

  try {
    const response = await resetPassword({
      token: token.value,
      email: email.value,
      password: form.password,
      password_confirmation: form.passwordConfirmation,
    })
    successMessage.value = response.data.message
    window.setTimeout(() => {
      router.push('/login')
    }, 1200)
  } catch (error) {
    apiError.value = error.response?.data?.message ?? 'Unable to reset your password. Please request a new link.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <main class="flex min-h-[calc(100vh-5rem)] w-full min-w-0 items-center justify-center px-margin-mobile py-lg md:px-gutter">
    <section class="flex w-full min-w-0 items-center justify-center">
      <div class="glass-card w-full min-w-0 max-w-[32rem] rounded-xl p-md md:p-lg">
        <div class="mb-lg w-full">
          <p class="mb-sm font-mono text-label-mono uppercase tracking-widest text-primary">Account recovery</p>
          <h1 class="w-full font-display text-headline-md font-semibold text-on-surface">Choose a new password</h1>
          <p class="mt-sm w-full text-body-md text-on-surface-variant">Set a new password for your EduMarket account.</p>
        </div>

        <p v-if="!hasResetParams" class="mb-md w-full rounded-lg border border-error/40 bg-error/10 px-md py-3 text-body-sm text-error">
          This reset link is missing required information. Please request a new password reset link.
        </p>

        <form class="w-full space-y-md" novalidate @submit.prevent="submit">
          <div class="w-full">
            <label for="reset-password" class="mb-2 block font-body text-body-sm font-medium text-on-surface">New password</label>
            <input
              id="reset-password"
              v-model="form.password"
              class="w-full rounded-lg border border-outline-variant bg-background px-md py-3 text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant/60 focus:border-primary"
              type="password"
              autocomplete="new-password"
              placeholder="Minimum 8 characters"
              :disabled="!hasResetParams"
              @blur="validate"
            />
            <p v-if="errors.password" class="mt-2 w-full text-body-sm text-error">{{ errors.password }}</p>
          </div>

          <div class="w-full">
            <label for="reset-confirmation" class="mb-2 block font-body text-body-sm font-medium text-on-surface">Confirm password</label>
            <input
              id="reset-confirmation"
              v-model="form.passwordConfirmation"
              class="w-full rounded-lg border border-outline-variant bg-background px-md py-3 text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant/60 focus:border-primary"
              type="password"
              autocomplete="new-password"
              placeholder="Repeat your password"
              :disabled="!hasResetParams"
              @blur="validate"
            />
            <p v-if="errors.passwordConfirmation" class="mt-2 w-full text-body-sm text-error">{{ errors.passwordConfirmation }}</p>
          </div>

          <p v-if="successMessage" class="w-full rounded-lg border border-primary/40 bg-primary/10 px-md py-3 text-body-sm text-on-surface">
            {{ successMessage }}
          </p>

          <p v-if="apiError" class="w-full rounded-lg border border-error/40 bg-error/10 px-md py-3 text-body-sm text-error">
            {{ apiError }}
          </p>

          <button
            class="w-full rounded-lg bg-primary-container py-3 font-display text-button-text text-on-primary-container shadow-lg shadow-primary-container/20 transition-all hover:opacity-90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
            type="submit"
            :disabled="isLoading || !hasResetParams"
          >
            {{ isLoading ? 'Resetting...' : 'Reset password' }}
          </button>
        </form>

        <p class="mt-lg w-full text-center text-body-md text-on-surface-variant">
          Need another link?
          <RouterLink to="/forgot-password" class="font-medium text-primary hover:underline">Request reset link</RouterLink>
        </p>
      </div>
    </section>
  </main>
</template>
