<script setup>
import { onMounted, reactive, ref } from 'vue'
import ShippingZoneFormModal from '../../components/dashboard/ShippingZoneFormModal.vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import { createAdminShippingZone, deleteAdminShippingZone, getAdminShippingZones, toggleAdminShippingZoneActive, updateAdminShippingZone } from '../../api/axios'
import { formatCurrency } from '../../utils/formatCurrency'

const zones = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const saving = ref(false)
const deletingId = ref(null)
const togglingId = ref(null)
const error = ref('')
const modalError = ref('')
const editingZone = ref(null)
const showForm = ref(false)
const filters = reactive({ search: '', page: 1 })

const fetchZones = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getAdminShippingZones({ ...filters, per_page: 10 })
    zones.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách khu vực giao hàng.'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingZone.value = null
  modalError.value = ''
  showForm.value = true
}

const openEdit = (zone) => {
  editingZone.value = zone
  modalError.value = ''
  showForm.value = true
}

const saveZone = async (payload) => {
  saving.value = true
  modalError.value = ''

  try {
    const normalizedPayload = {
      ...payload,
      fee: Number(payload.fee ?? 0),
      display_order: Number(payload.display_order ?? 0),
    }

    if (editingZone.value) await updateAdminShippingZone(editingZone.value.id, normalizedPayload)
    else await createAdminShippingZone(normalizedPayload)

    showForm.value = false
    await fetchZones()
  } catch (requestError) {
    const validationErrors = requestError.response?.data?.data?.errors
    modalError.value = validationErrors ? Object.values(validationErrors).flat()[0] : (requestError.response?.data?.message ?? 'Không thể lưu khu vực giao hàng.')
  } finally {
    saving.value = false
  }
}

const toggleZoneStatus = async (zone) => {
  if (togglingId.value === zone.id) return

  togglingId.value = zone.id
  error.value = ''
  const previousState = zone.is_active

  zones.value = zones.value.map((item) => item.id === zone.id ? { ...item, is_active: !previousState } : item)

  try {
    const response = await toggleAdminShippingZoneActive(zone.id)
    zones.value = zones.value.map((item) => item.id === zone.id ? response.data.data : item)
  } catch (requestError) {
    zones.value = zones.value.map((item) => item.id === zone.id ? { ...item, is_active: previousState } : item)
    error.value = requestError.response?.data?.message ?? 'Không thể cập nhật trạng thái khu vực giao hàng.'
  } finally {
    togglingId.value = null
  }
}

const removeZone = async (zone) => {
  if (!window.confirm(`Bạn chắc chắn muốn xóa khu vực “${zone.name}”?`)) return

  deletingId.value = zone.id
  error.value = ''

  try {
    await deleteAdminShippingZone(zone.id)
    await fetchZones()
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể xóa khu vực giao hàng.'
  } finally {
    deletingId.value = null
  }
}

const applySearch = () => {
  filters.page = 1
  fetchZones()
}

const changePage = (page) => {
  filters.page = page
  fetchZones()
}

onMounted(fetchZones)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Checkout</p>
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Shipping Zone</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Quản lý khu vực giao hàng, phí vận chuyển và thứ tự hiển thị ở checkout.</p>
      </div>
      <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 sm:w-auto" type="button" @click="openCreate">Thêm khu vực</button>
    </div>

    <form class="mt-lg flex w-full min-w-0 gap-sm rounded-lg border border-surface-variant bg-surface p-md" @submit.prevent="applySearch">
      <input v-model.trim="filters.search" class="w-full min-w-0 flex-1 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Tìm theo tên khu vực..." />
      <button class="shrink-0 rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary" type="submit">Tìm</button>
    </form>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant">
            <tr>
              <th class="px-md py-sm font-medium">Khu vực</th>
              <th class="px-md py-sm text-right font-medium">Phí giao hàng</th>
              <th class="px-md py-sm text-right font-medium">Thứ tự</th>
              <th class="px-md py-sm font-medium">Trạng thái</th>
              <th class="px-md py-sm text-right font-medium">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải khu vực giao hàng...</td>
            </tr>
            <tr v-else-if="!zones.length">
              <td colspan="5" class="px-md py-xl text-center text-on-surface-variant">Chưa có khu vực giao hàng phù hợp.</td>
            </tr>
            <tr v-for="zone in zones" v-else :key="zone.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 items-center gap-sm">
                  <span class="material-symbols-outlined shrink-0 rounded bg-primary/10 p-2 text-primary">local_shipping</span>
                  <div class="w-full min-w-0">
                    <p class="w-full font-medium text-on-surface">{{ zone.name }}</p>
                    <p class="w-full font-mono text-xs text-on-surface-variant">#{{ zone.id }}</p>
                  </div>
                </div>
              </td>
              <td class="px-md py-sm text-right font-mono text-on-surface">{{ formatCurrency(zone.fee) }}</td>
              <td class="px-md py-sm text-right font-mono text-on-surface">{{ zone.display_order }}</td>
              <td class="px-md py-sm">
                <div class="flex items-center gap-sm">
                  <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:cursor-not-allowed" :class="zone.is_active ? 'bg-[var(--accent-success)]' : 'bg-surface-container-highest'" type="button" :disabled="togglingId === zone.id" :aria-pressed="zone.is_active" :aria-label="zone.is_active ? `Tắt khu vực ${zone.name}` : `Bật khu vực ${zone.name}`" @click="toggleZoneStatus(zone)">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform" :class="zone.is_active ? 'translate-x-5' : 'translate-x-0'"></span>
                  </button>
                  <span class="text-xs" :class="zone.is_active ? 'text-[var(--accent-success)]' : 'text-on-surface-variant'">{{ zone.is_active ? 'Active' : 'Inactive' }}</span>
                </div>
              </td>
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 justify-end gap-xs">
                  <button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="openEdit(zone)">Sửa</button>
                  <button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10 disabled:opacity-50" type="button" :disabled="deletingId === zone.id" @click="removeZone(zone)">{{ deletingId === zone.id ? 'Đang xóa...' : 'Xóa' }}</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>

    <ShippingZoneFormModal v-if="showForm" :zone="editingZone" :loading="saving" :error="modalError" @close="showForm = false" @submit="saveZone" />
  </main>
</template>
