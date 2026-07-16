<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import DashboardPagination from '../components/dashboard/DashboardPagination.vue'
import OrderDetailModal from '../components/dashboard/OrderDetailModal.vue'
import { getMyOrders } from '../services/api'
import { formatCurrency } from '../utils/formatCurrency'
import { formatDateTime } from '../utils/formatDate'

const orders = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const selectedOrder = ref(null)
const detailOpen = ref(false)
const filters = reactive({ status: '', page: 1 })

const statusClasses = {
  pending: 'bg-tertiary/10 text-tertiary',
  paid: 'bg-[var(--accent-success)]/10 text-[var(--accent-success)]',
  failed: 'bg-error/10 text-error',
  cancelled: 'bg-surface-container-highest text-on-surface-variant',
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
  <main class="mx-auto min-h-[calc(100vh-5rem)] w-full max-w-container-max px-margin-mobile py-lg md:px-gutter">
    <div class="flex w-full min-w-0 flex-col gap-md sm:flex-row sm:items-end sm:justify-between">
      <div class="w-full min-w-0">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Purchase history</p>
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Đơn hàng của tôi</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Theo dõi trạng thái và xem lại các khóa học trong từng đơn hàng.</p>
      </div>
      <select v-model="filters.status" class="w-full rounded-lg border border-surface-variant bg-surface px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary sm:w-52"><option value="">Tất cả trạng thái</option><option value="pending">Pending</option><option value="paid">Paid</option><option value="failed">Failed</option><option value="cancelled">Cancelled</option></select>
    </div>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <section class="mt-lg w-full min-w-0 overflow-hidden rounded-xl border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant"><tr><th class="px-md py-sm font-medium">Mã đơn</th><th class="px-md py-sm font-medium">Ngày mua</th><th class="px-md py-sm font-medium">Khóa học</th><th class="px-md py-sm font-medium">Tổng tiền</th><th class="px-md py-sm font-medium">Trạng thái</th><th class="px-md py-sm text-right font-medium">Thao tác</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="6" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải đơn hàng...</td></tr>
            <tr v-else-if="!orders.length"><td colspan="6" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined mb-sm block text-5xl text-primary">receipt_long</span><p class="w-full">Chưa có đơn hàng ở trạng thái này.</p></td></tr>
            <tr v-for="order in orders" v-else :key="order.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
              <td class="px-md py-sm font-mono text-xs text-primary">{{ order.code }}</td>
              <td class="px-md py-sm text-on-surface-variant">{{ formatDateTime(order.paid_at ?? order.created_at) }}</td>
              <td class="px-md py-sm font-mono text-on-surface-variant">{{ order.items?.length ?? 0 }}</td>
              <td class="px-md py-sm font-mono font-semibold text-on-surface">{{ formatCurrency(order.total_amount) }}</td>
              <td class="px-md py-sm"><span class="rounded px-2 py-1 font-mono text-[11px] uppercase" :class="statusClasses[order.status] ?? statusClasses.cancelled">{{ order.status }}</span></td>
              <td class="px-md py-sm"><div class="flex w-full min-w-0 flex-wrap justify-end gap-xs"><button class="rounded-lg border border-surface-variant px-sm py-2 text-xs font-medium text-on-surface hover:border-primary hover:text-primary" type="button" @click="openOrder(order)">Xem</button><button v-if="order.status === 'paid'" class="cursor-not-allowed rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface-variant opacity-60" type="button" disabled title="Sắp có">Tải hóa đơn · Sắp có</button></div></td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>

    <OrderDetailModal v-if="detailOpen" :order="selectedOrder" :order-code="selectedOrder?.code" @close="closeOrder" />
  </main>
</template>
