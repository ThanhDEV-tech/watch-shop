import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import { useAuthStore } from '../stores/auth'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'home',
      component: HomeView
    },
    {
      path: '/products',
      name: 'products',
      component: () => import('../views/ProductListingView.vue'),
    },
    {
      path: '/products/:slug',
      name: 'product-detail',
      component: () => import('../views/ProductDetailView.vue'),
    },
    {
      path: '/courses/:id',
      name: 'course-detail',
      component: () => import('../views/CourseDetailView.vue'),
    },
    {
      path: '/category/:slug',
      name: 'category-courses',
      component: () => import('../views/CategoryCoursesView.vue'),
    },
    {
      path: '/search',
      name: 'search-results',
      component: () => import('../views/SearchResultsView.vue'),
    },
    {
      path: '/certifications',
      name: 'certifications',
      component: () => import('../views/CertificationsView.vue'),
    },
    {
      path: '/certifications/:id',
      name: 'certification-detail',
      component: () => import('../views/CertificationDetailView.vue'),
    },
    {
      path: '/cart',
      name: 'cart',
      component: () => import('../views/CartView.vue'),
    },
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue'),
      meta: { hideFooter: true },
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: () => import('../views/ForgotPasswordView.vue'),
      meta: { hideFooter: true },
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: () => import('../views/ResetPasswordView.vue'),
      meta: { hideFooter: true },
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue'),
      meta: { hideFooter: true },
    },
    {
      path: '/checkout',
      name: 'checkout',
      component: () => import('../views/CheckoutView.vue'),
      meta: { hideFooter: true },
    },
    {
      path: '/checkout/vnpay-return',
      name: 'vnpay-return',
      component: () => import('../views/VnpayReturnView.vue'),
    },
    {
      path: '/my-courses',
      name: 'my-courses',
      component: () => import('../views/MyCoursesView.vue'),
      meta: { hideFooter: true },
    },
    {
      path: '/my-orders',
      name: 'my-orders',
      component: () => import('../views/MyOrdersView.vue'),
      meta: { hideFooter: true, requiresAuth: true },
    },
    {
      path: '/orders',
      name: 'orders',
      component: () => import('../views/MyOrdersView.vue'),
      meta: { hideFooter: true, requiresAuth: true },
    },
    {
      path: '/account/orders',
      name: 'account-orders',
      component: () => import('../views/MyOrdersView.vue'),
      meta: { hideFooter: true, requiresAuth: true },
    },
    {
      path: '/profile',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { hideFooter: true, requiresAuth: true },
    },
    {
      path: '/learn/:courseId/:lessonId',
      name: 'lesson-player',
      component: () => import('../views/LessonPlayerView.vue'),
      meta: { hideFooter: true },
    },
    {
      path: '/instructor/courses/:id/preview',
      name: 'instructor-course-preview',
      component: () => import('../views/CourseDetailView.vue'),
      meta: { hideFooter: true, requiresAuth: true, roles: ['instructor', 'admin'] },
    },
    {
      path: '/admin',
      component: () => import('../layouts/DashboardLayout.vue'),
      meta: { dashboardLayout: true, hideFooter: true, requiresAuth: true, roles: ['admin'] },
      children: [
        { path: '', redirect: '/admin/dashboard' },
        { path: 'dashboard', name: 'admin-dashboard', component: () => import('../views/admin/AdminDashboardView.vue') },
        { path: 'products', name: 'admin-products', component: () => import('../views/admin/AdminPlaceholderView.vue'), props: { title: 'Catalog sản phẩm', eyebrow: 'Catalog', description: 'Quản lý sản phẩm đồng hồ trong admin API.' } },
        { path: 'product-variants', name: 'admin-product-variants', component: () => import('../views/admin/AdminPlaceholderView.vue'), props: { title: 'Variant/SKU', eyebrow: 'SKU', description: 'Quản lý biến thể, SKU, giá và tồn kho.' } },
        { path: 'brands', name: 'admin-brands', component: () => import('../views/admin/AdminPlaceholderView.vue'), props: { title: 'Brand', eyebrow: 'Catalog', description: 'Quản lý thương hiệu đồng hồ.' } },
        { path: 'categories', name: 'admin-categories', component: () => import('../views/admin/AdminCategoriesView.vue') },
        { path: 'collections', name: 'admin-collections', component: () => import('../views/admin/AdminPlaceholderView.vue'), props: { title: 'Collection', eyebrow: 'Marketing', description: 'Quản lý nhóm campaign và landing collection.' } },
        { path: 'shipping-zones', name: 'admin-shipping-zones', component: () => import('../views/admin/AdminPlaceholderView.vue'), props: { title: 'Shipping Zone', eyebrow: 'Checkout', description: 'Quản lý vùng giao hàng và phí vận chuyển.' } },
        { path: 'orders', name: 'admin-orders', component: () => import('../views/admin/OrdersManagementView.vue') },
        { path: 'vnpay-transactions', name: 'admin-vnpay-transactions', component: () => import('../views/admin/VnpayTransactionsView.vue') },
        { path: 'stock-movements', name: 'admin-stock-movements', component: () => import('../views/admin/StockMovementsView.vue') },
        { path: 'users', name: 'admin-users', component: () => import('../views/admin/UsersManagementView.vue') },
      ],
    },
    {
      path: '/instructor',
      component: () => import('../layouts/DashboardLayout.vue'),
      meta: { dashboardLayout: true, hideFooter: true, requiresAuth: true, roles: ['instructor'] },
      children: [
        { path: '', redirect: '/instructor/dashboard' },
        { path: 'dashboard', name: 'instructor-dashboard', component: () => import('../views/instructor/InstructorDashboardView.vue') },
        { path: 'courses', name: 'instructor-courses', component: () => import('../views/instructor/InstructorCoursesView.vue') },
        { path: 'courses/create', name: 'instructor-course-create', component: () => import('../views/instructor/CourseFormView.vue') },
        { path: 'courses/:id/edit', name: 'instructor-course-edit', component: () => import('../views/instructor/CourseFormView.vue') },
        { path: 'courses/:id/curriculum', name: 'instructor-course-curriculum', component: () => import('../views/instructor/CourseCurriculumManageView.vue') },
        { path: 'courses/:id/students', name: 'instructor-course-students', component: () => import('../views/instructor/CourseStudentsView.vue') },
      ],
    },
  ],
})

router.beforeEach(async (to) => {
  if (!to.meta.requiresAuth) return true

  const authStore = useAuthStore()

  if (!authStore.token) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (!authStore.user) {
    await authStore.fetchMe()
  }

  if (!authStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  const allowedRoles = to.meta.roles ?? []
  const currentRole = authStore.user?.role?.name

  if (allowedRoles.length && !allowedRoles.includes(currentRole)) {
    return { name: 'home' }
  }

  return true
})

export default router
