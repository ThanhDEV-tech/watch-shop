<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import BaseModal from '../ui/BaseModal.vue'
import {
  attachProductToCollection,
  detachProductFromCollection,
  getAdminProducts,
  updateCollectionProductOrder,
} from '../../api/axios'

const props = defineProps({
  collection: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'submit', 'changed'])

const form = reactive({
  name: '',
  slug: '',
  description: '',
  start_date: '',
  end_date: '',
})
const slugTouched = ref(false)
const workingCollection = ref(null)
const products = ref([])
const productSearch = ref('')
const selectedProductId = ref('')
const newDisplayOrder = ref(0)
const productLoading = ref(false)
const productActionId = ref(null)
const managerError = ref('')

const slugify = (value) => value
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .replace(/đ/g, 'd')
  .replace(/Đ/g, 'D')
  .toLowerCase()
  .trim()
  .replace(/[^a-z0-9]+/g, '-')
  .replace(/^-+|-+$/g, '')

const currentProducts = computed(() => workingCollection.value?.products ?? [])
const attachedProductIds = computed(() => new Set(currentProducts.value.map((product) => product.id)))
const selectableProducts = computed(() => products.value.filter((product) => !attachedProductIds.value.has(product.id)))

const extractError = (requestError, fallback) => {
  const validationErrors = requestError.response?.data?.data?.errors
  return validationErrors ? Object.values(validationErrors).flat()[0] : (requestError.response?.data?.message ?? fallback)
}

const syncFromCollection = (collection) => {
  workingCollection.value = collection ? { ...collection, products: collection.products ?? [] } : null
  form.name = collection?.name ?? ''
  form.slug = collection?.slug ?? ''
  form.description = collection?.description ?? ''
  form.start_date = collection?.start_date ?? ''
  form.end_date = collection?.end_date ?? ''
  slugTouched.value = Boolean(collection?.slug)
}

watch(() => props.collection, syncFromCollection, { immediate: true })

watch(() => form.name, (name) => {
  if (!slugTouched.value) form.slug = slugify(name)
})

const fetchProducts = async () => {
  productLoading.value = true
  managerError.value = ''

  try {
    const response = await getAdminProducts({ search: productSearch.value, per_page: 100 })
    products.value = response.data.data.items ?? []
  } catch (requestError) {
    managerError.value = extractError(requestError, 'Không thể tải danh sách sản phẩm.')
  } finally {
    productLoading.value = false
  }
}

const submit = () => {
  emit('submit', {
    ...form,
    slug: form.slug || slugify(form.name),
    start_date: form.start_date || null,
    end_date: form.end_date || null,
  })
}

const setCollectionFromResponse = (collection) => {
  syncFromCollection(collection)
  emit('changed', collection)
}

const addProduct = async () => {
  if (!workingCollection.value?.id || !selectedProductId.value) return

  productActionId.value = `add-${selectedProductId.value}`
  managerError.value = ''

  try {
    const response = await attachProductToCollection(workingCollection.value.id, {
      product_id: Number(selectedProductId.value),
      display_order: Number(newDisplayOrder.value || 0),
    })
    setCollectionFromResponse(response.data.data)
    selectedProductId.value = ''
    newDisplayOrder.value = 0
  } catch (requestError) {
    managerError.value = extractError(requestError, 'Không thể thêm sản phẩm vào bộ sưu tập.')
  } finally {
    productActionId.value = null
  }
}

const detachProduct = async (product) => {
  if (!workingCollection.value?.id) return
  if (!window.confirm(`Gỡ sản phẩm “${product.name}” khỏi bộ sưu tập này?`)) return

  productActionId.value = `detach-${product.id}`
  managerError.value = ''

  try {
    const response = await detachProductFromCollection(workingCollection.value.id, product.id)
    setCollectionFromResponse(response.data.data)
  } catch (requestError) {
    managerError.value = extractError(requestError, 'Không thể gỡ sản phẩm khỏi bộ sưu tập.')
  } finally {
    productActionId.value = null
  }
}

const updateOrder = async (product) => {
  if (!workingCollection.value?.id) return

  productActionId.value = `order-${product.id}`
  managerError.value = ''

  try {
    const response = await updateCollectionProductOrder(workingCollection.value.id, product.id, {
      display_order: Number(product.pivot?.display_order ?? 0),
    })
    setCollectionFromResponse(response.data.data)
  } catch (requestError) {
    managerError.value = extractError(requestError, 'Không thể cập nhật thứ tự sản phẩm.')
  } finally {
    productActionId.value = null
  }
}

onMounted(fetchProducts)
</script>

<template>
  <BaseModal max-width="wide" aria-labelledby="collection-form-title" @close="emit('close')">
    <div class="w-full min-w-0 overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl">
      <form class="w-full min-w-0" @submit.prevent="submit">
        <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface px-md py-sm">
          <div class="w-full min-w-0">
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Collection editor</p>
            <h2 id="collection-form-title" class="w-full font-display text-headline-sm font-semibold text-on-surface">
              {{ workingCollection ? 'Sửa bộ sưu tập' : 'Thêm bộ sưu tập' }}
            </h2>
          </div>
          <button class="material-symbols-outlined shrink-0 p-2 text-on-surface-variant hover:text-on-surface" type="button" @click="emit('close')">close</button>
        </header>

        <div class="grid w-full min-w-0 gap-md p-md lg:grid-cols-2">
          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="collection-name">Tên bộ sưu tập</label>
            <input id="collection-name" v-model.trim="form.name" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </div>

          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="collection-slug">Slug</label>
            <input id="collection-slug" v-model.trim="form.slug" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 font-mono text-body-sm text-on-surface outline-none focus:border-primary" @input="slugTouched = true" />
          </div>

          <div class="w-full min-w-0 lg:col-span-2">
            <label class="block w-full text-body-sm text-on-surface" for="collection-description">Mô tả</label>
            <textarea id="collection-description" v-model.trim="form.description" rows="3" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
          </div>

          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="collection-start-date">Ngày bắt đầu</label>
            <input id="collection-start-date" v-model="form.start_date" type="date" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </div>

          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="collection-end-date">Ngày kết thúc</label>
            <input id="collection-end-date" v-model="form.end_date" type="date" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </div>

          <p v-if="error" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error lg:col-span-2">{{ error }}</p>
        </div>

        <footer class="flex w-full min-w-0 flex-col-reverse gap-sm border-t border-surface-variant p-md sm:flex-row sm:justify-end">
          <button class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface sm:w-auto" type="button" :disabled="loading" @click="emit('close')">Đóng</button>
          <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50 sm:w-auto" type="submit" :disabled="loading">
            {{ loading ? 'Đang lưu...' : 'Lưu bộ sưu tập' }}
          </button>
        </footer>
      </form>

      <section class="w-full min-w-0 border-t border-surface-variant bg-surface/60 p-md">
        <div class="flex w-full min-w-0 flex-col gap-xs md:flex-row md:items-end md:justify-between">
          <div class="w-full min-w-0">
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Products in collection</p>
            <h3 class="w-full font-display text-title-md font-semibold text-on-surface">Sản phẩm trong bộ sưu tập</h3>
            <p class="mt-xs w-full text-body-sm text-on-surface-variant">
              Lưu bộ sưu tập trước, sau đó thêm sản phẩm và chỉnh display_order.
            </p>
          </div>
          <form class="flex w-full min-w-0 gap-xs md:max-w-md" @submit.prevent="fetchProducts">
            <input v-model.trim="productSearch" class="w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-2 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Tìm sản phẩm để thêm..." />
            <button class="shrink-0 rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="submit">Tìm</button>
          </form>
        </div>

        <p v-if="managerError" class="mt-sm w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ managerError }}</p>

        <div v-if="workingCollection?.id" class="mt-md grid w-full min-w-0 gap-md lg:grid-cols-[0.9fr_1.1fr]">
          <form class="flex w-full min-w-0 flex-col gap-sm rounded-lg border border-surface-variant bg-background p-md" @submit.prevent="addProduct">
            <label class="block w-full text-body-sm text-on-surface" for="collection-product-select">Thêm sản phẩm</label>
            <select id="collection-product-select" v-model="selectedProductId" required class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary">
              <option value="">{{ productLoading ? 'Đang tải sản phẩm...' : 'Chọn sản phẩm' }}</option>
              <option v-for="product in selectableProducts" :key="product.id" :value="product.id">
                {{ product.name }} · {{ product.brand?.name || 'Không brand' }} · {{ product.status }}
              </option>
            </select>
            <label class="block w-full text-body-sm text-on-surface" for="collection-product-order">Display order</label>
            <input id="collection-product-order" v-model.number="newDisplayOrder" min="0" type="number" class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            <button class="rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50" type="submit" :disabled="!selectedProductId || Boolean(productActionId)">
              {{ productActionId?.startsWith('add-') ? 'Đang thêm...' : 'Thêm vào collection' }}
            </button>
          </form>

          <div class="w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-background">
            <div class="w-full min-w-0 overflow-x-auto">
              <table class="w-full min-w-[680px] text-left text-body-sm">
                <thead class="bg-surface-container-lowest text-on-surface-variant">
                  <tr>
                    <th class="px-sm py-xs font-medium">Sản phẩm</th>
                    <th class="px-sm py-xs font-medium">Status</th>
                    <th class="px-sm py-xs font-medium">Order</th>
                    <th class="px-sm py-xs text-right font-medium">Thao tác</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="!currentProducts.length">
                    <td colspan="4" class="px-md py-lg text-center text-on-surface-variant">Collection này chưa có sản phẩm.</td>
                  </tr>
                  <tr v-for="product in currentProducts" v-else :key="product.id" class="border-t border-surface-variant">
                    <td class="px-sm py-xs">
                      <div class="flex w-full min-w-0 items-center gap-sm">
                        <img v-if="product.thumbnail" :src="product.thumbnail" :alt="product.name" class="h-12 w-12 shrink-0 rounded-lg object-cover" />
                        <div v-else class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-surface-container-lowest text-primary"><span class="material-symbols-outlined text-base">watch</span></div>
                        <div class="w-full min-w-0">
                          <p class="w-full truncate font-medium text-on-surface">{{ product.name }}</p>
                          <p class="w-full truncate text-xs text-on-surface-variant">{{ product.brand?.name || 'Không brand' }} · {{ product.category?.name || 'Không danh mục' }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="px-sm py-xs"><span class="rounded-full bg-surface-container-lowest px-2 py-1 font-mono text-[11px] uppercase text-on-surface-variant">{{ product.status }}</span></td>
                    <td class="px-sm py-xs">
                      <div class="flex w-full min-w-0 items-center gap-xs">
                        <input v-model.number="product.pivot.display_order" min="0" type="number" class="w-24 rounded-lg border border-surface-variant bg-surface px-sm py-2 text-body-sm text-on-surface outline-none focus:border-primary" />
                        <button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary disabled:opacity-50" type="button" :disabled="productActionId === `order-${product.id}`" @click="updateOrder(product)">Lưu</button>
                      </div>
                    </td>
                    <td class="px-sm py-xs">
                      <div class="flex w-full min-w-0 justify-end">
                        <button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10 disabled:opacity-50" type="button" :disabled="productActionId === `detach-${product.id}`" @click="detachProduct(product)">Gỡ</button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div v-else class="mt-md w-full rounded-lg border border-dashed border-surface-variant bg-background p-md text-body-sm text-on-surface-variant">
          Sau khi bấm “Lưu bộ sưu tập”, khu vực chọn sản phẩm sẽ mở khóa ngay trong modal này.
        </div>
      </section>
    </div>
  </BaseModal>
</template>
