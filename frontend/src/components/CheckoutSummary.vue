<script setup>
import { formatCurrency } from '../utils/formatCurrency'

defineProps({
  originalPrice: { type: Number, required: true },
  discount: { type: Number, required: true },
  total: { type: Number, required: true },
  loading: { type: Boolean, default: false },
})

defineEmits(['pay'])
</script>

<template>
  <aside class="w-full min-w-0 space-y-md">
    <div class="glass-card w-full min-w-0 rounded-lg p-md shadow-xl">
      <h2 class="font-display text-headline-sm text-on-surface mb-lg">Order Summary</h2>

      <div class="w-full space-y-sm mb-lg">
        <div class="flex w-full justify-between gap-md text-body-md text-on-surface-variant">
          <span>Original Price</span>
          <span class="shrink-0 font-mono">{{ formatCurrency(originalPrice) }}</span>
        </div>

        <div v-if="discount > 0" class="flex w-full justify-between gap-md text-body-md text-primary">
          <span>Discounts</span>
          <span class="shrink-0 font-mono">-{{ formatCurrency(discount) }}</span>
        </div>

        <div class="my-md h-px bg-outline-variant"></div>

        <div class="flex w-full min-w-0 items-center justify-between gap-md">
          <span class="font-display text-headline-sm text-on-surface">Total Payment</span>
          <span class="shrink-0 font-mono text-headline-md text-on-surface">{{ formatCurrency(total) }}</span>
        </div>
      </div>

      <button class="w-full cursor-pointer rounded-lg bg-primary-container py-4 font-display text-headline-sm text-on-primary-container shadow-lg shadow-primary-container/20 transition-all hover:opacity-90 active:scale-[0.99] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="loading" @click="$emit('pay')">{{ loading ? 'Processing...' : 'Proceed to Payment' }}</button>

      <div class="mt-md flex w-full min-w-0 items-center justify-center gap-xs text-xs text-on-surface-variant">
        <span class="material-symbols-outlined text-[16px]">lock</span>
        <span>Secure payment checkout</span>
      </div>
    </div>

    <div class="w-full min-w-0 rounded-lg border border-outline-variant bg-surface-container-high/30 p-md">
      <div class="flex w-full items-start gap-sm">
        <span class="material-symbols-outlined shrink-0 text-primary">verified_user</span>
        <p class="w-full text-body-sm text-on-surface-variant">Your order details are encrypted and will only be used to process this purchase.</p>
      </div>
    </div>
  </aside>
</template>
