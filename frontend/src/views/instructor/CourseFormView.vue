<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  createInstructorCourse,
  getCategories,
  getCertifications,
  getInstructorCourses,
  updateInstructorCourse,
} from '../../services/api'

const route = useRoute()
const router = useRouter()

const isEditMode = computed(() => Boolean(route.params.id))
const categories = ref([])
const certifications = ref([])
const loading = ref(true)
const submitting = ref(false)
const error = ref('')
const fieldErrors = ref({})
const thumbnailFile = ref(null)
const thumbnailPreview = ref('')
let objectUrl = ''

const form = reactive({
  title: '',
  description: '',
  content: '',
  requirements: '',
  category_id: '',
  price: '',
  discount_price: '',
  level: 'beginner',
  certification_ids: [],
})

const fieldError = (field) => fieldErrors.value[field]?.[0] ?? ''

const loadData = async () => {
  loading.value = true
  error.value = ''

  try {
    const [categoriesResponse, certificationsResponse] = await Promise.all([
      getCategories(),
      getCertifications(),
    ])

    categories.value = categoriesResponse.data.data ?? []
    certifications.value = certificationsResponse.data.data ?? []

    if (isEditMode.value) {
      const coursesResponse = await getInstructorCourses()
      const course = (coursesResponse.data.data ?? []).find((item) => item.id === Number(route.params.id))

      if (!course) {
        throw new Error('Không tìm thấy khóa học hoặc bạn không phải chủ sở hữu.')
      }

      form.title = course.title ?? ''
      form.description = course.description ?? ''
      form.content = course.content ?? ''
      form.requirements = Array.isArray(course.requirements) ? course.requirements.join('\n') : ''
      form.category_id = course.category?.id ?? ''
      form.price = course.price ?? ''
      form.discount_price = course.discount_price ?? ''
      form.level = course.level ?? 'beginner'
      form.certification_ids = (course.certifications ?? []).map((certification) => certification.id)
      thumbnailPreview.value = course.thumbnail_url ?? ''
    }
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? requestError.message ?? 'Không thể tải dữ liệu form.'
  } finally {
    loading.value = false
  }
}

const selectThumbnail = (event) => {
  const file = event.target.files?.[0]
  fieldErrors.value.thumbnail = []
  if (!file) return

  const allowedTypes = ['image/jpeg', 'image/png', 'image/webp']

  if (!allowedTypes.includes(file.type)) {
    fieldErrors.value.thumbnail = ['Ảnh phải có định dạng JPEG, PNG hoặc WebP.']
    event.target.value = ''
    return
  }

  if (file.size > 2 * 1024 * 1024) {
    fieldErrors.value.thumbnail = ['Kích thước ảnh không được vượt quá 2MB.']
    event.target.value = ''
    return
  }

  if (objectUrl) URL.revokeObjectURL(objectUrl)
  objectUrl = URL.createObjectURL(file)
  thumbnailFile.value = file
  thumbnailPreview.value = objectUrl
}

const submit = async () => {
  submitting.value = true
  error.value = ''
  fieldErrors.value = {}

  const payload = new FormData()
  payload.append('title', form.title)
  payload.append('description', form.description)
  payload.append('content', form.content)
  payload.append('requirements', form.requirements)
  payload.append('category_id', String(form.category_id))
  payload.append('price', String(form.price))
  payload.append('discount_price', form.discount_price === '' ? '' : String(form.discount_price))
  payload.append('level', form.level)
  payload.append('sync_certifications', '1')
  form.certification_ids.forEach((certificationId) => {
    payload.append('certification_ids[]', String(certificationId))
  })

  if (thumbnailFile.value) {
    payload.append('thumbnail', thumbnailFile.value)
  }

  try {
    const response = isEditMode.value
      ? await updateInstructorCourse(route.params.id, payload)
      : await createInstructorCourse(payload)
    const course = response.data.data
    const notice = course.status === 'pending'
      ? 'Đã cập nhật khóa học và chuyển về trạng thái pending để admin duyệt lại.'
      : 'Đã lưu khóa học dạng draft. Hãy thêm chapter và lesson trước khi gửi duyệt.'

    await router.push({ path: '/instructor/courses', query: { notice } })
  } catch (requestError) {
    fieldErrors.value = requestError.response?.data?.data?.errors ?? {}
    error.value = requestError.response?.data?.message ?? 'Không thể lưu khóa học.'
  } finally {
    submitting.value = false
  }
}

onMounted(loadData)

onBeforeUnmount(() => {
  if (objectUrl) URL.revokeObjectURL(objectUrl)
})
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-5xl p-margin-mobile md:p-gutter lg:p-lg">
    <RouterLink to="/instructor/courses" class="inline-flex items-center gap-xs text-body-sm text-primary hover:underline">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span>
      Khóa học của tôi
    </RouterLink>

    <div class="mt-md w-full min-w-0">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Course editor</p>
      <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">{{ isEditMode ? 'Sửa khóa học' : 'Tạo khóa học mới' }}</h1>
      <p class="mt-xs w-full text-body-sm text-on-surface-variant">Khóa học cần có ít nhất một chapter và lesson trước khi gửi admin duyệt.</p>
    </div>

    <div v-if="loading" class="mt-lg h-[36rem] w-full animate-pulse rounded-lg border border-surface-variant bg-surface"></div>

    <form v-else class="mt-lg w-full min-w-0 space-y-md rounded-lg border border-surface-variant bg-surface p-md md:p-lg" enctype="multipart/form-data" @submit.prevent="submit">
      <p v-if="error" class="w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

      <div class="w-full min-w-0">
        <label class="block w-full text-body-sm font-medium text-on-surface" for="course-title">Tiêu đề</label>
        <input id="course-title" v-model.trim="form.title" required maxlength="255" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary" />
        <p v-if="fieldError('title')" class="mt-xs w-full text-xs text-error">{{ fieldError('title') }}</p>
      </div>

      <div class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2">
        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm font-medium text-on-surface" for="course-category">Danh mục</label>
          <select id="course-category" v-model="form.category_id" required class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary">
            <option disabled value="">Chọn danh mục</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
          </select>
          <p v-if="fieldError('category_id')" class="mt-xs w-full text-xs text-error">{{ fieldError('category_id') }}</p>
        </div>

        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm font-medium text-on-surface" for="course-level">Trình độ</label>
          <select id="course-level" v-model="form.level" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary">
            <option value="beginner">Beginner</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
          </select>
          <p v-if="fieldError('level')" class="mt-xs w-full text-xs text-error">{{ fieldError('level') }}</p>
        </div>
      </div>

      <div class="w-full min-w-0">
        <label class="block w-full text-body-sm font-medium text-on-surface">Chứng chỉ liên quan</label>
        <div class="mt-xs grid w-full min-w-0 grid-cols-1 gap-sm rounded-lg border border-surface-variant bg-background p-sm sm:grid-cols-2">
          <label v-for="certification in certifications" :key="certification.id" class="flex w-full min-w-0 cursor-pointer items-start gap-sm rounded-lg px-sm py-2 hover:bg-surface">
            <input v-model="form.certification_ids" class="mt-1 shrink-0 accent-primary" type="checkbox" :value="certification.id" />
            <span class="w-full min-w-0">
              <span class="block w-full truncate font-display text-body-sm font-semibold text-on-surface">{{ certification.name }}</span>
              <span class="block w-full font-mono text-label-mono uppercase tracking-wider text-primary">{{ certification.provider }}</span>
            </span>
          </label>
        </div>
        <p class="mt-xs w-full text-xs text-on-surface-variant">Chọn chứng chỉ mà khóa học này hỗ trợ ôn thi hoặc chuẩn bị kỹ năng.</p>
        <p v-if="fieldError('certification_ids')" class="mt-xs w-full text-xs text-error">{{ fieldError('certification_ids') }}</p>
      </div>

      <div class="w-full min-w-0">
        <label class="block w-full text-body-sm font-medium text-on-surface" for="course-description">Mô tả ngắn</label>
        <textarea id="course-description" v-model="form.description" rows="4" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
        <p v-if="fieldError('description')" class="mt-xs w-full text-xs text-error">{{ fieldError('description') }}</p>
      </div>

      <div class="w-full min-w-0">
        <label class="block w-full text-body-sm font-medium text-on-surface" for="course-content">Nội dung chi tiết</label>
        <textarea id="course-content" v-model="form.content" rows="10" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary"></textarea>
        <p v-if="fieldError('content')" class="mt-xs w-full text-xs text-error">{{ fieldError('content') }}</p>
      </div>

      <div class="w-full min-w-0">
        <label class="block w-full text-body-sm font-medium text-on-surface" for="course-requirements">Yêu cầu trước khi học</label>
        <textarea
          id="course-requirements"
          v-model="form.requirements"
          rows="5"
          class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary"
          placeholder="Mỗi dòng là một yêu cầu, ví dụ: Biết JavaScript cơ bản"
        ></textarea>
        <p class="mt-xs w-full text-xs text-on-surface-variant">Nhập mỗi yêu cầu trên một dòng để hiển thị thành danh sách trên trang chi tiết khóa học.</p>
        <p v-if="fieldError('requirements')" class="mt-xs w-full text-xs text-error">{{ fieldError('requirements') }}</p>
      </div>

      <div class="grid w-full min-w-0 grid-cols-1 gap-md md:grid-cols-2">
        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm font-medium text-on-surface" for="course-price">Giá</label>
          <input id="course-price" v-model.number="form.price" required min="0" max="50000000" step="1000" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary" />
          <p v-if="fieldError('price')" class="mt-xs w-full text-xs text-error">{{ fieldError('price') }}</p>
        </div>

        <div class="w-full min-w-0">
          <label class="block w-full text-body-sm font-medium text-on-surface" for="course-discount">Giá khuyến mãi</label>
          <input id="course-discount" v-model.number="form.discount_price" min="0" max="50000000" step="1000" type="number" class="mt-xs w-full min-w-0 rounded-lg border border-surface-variant bg-background px-md py-3 text-on-surface outline-none focus:border-primary" />
          <p v-if="fieldError('discount_price')" class="mt-xs w-full text-xs text-error">{{ fieldError('discount_price') }}</p>
        </div>
      </div>

      <div class="w-full min-w-0">
        <label class="block w-full text-body-sm font-medium text-on-surface" for="course-thumbnail">Thumbnail</label>
        <input id="course-thumbnail" type="file" accept="image/jpeg,image/png,image/webp" class="mt-xs block w-full min-w-0 rounded-lg border border-surface-variant bg-background p-sm text-body-sm text-on-surface file:mr-sm file:rounded file:border-0 file:bg-primary-container file:px-sm file:py-2 file:text-on-primary-container" @change="selectThumbnail" />
        <p class="mt-xs w-full text-xs text-on-surface-variant">JPEG, PNG hoặc WebP, tối đa 2MB.</p>
        <p v-if="fieldError('thumbnail')" class="mt-xs w-full text-xs text-error">{{ fieldError('thumbnail') }}</p>
        <img v-if="thumbnailPreview" :src="thumbnailPreview" alt="Thumbnail preview" class="mt-sm aspect-video w-full max-w-[28rem] rounded-lg border border-surface-variant object-cover" />
      </div>

      <div class="flex w-full min-w-0 flex-col-reverse gap-sm border-t border-surface-variant pt-md sm:flex-row sm:justify-end">
        <RouterLink to="/instructor/courses" class="w-full rounded-lg border border-surface-variant px-md py-3 text-center text-body-sm text-on-surface sm:w-auto">Hủy</RouterLink>
        <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container hover:opacity-90 disabled:opacity-50 sm:w-auto" type="submit" :disabled="submitting">
          {{ submitting ? 'Đang lưu...' : (isEditMode ? 'Lưu thay đổi' : 'Tạo khóa học') }}
        </button>
      </div>
    </form>
  </main>
</template>
