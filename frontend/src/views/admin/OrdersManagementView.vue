<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import OrderDetailModal from '../../components/dashboard/OrderDetailModal.vue'
import { getAdminOrder, getAdminOrders, markAdminOrderAsPaid } from '../../services/api'
import { formatCurrency } from '../../utils/formatCurrency'
import { formatDateTime } from '../../utils/formatDate'

const orders = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const detailOpen = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const markPaidError = ref('')
const markingPaid = ref(false)
const selectedOrder = ref(null)
const selectedOrderCode = ref('')
const filters = reactive({ status: '', page: 1 })

const fetchOrders = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await getAdminOrders({ ...filters, per_page: 10 })
    orders.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách đơn hàng.'
  } finally { loading.value = false }
}

const changePage = (page) => { filters.page = page; fetchOrders() }
const openOrder = async (order) => {
  detailOpen.value = true
  detailLoading.value = true
  detailError.value = ''
  markPaidError.value = ''
  selectedOrder.value = null
  selectedOrderCode.value = order.code

  try {
    const response = await getAdminOrder(order.id)
    selectedOrder.value = response.data.data
  } catch (requestError) {
    detailError.value = requestError.response?.data?.message ?? 'Không thể tải chi tiết đơn hàng.'
  } finally {
    detailLoading.value = false
  }
}

const closeOrder = () => {
  detailOpen.value = false
  selectedOrder.value = null
  detailError.value = ''
  markPaidError.value = ''
}

const markSelectedOrderAsPaid = async () => {
  if (!selectedOrder.value || markingPaid.value) return

  markingPaid.value = true
  markPaidError.value = ''

  try {
    const response = await markAdminOrderAsPaid(selectedOrder.value.id)
    selectedOrder.value = response.data.data
    orders.value = orders.value.map((order) => (
      order.id === selectedOrder.value.id
        ? { ...order, status: selectedOrder.value.status, paid_at: selectedOrder.value.paid_at }
        : order
    ))
  } catch (requestError) {
    markPaidError.value = requestError.response?.data?.message ?? 'Không thể đánh dấu đơn hàng đã thanh toán.'
  } finally {
    markingPaid.value = false
  }
}

watch(() => filters.status, () => { filters.page = 1; fetchOrders() })
onMounted(fetchOrders)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto"><p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Commerce</p><h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Quản lý đơn hàng</h1><p class="mt-xs w-full text-body-sm text-on-surface-variant">Theo dõi trạng thái và giá trị các đơn hàng.</p></div>
      <select v-model="filters.status" class="w-full rounded-lg border border-surface-variant bg-surface px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary sm:w-52"><option value="">Tất cả trạng thái</option><option value="pending">Pending</option><option value="paid">Paid</option><option value="failed">Failed</option><option value="cancelled">Cancelled</option></select>
    </div>
    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>
    <section class="mt-lg w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto"><table class="w-full min-w-[980px] text-left text-body-sm"><thead class="bg-surface-container-lowest text-on-surface-variant"><tr><th class="px-md py-sm font-medium">Mã đơn</th><th class="px-md py-sm font-medium">Khách hàng</th><th class="px-md py-sm font-medium">Số course</th><th class="px-md py-sm font-medium">Tổng tiền</th><th class="px-md py-sm font-medium">Trạng thái</th><th class="px-md py-sm font-medium">Ngày tạo</th><th class="px-md py-sm text-right font-medium">Thao tác</th></tr></thead><tbody>
        <tr v-if="loading"><td colspan="7" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải đơn hàng...</td></tr>
        <tr v-else-if="!orders.length"><td colspan="7" class="px-md py-xl text-center text-on-surface-variant">Chưa có đơn hàng ở trạng thái này.</td></tr>
        <tr v-for="order in orders" v-else :key="order.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40"><td class="px-md py-sm font-mono text-xs text-primary">{{ order.code }}</td><td class="px-md py-sm"><p class="w-full font-medium text-on-surface">{{ order.user?.name }}</p><p class="w-full text-xs text-on-surface-variant">{{ order.user?.email }}</p></td><td class="px-md py-sm font-mono text-on-surface-variant">{{ order.items?.length ?? 0 }}</td><td class="px-md py-sm font-mono font-semibold text-on-surface">{{ formatCurrency(order.total_amount) }}</td><td class="px-md py-sm"><span class="rounded px-2 py-1 font-mono text-[11px] uppercase" :class="order.status === 'paid' ? 'bg-[var(--accent-success)]/10 text-[var(--accent-success)]' : order.status === 'failed' ? 'bg-error/10 text-error' : 'bg-tertiary/10 text-tertiary'">{{ order.status }}</span></td><td class="px-md py-sm text-on-surface-variant">{{ formatDateTime(order.created_at ?? order.paid_at) }}</td><td class="px-md py-sm text-right"><button class="rounded-lg border border-surface-variant px-sm py-2 text-xs font-medium text-on-surface hover:border-primary hover:text-primary" type="button" @click="openOrder(order)">Xem</button></td></tr>
      </tbody></table></div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>
    <OrderDetailModal
      v-if="detailOpen"
      :order="selectedOrder"
      :order-code="selectedOrderCode"
      :loading="detailLoading"
      :error="detailError"
      :marking-paid="markingPaid"
      :mark-paid-error="markPaidError"
      @close="closeOrder"
      @mark-paid="markSelectedOrderAsPaid"
    />
  </main>
</template>
