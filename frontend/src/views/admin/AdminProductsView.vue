<script setup>
import { onMounted, reactive, ref } from 'vue'
import ProductFormModal from '../../components/dashboard/ProductFormModal.vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import {
  createProduct,
  deleteProduct,
  getAdminCategories,
  getAdminProduct,
  getAdminProducts,
  getBrands,
  getCollections,
  updateProduct,
} from '../../api/axios'
import { formatCurrency } from '../../utils/formatCurrency'

const products = ref([])
const brands = ref([])
const categories = ref([])
const collections = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const optionsLoading = ref(true)
const saving = ref(false)
const deletingId = ref(null)
const statusUpdatingId = ref(null)
const error = ref('')
const modalError = ref('')
const editingProduct = ref(null)
const showForm = ref(false)

const filters = reactive({
  search: '',
  status: '',
  brand_id: '',
  category_id: '',
  page: 1,
})

const statusOptions = ['', 'draft', 'active', 'inactive']

const firstApiError = (requestError, fallback) => {
  const errors = requestError.response?.data?.data?.errors
  return errors ? Object.values(errors).flat()[0] : (requestError.response?.data?.message ?? fallback)
}

const thumbnailOf = (product) => product.thumbnail
  ?? product.product_images?.find((image) => image.is_primary)?.url
  ?? product.product_images?.[0]?.url
  ?? 'https://placehold.co/240x240/F8F5EF/111111?text=Watchora'

const genderLabel = (value) => ({
  men: 'Nam',
  women: 'Nữ',
  unisex: 'Unisex',
}[value] ?? value)

const statusClass = (status) => ({
  active: 'border-[var(--accent-success)] bg-[rgb(34_197_94/0.08)] text-[var(--accent-success)]',
  draft: 'border-primary bg-[rgb(161_98_7/0.08)] text-primary',
  inactive: 'border-surface-variant bg-surface-container-highest text-on-surface-variant',
}[status] ?? 'border-surface-variant text-on-surface-variant')

const priceLabel = (product) => {
  const price = Number(product.min_final_price ?? 0)
  return price > 0 ? `từ ${formatCurrency(price)}` : 'Chưa có giá'
}

const fetchOptions = async () => {
  optionsLoading.value = true

  try {
    const [brandResponse, categoryResponse, collectionResponse] = await Promise.all([
      getBrands(),
      getAdminCategories({ per_page: 100 }),
      getCollections(),
    ])

    brands.value = brandResponse.data.data ?? []
    categories.value = categoryResponse.data.data.items ?? []
    collections.value = collectionResponse.data.data ?? []
  } catch (requestError) {
    error.value = firstApiError(requestError, 'Không thể tải dữ liệu chọn brand/category.')
  } finally {
    optionsLoading.value = false
  }
}

const fetchProducts = async () => {
  loading.value = true
  error.value = ''

  try {
    const params = {
      ...filters,
      per_page: 10,
      status: filters.status || undefined,
      brand_id: filters.brand_id || undefined,
      category_id: filters.category_id || undefined,
      search: filters.search || undefined,
    }
    const response = await getAdminProducts(params)
    products.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = firstApiError(requestError, 'Không thể tải danh sách sản phẩm.')
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingProduct.value = null
  modalError.value = ''
  showForm.value = true
}

const openEdit = async (product) => {
  modalError.value = ''
  error.value = ''

  try {
    const response = await getAdminProduct(product.id)
    editingProduct.value = response.data.data
    showForm.value = true
  } catch (requestError) {
    error.value = firstApiError(requestError, 'Không thể tải chi tiết sản phẩm.')
  }
}

const saveProduct = async (payload) => {
  saving.value = true
  modalError.value = ''

  try {
    const response = editingProduct.value
      ? await updateProduct(editingProduct.value.id, payload)
      : await createProduct(payload)

    editingProduct.value = response.data.data
    await fetchProducts()
  } catch (requestError) {
    modalError.value = firstApiError(requestError, 'Không thể lưu sản phẩm.')
  } finally {
    saving.value = false
  }
}

const refreshEditingProduct = async () => {
  if (!editingProduct.value?.id) return

  const response = await getAdminProduct(editingProduct.value.id)
  editingProduct.value = response.data.data
  await fetchProducts()
}

const changeProductStatus = async (product, status) => {
  if (!status || product.status === status || statusUpdatingId.value === product.id) return

  const previousStatus = product.status
  statusUpdatingId.value = product.id
  error.value = ''
  products.value = products.value.map((item) => item.id === product.id ? { ...item, status } : item)

  try {
    await updateProduct(product.id, { status })
    await fetchProducts()
  } catch (requestError) {
    products.value = products.value.map((item) => item.id === product.id ? { ...item, status: previousStatus } : item)
    error.value = firstApiError(requestError, 'Không thể đổi trạng thái sản phẩm.')
  } finally {
    statusUpdatingId.value = null
  }
}

const removeProduct = async (product) => {
  if (!window.confirm(`Bạn chắc chắn muốn xóa mềm sản phẩm “${product.name}”?`)) return
  deletingId.value = product.id
  error.value = ''

  try {
    await deleteProduct(product.id)
    await fetchProducts()
  } catch (requestError) {
    error.value = firstApiError(requestError, 'Không thể xóa sản phẩm.')
  } finally {
    deletingId.value = null
  }
}

const applyFilters = () => {
  filters.page = 1
  fetchProducts()
}

const resetFilters = () => {
  filters.search = ''
  filters.status = ''
  filters.brand_id = ''
  filters.category_id = ''
  filters.page = 1
  fetchProducts()
}

const changePage = (page) => {
  filters.page = page
  fetchProducts()
}

onMounted(async () => {
  await Promise.all([fetchOptions(), fetchProducts()])
})
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Catalog</p>
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Quản lý sản phẩm</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Tạo product, gallery và quản lý variant/SKU ngay trong trang edit.</p>
      </div>
      <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 sm:w-auto" type="button" @click="openCreate">
        Thêm sản phẩm
      </button>
    </div>

    <form class="mt-lg grid w-full min-w-0 grid-cols-1 gap-sm rounded-lg border border-surface-variant bg-surface p-md md:grid-cols-[1.5fr_0.8fr_0.8fr_0.8fr_auto_auto]" @submit.prevent="applyFilters">
      <input v-model.trim="filters.search" class="w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Tìm theo tên hoặc slug..." />
      <select v-model="filters.status" class="w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary">
        <option v-for="status in statusOptions" :key="status || 'all'" :value="status">{{ status || 'Tất cả status' }}</option>
      </select>
      <select v-model="filters.brand_id" class="w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary">
        <option value="">Tất cả brand</option>
        <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
      </select>
      <select v-model="filters.category_id" class="w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary">
        <option value="">Tất cả category</option>
        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
      </select>
      <button class="rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary" type="submit">Lọc</button>
      <button class="rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary" type="button" @click="resetFilters">Reset</button>
    </form>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[1120px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant">
            <tr>
              <th class="px-md py-sm font-medium">Sản phẩm</th>
              <th class="px-md py-sm font-medium">Brand</th>
              <th class="px-md py-sm font-medium">Category</th>
              <th class="px-md py-sm font-medium">Gender</th>
              <th class="px-md py-sm text-right font-medium">Giá</th>
              <th class="px-md py-sm text-right font-medium">Variant</th>
              <th class="px-md py-sm font-medium">Status</th>
              <th class="px-md py-sm text-right font-medium">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading || optionsLoading">
              <td colspan="8" class="px-md py-xl text-center text-on-surface-variant">
                <span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span>
                Đang tải sản phẩm...
              </td>
            </tr>
            <tr v-else-if="!products.length">
              <td colspan="8" class="px-md py-xl text-center text-on-surface-variant">Chưa có sản phẩm phù hợp.</td>
            </tr>
            <tr v-for="product in products" v-else :key="product.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 items-center gap-sm">
                  <img :src="thumbnailOf(product)" :alt="product.name" class="h-16 w-16 shrink-0 rounded-lg object-cover" />
                  <div class="w-full min-w-0">
                    <p class="w-full font-medium text-on-surface">{{ product.name }}</p>
                    <p class="line-clamp-1 w-full font-mono text-xs text-on-surface-variant">{{ product.slug }}</p>
                  </div>
                </div>
              </td>
              <td class="px-md py-sm text-on-surface">{{ product.brand?.name ?? '—' }}</td>
              <td class="px-md py-sm text-on-surface">{{ product.category?.name ?? '—' }}</td>
              <td class="px-md py-sm text-on-surface">{{ genderLabel(product.gender_target) }}</td>
              <td class="px-md py-sm text-right font-mono text-on-surface">{{ priceLabel(product) }}</td>
              <td class="px-md py-sm text-right font-mono text-on-surface">{{ product.variants_count ?? product.variants?.length ?? 0 }}</td>
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 items-center gap-xs">
                  <span class="rounded-full border px-sm py-1 text-[11px] font-semibold uppercase tracking-wide" :class="statusClass(product.status)">{{ product.status }}</span>
                  <select class="min-w-28 rounded-lg border border-surface-variant bg-background px-sm py-2 text-xs text-on-surface outline-none focus:border-primary disabled:opacity-50" :value="product.status" :disabled="statusUpdatingId === product.id" @change="changeProductStatus(product, $event.target.value)">
                    <option value="draft">draft</option>
                    <option value="active">active</option>
                    <option value="inactive">inactive</option>
                  </select>
                </div>
              </td>
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 justify-end gap-xs">
                  <button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="openEdit(product)">Sửa</button>
                  <button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10 disabled:opacity-50" type="button" :disabled="deletingId === product.id" @click="removeProduct(product)">
                    {{ deletingId === product.id ? 'Đang xóa...' : 'Xóa' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>

    <ProductFormModal
      v-if="showForm"
      :product="editingProduct"
      :brands="brands"
      :categories="categories"
      :collections="collections"
      :loading="saving"
      :error="modalError"
      @close="showForm = false"
      @submit="saveProduct"
      @variants-changed="refreshEditingProduct"
    />
  </main>
</template>
