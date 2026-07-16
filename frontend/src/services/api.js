import axios from 'axios'

const TOKEN_KEY = 'auth_token'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL ?? 'http://127.0.0.1:8000/api',
  headers: {
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY)

  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }

  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem(TOKEN_KEY)

      if (window.location.pathname !== '/login') {
        window.location.assign('/login')
      }
    }

    return Promise.reject(error)
  },
)

export const getCourses = (params = {}) => api.get('/courses', { params })

export const getCourseById = (id) => api.get(`/courses/${id}`)

export const getRelatedCourses = (courseId, params = {}) => api.get(`/courses/${courseId}/related`, { params })

export const getPublicInstructorCourses = (instructorId, params = {}) => (
  api.get(`/instructors/${instructorId}/courses`, { params })
)

export const getCategories = () => api.get('/categories')

export const getCertifications = () => api.get('/certifications')

export const getCertificationById = (certificationId, params = {}) => api.get(`/certifications/${certificationId}`, { params })

export const getPublicStats = () => api.get('/stats/public')

export const getFeaturedInstructors = () => api.get('/instructors/featured')

export const forgotPassword = (payload) => api.post('/auth/forgot-password', payload)

export const resetPassword = (payload) => api.post('/auth/reset-password', payload)

export const getCart = () => api.get('/cart')

export const addToCart = (courseId) => api.post('/cart/items', { course_id: courseId })

export const removeFromCart = (itemId) => api.delete(`/cart/items/${itemId}`)

export const checkout = () => api.post('/checkout')

export const createVnpayPayment = (orderId) => api.post('/payment/vnpay/create', {
  order_id: orderId,
})

export const getVnpayReturn = (params) => api.get('/payment/vnpay/return', { params })

export const getMyCourses = () => api.get('/my-courses')

export const getMyOrders = (params = {}) => api.get('/my-orders', { params })

export const getCourseChapters = (courseId) => api.get(`/courses/${courseId}/chapters`)

export const getLesson = (courseId, lessonId) => api.get(`/courses/${courseId}/lessons/${lessonId}`)

export const markLessonComplete = (lessonId) => api.post(`/lessons/${lessonId}/complete`)

export const sendAiChat = (payload) => api.post('/ai/chat', payload)

export const getCourseReviews = (courseId, params = {}) => api.get(`/courses/${courseId}/reviews`, { params })

export const submitCourseReview = (courseId, payload) => api.post(`/courses/${courseId}/reviews`, payload)

export const getLessonComments = (lessonId, params = {}) => api.get(`/lessons/${lessonId}/comments`, { params })

export const createLessonComment = (lessonId, payload) => api.post(`/lessons/${lessonId}/comments`, payload)

export const deleteLessonComment = (commentId) => api.delete(`/comments/${commentId}`)

export const getAdminDashboardStats = () => api.get('/admin/dashboard/stats')

export const getAdminUsers = (params = {}) => api.get('/admin/users', { params })

export const toggleAdminUserActive = (userId) => api.patch(`/admin/users/${userId}/toggle-active`)

export const getAdminOrders = (params = {}) => api.get('/admin/orders', { params })

export const getAdminOrder = (orderId) => api.get(`/admin/orders/${orderId}`)

export const markAdminOrderAsPaid = (orderId) => api.post(`/admin/orders/${orderId}/mark-as-paid`)

export const getAdminVnpayTransactions = (params = {}) => api.get('/admin/vnpay-transactions', { params })

export const getAdminCourses = (params = {}) => api.get('/admin/courses', { params })

export const getAdminCourse = (courseId) => api.get(`/admin/courses/${courseId}`)

export const approveAdminCourse = (courseId) => api.patch(`/admin/courses/${courseId}/approve`)

export const rejectAdminCourse = (courseId, reason) => api.patch(`/admin/courses/${courseId}/reject`, { reason })

export const getAdminCategories = (params = {}) => api.get('/admin/categories', { params })

export const createAdminCategory = (payload) => api.post('/admin/categories', payload)

export const updateAdminCategory = (categoryId, payload) => api.put(`/admin/categories/${categoryId}`, payload)

export const toggleAdminCategoryActive = (categoryId) => api.patch(`/admin/categories/${categoryId}/toggle-active`)

export const deleteAdminCategory = (categoryId) => api.delete(`/admin/categories/${categoryId}`)

export const getAdminCertifications = (params = {}) => api.get('/admin/certifications', { params })

export const createAdminCertification = (payload) => api.post('/admin/certifications', payload)

export const updateAdminCertification = (certificationId, payload) => api.put(`/admin/certifications/${certificationId}`, payload)

export const deleteAdminCertification = (certificationId) => api.delete(`/admin/certifications/${certificationId}`)

export const regenerateAdminCertificationBadge = (certificationId) => (
  api.post(`/admin/certifications/${certificationId}/regenerate-badge`)
)

export const getInstructorDashboardStats = () => api.get('/instructor/dashboard/stats')

export const getInstructorCourses = () => api.get('/instructor/courses')

export const createInstructorCourse = (formData) => api.post('/instructor/courses', formData)

export const updateInstructorCourse = (courseId, formData) => {
  formData.set('_method', 'PUT')
  return api.post(`/instructor/courses/${courseId}`, formData)
}

export const deleteInstructorCourse = (courseId) => api.delete(`/instructor/courses/${courseId}`)

export const submitInstructorCourse = (courseId) => api.post(`/instructor/courses/${courseId}/submit`)

export const createInstructorChapter = (courseId, payload) => api.post(`/instructor/courses/${courseId}/chapters`, payload)

export const reorderInstructorChapters = (courseId, items) => api.patch(`/instructor/courses/${courseId}/chapters/reorder`, { items })

export const updateInstructorChapter = (chapterId, payload) => api.put(`/instructor/chapters/${chapterId}`, payload)

export const deleteInstructorChapter = (chapterId) => api.delete(`/instructor/chapters/${chapterId}`)

export const createInstructorLesson = (chapterId, payload) => api.post(`/instructor/chapters/${chapterId}/lessons`, payload)

export const reorderInstructorLessons = (chapterId, items) => api.patch(`/instructor/chapters/${chapterId}/lessons/reorder`, { items })

export const updateInstructorLesson = (lessonId, payload) => api.put(`/instructor/lessons/${lessonId}`, payload)

export const deleteInstructorLesson = (lessonId) => api.delete(`/instructor/lessons/${lessonId}`)

export const getInstructorCourseStudents = (courseId, params = {}) => (
  api.get(`/instructor/courses/${courseId}/students`, { params })
)

export { TOKEN_KEY }
export default api
