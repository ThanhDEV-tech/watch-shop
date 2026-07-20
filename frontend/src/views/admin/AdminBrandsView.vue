<script setup>
import { onMounted, reactive, ref } from 'vue'
import BrandFormModal from '../../components/dashboard/BrandFormModal.vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import { createBrand, deleteBrand, getAdminBrands, toggleBrandActive, updateBrand } from '../../api/axios'

const brands = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const saving = ref(false)
const deletingId = ref(null)
const togglingId = ref(null)
const error = ref('')
const modalError = ref('')
const editingBrand = ref(null)
const showForm = ref(false)
const filters = reactive({ search: '', page: 1 })

const extractError = (requestError, fallback) => {
  const validationErrors = requestError.response?.data?.data?.errors
  return validationErrors ? Object.values(validationErrors).flat()[0] : (requestError.response?.data?.message ?? fallback)
}

const fetchBrands = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getAdminBrands({ ...filters, per_page: 10 })
    brands.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = extractError(requestError, 'Không thể tải danh sách thương hiệu.')
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingBrand.value = null
  modalError.value = ''
  showForm.value = true
}

const openEdit = (brand) => {
  editingBrand.value = brand
  modalError.value = ''
  showForm.value = true
}

const saveBrand = async (payload) => {
  saving.value = true
  modalError.value = ''

  try {
    if (editingBrand.value) await updateBrand(editingBrand.value.id, payload)
    else await createBrand(payload)

    showForm.value = false
    await fetchBrands()
  } catch (requestError) {
    modalError.value = extractError(requestError, 'Không thể lưu thương hiệu.')
  } finally {
    saving.value = false
  }
}

const toggleBrandStatus = async (brand) => {
  if (togglingId.value === brand.id) return

  togglingId.value = brand.id
  error.value = ''
  const previousState = brand.is_active
  brands.value = brands.value.map((item) => item.id === brand.id ? { ...item, is_active: !previousState } : item)

  try {
    const response = await toggleBrandActive(brand.id)
    brands.value = brands.value.map((item) => item.id === brand.id ? response.data.data : item)
  } catch (requestError) {
    brands.value = brands.value.map((item) => item.id === brand.id ? { ...item, is_active: previousState } : item)
    error.value = extractError(requestError, 'Không thể cập nhật trạng thái thương hiệu.')
  } finally {
    togglingId.value = null
  }
}

const removeBrand = async (brand) => {
  if (!window.confirm(`Bạn chắc chắn muốn xóa thương hiệu “${brand.name}”?`)) return

  deletingId.value = brand.id
  error.value = ''

  try {
    await deleteBrand(brand.id)
    await fetchBrands()
  } catch (requestError) {
    error.value = extractError(requestError, 'Không thể xóa thương hiệu.')
  } finally {
    deletingId.value = null
  }
}

const applySearch = () => {
  filters.page = 1
  fetchBrands()
}

const changePage = (page) => {
  filters.page = page
  fetchBrands()
}

onMounted(fetchBrands)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Catalog</p>
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Quản lý thương hiệu</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Theo dõi brand đồng hồ, logo, quốc gia và trạng thái hiển thị.</p>
      </div>
      <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 sm:w-auto" type="button" @click="openCreate">Thêm thương hiệu</button>
    </div>

    <form class="mt-lg flex w-full min-w-0 gap-sm rounded-lg border border-surface-variant bg-surface p-md" @submit.prevent="applySearch">
      <input v-model.trim="filters.search" class="w-full min-w-0 flex-1 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Tìm theo tên, slug hoặc quốc gia..." />
      <button class="shrink-0 rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary" type="submit">Tìm</button>
    </form>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[980px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant">
            <tr>
              <th class="px-md py-sm font-medium">Thương hiệu</th>
              <th class="px-md py-sm font-medium">Slug</th>
              <th class="px-md py-sm font-medium">Quốc gia</th>
              <th class="px-md py-sm font-medium">Số sản phẩm</th>
              <th class="px-md py-sm font-medium">Trạng thái</th>
              <th class="px-md py-sm text-right font-medium">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="6" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải thương hiệu...</td>
            </tr>
            <tr v-else-if="!brands.length">
              <td colspan="6" class="px-md py-xl text-center text-on-surface-variant">Chưa có thương hiệu phù hợp.</td>
            </tr>
            <tr v-for="brand in brands" v-else :key="brand.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 items-center gap-sm">
                  <img v-if="brand.logo" :src="brand.logo" :alt="brand.name" class="h-12 w-16 shrink-0 rounded-lg border border-surface-variant bg-white object-contain p-2" />
                  <div v-else class="flex h-12 w-16 shrink-0 items-center justify-center rounded-lg border border-surface-variant bg-surface-container-lowest text-primary">
                    <span class="material-symbols-outlined">watch</span>
                  </div>
                  <div class="w-full min-w-0">
                    <p class="w-full font-medium text-on-surface">{{ brand.name }}</p>
                    <p class="line-clamp-1 w-full text-xs text-on-surface-variant">{{ brand.description || 'Không có mô tả' }}</p>
                  </div>
                </div>
              </td>
              <td class="px-md py-sm font-mono text-xs text-on-surface-variant">{{ brand.slug }}</td>
              <td class="px-md py-sm text-on-surface">{{ brand.country || '—' }}</td>
              <td class="px-md py-sm font-mono text-on-surface">{{ brand.products_count ?? 0 }}</td>
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 items-center gap-sm">
                  <button class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:cursor-not-allowed" :class="brand.is_active ? 'bg-[var(--accent-success)]' : 'bg-surface-container-highest'" type="button" :disabled="togglingId === brand.id" :aria-pressed="brand.is_active" :aria-label="brand.is_active ? `Tắt thương hiệu ${brand.name}` : `Bật thương hiệu ${brand.name}`" @click="toggleBrandStatus(brand)">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform" :class="brand.is_active ? 'translate-x-5' : 'translate-x-0'"></span>
                  </button>
                  <span class="min-w-0 text-xs" :class="brand.is_active ? 'text-[var(--accent-success)]' : 'text-on-surface-variant'">{{ brand.is_active ? 'Active' : 'Inactive' }}</span>
                </div>
              </td>
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 justify-end gap-xs">
                  <button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="openEdit(brand)">Sửa</button>
                  <button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10 disabled:opacity-50" type="button" :disabled="deletingId === brand.id" @click="removeBrand(brand)">{{ deletingId === brand.id ? 'Đang xóa...' : 'Xóa' }}</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>

    <BrandFormModal v-if="showForm" :brand="editingBrand" :loading="saving" :error="modalError" @close="showForm = false" @submit="saveBrand" />
  </main>
</template>
