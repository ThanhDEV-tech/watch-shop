<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })
const apiError = ref('')
const isLoading = ref(false)

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const validate = () => {
  errors.email = emailPattern.test(form.email) ? '' : 'Enter a valid email address.'
  errors.password = form.password.length >= 8 ? '' : 'Password must contain at least 8 characters.'

  return !errors.email && !errors.password
}

const submit = async () => {
  if (!validate()) return

  apiError.value = ''
  isLoading.value = true

  try {
    await authStore.login({ ...form })
    await router.push('/')
  } catch (error) {
    apiError.value = error.response?.data?.message ?? 'Unable to log in. Please try again.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <main class="flex w-full min-w-0 items-center justify-center px-margin-mobile py-lg md:px-gutter min-h-[calc(100vh-5rem)]">
    <section class="flex w-full min-w-0 items-center justify-center">
      <div class="glass-card w-full min-w-0 max-w-[32rem] rounded-xl p-md md:p-lg">
        <div class="mb-lg w-full">
          <p class="mb-sm font-mono text-label-mono uppercase tracking-widest text-primary">Account access</p>
          <h1 class="w-full font-display text-headline-md font-semibold text-on-surface">Log in to EduMarket</h1>
          <p class="mt-sm w-full text-body-md text-on-surface-variant">Pick up where you left off and keep shipping.</p>
        </div>

        <form class="w-full space-y-md" novalidate @submit.prevent="submit">
          <div class="w-full">
            <label for="login-email" class="mb-2 block font-body text-body-sm font-medium text-on-surface">Email address</label>
            <input
              id="login-email"
              v-model.trim="form.email"
              class="w-full rounded-lg border border-outline-variant bg-background px-md py-3 text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant/60 focus:border-primary"
              type="email"
              autocomplete="email"
              placeholder="developer@example.com"
              @blur="validate"
            />
            <p v-if="errors.email" class="mt-2 w-full text-body-sm text-error">{{ errors.email }}</p>
          </div>

          <div class="w-full">
            <div class="mb-2 flex w-full items-center justify-between gap-sm">
              <label for="login-password" class="font-body text-body-sm font-medium text-on-surface">Password</label>
              <RouterLink to="/forgot-password" class="shrink-0 rounded text-body-sm text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background">Forgot password?</RouterLink>
            </div>
            <input
              id="login-password"
              v-model="form.password"
              class="w-full rounded-lg border border-outline-variant bg-background px-md py-3 text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant/60 focus:border-primary"
              type="password"
              autocomplete="current-password"
              placeholder="Minimum 8 characters"
              @blur="validate"
            />
            <p v-if="errors.password" class="mt-2 w-full text-body-sm text-error">{{ errors.password }}</p>
          </div>

          <p v-if="apiError" class="w-full rounded-lg border border-error/40 bg-error/10 px-md py-3 text-body-sm text-error">
            {{ apiError }}
          </p>

          <button
            class="w-full rounded-lg bg-primary-container py-3 font-display text-button-text text-on-primary-container shadow-lg shadow-primary-container/20 transition-all hover:opacity-90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
            type="submit"
            :disabled="isLoading"
          >
            {{ isLoading ? 'Logging in...' : 'Log in' }}
          </button>
        </form>

        <p class="mt-lg w-full text-center text-body-md text-on-surface-variant">
          New to EduMarket?
          <RouterLink to="/register" class="font-medium text-primary hover:underline">Create an account</RouterLink>
        </p>
      </div>
    </section>
  </main>
</template>
