<script setup>
import { reactive, ref } from 'vue'
import { forgotPassword } from '../services/api'

const form = reactive({ email: '' })
const errors = reactive({ email: '' })
const apiError = ref('')
const successMessage = ref('')
const isLoading = ref(false)

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const validate = () => {
  errors.email = emailPattern.test(form.email) ? '' : 'Enter a valid email address.'

  return !errors.email
}

const submit = async () => {
  if (!validate()) return

  apiError.value = ''
  successMessage.value = ''
  isLoading.value = true

  try {
    const response = await forgotPassword({ email: form.email })
    successMessage.value = response.data.message
  } catch (error) {
    apiError.value = error.response?.data?.message ?? 'Unable to send the reset link. Please try again.'
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
          <h1 class="w-full font-display text-headline-md font-semibold text-on-surface">Reset your password</h1>
          <p class="mt-sm w-full text-body-md text-on-surface-variant">Enter your email and we will send password reset instructions.</p>
        </div>

        <form class="w-full space-y-md" novalidate @submit.prevent="submit">
          <div class="w-full">
            <label for="forgot-email" class="mb-2 block font-body text-body-sm font-medium text-on-surface">Email address</label>
            <input
              id="forgot-email"
              v-model.trim="form.email"
              class="w-full rounded-lg border border-outline-variant bg-background px-md py-3 text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant/60 focus:border-primary"
              type="email"
              autocomplete="email"
              placeholder="developer@example.com"
              @blur="validate"
            />
            <p v-if="errors.email" class="mt-2 w-full text-body-sm text-error">{{ errors.email }}</p>
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
            :disabled="isLoading"
          >
            {{ isLoading ? 'Sending...' : 'Send reset link' }}
          </button>
        </form>

        <p class="mt-lg w-full text-center text-body-md text-on-surface-variant">
          Remembered your password?
          <RouterLink to="/login" class="font-medium text-primary hover:underline">Log in</RouterLink>
        </p>
      </div>
    </section>
  </main>
</template>
