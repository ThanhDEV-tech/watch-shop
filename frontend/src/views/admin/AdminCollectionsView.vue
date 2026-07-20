<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import CollectionFormModal from '../../components/dashboard/CollectionFormModal.vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import { createCollection, deleteCollection, getAdminCollections, updateCollection } from '../../api/axios'

const collections = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const saving = ref(false)
const deletingId = ref(null)
const error = ref('')
const modalError = ref('')
const editingCollection = ref(null)
const showForm = ref(false)
const filters = reactive({ search: '', page: 1 })
const today = computed(() => new Date().toISOString().slice(0, 10))

const extractError = (requestError, fallback) => {
  const validationErrors = requestError.response?.data?.data?.errors
  return validationErrors ? Object.values(validationErrors).flat()[0] : (requestError.response?.data?.message ?? fallback)
}

const collectionStatus = (collection) => {
  if (collection.start_date && collection.start_date > today.value) return { label: 'Sắp diễn ra', tone: 'pending' }
  if (collection.end_date && collection.end_date < today.value) return { label: 'Đã hết hạn', tone: 'expired' }
  return { label: 'Đang active', tone: 'active' }
}

const fetchCollections = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getAdminCollections({ ...filters, per_page: 10 })
    collections.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = extractError(requestError, 'Không thể tải danh sách bộ sưu tập.')
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingCollection.value = null
  modalError.value = ''
  showForm.value = true
}

const openEdit = (collection) => {
  editingCollection.value = collection
  modalError.value = ''
  showForm.value = true
}

const saveCollection = async (payload) => {
  saving.value = true
  modalError.value = ''

  try {
    const response = editingCollection.value
      ? await updateCollection(editingCollection.value.id, payload)
      : await createCollection(payload)

    editingCollection.value = response.data.data
    await fetchCollections()
  } catch (requestError) {
    modalError.value = extractError(requestError, 'Không thể lưu bộ sưu tập.')
  } finally {
    saving.value = false
  }
}

const syncCollectionFromModal = (collection) => {
  editingCollection.value = collection
  collections.value = collections.value.map((item) => item.id === collection.id ? collection : item)
}

const removeCollection = async (collection) => {
  if (!window.confirm(`Bạn chắc chắn muốn xóa bộ sưu tập “${collection.name}”?`)) return

  deletingId.value = collection.id
  error.value = ''

  try {
    await deleteCollection(collection.id)
    await fetchCollections()
  } catch (requestError) {
    error.value = extractError(requestError, 'Không thể xóa bộ sưu tập.')
  } finally {
    deletingId.value = null
  }
}

const applySearch = () => {
  filters.page = 1
  fetchCollections()
}

const changePage = (page) => {
  filters.page = page
  fetchCollections()
}

onMounted(fetchCollections)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Marketing</p>
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Quản lý bộ sưu tập</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Tạo campaign collection, chọn sản phẩm và sắp xếp thứ tự hiển thị.</p>
      </div>
      <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 sm:w-auto" type="button" @click="openCreate">Thêm bộ sưu tập</button>
    </div>

    <form class="mt-lg flex w-full min-w-0 gap-sm rounded-lg border border-surface-variant bg-surface p-md" @submit.prevent="applySearch">
      <input v-model.trim="filters.search" class="w-full min-w-0 flex-1 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Tìm theo tên hoặc slug..." />
      <button class="shrink-0 rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary" type="submit">Tìm</button>
    </form>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[1040px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant">
            <tr>
              <th class="px-md py-sm font-medium">Bộ sưu tập</th>
              <th class="px-md py-sm font-medium">Slug</th>
              <th class="px-md py-sm font-medium">Ngày bắt đầu</th>
              <th class="px-md py-sm font-medium">Ngày kết thúc</th>
              <th class="px-md py-sm font-medium">Trạng thái</th>
              <th class="px-md py-sm font-medium">Số sản phẩm</th>
              <th class="px-md py-sm text-right font-medium">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải bộ sưu tập...</td>
            </tr>
            <tr v-else-if="!collections.length">
              <td colspan="7" class="px-md py-xl text-center text-on-surface-variant">Chưa có bộ sưu tập phù hợp.</td>
            </tr>
            <tr v-for="collection in collections" v-else :key="collection.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
              <td class="px-md py-sm">
                <div class="w-full min-w-0">
                  <p class="w-full font-medium text-on-surface">{{ collection.name }}</p>
                  <p class="line-clamp-1 w-full text-xs text-on-surface-variant">{{ collection.description || 'Không có mô tả' }}</p>
                </div>
              </td>
              <td class="px-md py-sm font-mono text-xs text-on-surface-variant">{{ collection.slug }}</td>
              <td class="px-md py-sm text-on-surface">{{ collection.start_date || 'Không giới hạn' }}</td>
              <td class="px-md py-sm text-on-surface">{{ collection.end_date || 'Không giới hạn' }}</td>
              <td class="px-md py-sm">
                <span class="rounded-full px-2 py-1 text-xs font-semibold" :class="{
                  'bg-[rgb(34_197_94/0.12)] text-[var(--accent-success)]': collectionStatus(collection).tone === 'active',
                  'bg-[rgb(245_158_11/0.12)] text-[rgb(180_83_9)]': collectionStatus(collection).tone === 'pending',
                  'bg-surface-container-highest text-on-surface-variant': collectionStatus(collection).tone === 'expired',
                }">{{ collectionStatus(collection).label }}</span>
              </td>
              <td class="px-md py-sm font-mono text-on-surface">{{ collection.products_count ?? collection.products?.length ?? 0 }}</td>
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 justify-end gap-xs">
                  <button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="openEdit(collection)">Sửa</button>
                  <button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10 disabled:opacity-50" type="button" :disabled="deletingId === collection.id" @click="removeCollection(collection)">{{ deletingId === collection.id ? 'Đang xóa...' : 'Xóa' }}</button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>

    <CollectionFormModal v-if="showForm" :collection="editingCollection" :loading="saving" :error="modalError" @close="showForm = false" @submit="saveCollection" @changed="syncCollectionFromModal" />
  </main>
</template>
