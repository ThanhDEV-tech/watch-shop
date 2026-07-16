<script setup>
import { onMounted, reactive, ref } from 'vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import {
  createAdminCertification,
  deleteAdminCertification,
  getAdminCertifications,
  regenerateAdminCertificationBadge,
  updateAdminCertification,
} from '../../services/api'

const certifications = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const saving = ref(false)
const regenerating = ref(false)
const deletingId = ref(null)
const error = ref('')
const modalError = ref('')
const showForm = ref(false)
const editingCertification = ref(null)
const filters = reactive({ search: '', page: 1 })
const form = reactive({
  name: '',
  provider: '',
  description: '',
  icon: 'verified',
  accent_color: '#FF6B4A',
  exam_info: '',
  external_link: '',
  badge_image_url: '',
})

const resetForm = (certification = null) => {
  form.name = certification?.name ?? ''
  form.provider = certification?.provider ?? ''
  form.description = certification?.description ?? ''
  form.icon = certification?.icon ?? 'verified'
  form.accent_color = certification?.accent_color ?? '#FF6B4A'
  form.exam_info = certification?.exam_info ?? ''
  form.external_link = certification?.external_link ?? ''
  form.badge_image_url = certification?.badge_image_url ?? ''
}

const fetchCertifications = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getAdminCertifications({ ...filters, per_page: 10 })
    certifications.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách chứng chỉ.'
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  editingCertification.value = null
  modalError.value = ''
  resetForm()
  showForm.value = true
}

const openEdit = (certification) => {
  editingCertification.value = certification
  modalError.value = ''
  resetForm(certification)
  showForm.value = true
}

const formPayload = () => ({
  name: form.name,
  provider: form.provider,
  description: form.description,
  icon: form.icon,
  accent_color: form.accent_color,
  exam_info: form.exam_info,
  external_link: form.external_link || null,
})

const saveCertification = async () => {
  saving.value = true
  modalError.value = ''

  try {
    if (editingCertification.value) {
      const response = await updateAdminCertification(editingCertification.value.id, formPayload())
      editingCertification.value = response.data.data
      resetForm(response.data.data)
    } else {
      await createAdminCertification(formPayload())
    }

    showForm.value = false
    await fetchCertifications()
  } catch (requestError) {
    const validationErrors = requestError.response?.data?.data?.errors
    modalError.value = validationErrors
      ? Object.values(validationErrors).flat()[0]
      : (requestError.response?.data?.message ?? 'Không thể lưu chứng chỉ.')
  } finally {
    saving.value = false
  }
}

const regenerateBadge = async () => {
  if (!editingCertification.value || regenerating.value) return

  regenerating.value = true
  modalError.value = ''

  try {
    const response = await regenerateAdminCertificationBadge(editingCertification.value.id)
    editingCertification.value = response.data.data
    resetForm(response.data.data)
    certifications.value = certifications.value.map((item) => (
      item.id === response.data.data.id ? response.data.data : item
    ))
  } catch (requestError) {
    modalError.value = requestError.response?.data?.message ?? 'Không thể sinh lại badge.'
  } finally {
    regenerating.value = false
  }
}

const removeCertification = async (certification) => {
  if (!window.confirm(`Bạn chắc chắn muốn xóa chứng chỉ “${certification.name}”?`)) return

  deletingId.value = certification.id
  error.value = ''

  try {
    await deleteAdminCertification(certification.id)
    await fetchCertifications()
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể xóa chứng chỉ.'
  } finally {
    deletingId.value = null
  }
}

const applySearch = () => {
  filters.page = 1
  fetchCertifications()
}

const changePage = (page) => {
  filters.page = page
  fetchCertifications()
}

onMounted(fetchCertifications)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="flex w-full min-w-0 flex-wrap items-end justify-between gap-md">
      <div class="w-full min-w-0 sm:w-auto">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Credentials</p>
        <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Quản lý chứng chỉ</h1>
        <p class="mt-xs w-full text-body-sm text-on-surface-variant">Tạo, chỉnh sửa và sinh lại badge cho các chứng chỉ nghề nghiệp.</p>
      </div>
      <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 sm:w-auto" type="button" @click="openCreate">
        Thêm chứng chỉ
      </button>
    </div>

    <form class="mt-lg flex w-full min-w-0 gap-sm rounded-lg border border-surface-variant bg-surface p-md" @submit.prevent="applySearch">
      <input v-model.trim="filters.search" class="w-full min-w-0 flex-1 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Tìm theo tên hoặc provider..." />
      <button class="shrink-0 rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface hover:border-primary" type="submit">Tìm</button>
    </form>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[960px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant">
            <tr>
              <th class="px-md py-sm font-medium">Chứng chỉ</th>
              <th class="px-md py-sm font-medium">Provider</th>
              <th class="px-md py-sm font-medium">Màu</th>
              <th class="px-md py-sm font-medium">Course</th>
              <th class="px-md py-sm text-right font-medium">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="5" class="px-md py-xl text-center text-on-surface-variant">
                <span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span>
                Đang tải chứng chỉ...
              </td>
            </tr>
            <tr v-else-if="!certifications.length">
              <td colspan="5" class="px-md py-xl text-center text-on-surface-variant">Chưa có chứng chỉ phù hợp.</td>
            </tr>
            <tr v-for="certification in certifications" v-else :key="certification.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 items-center gap-sm">
                  <img v-if="certification.badge_image_url" :src="certification.badge_image_url" :alt="certification.name" class="h-12 w-16 shrink-0 rounded object-cover" />
                  <div v-else class="flex h-12 w-16 shrink-0 items-center justify-center rounded bg-surface-container-lowest">
                    <span class="material-symbols-outlined" :style="{ color: certification.accent_color || '#FF6B4A' }">{{ certification.icon || 'verified' }}</span>
                  </div>
                  <div class="w-full min-w-0">
                    <p class="w-full font-medium text-on-surface">{{ certification.name }}</p>
                    <p class="line-clamp-1 w-full text-xs text-on-surface-variant">{{ certification.description || 'Không có mô tả' }}</p>
                  </div>
                </div>
              </td>
              <td class="px-md py-sm font-mono text-xs uppercase text-primary">{{ certification.provider }}</td>
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 items-center gap-xs">
                  <span class="h-4 w-4 shrink-0 rounded-full border border-white/20" :style="{ backgroundColor: certification.accent_color || '#FF6B4A' }"></span>
                  <span class="min-w-0 font-mono text-xs text-on-surface-variant">{{ certification.accent_color }}</span>
                </div>
              </td>
              <td class="px-md py-sm font-mono text-on-surface">{{ certification.courses_count }}</td>
              <td class="px-md py-sm">
                <div class="flex w-full min-w-0 justify-end gap-xs">
                  <button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface hover:border-primary" type="button" @click="openEdit(certification)">Sửa</button>
                  <button class="rounded-lg border border-error/30 px-sm py-2 text-xs text-error hover:bg-error/10 disabled:opacity-50" type="button" :disabled="deletingId === certification.id" @click="removeCertification(certification)">
                    {{ deletingId === certification.id ? 'Đang xóa...' : 'Xóa' }}
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>

    <BaseModal v-if="showForm" max-width="xl" aria-labelledby="certification-form-title" @close="showForm = false">
      <form class="w-full min-w-0 overflow-hidden rounded-xl border border-surface-variant bg-background shadow-2xl" @submit.prevent="saveCertification">
        <header class="flex w-full min-w-0 items-center justify-between gap-md border-b border-surface-variant bg-surface px-md py-sm">
          <div class="w-full min-w-0">
            <p class="w-full font-mono text-[11px] uppercase tracking-widest text-primary">Certification editor</p>
            <h2 id="certification-form-title" class="w-full font-display text-headline-sm font-semibold text-on-surface">
              {{ editingCertification ? 'Sửa chứng chỉ' : 'Thêm chứng chỉ' }}
            </h2>
          </div>
          <button class="material-symbols-outlined shrink-0 p-2 text-on-surface-variant hover:text-on-surface" type="button" @click="showForm = false">close</button>
        </header>

        <div class="max-h-[70vh] w-full min-w-0 space-y-md overflow-y-auto p-md">
          <div class="grid w-full min-w-0 grid-cols-1 gap-md sm:grid-cols-2">
            <div class="w-full min-w-0">
              <label class="block w-full text-body-sm text-on-surface" for="certification-name">Tên chứng chỉ</label>
              <input id="certification-name" v-model.trim="form.name" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            </div>

            <div class="w-full min-w-0">
              <label class="block w-full text-body-sm text-on-surface" for="certification-provider">Provider</label>
              <input id="certification-provider" v-model.trim="form.provider" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            </div>
          </div>

          <div class="grid w-full min-w-0 grid-cols-1 gap-md sm:grid-cols-[1fr_auto]">
            <div class="w-full min-w-0">
              <label class="block w-full text-body-sm text-on-surface" for="certification-icon">Material icon</label>
              <input id="certification-icon" v-model.trim="form.icon" maxlength="255" placeholder="verified" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
            </div>

            <div class="w-full min-w-0 sm:w-44">
              <label class="block w-full text-body-sm text-on-surface" for="certification-accent">Màu accent</label>
              <div class="mt-xs flex w-full min-w-0 items-center gap-xs">
                <input id="certification-accent" v-model="form.accent_color" type="color" class="h-12 w-14 shrink-0 cursor-pointer rounded-lg border border-surface-variant bg-surface p-1" />
                <input v-model.trim="form.accent_color" maxlength="7" class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-sm py-3 font-mono text-xs text-on-surface outline-none focus:border-primary" />
              </div>
            </div>
          </div>

          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="certification-description">Mô tả</label>
            <textarea id="certification-description" v-model.trim="form.description" required rows="3" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
          </div>

          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="certification-exam">Thông tin kỳ thi</label>
            <textarea id="certification-exam" v-model.trim="form.exam_info" required rows="5" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
          </div>

          <div class="w-full min-w-0">
            <label class="block w-full text-body-sm text-on-surface" for="certification-link">External link</label>
            <input id="certification-link" v-model.trim="form.external_link" type="url" placeholder="https://..." class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-md py-3 text-on-surface outline-none focus:border-primary" />
          </div>

          <section v-if="editingCertification" class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface p-md">
            <div class="flex w-full min-w-0 flex-col gap-md sm:flex-row sm:items-center sm:justify-between">
              <div class="flex w-full min-w-0 items-center gap-sm">
                <img v-if="form.badge_image_url" :src="form.badge_image_url" :alt="form.name" class="h-16 w-24 shrink-0 rounded object-cover" />
                <div v-else class="flex h-16 w-24 shrink-0 items-center justify-center rounded bg-surface-container-lowest">
                  <span class="material-symbols-outlined text-primary">{{ form.icon || 'verified' }}</span>
                </div>
                <div class="w-full min-w-0">
                  <h3 class="w-full font-display text-body-md font-semibold text-on-surface">Badge image</h3>
                  <p class="mt-xs w-full text-body-sm text-on-surface-variant">Sinh lại badge bằng Pollinations.ai cho riêng chứng chỉ này.</p>
                </div>
              </div>
              <button class="w-full shrink-0 rounded-lg border border-primary px-md py-3 text-body-sm font-medium text-primary hover:bg-primary hover:text-white disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto" type="button" :disabled="regenerating" @click="regenerateBadge">
                {{ regenerating ? 'Đang sinh...' : 'Sinh lại badge' }}
              </button>
            </div>
          </section>

          <p v-if="modalError" class="w-full rounded-lg border border-error/40 bg-error/10 p-sm text-body-sm text-error">{{ modalError }}</p>
        </div>

        <footer class="flex w-full min-w-0 flex-col-reverse gap-sm border-t border-surface-variant p-md sm:flex-row sm:justify-end">
          <button class="w-full rounded-lg border border-surface-variant px-md py-3 text-body-sm text-on-surface sm:w-auto" type="button" :disabled="saving || regenerating" @click="showForm = false">Hủy</button>
          <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container disabled:opacity-50 sm:w-auto" type="submit" :disabled="saving || regenerating">
            {{ saving ? 'Đang lưu...' : 'Lưu chứng chỉ' }}
          </button>
        </footer>
      </form>
    </BaseModal>
  </main>
</template>
