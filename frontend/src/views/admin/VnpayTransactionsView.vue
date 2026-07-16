<script setup>
import { onMounted, reactive, ref } from 'vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import { getAdminVnpayTransactions } from '../../services/api'
import { formatCurrency } from '../../utils/formatCurrency'

const transactions = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const error = ref('')
const filters = reactive({ response_code: '', page: 1 })

const fetchTransactions = async () => {
  loading.value = true; error.value = ''
  try { const response = await getAdminVnpayTransactions({ ...filters, per_page: 10 }); transactions.value = response.data.data.items ?? []; pagination.value = response.data.data.pagination }
  catch (requestError) { error.value = requestError.response?.data?.message ?? 'Không thể tải giao dịch VNPay.' }
  finally { loading.value = false }
}
const applyFilter = () => { filters.page = 1; fetchTransactions() }
const changePage = (page) => { filters.page = page; fetchTransactions() }
onMounted(fetchTransactions)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="w-full min-w-0"><p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Payment audit</p><h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Giao dịch VNPay</h1><p class="mt-xs w-full text-body-sm text-on-surface-variant">Đối soát mã phản hồi và đơn hàng liên quan.</p></div>
    <form class="mt-lg flex w-full min-w-0 gap-sm rounded-lg border border-surface-variant bg-surface p-md" @submit.prevent="applyFilter"><input v-model.trim="filters.response_code" maxlength="10" class="w-full min-w-0 flex-1 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Response code, ví dụ 00" /><button class="shrink-0 rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container" type="submit">Lọc</button></form>
    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>
    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface"><div class="w-full min-w-0 overflow-x-auto"><table class="w-full min-w-[900px] text-left text-body-sm"><thead class="bg-surface-container-lowest text-on-surface-variant"><tr><th class="px-md py-sm font-medium">Mã giao dịch</th><th class="px-md py-sm font-medium">Ngân hàng</th><th class="px-md py-sm font-medium">Response</th><th class="px-md py-sm font-medium">Số tiền</th><th class="px-md py-sm font-medium">Đơn hàng</th><th class="px-md py-sm font-medium">Xác thực</th></tr></thead><tbody>
      <tr v-if="loading"><td colspan="6" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải giao dịch...</td></tr>
      <tr v-else-if="!transactions.length"><td colspan="6" class="px-md py-xl text-center text-on-surface-variant">Không có giao dịch phù hợp bộ lọc.</td></tr>
      <tr v-for="transaction in transactions" v-else :key="transaction.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40"><td class="px-md py-sm font-mono text-xs text-primary">{{ transaction.transaction_no || transaction.txn_ref }}</td><td class="px-md py-sm text-on-surface">{{ transaction.bank_code || '—' }}</td><td class="px-md py-sm"><span class="rounded px-2 py-1 font-mono text-xs" :class="transaction.response_code === '00' ? 'bg-[var(--accent-success)]/10 text-[var(--accent-success)]' : 'bg-error/10 text-error'">{{ transaction.response_code || '—' }}</span></td><td class="px-md py-sm font-mono font-semibold text-on-surface">{{ formatCurrency(transaction.amount) }}</td><td class="px-md py-sm font-mono text-xs text-on-surface-variant">{{ transaction.order?.code || '—' }}</td><td class="px-md py-sm"><span :class="transaction.is_verified ? 'text-[var(--accent-success)]' : 'text-tertiary'">{{ transaction.is_verified ? 'Verified' : 'Unverified' }}</span></td></tr>
    </tbody></table></div><DashboardPagination :pagination="pagination" @change="changePage" /></section>
  </main>
</template>
