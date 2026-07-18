<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { getCategories } from '../api/axios'
import { useAuthStore } from '../stores/auth'
import { useCartStore } from '../stores/cart'
import { formatCurrency } from '../utils/formatCurrency'

const router = useRouter()
const authStore = useAuthStore()
const cartStore = useCartStore()
const isLoggingOut = ref(false)

const searchQuery = ref('')
const isCategoriesOpen = ref(false)
const categoriesMenu = ref(null)
const isCartOpen = ref(false)
const cartMenu = ref(null)
const isUserMenuOpen = ref(false)
const userMenu = ref(null)
const categories = ref([])
const isCategoriesLoading = ref(false)
const categoriesError = ref('')

const cartItems = computed(() => cartStore.items)
const cartTotal = computed(() => cartStore.totalAmount)
const currentRole = computed(() => authStore.user?.role?.name ?? '')

const closeCategories = () => {
  isCategoriesOpen.value = false
}

const toggleCategories = () => {
  isCategoriesOpen.value = !isCategoriesOpen.value
}

const fetchCategories = async () => {
  isCategoriesLoading.value = true
  categoriesError.value = ''

  try {
    const response = await getCategories()
    categories.value = response.data.data ?? []
  } catch (error) {
    categoriesError.value = error.response?.data?.message ?? 'Unable to load categories.'
  } finally {
    isCategoriesLoading.value = false
  }
}

const openCart = async () => {
  isCategoriesOpen.value = false
  isUserMenuOpen.value = false
  isCartOpen.value = true

  if (authStore.isAuthenticated) {
    try {
      await cartStore.fetchCart()
    } catch {
      // Keep the existing dropdown state if the network is temporarily unavailable.
    }
  }
}

const closeCart = () => {
  isCartOpen.value = false
}

const openUserMenu = () => {
  isCategoriesOpen.value = false
  isCartOpen.value = false
  isUserMenuOpen.value = true
}

const closeUserMenu = () => {
  isUserMenuOpen.value = false
}

const toggleUserMenu = () => {
  isUserMenuOpen.value = !isUserMenuOpen.value
}

const submitSearch = () => {
  const keyword = searchQuery.value.trim()

  if (!keyword) return

  router.push({ name: 'products', query: { search: keyword } })
}

const handleLogout = async () => {
  isLoggingOut.value = true
  closeUserMenu()

  try {
    await authStore.logout()
    await router.push('/login')
  } finally {
    isLoggingOut.value = false
  }
}

const handleClickOutside = (event) => {
  if (categoriesMenu.value && !categoriesMenu.value.contains(event.target)) {
    closeCategories()
  }

  if (cartMenu.value && !cartMenu.value.contains(event.target)) {
    closeCart()
  }

  if (userMenu.value && !userMenu.value.contains(event.target)) {
    closeUserMenu()
  }
}

onMounted(() => {
  fetchCategories()
  document.addEventListener('pointerdown', handleClickOutside)
})

onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', handleClickOutside)
})
</script>

<template>
  <nav class="bg-background border-b border-surface-variant sticky top-0 z-50 font-body">
    <div class="flex justify-between items-center w-full px-margin-mobile md:px-gutter max-w-container-max mx-auto h-20">
      <div class="flex min-w-0 items-center gap-sm md:gap-lg">
        <RouterLink class="font-display text-headline-sm font-bold text-primary" to="/">Watchora</RouterLink>
        <div
          ref="categoriesMenu"
          class="relative flex min-w-0 items-center"
          @mouseenter="isCategoriesOpen = true"
          @mouseleave="isCategoriesOpen = false"
        >
          <button
            class="flex cursor-pointer items-center gap-1 border-b-2 border-primary pb-1 font-bold text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            type="button"
            aria-haspopup="true"
            aria-controls="categories-dropdown"
            :aria-expanded="isCategoriesOpen"
            @click="toggleCategories"
          >
            Catalog
            <span
              class="material-symbols-outlined text-[18px] transition-transform duration-200"
              :class="{ 'rotate-180': isCategoriesOpen }"
              aria-hidden="true"
            >
              keyboard_arrow_down
            </span>
          </button>

          <Transition name="category-dropdown">
            <div
              v-if="isCategoriesOpen"
              id="categories-dropdown"
              class="absolute left-0 top-[calc(100%+0.75rem)] z-[70] w-72 max-w-[calc(100vw-2rem)] rounded-lg border border-surface-variant bg-surface p-2 shadow-[0_18px_45px_rgba(0,0,0,0.45)]"
            >
              <p v-if="isCategoriesLoading" class="px-4 py-3 text-body-sm text-on-surface-variant">
                Loading categories...
              </p>
              <p v-else-if="categoriesError" class="px-4 py-3 text-body-sm text-error">
                {{ categoriesError }}
              </p>
              <p v-else-if="!categories.length" class="px-4 py-3 text-body-sm text-on-surface-variant">
                No categories yet.
              </p>
              <template v-else>
                <RouterLink
                  v-for="category in categories"
                  :key="category.id ?? category.slug"
                  :to="{ name: 'products', query: { category: category.slug } }"
                  class="block w-full cursor-pointer rounded-md px-4 py-3 text-body-sm font-medium text-on-surface transition-colors hover:bg-surface-container-highest hover:text-primary focus-visible:bg-surface-container-highest focus-visible:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                  @click="closeCategories"
                >
                  {{ category.name }}
                </RouterLink>
              </template>
            </div>
          </Transition>
        </div>
      </div>
      <div class="w-full min-w-[200px] flex-1 max-w-md mx-8 hidden lg:block">
        <div class="relative flex w-full min-w-[200px] items-center">
          <button
            class="material-symbols-outlined absolute left-3 cursor-pointer rounded text-on-surface-variant transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            type="button"
            aria-label="Tìm đồng hồ"
            @click="submitSearch"
          >
            search
          </button>
          <input
            v-model="searchQuery"
            class="w-full bg-surface-container-low border border-surface-variant rounded-lg py-2 pl-10 pr-4 focus:outline-none focus:border-primary-container focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background text-body-sm transition-all text-on-surface"
            placeholder="Tìm đồng hồ, thương hiệu..."
            type="text"
            @keyup.enter="submitSearch"
          />
        </div>
      </div>
      <div class="flex min-w-0 items-center gap-md">
        <div
          ref="cartMenu"
          class="relative flex min-w-0 items-center"
          @mouseenter="openCart"
          @mouseleave="closeCart"
        >
          <RouterLink
            to="/cart"
            class="relative inline-flex cursor-pointer items-center justify-center rounded text-on-surface-variant transition-colors hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            aria-label="Cart"
            aria-haspopup="true"
            aria-controls="cart-dropdown"
            :aria-expanded="isCartOpen"
          >
            <span class="material-symbols-outlined">shopping_cart</span>
            <span
              v-if="cartStore.itemCount > 0"
              class="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1 font-body text-[10px] font-bold leading-none text-white"
            >
              {{ cartStore.itemCount }}
            </span>
          </RouterLink>

          <Transition name="category-dropdown">
            <div
              v-if="isCartOpen"
              id="cart-dropdown"
              class="absolute right-0 top-full z-[70] w-96 max-w-[calc(100vw-2rem)] pt-3"
            >
              <div class="w-full rounded-lg border border-surface-variant bg-surface p-4 shadow-[0_18px_45px_rgba(0,0,0,0.45)]">
                <template v-if="cartStore.itemCount">
                  <div class="w-full space-y-3">
                    <RouterLink
                      v-for="item in cartItems.slice(0, 3)"
                      :key="item.id"
                      :to="`/products/${item.product_variant?.product?.slug ?? ''}`"
                      class="flex w-full min-w-0 cursor-pointer gap-3 rounded-md p-2 transition-colors hover:bg-surface-container-highest focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                      @click="closeCart"
                    >
                      <img
                        :src="item.product_variant?.image || item.product_variant?.product?.thumbnail"
                        :alt="item.product_variant?.product?.name"
                        class="h-14 w-20 shrink-0 rounded-md object-cover"
                      />
                      <div class="min-w-0 flex-1">
                        <p class="line-clamp-2 w-full text-body-sm font-semibold leading-5 text-on-surface">
                          {{ item.product_variant?.product?.name }}
                        </p>
                        <p class="mt-0.5 w-full truncate text-xs text-on-surface-variant">
                          {{ item.product_variant?.strap_color }} / {{ item.product_variant?.dial_color }} · SL {{ item.quantity }}
                        </p>
                        <p class="mt-1 font-mono text-body-sm font-medium text-primary">
                          {{ formatCurrency(item.product_variant?.final_price) }}
                        </p>
                      </div>
                    </RouterLink>
                  </div>

                  <div class="my-4 h-px w-full bg-outline-variant"></div>

                  <div class="mb-4 flex w-full items-center justify-between gap-4">
                    <span class="font-display font-semibold text-on-surface">Total</span>
                    <span class="shrink-0 font-mono text-lg font-semibold text-primary">
                      {{ formatCurrency(cartTotal) }}
                    </span>
                  </div>

                  <RouterLink
                    to="/cart"
                    class="block w-full cursor-pointer rounded-lg bg-primary px-4 py-3 text-center font-display text-button-text font-semibold text-on-primary transition-all hover:opacity-90 active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                    @click="closeCart"
                  >
                    Xem giỏ hàng
                  </RouterLink>
                </template>

                <p v-else class="w-full py-6 text-center text-body-sm text-on-surface-variant">
                  Giỏ hàng đang trống
                </p>
              </div>
            </div>
          </Transition>
        </div>
        <div class="h-6 w-px bg-surface-variant mx-2"></div>
        <template v-if="authStore.isAuthenticated">
          <div
            ref="userMenu"
            class="relative flex min-w-0 items-center"
            @mouseenter="openUserMenu"
            @mouseleave="closeUserMenu"
          >
            <button
              class="flex max-w-52 min-w-0 cursor-pointer items-center gap-1 rounded-lg border border-transparent px-sm py-2 font-display text-button-text text-on-surface transition-colors hover:border-surface-variant hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
              type="button"
              aria-haspopup="true"
              aria-controls="user-dropdown"
              :aria-expanded="isUserMenuOpen"
              @click="toggleUserMenu"
            >
              <img v-if="authStore.user.avatar_url || authStore.user.avatar" :src="authStore.user.avatar_url || authStore.user.avatar" :alt="authStore.user.name" class="h-7 w-7 shrink-0 rounded-full object-cover" />
              <span v-else class="material-symbols-outlined shrink-0 text-[20px] text-primary">account_circle</span>
              <span class="min-w-0 truncate">{{ authStore.user.name }}</span>
              <span
                class="material-symbols-outlined shrink-0 text-[18px] transition-transform duration-200"
                :class="{ 'rotate-180': isUserMenuOpen }"
                aria-hidden="true"
              >
                keyboard_arrow_down
              </span>
            </button>

            <Transition name="category-dropdown">
              <div
                v-if="isUserMenuOpen"
                id="user-dropdown"
                class="before:content-[''] before:absolute before:-top-3 before:left-0 before:h-3 before:w-full absolute right-0 top-[calc(100%+0.75rem)] z-[70] w-64 max-w-[calc(100vw-2rem)] rounded-lg border border-surface-variant bg-surface p-2 shadow-[0_18px_45px_rgba(0,0,0,0.45)]"
              >
                <div class="flex w-full min-w-0 items-center gap-sm px-4 py-3">
                  <img v-if="authStore.user.avatar_url || authStore.user.avatar" :src="authStore.user.avatar_url || authStore.user.avatar" :alt="authStore.user.name" class="h-10 w-10 shrink-0 rounded-full object-cover" />
                  <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/15"><span class="material-symbols-outlined text-primary">person</span></div>
                  <div class="w-full min-w-0"><p class="w-full truncate text-body-sm font-semibold text-on-surface">{{ authStore.user.name }}</p><p class="w-full truncate text-xs text-on-surface-variant">{{ authStore.user.email }}</p></div>
                </div>
                <div class="my-2 h-px w-full bg-outline-variant"></div>
                <RouterLink
                  v-if="currentRole === 'admin'"
                  to="/admin/dashboard"
                  class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-md px-4 py-3 text-body-sm font-medium text-on-surface transition-colors hover:bg-surface-container-highest hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                  @click="closeUserMenu"
                >
                  <span class="material-symbols-outlined shrink-0 text-[19px]">admin_panel_settings</span>
                  <span class="min-w-0">Quản trị</span>
                </RouterLink>

                <RouterLink
                  v-if="currentRole === 'instructor'"
                  to="/instructor/dashboard"
                  class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-md px-4 py-3 text-body-sm font-medium text-on-surface transition-colors hover:bg-surface-container-highest hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                  @click="closeUserMenu"
                >
                  <span class="material-symbols-outlined shrink-0 text-[19px]">co_present</span>
                  <span class="min-w-0">Kênh giảng dạy</span>
                </RouterLink>

                <RouterLink
                  to="/my-courses"
                  class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-md px-4 py-3 text-body-sm font-medium text-on-surface transition-colors hover:bg-surface-container-highest hover:text-primary focus-visible:bg-surface-container-highest focus-visible:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                  @click="closeUserMenu"
                >
                  <span class="material-symbols-outlined shrink-0 text-[19px]">school</span>
                  <span class="min-w-0">Sản phẩm đã mua</span>
                </RouterLink>

                <RouterLink
                  to="/my-orders"
                  class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-md px-4 py-3 text-body-sm font-medium text-on-surface transition-colors hover:bg-surface-container-highest hover:text-primary focus-visible:bg-surface-container-highest focus-visible:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                  @click="closeUserMenu"
                >
                  <span class="material-symbols-outlined shrink-0 text-[19px]">receipt_long</span>
                  <span class="min-w-0">Đơn hàng của tôi</span>
                </RouterLink>

                <RouterLink
                  to="/profile"
                  class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-md px-4 py-3 text-body-sm font-medium text-on-surface transition-colors hover:bg-surface-container-highest hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                  @click="closeUserMenu"
                >
                  <span class="material-symbols-outlined shrink-0 text-[19px]">person</span>
                  <span class="min-w-0">My Profile</span>
                </RouterLink>

                <div class="my-2 h-px w-full bg-outline-variant"></div>

                <button
                  class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-md px-4 py-3 text-left text-body-sm font-medium text-error transition-colors hover:bg-error/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60"
                  type="button"
                  :disabled="isLoggingOut"
                  @click="handleLogout"
                >
                  <span class="material-symbols-outlined shrink-0 text-[19px]">logout</span>
                  <span class="min-w-0">{{ isLoggingOut ? 'Logging out...' : 'Logout' }}</span>
                </button>
              </div>
            </Transition>
          </div>
        </template>
        <template v-else>
          <RouterLink to="/login" class="cursor-pointer rounded text-on-surface-variant hover:text-primary font-display text-button-text transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background">Đăng nhập</RouterLink>
          <RouterLink to="/register" class="cursor-pointer bg-primary-container text-on-primary-container px-md py-2 rounded-lg font-display text-button-text hover:opacity-90 active:scale-95 transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background">
            Đăng ký
          </RouterLink>
        </template>
      </div>
    </div>
  </nav>
</template>

<style scoped>
.category-dropdown-enter-active,
.category-dropdown-leave-active {
  transition:
    opacity 160ms ease,
    transform 160ms ease;
}

.category-dropdown-enter-from,
.category-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
