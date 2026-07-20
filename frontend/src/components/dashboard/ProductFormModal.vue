<script setup>
import { computed, reactive, ref, watch } from 'vue'
import BaseModal from '../ui/BaseModal.vue'
import {
  createProductVariant,
  deleteProductVariant,
  getAdminProductVariants,
  updateProductVariant,
} from '../../api/axios'
import { formatCurrency } from '../../utils/formatCurrency'

const props = defineProps({
  product: { type: Object, default: null },
  brands: { type: Array, default: () => [] },
  categories: { type: Array, default: () => [] },
  collections: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
})

const emit = defineEmits(['close', 'submit', 'variants-changed'])

const statuses = ['draft', 'active', 'inactive']
const genders = [
  { value: 'men', label: 'Nam' },
  { value: 'women', label: 'Nữ' },
  { value: 'unisex', label: 'Unisex' },
]
const colors = ['Đen', 'Bạc', 'Vàng gold', 'Vàng rose', 'Trắng', 'Xanh navy', 'Nâu']
const metalStrapColors = ['Đen', 'Bạc', 'Vàng gold', 'Vàng rose']
const softStrapColors = ['Đen', 'Nâu', 'Xanh navy', 'Trắng']
const dialColors = colors
const movementTypes = ['quartz', 'automatic']

const form = reactive({
  brand_id: '',
  category_id: '',
  collection_ids: [],
  name: '',
  slug: '',
  gender_target: 'unisex',
  description: '',
  content: '',
  thumbnail: '',
  case_material: '',
  strap_material: '',
  glass_material: '',
  water_resistance: '',
  warranty_months: 12,
  warranty_note: '',
  status: 'draft',
  product_images: [],
})

const variantForm = reactive({
  id: null,
  sku: '',
  strap_color: 'Đen',
  dial_color: 'Trắng',
  diameter_mm: 36,
  movement_type: 'quartz',
  price: 0,
  discount_price: '',
  stock_quantity: 0,
  image: '',
  is_active: true,
})

const localError = ref('')
const variantError = ref('')
const variants = ref([])
const variantsLoading = ref(false)
const variantSaving = ref(false)
const deletingVariantId = ref(null)
const togglingVariantId = ref(null)
const slugTouched = ref(false)

const slugify = (value) => value
  .toString()
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()
  .replace(/đ/g, 'd')
  .replace(/[^a-z0-9]+/g, '-')
  .replace(/^-+|-+$/g, '')

const resetVariantForm = () => {
  variantForm.id = null
  variantForm.sku = ''
  variantForm.strap_color = 'Đen'
  variantForm.dial_color = 'Trắng'
  variantForm.diameter_mm = 36
  variantForm.movement_type = 'quartz'
  variantForm.price = 0
  variantForm.discount_price = ''
  variantForm.stock_quantity = 0
  variantForm.image = ''
  variantForm.is_active = true
  variantError.value = ''
}

const normalizedText = (value) => (value ?? '')
  .toString()
  .normalize('NFD')
  .replace(/[\u0300-\u036f]/g, '')
  .toLowerCase()

const allowedStrapColors = computed(() => {
  const material = normalizedText(form.strap_material)
  if (material.includes('kim loai') || material.includes('thep') || material.includes('metal') || material.includes('steel') || material.includes('inox')) return metalStrapColors
  if (material.includes('da') || material.includes('vai') || material.includes('fabric') || material.includes('leather') || material.includes('rubber') || material.includes('cao su')) return softStrapColors
  return colors
})

const variantColorWarning = computed(() => {
  if (allowedStrapColors.value.includes(variantForm.strap_color)) return ''
  return `Cảnh báo: màu dây “${variantForm.strap_color}” có thể không phù hợp với chất liệu dây “${form.strap_material || 'chưa nhập'}”.`
})

const productId = computed(() => props.product?.id ?? null)
const modalTitle = computed(() => props.product ? 'Sửa sản phẩm' : 'Thêm sản phẩm')
const primaryImage = computed(() => form.product_images.find((image) => image.is_primary) ?? form.product_images[0] ?? null)

const normalizeImages = (product) => {
  const source = product?.product_images ?? product?.images ?? []
  return source.map((image, index) => ({
    image_path: image.image_path ?? image.url ?? '',
    alt_text: image.alt_text ?? product?.name ?? '',
    display_order: Number(image.display_order ?? index),
    is_primary: Boolean(image.is_primary),
  }))
}

watch(() => props.product, async (product) => {
  slugTouched.value = Boolean(product)
  localError.value = ''

  form.brand_id = product?.brand_id ?? ''
  form.category_id = product?.category_id ?? ''
  form.collection_ids = (product?.collections ?? []).map((collection) => collection.id)
  form.name = product?.name ?? ''
  form.slug = product?.slug ?? ''
  form.gender_target = product?.gender_target ?? 'unisex'
  form.description = product?.description ?? ''
  form.content = product?.content ?? ''
  form.thumbnail = product?.thumbnail ?? ''
  form.case_material = product?.case_material ?? ''
  form.strap_material = product?.strap_material ?? ''
  form.glass_material = product?.glass_material ?? ''
  form.water_resistance = product?.water_resistance ?? ''
  form.warranty_months = product?.warranty_months ?? 12
  form.warranty_note = product?.warranty_note ?? ''
  form.status = product?.status ?? 'draft'
  form.product_images = normalizeImages(product)

  resetVariantForm()
  variants.value = product?.variants ?? []

  if (product?.id) {
    await fetchVariants()
  }
}, { immediate: true })

watch(() => form.name, (name) => {
  if (!props.product && !slugTouched.value) {
    form.slug = slugify(name)
  }
})

const firstApiError = (requestError, fallback) => {
  const errors = requestError.response?.data?.data?.errors
  return errors ? Object.values(errors).flat()[0] : (requestError.response?.data?.message ?? fallback)
}

const addImage = () => {
  form.product_images.push({
    image_path: '',
    alt_text: form.name,
    display_order: form.product_images.length,
    is_primary: form.product_images.length === 0,
  })
}

const removeImage = (index) => {
  const wasPrimary = form.product_images[index]?.is_primary
  form.product_images.splice(index, 1)
  form.product_images.forEach((image, imageIndex) => {
    image.display_order = imageIndex
  })
  if (wasPrimary && form.product_images.length) {
    form.product_images[0].is_primary = true
  }
}

const markPrimary = (index) => {
  form.product_images.forEach((image, imageIndex) => {
    image.is_primary = imageIndex === index
  })
}

const buildProductPayload = () => ({
  brand_id: Number(form.brand_id),
  category_id: Number(form.category_id),
  collection_ids: form.collection_ids.map(Number),
  name: form.name.trim(),
  slug: form.slug.trim() || undefined,
  gender_target: form.gender_target,
  description: form.description.trim() || null,
  content: form.content.trim() || null,
  thumbnail: form.thumbnail.trim() || primaryImage.value?.image_path || null,
  case_material: form.case_material.trim() || null,
  strap_material: form.strap_material.trim() || null,
  glass_material: form.glass_material.trim() || null,
  water_resistance: form.water_resistance.trim() || null,
  warranty_months: Number(form.warranty_months ?? 0),
  warranty_note: form.warranty_note.trim() || null,
  status: form.status,
  product_images: form.product_images
    .filter((image) => image.image_path.trim())
    .map((image, index) => ({
      image_path: image.image_path.trim(),
      alt_text: image.alt_text.trim() || form.name.trim(),
      display_order: Number(image.display_order ?? index),
      is_primary: Boolean(image.is_primary),
    })),
})

const submitProduct = () => {
  localError.value = ''

  if (!form.brand_id || !form.category_id || !form.name.trim() || !form.gender_target || !form.status) {
    localError.value = 'Vui lòng nhập đầy đủ brand, category, tên, giới tính mục tiêu và trạng thái.'
    return
  }

  emit('submit', buildProductPayload())
}

const fetchVariants = async () => {
  if (!productId.value) return
  variantsLoading.value = true
  variantError.value = ''

  try {
    const response = await getAdminProductVariants({ product_id: productId.value, per_page: 100 })
    variants.value = response.data.data.items ?? []
  } catch (requestError) {
    variantError.value = firstApiError(requestError, 'Không thể tải danh sách variant.')
  } finally {
    variantsLoading.value = false
  }
}

const editVariant = (variant) => {
  variantForm.id = variant.id
  variantForm.sku = variant.sku ?? ''
  variantForm.strap_color = variant.strap_color ?? 'Đen'
  variantForm.dial_color = variant.dial_color ?? 'Trắng'
  variantForm.diameter_mm = variant.diameter_mm ?? 36
  variantForm.movement_type = variant.movement_type ?? 'quartz'
  variantForm.price = Number(variant.price ?? 0)
  variantForm.discount_price = variant.discount_price ?? ''
  variantForm.stock_quantity = Number(variant.stock_quantity ?? 0)
  variantForm.image = variant.image ?? ''
  variantForm.is_active = Boolean(variant.is_active)
  variantError.value = ''
}

const buildVariantPayload = () => ({
  product_id: productId.value,
  sku: variantForm.sku.trim() || null,
  strap_color: variantForm.strap_color,
  dial_color: variantForm.dial_color,
  diameter_mm: Number(variantForm.diameter_mm),
  movement_type: variantForm.movement_type,
  price: Number(variantForm.price ?? 0),
  discount_price: variantForm.discount_price === '' || variantForm.discount_price === null ? null : Number(variantForm.discount_price),
  stock_quantity: Number(variantForm.stock_quantity ?? 0),
  image: variantForm.image.trim() || null,
  is_active: Boolean(variantForm.is_active),
})

const saveVariant = async () => {
  if (!productId.value) {
    variantError.value = 'Hãy lưu sản phẩm trước khi thêm variant.'
    return
  }

  if (Number(variantForm.diameter_mm) < 24 || Number(variantForm.diameter_mm) > 50) {
    variantError.value = 'Đường kính phải nằm trong khoảng 24-50mm.'
    return
  }

  variantSaving.value = true
  variantError.value = ''

  try {
    const payload = buildVariantPayload()
    if (variantForm.id) await updateProductVariant(variantForm.id, payload)
    else await createProductVariant(payload)
    resetVariantForm()
    await fetchVariants()
    emit('variants-changed')
  } catch (requestError) {
    variantError.value = firstApiError(requestError, 'Không thể lưu variant.')
  } finally {
    variantSaving.value = false
  }
}

const toggleVariantActive = async (variant) => {
  if (togglingVariantId.value === variant.id) return
  togglingVariantId.value = variant.id
  variantError.value = ''

  try {
    await updateProductVariant(variant.id, { is_active: !variant.is_active })
    await fetchVariants()
    emit('variants-changed')
  } catch (requestError) {
    variantError.value = firstApiError(requestError, 'Không thể đổi trạng thái variant.')
  } finally {
    togglingVariantId.value = null
  }
}

const removeVariant = async (variant) => {
  if (!window.confirm(`Bạn chắc chắn muốn xóa variant “${variant.sku}”?`)) return
  deletingVariantId.value = variant.id
  variantError.value = ''

  try {
    await deleteProductVariant(variant.id)
    await fetchVariants()
    emit('variants-changed')
  } catch (requestError) {
    variantError.value = firstApiError(requestError, 'Không thể xóa variant.')
  } finally {
    deletingVariantId.value = null
  }
}
</script>

<template>
  <BaseModal max-width="wide" aria-labelledby="product-form-title" :close-on-backdrop="false" @close="emit('close')">
    <form class="max-h-[92vh] w-full min-w-0 overflow-y-auto rounded-xl border border-surface-variant bg-background shadow-2xl" @submit.prevent="submitProduct">
      <header class="sticky top-0 z-10 flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface px-md py-sm">
        <div class="w-full min-w-0">
          <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Product editor</p>
          <h2 id="product-form-title" class="w-full font-display text-headline-sm font-semibold text-on-surface">{{ modalTitle }}</h2>
        </div>
        <button class="material-symbols-outlined shrink-0 p-2 text-on-surface-variant hover:text-on-surface" type="button" @click="emit('close')">close</button>
      </header>

      <div class="grid w-full min-w-0 gap-lg p-md lg:grid-cols-[1.25fr_0.75fr]">
        <section class="w-full min-w-0 space-y-md">
          <div class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2">
            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Brand</span>
              <select v-model="form.brand_id" required class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary">
                <option value="">Chọn brand</option>
                <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.name }}</option>
              </select>
            </label>

            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Category</span>
              <select v-model="form.category_id" required class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary">
                <option value="">Chọn category</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
              </select>
            </label>
          </div>

          <div class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-[1.2fr_0.8fr]">
            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Tên sản phẩm</span>
              <input v-model.trim="form.name" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            </label>

            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Slug</span>
              <input v-model.trim="form.slug" maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 font-mono text-body-sm text-on-surface outline-none focus:border-primary" @input="slugTouched = true" />
            </label>
          </div>

          <div class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-3">
            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Gender target</span>
              <select v-model="form.gender_target" required class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary">
                <option v-for="gender in genders" :key="gender.value" :value="gender.value">{{ gender.label }}</option>
              </select>
            </label>

            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Status</span>
              <select v-model="form.status" required class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary">
                <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
              </select>
            </label>

            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Bảo hành (tháng)</span>
              <input v-model.number="form.warranty_months" min="0" max="240" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            </label>
          </div>

          <label class="block w-full min-w-0">
            <span class="block w-full text-body-sm text-on-surface">Thumbnail URL</span>
            <input v-model.trim="form.thumbnail" maxlength="255" placeholder="https://..." class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </label>

          <label class="block w-full min-w-0">
            <span class="block w-full text-body-sm text-on-surface">Mô tả ngắn</span>
            <textarea v-model.trim="form.description" rows="3" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
          </label>

          <label class="block w-full min-w-0">
            <span class="block w-full text-body-sm text-on-surface">Nội dung chi tiết</span>
            <textarea v-model.trim="form.content" rows="5" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
          </label>

          <div class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2">
            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Vỏ</span>
              <input v-model.trim="form.case_material" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            </label>
            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Dây đeo</span>
              <input v-model.trim="form.strap_material" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            </label>
            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Mặt kính</span>
              <input v-model.trim="form.glass_material" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            </label>
            <label class="block w-full min-w-0">
              <span class="block w-full text-body-sm text-on-surface">Chống nước</span>
              <input v-model.trim="form.water_resistance" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            </label>
          </div>

          <label class="block w-full min-w-0">
            <span class="block w-full text-body-sm text-on-surface">Ghi chú bảo hành</span>
            <textarea v-model.trim="form.warranty_note" rows="2" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
          </label>
        </section>

        <aside class="w-full min-w-0 space-y-md">
          <section class="w-full min-w-0 rounded-xl border border-surface-variant bg-surface p-md">
            <div class="flex w-full min-w-0 items-center justify-between gap-sm">
              <div class="w-full min-w-0">
                <h3 class="w-full font-display text-xl font-semibold text-on-surface">Collections</h3>
                <p class="mt-1 w-full text-xs text-on-surface-variant">Không bắt buộc, dùng cho campaign.</p>
              </div>
            </div>
            <div class="mt-sm max-h-44 w-full min-w-0 space-y-xs overflow-y-auto">
              <label v-for="collection in collections" :key="collection.id" class="flex w-full min-w-0 items-center gap-sm rounded-lg border border-surface-variant bg-background px-sm py-2 text-body-sm text-on-surface">
                <input v-model="form.collection_ids" type="checkbox" class="h-4 w-4 accent-[var(--accent-primary)]" :value="collection.id" />
                <span class="min-w-0 truncate">{{ collection.name }}</span>
              </label>
              <p v-if="!collections.length" class="w-full text-sm text-on-surface-variant">Chưa có collection.</p>
            </div>
          </section>

          <section class="w-full min-w-0 rounded-xl border border-surface-variant bg-surface p-md">
            <div class="flex w-full min-w-0 items-center justify-between gap-sm">
              <div class="w-full min-w-0">
                <h3 class="w-full font-display text-xl font-semibold text-on-surface">Gallery</h3>
                <p class="mt-1 w-full text-xs text-on-surface-variant">Thêm URL ảnh, chọn 1 ảnh primary.</p>
              </div>
              <button class="shrink-0 rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="addImage">Thêm ảnh</button>
            </div>

            <div class="mt-sm w-full min-w-0 space-y-sm">
              <div v-for="(image, index) in form.product_images" :key="index" class="w-full min-w-0 rounded-lg border border-surface-variant bg-background p-sm">
                <div class="flex w-full min-w-0 gap-sm">
                  <img :src="image.image_path || 'https://placehold.co/160x120/F8F5EF/111111?text=Image'" :alt="image.alt_text || form.name" class="h-20 w-24 shrink-0 rounded-lg object-cover" />
                  <div class="w-full min-w-0 space-y-xs">
                    <input v-model.trim="image.image_path" maxlength="255" class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-sm py-2 text-xs text-on-surface outline-none focus:border-primary" placeholder="Image URL" />
                    <input v-model.trim="image.alt_text" class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-sm py-2 text-xs text-on-surface outline-none focus:border-primary" placeholder="Alt text" />
                    <div class="flex w-full min-w-0 items-center gap-xs">
                      <input v-model.number="image.display_order" min="0" type="number" class="w-24 rounded-lg border border-surface-variant bg-surface px-sm py-2 text-xs text-on-surface outline-none focus:border-primary" />
                      <button class="rounded-lg border px-sm py-2 text-xs" :class="image.is_primary ? 'border-primary bg-primary/10 text-primary' : 'border-surface-variant text-on-surface'" type="button" @click="markPrimary(index)">Primary</button>
                      <button class="ml-auto rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10" type="button" @click="removeImage(index)">Xóa</button>
                    </div>
                  </div>
                </div>
              </div>
              <p v-if="!form.product_images.length" class="w-full rounded-lg border border-dashed border-surface-variant p-sm text-sm text-on-surface-variant">Chưa có ảnh gallery.</p>
            </div>
          </section>
        </aside>
      </div>

      <section v-if="productId" class="mx-md mb-md w-auto min-w-0 rounded-xl border border-surface-variant bg-surface p-md">
        <div class="flex w-full min-w-0 flex-wrap items-start justify-between gap-md">
          <div class="w-full min-w-0 sm:w-auto">
            <h3 class="w-full font-display text-2xl font-semibold text-on-surface">Variant/SKU</h3>
            <p class="mt-1 w-full text-sm text-on-surface-variant">Variant luôn thuộc product đang edit. SKU có thể để trống để backend tự sinh.</p>
          </div>
          <button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="resetVariantForm">Form variant mới</button>
        </div>

        <p v-if="variantError" class="mt-sm w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ variantError }}</p>

        <div class="mt-md w-full min-w-0 overflow-x-auto">
          <table class="w-full min-w-[900px] text-left text-xs">
            <thead class="bg-surface-container-lowest text-on-surface-variant">
              <tr>
                <th class="px-sm py-2 font-medium">SKU</th>
                <th class="px-sm py-2 font-medium">Dây / mặt</th>
                <th class="px-sm py-2 font-medium">Size / máy</th>
                <th class="px-sm py-2 text-right font-medium">Giá</th>
                <th class="px-sm py-2 text-right font-medium">Tồn</th>
                <th class="px-sm py-2 font-medium">Active</th>
                <th class="px-sm py-2 text-right font-medium">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="variantsLoading">
                <td colspan="7" class="px-sm py-md text-center text-on-surface-variant">Đang tải variant...</td>
              </tr>
              <tr v-else-if="!variants.length">
                <td colspan="7" class="px-sm py-md text-center text-on-surface-variant">Chưa có variant.</td>
              </tr>
              <tr v-for="variant in variants" v-else :key="variant.id" class="border-t border-surface-variant">
                <td class="px-sm py-2 font-mono text-on-surface">{{ variant.sku }}</td>
                <td class="px-sm py-2 text-on-surface">{{ variant.strap_color }} / {{ variant.dial_color }}</td>
                <td class="px-sm py-2 text-on-surface">{{ variant.diameter_mm }}mm / {{ variant.movement_type }}</td>
                <td class="px-sm py-2 text-right font-mono text-on-surface">
                  <span>{{ formatCurrency(variant.price) }}</span>
                  <span v-if="variant.discount_price" class="ml-1 text-primary">→ {{ formatCurrency(variant.discount_price) }}</span>
                </td>
                <td class="px-sm py-2 text-right font-mono text-on-surface">{{ variant.stock_quantity }}</td>
                <td class="px-sm py-2">
                  <button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:cursor-not-allowed" :class="variant.is_active ? 'bg-[var(--accent-success)]' : 'bg-surface-container-highest'" type="button" :disabled="togglingVariantId === variant.id" :aria-pressed="variant.is_active" @click="toggleVariantActive(variant)">
                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform" :class="variant.is_active ? 'translate-x-5' : 'translate-x-0'"></span>
                  </button>
                </td>
                <td class="px-sm py-2">
                  <div class="flex w-full min-w-0 justify-end gap-xs">
                    <button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="editVariant(variant)">Sửa</button>
                    <button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10 disabled:opacity-50" type="button" :disabled="deletingVariantId === variant.id" @click="removeVariant(variant)">Xóa</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-md grid w-full min-w-0 grid-cols-1 gap-sm md:grid-cols-4">
          <label class="block w-full min-w-0">
            <span class="block w-full text-xs text-on-surface">SKU</span>
            <input v-model.trim="variantForm.sku" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-sm py-2 text-sm text-on-surface outline-none focus:border-primary" placeholder="Để trống để tự sinh" />
          </label>
          <label class="block w-full min-w-0">
            <span class="block w-full text-xs text-on-surface">Màu dây</span>
            <select v-model="variantForm.strap_color" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-sm py-2 text-sm text-on-surface outline-none focus:border-primary">
              <option v-for="color in colors" :key="color" :value="color">{{ color }}</option>
            </select>
          </label>
          <label class="block w-full min-w-0">
            <span class="block w-full text-xs text-on-surface">Màu mặt</span>
            <select v-model="variantForm.dial_color" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-sm py-2 text-sm text-on-surface outline-none focus:border-primary">
              <option v-for="color in dialColors" :key="color" :value="color">{{ color }}</option>
            </select>
          </label>
          <label class="block w-full min-w-0">
            <span class="block w-full text-xs text-on-surface">Đường kính</span>
            <input v-model.number="variantForm.diameter_mm" min="24" max="50" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-sm py-2 text-sm text-on-surface outline-none focus:border-primary" />
          </label>
          <label class="block w-full min-w-0">
            <span class="block w-full text-xs text-on-surface">Bộ máy</span>
            <select v-model="variantForm.movement_type" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-sm py-2 text-sm text-on-surface outline-none focus:border-primary">
              <option v-for="movement in movementTypes" :key="movement" :value="movement">{{ movement }}</option>
            </select>
          </label>
          <label class="block w-full min-w-0">
            <span class="block w-full text-xs text-on-surface">Giá</span>
            <input v-model.number="variantForm.price" min="0" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-sm py-2 text-sm text-on-surface outline-none focus:border-primary" />
          </label>
          <label class="block w-full min-w-0">
            <span class="block w-full text-xs text-on-surface">Giá giảm</span>
            <input v-model.number="variantForm.discount_price" min="0" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-sm py-2 text-sm text-on-surface outline-none focus:border-primary" />
          </label>
          <label class="block w-full min-w-0">
            <span class="block w-full text-xs text-on-surface">Tồn kho</span>
            <input v-model.number="variantForm.stock_quantity" min="0" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-sm py-2 text-sm text-on-surface outline-none focus:border-primary" />
          </label>
        </div>

        <label class="mt-sm block w-full min-w-0">
          <span class="block w-full text-xs text-on-surface">Ảnh riêng variant (optional)</span>
          <input v-model.trim="variantForm.image" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-sm py-2 text-sm text-on-surface outline-none focus:border-primary" placeholder="https://..." />
        </label>

        <div class="mt-sm flex w-full min-w-0 flex-wrap items-center justify-between gap-sm">
          <div class="w-full min-w-0 sm:w-auto">
            <label class="flex w-full min-w-0 items-center gap-sm text-sm text-on-surface">
              <input v-model="variantForm.is_active" type="checkbox" class="h-4 w-4 accent-[var(--accent-primary)]" />
              <span class="min-w-0">Variant active</span>
            </label>
            <p v-if="variantColorWarning" class="mt-xs w-full text-xs text-primary">{{ variantColorWarning }}</p>
            <p v-else class="mt-xs w-full text-xs text-on-surface-variant">Màu dây phù hợp với chất liệu hiện tại.</p>
          </div>
          <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50 sm:w-auto" type="button" :disabled="variantSaving" @click="saveVariant">
            {{ variantSaving ? 'Đang lưu...' : (variantForm.id ? 'Lưu variant' : 'Thêm variant') }}
          </button>
        </div>
      </section>

      <section v-else class="mx-md mb-md w-auto min-w-0 rounded-xl border border-dashed border-surface-variant bg-surface p-md text-sm text-on-surface-variant">
        Lưu product trước, sau đó bạn sẽ thêm variant/SKU ngay trong modal này.
      </section>

      <p v-if="localError || error" class="mx-md mb-md w-auto rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ localError || error }}</p>

      <footer class="sticky bottom-0 flex w-full min-w-0 flex-col-reverse gap-sm border-t border-surface-variant bg-background p-md sm:flex-row sm:justify-end">
        <button class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface sm:w-auto" type="button" :disabled="loading" @click="emit('close')">Đóng</button>
        <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50 sm:w-auto" type="submit" :disabled="loading">
          {{ loading ? 'Đang lưu...' : 'Lưu product' }}
        </button>
      </footer>
    </form>
  </BaseModal>
</template>
