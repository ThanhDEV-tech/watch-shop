<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProductShowcaseGrid from '../components/ProductShowcaseGrid.vue'
import { getBrands, getCategories, getCollections, getProducts } from '../api/axios'

const route = useRoute()
const router = useRouter()

const products = ref([])
const categories = ref([])
const brands = ref([])
const collections = ref([])
const pagination = ref(null)
const isLoading = ref(false)
const errorMessage = ref('')

const filters = reactive({
  gender_target: '',
  category: '',
  brand: '',
  collection: '',
  search: '',
})

const activeFilterCount = computed(() => Object.values(filters).filter(Boolean).length)

const syncFiltersFromRoute = () => {
  filters.gender_target = route.query.gender_target ?? ''
  filters.category = route.query.category ?? ''
  filters.brand = route.query.brand ?? ''
  filters.collection = route.query.collection ?? ''
  filters.search = route.query.search ?? ''
}

const filterParams = () => Object.fromEntries(
  Object.entries(filters).filter(([, value]) => Boolean(value)),
)

const fetchProducts = async () => {
  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await getProducts({
      ...filterParams(),
      per_page: 24,
    })

    products.value = response.data.data?.items ?? []
    pagination.value = response.data.data?.pagination ?? null
  } catch (error) {
    errorMessage.value = error.response?.data?.message ?? 'Không thể tải danh sách sản phẩm.'
  } finally {
    isLoading.value = false
  }
}

const fetchFilters = async () => {
  const [categoryResponse, brandResponse, collectionResponse] = await Promise.all([
    getCategories(),
    getBrands(),
    getCollections(),
  ])

  categories.value = categoryResponse.data.data ?? []
  brands.value = brandResponse.data.data ?? []
  collections.value = collectionResponse.data.data ?? []
}

const updateRouteQuery = () => {
  router.replace({
    name: 'products',
    query: filterParams(),
  })
}

const clearFilters = () => {
  filters.gender_target = ''
  filters.category = ''
  filters.brand = ''
  filters.collection = ''
  filters.search = ''
}

watch(filters, () => {
  updateRouteQuery()
  fetchProducts()
})

onMounted(async () => {
  syncFiltersFromRoute()
  await Promise.all([fetchFilters(), fetchProducts()])
})
</script>

<template>
  <main class="w-full min-w-0 bg-background text-on-surface">
    <section class="border-b border-border bg-surface">
      <div class="mx-auto flex w-full max-w-container-max flex-col gap-8 px-margin-mobile py-14 md:px-gutter lg:flex-row lg:items-end lg:justify-between">
        <div class="w-full min-w-0 lg:max-w-3xl">
          <p class="w-full text-xs font-semibold uppercase tracking-[0.2em] text-primary/65">
            Watchora Catalog
          </p>
          <h1 class="mt-4 w-full font-display text-[clamp(2.8rem,7vw,6rem)] font-semibold leading-[0.9] text-primary">
            Đồng hồ cho từng khoảnh khắc.
          </h1>
        </div>
        <p class="w-full min-w-0 text-base leading-7 text-on-surface-variant lg:max-w-md">
          Chọn theo phong cách, thương hiệu, bộ sưu tập hoặc đối tượng sử dụng. Tất cả sản phẩm hiển thị tại đây đều đang mở bán.
        </p>
      </div>
    </section>

    <section class="mx-auto w-full max-w-container-max px-margin-mobile py-10 md:px-gutter">
      <div class="grid w-full min-w-0 gap-4 border-b border-border pb-8 md:grid-cols-2 xl:grid-cols-5">
        <label class="flex w-full min-w-0 flex-col gap-2">
          <span class="w-full text-xs font-semibold uppercase tracking-[0.14em] text-primary/65">Đối tượng</span>
          <select v-model="filters.gender_target" class="w-full rounded-[var(--radius-watch-md)] border border-border bg-surface px-3 py-3 text-sm text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            <option value="">Tất cả</option>
            <option value="men">Nam</option>
            <option value="women">Nữ</option>
            <option value="unisex">Unisex</option>
          </select>
        </label>

        <label class="flex w-full min-w-0 flex-col gap-2">
          <span class="w-full text-xs font-semibold uppercase tracking-[0.14em] text-primary/65">Danh mục</span>
          <select v-model="filters.category" class="w-full rounded-[var(--radius-watch-md)] border border-border bg-surface px-3 py-3 text-sm text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            <option value="">Tất cả</option>
            <option v-for="category in categories" :key="category.slug" :value="category.slug">
              {{ category.name }}
            </option>
          </select>
        </label>

        <label class="flex w-full min-w-0 flex-col gap-2">
          <span class="w-full text-xs font-semibold uppercase tracking-[0.14em] text-primary/65">Thương hiệu</span>
          <select v-model="filters.brand" class="w-full rounded-[var(--radius-watch-md)] border border-border bg-surface px-3 py-3 text-sm text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            <option value="">Tất cả</option>
            <option v-for="brand in brands" :key="brand.slug" :value="brand.slug">
              {{ brand.name }}
            </option>
          </select>
        </label>

        <label class="flex w-full min-w-0 flex-col gap-2">
          <span class="w-full text-xs font-semibold uppercase tracking-[0.14em] text-primary/65">Collection</span>
          <select v-model="filters.collection" class="w-full rounded-[var(--radius-watch-md)] border border-border bg-surface px-3 py-3 text-sm text-on-surface outline-none focus:border-primary focus:ring-2 focus:ring-primary/20">
            <option value="">Tất cả</option>
            <option v-for="collection in collections" :key="collection.slug" :value="collection.slug">
              {{ collection.name }}
            </option>
          </select>
        </label>

        <div class="flex w-full min-w-0 items-end">
          <button
            type="button"
            class="w-full rounded-[var(--radius-watch-md)] border border-border px-4 py-3 text-sm font-semibold text-primary transition-colors hover:border-primary disabled:cursor-not-allowed disabled:opacity-45"
            :disabled="activeFilterCount === 0"
            @click="clearFilters"
          >
            Xóa bộ lọc
          </button>
        </div>
      </div>

      <div class="mt-8 flex w-full min-w-0 flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <p class="w-full min-w-0 text-sm text-on-surface-variant">
          {{ pagination?.total ?? products.length }} sản phẩm đang hiển thị
        </p>
        <p v-if="activeFilterCount" class="w-full min-w-0 text-sm font-medium text-primary sm:text-right">
          {{ activeFilterCount }} bộ lọc đang áp dụng
        </p>
      </div>

      <div v-if="isLoading" class="mt-10 w-full rounded-[var(--radius-watch-lg)] border border-border bg-surface p-10 text-center text-on-surface-variant">
        Đang tải sản phẩm...
      </div>
      <div v-else-if="errorMessage" class="mt-10 w-full rounded-[var(--radius-watch-lg)] border border-[var(--accent-danger)] bg-[var(--accent-danger-surface)] p-6 text-[var(--accent-danger)]">
        {{ errorMessage }}
      </div>
      <div v-else-if="!products.length" class="mt-10 w-full rounded-[var(--radius-watch-lg)] border border-border bg-surface p-10 text-center text-on-surface-variant">
        Chưa có sản phẩm phù hợp với bộ lọc hiện tại.
      </div>
      <ProductShowcaseGrid v-else class="mt-10" :products="products" />
    </section>
  </main>
</template>
