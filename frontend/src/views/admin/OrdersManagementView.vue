<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import OrderDetailModal from '../../components/dashboard/OrderDetailModal.vue'
import { getAdminOrder, getAdminOrders, markAdminOrderAsPaid, markAdminOrderAsRefunded } from '../../api/axios'
import { formatCurrency } from '../../utils/formatCurrency'
import { formatDateTime } from '../../utils/formatDate'

const route = useRoute()
const router = useRouter()
const orders = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const detailOpen = ref(false)
const detailLoading = ref(false)
const detailError = ref('')
const actionError = ref('')
const markingPaid = ref(false)
const refunding = ref(false)
const refundingId = ref(null)
const selectedOrder = ref(null)
const selectedOrderCode = ref('')
const filters = reactive({ status: route.query.status?.toString() ?? '', page: 1 })

const statuses = ['pending', 'paid', 'paid_stock_issue', 'failed', 'cancelled', 'shipping', 'completed', 'refunded']
const refundableStatuses = ['paid', 'paid_stock_issue', 'shipping']
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

const fetchOrders = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await getAdminOrders({ ...filters, per_page: 10 })
    orders.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách đơn hàng.'
  } finally {
    loading.value = false
  }
}

const syncRouteQuery = () => {
  router.replace({ query: { ...route.query, status: filters.status || undefined } })
}

const setStatus = (status) => {
  filters.status = status
}

const changePage = (page) => {
  filters.page = page
  fetchOrders()
}

const openOrder = async (order) => {
  detailOpen.value = true
  detailLoading.value = true
  detailError.value = ''
  actionError.value = ''
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
  actionError.value = ''
}

const patchOrderInList = (updatedOrder) => {
  orders.value = orders.value.map((order) => (
    order.id === updatedOrder.id
      ? { ...order, status: updatedOrder.status, paid_at: updatedOrder.paid_at, refunded_at: updatedOrder.refunded_at, needs_attention: updatedOrder.needs_attention }
      : order
  ))
}

const markSelectedOrderAsPaid = async () => {
  if (!selectedOrder.value || markingPaid.value) return

  markingPaid.value = true
  actionError.value = ''

  try {
    const response = await markAdminOrderAsPaid(selectedOrder.value.id)
    selectedOrder.value = response.data.data
    patchOrderInList(selectedOrder.value)
  } catch (requestError) {
    actionError.value = requestError.response?.data?.message ?? 'Không thể đánh dấu đơn hàng đã thanh toán.'
  } finally {
    markingPaid.value = false
  }
}

const markOrderAsRefunded = async (order) => {
  if (!order || refunding.value) return

  const refundNote = window.prompt('Ghi chú hoàn tiền thủ công:', '')
  if (refundNote === null) return

  refunding.value = true
  refundingId.value = order.id
  actionError.value = ''

  try {
    const response = await markAdminOrderAsRefunded(order.id, { refund_note: refundNote })
    const updatedOrder = response.data.data
    if (selectedOrder.value?.id === updatedOrder.id) selectedOrder.value = updatedOrder
    patchOrderInList(updatedOrder)
  } catch (requestError) {
    actionError.value = requestError.response?.data?.message ?? 'Không thể đánh dấu đơn hàng đã hoàn tiền.'
  } finally {
    refunding.value = false
    refundingId.value = null
  }
}

watch(() => filters.status, () => {
  filters.page = 1
  syncRouteQuery()
  fetchOrders()
})

onMounted(fetchOrders)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Commerce</p>
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Order Management</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Theo dõi trạng thái, đơn lỗi tồn kho và hoàn tiền thủ công.</p>
      </div>
      <select v-model="filters.status" class="w-full rounded-lg border border-surface-variant bg-surface px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary sm:w-60">
        <option value="">Tất cả trạng thái</option>
        <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
      </select>
    </div>

    <div class="mt-md flex w-full min-w-0 flex-wrap gap-sm">
      <button class="rounded-lg border px-sm py-2 text-body-sm" :class="filters.status === '' ? 'border-primary text-primary' : 'border-surface-variant text-on-surface-variant'" type="button" @click="setStatus('')">Tất cả</button>
      <button class="rounded-lg border px-sm py-2 text-body-sm" :class="filters.status === 'paid_stock_issue' ? 'border-error bg-error/10 text-error' : 'border-error/40 text-error'" type="button" @click="setStatus('paid_stock_issue')">Cần xử lý</button>
      <button v-for="status in ['pending', 'paid', 'shipping', 'completed', 'refunded']" :key="status" class="rounded-lg border px-sm py-2 text-body-sm" :class="filters.status === status ? 'border-primary text-primary' : 'border-surface-variant text-on-surface-variant'" type="button" @click="setStatus(status)">{{ status }}</button>
    </div>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>
    <p v-if="actionError && !detailOpen" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ actionError }}</p>

    <section class="mt-lg w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[1080px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant">
            <tr><th class="px-md py-sm font-medium">Mã đơn</th><th class="px-md py-sm font-medium">Khách hàng</th><th class="px-md py-sm font-medium">Số item</th><th class="px-md py-sm font-medium">Tổng tiền</th><th class="px-md py-sm font-medium">Trạng thái</th><th class="px-md py-sm font-medium">Ngày tạo</th><th class="px-md py-sm text-right font-medium">Thao tác</th></tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="7" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải đơn hàng...</td></tr>
            <tr v-else-if="!orders.length"><td colspan="7" class="px-md py-xl text-center text-on-surface-variant">Chưa có đơn hàng ở trạng thái này.</td></tr>
            <tr v-for="order in orders" v-else :key="order.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40" :class="{ 'bg-error/5': order.status === 'paid_stock_issue' }">
              <td class="px-md py-sm font-mono text-xs text-primary">{{ order.code }}</td>
              <td class="px-md py-sm"><p class="w-full font-medium text-on-surface">{{ order.user?.name }}</p><p class="w-full text-xs text-on-surface-variant">{{ order.user?.email }}</p></td>
              <td class="px-md py-sm font-mono text-on-surface-variant">{{ order.items?.length ?? 0 }}</td>
              <td class="px-md py-sm font-mono font-semibold text-on-surface">{{ formatCurrency(order.total_amount) }}</td>
              <td class="px-md py-sm"><span class="rounded px-2 py-1 font-mono text-[11px] uppercase" :class="statusClasses[order.status] ?? statusClasses.cancelled">{{ order.status }}</span></td>
              <td class="px-md py-sm text-on-surface-variant">{{ formatDateTime(order.created_at ?? order.paid_at) }}</td>
              <td class="px-md py-sm text-right">
                <div class="flex w-full min-w-0 flex-wrap justify-end gap-xs">
                  <button v-if="refundableStatuses.includes(order.status)" class="rounded-lg border border-error/40 px-sm py-2 text-xs font-medium text-error hover:bg-error/10 disabled:cursor-not-allowed disabled:opacity-60" type="button" :disabled="refunding && refundingId === order.id" @click="markOrderAsRefunded(order)">{{ refunding && refundingId === order.id ? 'Đang xử lý...' : 'Mark as refunded' }}</button>
                  <button class="rounded-lg border border-surface-variant px-sm py-2 text-xs font-medium text-on-surface hover:border-primary hover:text-primary" type="button" @click="openOrder(order)">Xem</button>
                </div>
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
      :order-code="selectedOrderCode"
      :loading="detailLoading"
      :error="detailError"
      :marking-paid="markingPaid"
      :refunding="refunding"
      :action-error="actionError"
      @close="closeOrder"
      @mark-paid="markSelectedOrderAsPaid"
      @mark-refunded="markOrderAsRefunded(selectedOrder)"
    />
  </main>
</template>
