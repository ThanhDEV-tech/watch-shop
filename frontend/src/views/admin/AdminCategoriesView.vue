<script setup>
import { onMounted, reactive, ref } from 'vue'
import CategoryFormModal from '../../components/dashboard/CategoryFormModal.vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import { createAdminCategory, deleteAdminCategory, getAdminCategories, toggleAdminCategoryActive, updateAdminCategory } from '../../api/axios'

const categories = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const saving = ref(false)
const deletingId = ref(null)
const togglingId = ref(null)
const error = ref('')
const modalError = ref('')
const editingCategory = ref(null)
const showForm = ref(false)
const filters = reactive({ search: '', page: 1 })

const fetchCategories = async () => {
  loading.value = true
  error.value = ''
  try {
    const response = await getAdminCategories({ ...filters, per_page: 10 })
    categories.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách danh mục.'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingCategory.value = null
  modalError.value = ''
  showForm.value = true
}

const openEdit = (category) => {
  editingCategory.value = category
  modalError.value = ''
  showForm.value = true
}

const saveCategory = async (payload) => {
  saving.value = true
  modalError.value = ''
  try {
    if (editingCategory.value) await updateAdminCategory(editingCategory.value.id, payload)
    else await createAdminCategory(payload)
    showForm.value = false
    await fetchCategories()
  } catch (requestError) {
    const validationErrors = requestError.response?.data?.data?.errors
    modalError.value = validationErrors ? Object.values(validationErrors).flat()[0] : (requestError.response?.data?.message ?? 'Không thể lưu danh mục.')
  } finally {
    saving.value = false
  }
}

const toggleCategoryStatus = async (category) => {
  if (togglingId.value === category.id) return

  togglingId.value = category.id
  error.value = ''
  const previousState = category.is_active

  categories.value = categories.value.map((item) => item.id === category.id ? { ...item, is_active: !previousState } : item)

  try {
    const response = await toggleAdminCategoryActive(category.id)
    categories.value = categories.value.map((item) => item.id === category.id ? response.data.data : item)
  } catch (requestError) {
    categories.value = categories.value.map((item) => item.id === category.id ? { ...item, is_active: previousState } : item)
    error.value = requestError.response?.data?.message ?? 'Không thể cập nhật trạng thái danh mục.'
  } finally {
    togglingId.value = null
  }
}

const removeCategory = async (category) => {
  if (!window.confirm(`Bạn chắc chắn muốn xóa danh mục “${category.name}”?`)) return
  deletingId.value = category.id
  error.value = ''
  try {
    await deleteAdminCategory(category.id)
    await fetchCategories()
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể xóa danh mục.'
  } finally {
    deletingId.value = null
  }
}

const applySearch = () => { filters.page = 1; fetchCategories() }
const changePage = (page) => { filters.page = page; fetchCategories() }
onMounted(fetchCategories)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto"><p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Taxonomy</p><h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Quản lý danh mục</h1><p class="mt-xs w-full text-body-sm text-on-surface-variant">Tổ chức category và kiểm soát trạng thái hiển thị.</p></div>
      <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 sm:w-auto" type="button" @click="openCreate">Thêm danh mục</button>
    </div>

    <form class="mt-lg flex w-full min-w-0 gap-sm rounded-lg border border-surface-variant bg-surface p-md" @submit.prevent="applySearch"><input v-model.trim="filters.search" class="w-full min-w-0 flex-1 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Tìm theo tên hoặc slug..." /><button class="shrink-0 rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary" type="submit">Tìm</button></form>
    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto"><table class="w-full min-w-[820px] text-left text-body-sm"><thead class="bg-surface-container-lowest text-on-surface-variant"><tr><th class="px-md py-sm font-medium">Danh mục</th><th class="px-md py-sm font-medium">Slug</th><th class="px-md py-sm font-medium">Số course</th><th class="px-md py-sm font-medium">Trạng thái</th><th class="px-md py-sm text-right font-medium">Thao tác</th></tr></thead><tbody>
        <tr v-if="loading"><td colspan="5" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải danh mục...</td></tr>
        <tr v-else-if="!categories.length"><td colspan="5" class="px-md py-xl text-center text-on-surface-variant">Chưa có danh mục phù hợp.</td></tr>
        <tr v-for="category in categories" v-else :key="category.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40"><td class="px-md py-sm"><div class="flex w-full min-w-0 items-center gap-sm"><span class="material-symbols-outlined shrink-0 rounded bg-primary/10 p-2 text-primary">{{ category.icon || 'category' }}</span><div class="w-full min-w-0"><p class="w-full font-medium text-on-surface">{{ category.name }}</p><p class="line-clamp-1 w-full text-xs text-on-surface-variant">{{ category.description || 'Không có mô tả' }}</p></div></div></td><td class="px-md py-sm font-mono text-xs text-on-surface-variant">{{ category.slug }}</td><td class="px-md py-sm font-mono text-on-surface">{{ category.courses_count }}</td><td class="px-md py-sm"><div class="flex items-center gap-sm"><button class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-primary/40 disabled:cursor-not-allowed" :class="category.is_active ? 'bg-[var(--accent-success)]' : 'bg-surface-container-highest'" type="button" :disabled="togglingId === category.id" :aria-pressed="category.is_active" :aria-label="category.is_active ? `Tắt danh mục ${category.name}` : `Bật danh mục ${category.name}`" @click="toggleCategoryStatus(category)"><span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform" :class="category.is_active ? 'translate-x-5' : 'translate-x-0'"></span></button><span class="text-xs" :class="category.is_active ? 'text-[var(--accent-success)]' : 'text-on-surface-variant'">{{ category.is_active ? 'Active' : 'Inactive' }}</span></div></td><td class="px-md py-sm"><div class="flex w-full min-w-0 justify-end gap-xs"><button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="openEdit(category)">Sửa</button><button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10 disabled:opacity-50" type="button" :disabled="deletingId === category.id" @click="removeCategory(category)">{{ deletingId === category.id ? 'Đang xóa...' : 'Xóa' }}</button></div></td></tr>
      </tbody></table></div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>

    <CategoryFormModal v-if="showForm" :category="editingCategory" :loading="saving" :error="modalError" @close="showForm = false" @submit="saveCategory" />
  </main>
</template>
