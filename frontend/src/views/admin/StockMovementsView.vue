<script setup>
import { onMounted, reactive, ref } from 'vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import { getAdminStockMovements } from '../../services/api'
import { formatDateTime } from '../../utils/formatDate'

const movements = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const filters = reactive({ product_variant_id: '', order_id: '', page: 1 })

const fetchMovements = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {
      product_variant_id: filters.product_variant_id || undefined,
      order_id: filters.order_id || undefined,
      page: filters.page,
      per_page: 10,
    }
    const response = await getAdminStockMovements(params)
    movements.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải lịch sử tồn kho.'
  } finally {
    loading.value = false
  }
}

const applyFilter = () => {
  filters.page = 1
  fetchMovements()
}

const changePage = (page) => {
  filters.page = page
  fetchMovements()
}
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="w-full min-w-0">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Inventory audit</p>
      <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Stock Movement History</h1>
      <p class="mt-xs w-full text-body-sm text-on-surface-variant">Đối chiếu mọi thay đổi tồn kho theo variant hoặc order.</p>
    </div>

    <form class="mt-lg grid w-full min-w-0 grid-cols-1 gap-sm rounded-lg border border-surface-variant bg-surface p-md md:grid-cols-[1fr_1fr_auto]" @submit.prevent="applyFilter">
      <input v-model.trim="filters.product_variant_id" inputmode="numeric" class="w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="product_variant_id" />
      <input v-model.trim="filters.order_id" inputmode="numeric" class="w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="order_id" />
      <button class="shrink-0 rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container" type="submit">Lọc</button>
    </form>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[980px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant">
            <tr><th class="px-md py-sm font-medium">Thời gian</th><th class="px-md py-sm font-medium">Variant</th><th class="px-md py-sm font-medium">Order</th><th class="px-md py-sm font-medium">Type</th><th class="px-md py-sm text-right font-medium">Thay đổi</th><th class="px-md py-sm text-right font-medium">Stock sau</th><th class="px-md py-sm font-medium">Ghi chú</th></tr>
          </thead>
          <tbody>
            <tr v-if="loading"><td colspan="7" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải lịch sử tồn kho...</td></tr>
            <tr v-else-if="!movements.length"><td colspan="7" class="px-md py-xl text-center text-on-surface-variant">Không có movement phù hợp bộ lọc.</td></tr>
            <tr v-for="movement in movements" v-else :key="movement.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
              <td class="px-md py-sm text-on-surface-variant">{{ formatDateTime(movement.created_at) }}</td>
              <td class="px-md py-sm"><p class="w-full font-mono text-xs text-primary">#{{ movement.product_variant_id }} · {{ movement.product_variant?.sku }}</p><p class="w-full text-xs text-on-surface-variant">{{ movement.product_variant?.product?.name }}</p></td>
              <td class="px-md py-sm font-mono text-xs text-on-surface-variant">{{ movement.order?.code ?? (movement.order_id ? `#${movement.order_id}` : '—') }}</td>
              <td class="px-md py-sm"><span class="rounded bg-surface-container-highest px-2 py-1 font-mono text-[11px] uppercase text-on-surface-variant">{{ movement.type }}</span></td>
              <td class="px-md py-sm text-right font-mono font-semibold" :class="movement.quantity_change < 0 ? 'text-error' : 'text-[var(--accent-success)]'">{{ movement.quantity_change }}</td>
              <td class="px-md py-sm text-right font-mono text-on-surface">{{ movement.stock_after }}</td>
              <td class="px-md py-sm text-on-surface-variant">{{ movement.note || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>
  </main>
</template>
