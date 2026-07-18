<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import DashboardPagination from '../components/dashboard/DashboardPagination.vue'
import OrderDetailModal from '../components/dashboard/OrderDetailModal.vue'
import { getMyOrders } from '../api/axios'
import { formatCurrency } from '../utils/formatCurrency'
import { formatDateTime } from '../utils/formatDate'

const orders = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const selectedOrder = ref(null)
const detailOpen = ref(false)
const filters = reactive({ status: '', page: 1 })

const statuses = [
  { value: '', label: 'Tất cả trạng thái' },
  { value: 'pending', label: 'Pending' },
  { value: 'paid', label: 'Paid' },
  { value: 'paid_stock_issue', label: 'Cần xử lý tồn kho' },
  { value: 'failed', label: 'Failed' },
  { value: 'cancelled', label: 'Cancelled' },
  { value: 'shipping', label: 'Shipping' },
  { value: 'completed', label: 'Completed' },
  { value: 'refunded', label: 'Refunded' },
]

const statusLabels = {
  pending: 'Chờ thanh toán',
  paid: 'Đã thanh toán',
  paid_stock_issue: 'Cần xử lý tồn kho',
  failed: 'Thanh toán thất bại',
  cancelled: 'Đã hủy',
  shipping: 'Đang giao',
  completed: 'Hoàn tất',
  refunded: 'Đã hoàn tiền',
}

const statusClasses = {
  pending: 'bg-[rgb(161_98_7/0.10)] text-[var(--accent-primary)] ring-1 ring-[rgb(161_98_7/0.22)]',
  paid: 'bg-[var(--accent-success)]/10 text-[var(--accent-success)]',
  paid_stock_issue: 'bg-[var(--accent-danger-surface)] text-[var(--accent-danger)] ring-1 ring-[var(--accent-danger)]/30',
  failed: 'bg-[var(--accent-danger-surface)] text-[var(--accent-danger)]',
  cancelled: 'bg-surface-container-highest text-on-surface-variant',
  shipping: 'bg-primary/10 text-primary',
  completed: 'bg-[var(--accent-success)]/10 text-[var(--accent-success)]',
  refunded: 'bg-surface-container-highest text-on-surface-variant',
}

const fetchOrders = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getMyOrders({ ...filters, per_page: 10 })
    orders.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải lịch sử đơn hàng.'
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  filters.page = page
  fetchOrders()
}

const openOrder = (order) => {
  selectedOrder.value = order
  detailOpen.value = true
}

const closeOrder = () => {
  detailOpen.value = false
  selectedOrder.value = null
}

watch(() => filters.status, () => {
  filters.page = 1
  fetchOrders()
})

onMounted(fetchOrders)
</script>

<template>
  <main class="mx-auto min-h-[calc(100vh-5rem)] w-full max-w-container-max px-margin-mobile py-12 md:px-gutter">
    <div class="flex w-full min-w-0 flex-col gap-4 md:flex-row md:items-end md:justify-between">
      <div class="w-full min-w-0">
        <p class="watch-accent-text w-full text-xs font-bold uppercase tracking-[0.2em]">Order history</p>
        <h1 class="mt-3 w-full font-display text-5xl font-semibold leading-none text-primary md:text-6xl">
          Đơn hàng của tôi
        </h1>
        <p class="mt-4 w-full max-w-2xl text-base leading-7 text-on-surface-variant">
          Theo dõi trạng thái xử lý và xem lại snapshot sản phẩm trong từng đơn hàng.
        </p>
      </div>

      <select
        v-model="filters.status"
        class="h-12 w-full rounded-[var(--radius-watch-md)] border border-border bg-surface px-4 text-sm text-on-surface outline-none focus:border-[var(--accent-primary)] md:w-64"
      >
        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
      </select>
    </div>

    <p v-if="error" class="mt-8 w-full rounded-[var(--radius-watch-md)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] p-4 text-sm text-[var(--accent-danger)]">
      {{ error }}
    </p>

    <section class="mt-10 w-full min-w-0 overflow-hidden rounded-[var(--radius-watch-lg)] border border-border bg-surface shadow-[var(--shadow-watch-soft)]">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[980px] text-left text-sm">
          <thead class="border-b border-border bg-background text-on-surface-variant">
            <tr>
              <th class="px-5 py-4 font-semibold">Mã đơn</th>
              <th class="px-5 py-4 font-semibold">Ngày tạo</th>
              <th class="px-5 py-4 font-semibold">Người nhận</th>
              <th class="px-5 py-4 font-semibold">Sản phẩm</th>
              <th class="px-5 py-4 font-semibold">Tổng tiền</th>
              <th class="px-5 py-4 font-semibold">Trạng thái</th>
              <th class="px-5 py-4 text-right font-semibold">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="px-5 py-14 text-center text-on-surface-variant">
                <span class="material-symbols-outlined animate-spin align-middle text-[var(--accent-primary)]">progress_activity</span>
                Đang tải đơn hàng...
              </td>
            </tr>

            <tr v-else-if="!orders.length">
              <td colspan="7" class="px-5 py-16 text-center text-on-surface-variant">
                <span class="material-symbols-outlined mb-3 block text-5xl text-[var(--accent-primary)]">receipt_long</span>
                <p class="w-full">Chưa có đơn hàng ở trạng thái này.</p>
              </td>
            </tr>

            <tr
              v-for="order in orders"
              v-else
              :key="order.id"
              class="border-t border-border transition-colors hover:bg-background/60"
              :class="{ 'bg-[var(--accent-danger-surface)]/40': order.status === 'paid_stock_issue' }"
            >
              <td class="px-5 py-4 font-mono text-xs text-[var(--accent-primary)]">{{ order.code }}</td>
              <td class="px-5 py-4 text-on-surface-variant">{{ formatDateTime(order.created_at) }}</td>
              <td class="px-5 py-4">
                <p class="w-full font-medium text-on-surface">{{ order.receiver_name }}</p>
                <p class="mt-1 w-full text-xs text-on-surface-variant">{{ order.receiver_phone }}</p>
              </td>
              <td class="px-5 py-4 text-on-surface-variant">{{ order.items?.length ?? 0 }} item</td>
              <td class="px-5 py-4 font-semibold text-on-surface">{{ formatCurrency(order.total_amount) }}</td>
              <td class="px-5 py-4">
                <span class="rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-[0.12em]" :class="statusClasses[order.status] ?? statusClasses.cancelled">
                  {{ statusLabels[order.status] ?? order.status }}
                </span>
              </td>
              <td class="px-5 py-4 text-right">
                <button
                  class="rounded-[var(--radius-watch-md)] border border-border px-4 py-2 text-xs font-bold uppercase tracking-[0.12em] text-on-surface transition-colors hover:border-[var(--accent-primary)] hover:text-[var(--accent-primary)]"
                  type="button"
                  @click="openOrder(order)"
                >
                  Xem chi tiết
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>

    <OrderDetailModal
      v-if="detailOpen"
      :order="selectedOrder"
      :order-code="selectedOrder?.code"
      :show-admin-actions="false"
      @close="closeOrder"
    />
  </main>
</template>
