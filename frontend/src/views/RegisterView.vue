<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ name: '', email: '', password: '', passwordConfirmation: '' })
const errors = reactive({ name: '', email: '', password: '', passwordConfirmation: '' })
const apiError = ref('')
const isLoading = ref(false)

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const validate = () => {
  errors.name = form.name.trim().length >= 2 ? '' : 'Enter your full name.'
  errors.email = emailPattern.test(form.email) ? '' : 'Enter a valid email address.'
  errors.password = form.password.length >= 8 ? '' : 'Password must contain at least 8 characters.'
  errors.passwordConfirmation = form.passwordConfirmation === form.password ? '' : 'Passwords do not match.'

  return !Object.values(errors).some(Boolean)
}

const submit = async () => {
  if (!validate()) return

  apiError.value = ''
  isLoading.value = true

  try {
    await authStore.register({
      name: form.name,
      email: form.email,
      password: form.password,
      password_confirmation: form.passwordConfirmation,
    })
    await router.push('/')
  } catch (error) {
    apiError.value = error.response?.data?.message ?? 'Unable to create your account. Please try again.'
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
          <p class="mb-sm font-mono text-label-mono uppercase tracking-widest text-primary">Join the community</p>
          <h1 class="w-full font-display text-headline-md font-semibold text-on-surface">Create your account</h1>
          <p class="mt-sm w-full text-body-md text-on-surface-variant">Set up your developer profile and start learning today.</p>
        </div>

        <form class="w-full space-y-md" novalidate @submit.prevent="submit">
          <div class="w-full">
            <label for="register-name" class="mb-2 block text-body-sm font-medium text-on-surface">Full name</label>
            <input id="register-name" v-model.trim="form.name" class="w-full rounded-lg border border-outline-variant bg-background px-md py-3 text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant/60 focus:border-primary" type="text" autocomplete="name" placeholder="Alex Morgan" @blur="validate" />
            <p v-if="errors.name" class="mt-2 w-full text-body-sm text-error">{{ errors.name }}</p>
          </div>

          <div class="w-full">
            <label for="register-email" class="mb-2 block text-body-sm font-medium text-on-surface">Email address</label>
            <input id="register-email" v-model.trim="form.email" class="w-full rounded-lg border border-outline-variant bg-background px-md py-3 text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant/60 focus:border-primary" type="email" autocomplete="email" placeholder="developer@example.com" @blur="validate" />
            <p v-if="errors.email" class="mt-2 w-full text-body-sm text-error">{{ errors.email }}</p>
          </div>

          <div class="w-full">
            <label for="register-password" class="mb-2 block text-body-sm font-medium text-on-surface">Password</label>
            <input id="register-password" v-model="form.password" class="w-full rounded-lg border border-outline-variant bg-background px-md py-3 text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant/60 focus:border-primary" type="password" autocomplete="new-password" placeholder="Minimum 8 characters" @blur="validate" />
            <p v-if="errors.password" class="mt-2 w-full text-body-sm text-error">{{ errors.password }}</p>
          </div>

          <div class="w-full">
            <label for="register-confirmation" class="mb-2 block text-body-sm font-medium text-on-surface">Confirm password</label>
            <input id="register-confirmation" v-model="form.passwordConfirmation" class="w-full rounded-lg border border-outline-variant bg-background px-md py-3 text-body-md text-on-surface outline-none transition-colors placeholder:text-on-surface-variant/60 focus:border-primary" type="password" autocomplete="new-password" placeholder="Repeat your password" @blur="validate" />
            <p v-if="errors.passwordConfirmation" class="mt-2 w-full text-body-sm text-error">{{ errors.passwordConfirmation }}</p>
          </div>

          <p v-if="apiError" class="w-full rounded-lg border border-error/40 bg-error/10 px-md py-3 text-body-sm text-error">
            {{ apiError }}
          </p>

          <button
            class="w-full rounded-lg bg-primary-container py-3 font-display text-button-text text-on-primary-container shadow-lg shadow-primary-container/20 transition-all hover:opacity-90 active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
            type="submit"
            :disabled="isLoading"
          >
            {{ isLoading ? 'Creating account...' : 'Create account' }}
          </button>
        </form>

        <p class="mt-lg w-full text-center text-body-md text-on-surface-variant">
          Already have an account?
          <RouterLink to="/login" class="font-medium text-primary hover:underline">Log in</RouterLink>
        </p>
      </div>
    </section>
  </main>
</template>
