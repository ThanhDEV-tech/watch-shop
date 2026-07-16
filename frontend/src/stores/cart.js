import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import {
  addToCart,
  checkout,
  getCart,
  removeFromCart,
} from '../services/api'

const extractApiError = (requestError) => {
  const errors = requestError.response?.data?.data?.errors
  const firstValidationError = errors
    ? Object.values(errors).flat().find(Boolean)
    : null

  return firstValidationError
    ?? requestError.response?.data?.message
    ?? 'Unable to update your cart.'
}

export const useCartStore = defineStore('cart', () => {
  const items = ref([])
  const totalAmount = ref(0)
  const loading = ref(false)
  const error = ref('')

  const itemCount = computed(() => items.value.length)

  const itemPrice = (item) => Number(
    item.course?.final_price
      ?? item.course?.discount_price
      ?? item.price_snapshot
      ?? item.course?.price
      ?? 0,
  )

  const updateCart = (cart) => {
    items.value = cart?.items ?? []
    totalAmount.value = items.value.reduce((sum, item) => sum + itemPrice(item), 0)
  }

  const clearCart = () => {
    items.value = []
    totalAmount.value = 0
    error.value = ''
  }

  const runCartAction = async (request) => {
    loading.value = true
    error.value = ''

    try {
      const response = await request()
      updateCart(response.data.data)

      return response.data
    } catch (requestError) {
      error.value = extractApiError(requestError)
      throw requestError
    } finally {
      loading.value = false
    }
  }

  const fetchCart = () => runCartAction(getCart)

  const addItem = (courseId) => runCartAction(() => addToCart(courseId))

  const removeItem = (itemId) => runCartAction(() => removeFromCart(itemId))

  const doCheckout = async () => {
    loading.value = true
    error.value = ''

    try {
      const response = await checkout()

      return response.data
    } catch (requestError) {
      error.value = extractApiError(requestError)
      throw requestError
    } finally {
      loading.value = false
    }
  }

  return {
    items,
    totalAmount,
    loading,
    error,
    itemCount,
    fetchCart,
    addItem,
    removeItem,
    doCheckout,
    clearCart,
  }
})
