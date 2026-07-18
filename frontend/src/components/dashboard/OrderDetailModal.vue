<script setup>
import { formatCurrency } from '../../utils/formatCurrency'
import { formatDateTime } from '../../utils/formatDate'
import BaseModal from '../ui/BaseModal.vue'

defineProps({
  order: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  orderCode: { type: String, default: '' },
  markingPaid: { type: Boolean, default: false },
  refunding: { type: Boolean, default: false },
  actionError: { type: String, default: '' },
  showAdminActions: { type: Boolean, default: true },
})

const emit = defineEmits(['close', 'mark-paid', 'mark-refunded'])

const confirmMarkPaid = () => {
  if (!window.confirm('Chỉ dùng khi chắc chắn khách đã thanh toán thành công qua kênh khác. Thao tác này sẽ xử lý tồn kho.')) return

  emit('mark-paid')
}

const statusClasses = {
  pending: 'bg-tertiary/10 text-tertiary',
  paid: 'bg-[var(--accent-success)]/10 text-[var(--accent-success)]',
  paid_stock_issue: 'bg-error/10 text-error ring-1 ring-error/30',
  failed: 'bg-error/10 text-error',
  cancelled: 'bg-surface-container-highest text-on-surface-variant',
  shipping: 'bg-primary/10 text-primary',
  completed: 'bg-[var(--accent-success)]/10 text-[var(--accent-success)]',
  refunded: 'bg-surface-container-highest text-on-surface-variant',
}
</script>

<template>
  <BaseModal max-width="lg" aria-labelledby="order-detail-title" @close="emit('close')">
    <section class="flex max-h-[90vh] w-full min-w-0 flex-col overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl">
      <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface px-md py-sm">
        <div class="w-full min-w-0">
          <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Order detail</p>
          <h2 id="order-detail-title" class="w-full truncate font-display text-headline-sm font-semibold text-on-surface">
            {{ order?.code ?? orderCode ?? 'Chi tiết đơn hàng' }}
          </h2>
        </div>
        <button class="material-symbols-outlined h-10 w-10 shrink-0 rounded-lg text-on-surface-variant hover:bg-surface-container-highest hover:text-on-surface" type="button" aria-label="Đóng modal" @click="emit('close')">close</button>
      </header>

      <div v-if="loading" class="flex min-h-[320px] w-full min-w-0 items-center justify-center gap-xs p-md text-body-sm text-on-surface-variant">
        <span class="material-symbols-outlined shrink-0 animate-spin text-primary">progress_activity</span>
        <span class="min-w-0">Đang tải chi tiết đơn hàng...</span>
      </div>

      <div v-else-if="error" class="flex min-h-[280px] w-full min-w-0 items-center justify-center p-md text-center">
        <div class="w-full min-w-0 max-w-[24rem]">
          <span class="material-symbols-outlined text-5xl text-error">error</span>
          <p class="mt-sm w-full text-body-sm text-error">{{ error }}</p>
          <button class="mt-md w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary" type="button" @click="emit('close')">Đóng</button>
        </div>
      </div>

      <div v-else-if="order" class="w-full min-w-0 flex-1 overflow-y-auto p-md">
        <div class="flex w-full min-w-0 items-center justify-between gap-md">
          <div class="w-full min-w-0">
            <p class="w-full text-xs text-on-surface-variant">Mã đơn hàng</p>
            <p class="mt-xs w-full break-words font-mono text-body-sm text-primary">{{ order.code }}</p>
          </div>
          <span class="shrink-0 rounded px-2 py-1 font-mono text-[11px] uppercase" :class="statusClasses[order.status] ?? statusClasses.cancelled">{{ order.status }}</span>
        </div>

        <section v-if="order.status === 'paid_stock_issue'" class="mt-md w-full min-w-0 rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">
          Đơn đã thanh toán nhưng tồn kho không đủ. Admin cần liên hệ khách để đổi variant hoặc hoàn tiền thủ công.
        </section>

        <section class="mt-md w-full min-w-0 rounded-lg border border-surface-variant bg-surface p-md">
          <h3 class="w-full font-display text-body-md font-semibold text-on-surface">Khách hàng</h3>
          <p class="mt-sm w-full text-body-sm font-medium text-on-surface">{{ order.user?.name }}</p>
          <p class="mt-xs w-full break-words text-body-sm text-on-surface-variant">{{ order.user?.email }}</p>
          <p class="mt-sm w-full text-body-sm text-on-surface-variant">{{ order.receiver_name }} · {{ order.receiver_phone }}</p>
          <p class="mt-xs w-full text-body-sm text-on-surface-variant">{{ order.shipping_address }}</p>
        </section>

        <div class="mt-md grid w-full min-w-0 grid-cols-1 gap-sm sm:grid-cols-2">
          <div class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface p-sm"><p class="w-full text-xs text-on-surface-variant">Ngày tạo</p><p class="mt-xs w-full text-body-sm text-on-surface">{{ formatDateTime(order.created_at) }}</p></div>
          <div class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface p-sm"><p class="w-full text-xs text-on-surface-variant">Ngày thanh toán</p><p class="mt-xs w-full text-body-sm text-on-surface">{{ formatDateTime(order.paid_at) }}</p></div>
        </div>

        <section class="mt-md w-full min-w-0">
          <h3 class="w-full font-display text-body-md font-semibold text-on-surface">Sản phẩm đã mua</h3>
          <div class="mt-sm w-full min-w-0 divide-y divide-surface-variant overflow-hidden rounded-lg border border-surface-variant bg-surface">
            <article v-for="item in order.items" :key="item.id" class="flex w-full min-w-0 items-center gap-sm p-sm">
              <img v-if="item.thumbnail_url" :src="item.thumbnail_url" :alt="item.product_name" class="h-14 w-20 shrink-0 rounded object-cover" />
              <div v-else class="flex h-14 w-20 shrink-0 items-center justify-center rounded bg-surface-container-lowest"><span class="material-symbols-outlined text-on-surface-variant">image</span></div>
              <div class="w-full min-w-0">
                <p class="line-clamp-2 w-full text-body-sm font-medium text-on-surface">{{ item.product_name }}</p>
                <p class="mt-xs w-full text-xs text-on-surface-variant">{{ item.brand_name || 'No brand' }} · SKU {{ item.sku }}</p>
                <p class="mt-xs w-full text-xs text-on-surface-variant">{{ item.strap_color }} / {{ item.dial_color }} / {{ item.diameter_mm }}mm / {{ item.movement_type }}</p>
              </div>
              <div class="shrink-0 text-right">
                <p class="font-mono text-xs text-on-surface-variant">x{{ item.quantity }}</p>
                <p class="mt-xs font-mono text-xs text-primary">{{ formatCurrency(item.line_total) }}</p>
              </div>
            </article>
            <p v-if="!order.items?.length" class="w-full p-md text-center text-body-sm text-on-surface-variant">Đơn hàng không có item.</p>
          </div>
        </section>

        <section v-if="order.vnpay_transaction" class="mt-md w-full min-w-0 rounded-lg border border-primary/30 bg-primary/5 p-md">
          <h3 class="w-full font-display text-body-md font-semibold text-on-surface">Giao dịch VNPay</h3>
          <dl class="mt-sm w-full min-w-0 space-y-sm text-body-sm">
            <div class="flex w-full min-w-0 justify-between gap-md"><dt class="min-w-0 text-on-surface-variant">Ngân hàng</dt><dd class="shrink-0 font-mono text-on-surface">{{ order.vnpay_transaction.bank_code || '—' }}</dd></div>
            <div class="flex w-full min-w-0 justify-between gap-md"><dt class="min-w-0 text-on-surface-variant">Mã giao dịch</dt><dd class="min-w-0 break-all text-right font-mono text-on-surface">{{ order.vnpay_transaction.transaction_no || '—' }}</dd></div>
            <div class="flex w-full min-w-0 justify-between gap-md"><dt class="min-w-0 text-on-surface-variant">Response code</dt><dd class="shrink-0 font-mono text-primary">{{ order.vnpay_transaction.response_code || '—' }}</dd></div>
          </dl>
        </section>

        <div class="mt-md flex w-full min-w-0 items-center justify-between gap-md border-t border-surface-variant pt-md">
          <span class="min-w-0 font-display text-body-md font-semibold text-on-surface">Tổng thanh toán</span>
          <span class="shrink-0 font-mono text-lg font-bold text-primary">{{ formatCurrency(order.total_amount) }}</span>
        </div>
      </div>

      <footer v-if="!loading && !error" class="w-full min-w-0 border-t border-surface-variant bg-surface p-md">
        <p v-if="actionError" class="mb-sm w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ actionError }}</p>
        <button
          v-if="order?.status === 'pending'"
          v-show="showAdminActions"
          class="mb-sm w-full rounded-lg border border-tertiary bg-tertiary/10 px-md py-3 text-body-sm font-semibold text-tertiary hover:bg-tertiary hover:text-background disabled:cursor-not-allowed disabled:opacity-60"
          type="button"
          :disabled="markingPaid"
          @click="confirmMarkPaid"
        >
          {{ markingPaid ? 'Đang xử lý...' : 'Đánh dấu đã thanh toán' }}
        </button>
        <button
          v-if="['paid', 'paid_stock_issue', 'shipping'].includes(order?.status)"
          v-show="showAdminActions"
          class="mb-sm w-full rounded-lg border border-error/40 bg-error/10 px-md py-3 text-body-sm font-semibold text-error hover:bg-error hover:text-background disabled:cursor-not-allowed disabled:opacity-60"
          type="button"
          :disabled="refunding"
          @click="emit('mark-refunded')"
        >
          {{ refunding ? 'Đang xử lý...' : 'Mark as refunded' }}
        </button>
        <button class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm font-medium text-on-surface hover:border-primary" type="button" @click="emit('close')">Đóng</button>
      </footer>
    </section>
  </BaseModal>
</template>
