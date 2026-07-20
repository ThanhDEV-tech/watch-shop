import axios from 'axios'

export const TOKEN_KEY = 'auth_token'

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

export const getCategories = () => api.get('/categories')
export const getBrands = () => api.get('/brands')
export const getCollections = () => api.get('/collections')
export const getShippingZones = () => api.get('/shipping-zones')
export const getProducts = (params = {}) => api.get('/products', { params })
export const getProductBySlug = (slug) => api.get(`/products/${slug}`)
export const getPublicStats = () => api.get('/stats/public')
export const forgotPassword = (payload) => api.post('/auth/forgot-password', payload)
export const resetPassword = (payload) => api.post('/auth/reset-password', payload)
export const getCart = () => api.get('/cart')
export const addToCart = (productVariantId, quantity = 1) => api.post('/cart/items', {
  product_variant_id: productVariantId,
  quantity,
})
export const removeFromCart = (itemId) => api.delete(`/cart/items/${itemId}`)
export const updateCartItem = (itemId, quantity) => api.patch(`/cart/items/${itemId}`, { quantity })
export const checkout = (payload) => api.post('/checkout', payload)
export const createVnpayPayment = (orderId) => api.post('/payment/vnpay/create', { order_id: orderId })
export const getVnpayReturn = (params) => api.get('/payment/vnpay/return', { params })
export const getMyOrders = (params = {}) => api.get('/my-orders', { params })
export const sendAiChat = (payload) => api.post('/ai/chat', payload)
export const getProductReviews = (productId, params = {}) => api.get(`/products/${productId}/reviews`, { params })
export const submitProductReview = (productId, payload) => api.post(`/products/${productId}/reviews`, payload)
export const getAdminDashboardStats = () => api.get('/admin/dashboard/stats')
export const getAdminUsers = (params = {}) => api.get('/admin/users', { params })
export const toggleAdminUserActive = (userId) => api.patch(`/admin/users/${userId}/toggle-active`)
export const getAdminOrders = (params = {}) => api.get('/admin/orders', { params })
export const getAdminOrder = (orderId) => api.get(`/admin/orders/${orderId}`)
export const markAdminOrderAsPaid = (orderId) => api.post(`/admin/orders/${orderId}/mark-as-paid`)
export const markAdminOrderAsRefunded = (orderId, payload = {}) => api.post(`/admin/orders/${orderId}/mark-as-refunded`, payload)
export const getAdminVnpayTransactions = (params = {}) => api.get('/admin/vnpay-transactions', { params })
export const getAdminStockMovements = (params = {}) => api.get('/admin/stock-movements', { params })
export const getAdminProducts = (params = {}) => api.get('/admin/products', { params })
export const getAdminProduct = (productId) => api.get(`/admin/products/${productId}`)
export const createProduct = (payload) => api.post('/admin/products', payload)
export const updateProduct = (productId, payload) => api.put(`/admin/products/${productId}`, payload)
export const deleteProduct = (productId) => api.delete(`/admin/products/${productId}`)
export const getAdminProductVariants = (params = {}) => api.get('/admin/product-variants', { params })
export const createProductVariant = (payload) => api.post('/admin/product-variants', payload)
export const updateProductVariant = (productVariantId, payload) => api.put(`/admin/product-variants/${productVariantId}`, payload)
export const deleteProductVariant = (productVariantId) => api.delete(`/admin/product-variants/${productVariantId}`)
export const getAdminShippingZones = (params = {}) => api.get('/admin/shipping-zones', { params })
export const createAdminShippingZone = (payload) => api.post('/admin/shipping-zones', payload)
export const updateAdminShippingZone = (shippingZoneId, payload) => api.put(`/admin/shipping-zones/${shippingZoneId}`, payload)
export const toggleAdminShippingZoneActive = (shippingZoneId) => api.patch(`/admin/shipping-zones/${shippingZoneId}/toggle-active`)
export const deleteAdminShippingZone = (shippingZoneId) => api.delete(`/admin/shipping-zones/${shippingZoneId}`)
export const getAdminBrands = (params = {}) => api.get('/admin/brands', { params })
export const createBrand = (payload) => api.post('/admin/brands', payload)
export const updateBrand = (brandId, payload) => api.put(`/admin/brands/${brandId}`, payload)
export const toggleBrandActive = (brandId) => api.patch(`/admin/brands/${brandId}/toggle-active`)
export const deleteBrand = (brandId) => api.delete(`/admin/brands/${brandId}`)
export const getAdminCollections = (params = {}) => api.get('/admin/collections', { params })
export const createCollection = (payload) => api.post('/admin/collections', payload)
export const updateCollection = (collectionId, payload) => api.put(`/admin/collections/${collectionId}`, payload)
export const deleteCollection = (collectionId) => api.delete(`/admin/collections/${collectionId}`)
export const attachProductToCollection = (collectionId, payload) => api.post(`/admin/collections/${collectionId}/products`, payload)
export const detachProductFromCollection = (collectionId, productId) => api.delete(`/admin/collections/${collectionId}/products/${productId}`)
export const updateCollectionProductOrder = (collectionId, productId, payload) => api.patch(`/admin/collections/${collectionId}/products/${productId}/order`, payload)
export const getAdminCategories = (params = {}) => api.get('/admin/categories', { params })
export const createAdminCategory = (payload) => api.post('/admin/categories', payload)
export const updateAdminCategory = (categoryId, payload) => api.put(`/admin/categories/${categoryId}`, payload)
export const toggleAdminCategoryActive = (categoryId) => api.patch(`/admin/categories/${categoryId}/toggle-active`)
export const deleteAdminCategory = (categoryId) => api.delete(`/admin/categories/${categoryId}`)

export default api
