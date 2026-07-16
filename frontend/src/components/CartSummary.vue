<script setup>
import { ref } from 'vue'
import { formatCurrency } from '../utils/formatCurrency'

defineProps({
  originalPrice: { type: Number, required: true },
  discount: { type: Number, required: true },
  total: { type: Number, required: true },
})

const coupon = ref('')

defineEmits(['checkout'])
</script>

<template>
  <aside class="w-full min-w-0 space-y-md">
    <div class="glass-card w-full min-w-0 p-md rounded-lg shadow-xl">
      <h2 class="font-display text-headline-sm mb-lg">Order Summary</h2>
      <div class="w-full space-y-sm mb-lg">
        <div class="flex w-full justify-between gap-md text-body-md text-on-surface-variant">
          <span>Original Price</span><span class="shrink-0 font-mono">{{ formatCurrency(originalPrice) }}</span>
        </div>
        <div class="flex w-full justify-between gap-md text-body-md text-primary">
          <span>Discounts</span><span class="shrink-0 font-mono">-{{ formatCurrency(discount) }}</span>
        </div>
        <div class="h-px bg-outline-variant my-md"></div>
        <div class="flex w-full min-w-0 justify-between items-center gap-md">
          <span class="font-display text-headline-sm">Total</span><span class="shrink-0 font-mono text-headline-md text-on-surface">{{ formatCurrency(total) }}</span>
        </div>
      </div>
      <div class="w-full mb-lg">
        <label class="block text-[11px] font-mono uppercase tracking-widest text-on-surface-variant mb-2">Apply Coupon</label>
        <div class="flex w-full min-w-0 gap-2">
          <input v-model="coupon" class="min-w-0 flex-1 bg-background border border-outline-variant rounded-lg px-md py-2 text-body-sm focus:outline-none focus:border-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background text-on-surface" placeholder="Enter code" type="text" />
          <button class="shrink-0 cursor-pointer px-md py-2 border border-outline-variant rounded-lg text-body-sm font-semibold hover:bg-surface-variant transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background" type="button">Apply</button>
        </div>
      </div>
      <button class="w-full cursor-pointer bg-primary text-on-primary py-4 rounded-lg font-display text-headline-sm hover:opacity-90 active:scale-95 transition-all shadow-lg shadow-primary/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background" type="button" @click="$emit('checkout')">Proceed to Checkout</button>
      <div class="mt-md flex w-full min-w-0 items-center justify-center gap-xs text-xs text-on-surface-variant">
        <span class="material-symbols-outlined text-[16px]">lock</span><span>Secure Checkout Powered by Stripe</span>
      </div>
    </div>
    <div class="w-full min-w-0 p-md border border-outline-variant rounded-lg bg-surface-container-high/30">
      <h4 class="font-display text-on-surface mb-2">Need Help?</h4>
      <p class="w-full text-body-sm text-on-surface-variant">Contact our developer support team at <a class="text-primary hover:underline" href="mailto:support@edumarket.io">support@edumarket.io</a> or check our FAQ.</p>
    </div>
  </aside>
</template>
