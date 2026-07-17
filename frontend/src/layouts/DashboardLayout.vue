<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const authStore = useAuthStore()
const router = useRouter()
const isUserMenuOpen = ref(false)
const isLoggingOut = ref(false)

const menus = {
  admin: [
    { label: 'Tổng quan', icon: 'dashboard', to: '/admin/dashboard' },
    { label: 'Catalog sản phẩm', icon: 'inventory_2', to: '/admin/products' },
    { label: 'Variant/SKU', icon: 'qr_code_2', to: '/admin/product-variants' },
    { label: 'Brand', icon: 'sell', to: '/admin/brands' },
    { label: 'Category', icon: 'category', to: '/admin/categories' },
    { label: 'Collection', icon: 'collections_bookmark', to: '/admin/collections' },
    { label: 'Shipping Zone', icon: 'local_shipping', to: '/admin/shipping-zones' },
    { label: 'Order Management', icon: 'receipt_long', to: '/admin/orders' },
    { label: 'VNPay Transaction Logs', icon: 'account_balance', to: '/admin/vnpay-transactions' },
    { label: 'Stock Movement History', icon: 'history', to: '/admin/stock-movements' },
    { label: 'User Management', icon: 'group', to: '/admin/users' },
  ],
  instructor: [
    { label: 'Tổng quan', icon: 'dashboard', to: '/instructor/dashboard' },
    { label: 'Khóa học của tôi', icon: 'menu_book', to: '/instructor/courses' },
  ],
}

const roleName = computed(() => authStore.user?.role?.name ?? '')
const menuItems = computed(() => menus[roleName.value] ?? [])
const roleLabel = computed(() => roleName.value === 'admin' ? 'Admin Console' : 'Instructor Studio')
const productLabel = computed(() => roleName.value === 'admin' ? 'Watchora Admin' : 'EduMarket Instructor')
const dashboardLink = computed(() => roleName.value === 'admin' ? '/admin/dashboard' : '/instructor/dashboard')
const dashboardLinkLabel = computed(() => roleName.value === 'admin' ? 'Quản trị' : 'Kênh giảng dạy')

const handleLogout = async () => {
  if (isLoggingOut.value) return

  isLoggingOut.value = true
  isUserMenuOpen.value = false

  try {
    await authStore.logout()
    await router.push('/login')
  } finally {
    isLoggingOut.value = false
  }
}
</script>

<template>
  <div class="min-h-screen w-full min-w-0 bg-background">
    <header class="sticky top-0 z-50 flex h-16 w-full min-w-0 items-center justify-between border-b border-surface-variant bg-surface-container-lowest px-margin-mobile md:px-gutter">
      <RouterLink :to="dashboardLink" class="min-w-0 font-display text-lg font-bold text-primary">
        {{ productLabel }}
      </RouterLink>

      <div class="relative shrink-0" @mouseenter="isUserMenuOpen = true" @mouseleave="isUserMenuOpen = false">
        <button
          class="flex max-w-64 min-w-0 cursor-pointer items-center gap-xs rounded-lg border border-transparent px-sm py-2 text-body-sm text-on-surface transition-colors hover:border-surface-variant hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          type="button"
          :aria-expanded="isUserMenuOpen"
          aria-haspopup="true"
          @click="isUserMenuOpen = !isUserMenuOpen"
        >
          <span class="material-symbols-outlined shrink-0 text-[20px] text-primary">account_circle</span>
          <span class="min-w-0 truncate font-medium">{{ authStore.user?.name }}</span>
          <span class="material-symbols-outlined shrink-0 text-[18px] transition-transform" :class="{ 'rotate-180': isUserMenuOpen }">keyboard_arrow_down</span>
        </button>

        <Transition name="dashboard-menu">
          <div v-if="isUserMenuOpen" class="absolute right-0 top-full z-[70] w-64 max-w-[calc(100vw-2rem)] pt-2">
            <div class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface p-2 shadow-[0_18px_45px_rgba(0,0,0,0.45)]">
              <RouterLink
                :to="dashboardLink"
                class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-md px-4 py-3 text-body-sm font-medium text-on-surface transition-colors hover:bg-surface-container-highest hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                @click="isUserMenuOpen = false"
              >
                <span class="material-symbols-outlined shrink-0 text-[19px]">dashboard</span>
                <span class="min-w-0">{{ dashboardLinkLabel }}</span>
              </RouterLink>

              <RouterLink
                to="/my-orders"
                class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-md px-4 py-3 text-body-sm font-medium text-on-surface transition-colors hover:bg-surface-container-highest hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
                @click="isUserMenuOpen = false"
              >
                <span class="material-symbols-outlined shrink-0 text-[19px]">shopping_bag</span>
                <span class="min-w-0">My Orders</span>
              </RouterLink>

              <div class="my-2 h-px w-full bg-outline-variant"></div>

              <button
                class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-md px-4 py-3 text-left text-body-sm font-medium text-error transition-colors hover:bg-error/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-60"
                type="button"
                :disabled="isLoggingOut"
                @click="handleLogout"
              >
                <span class="material-symbols-outlined shrink-0 text-[19px]">logout</span>
                <span class="min-w-0">{{ isLoggingOut ? 'Đang đăng xuất...' : 'Logout' }}</span>
              </button>
            </div>
          </div>
        </Transition>
      </div>
    </header>

    <div class="flex min-h-[calc(100vh-4rem)] w-full min-w-0">
      <aside class="hidden w-64 shrink-0 border-r border-surface-variant bg-surface-container-lowest lg:block">
        <div class="sticky top-16 flex h-[calc(100vh-4rem)] w-full min-w-0 flex-col p-md">
          <div class="w-full min-w-0 border-b border-surface-variant pb-md">
            <p class="w-full font-mono text-[11px] uppercase tracking-[0.2em] text-primary">{{ roleLabel }}</p>
            <p class="mt-xs w-full truncate font-display text-body-md font-semibold text-on-surface">{{ authStore.user?.name }}</p>
          </div>

          <nav class="mt-md flex w-full min-w-0 flex-col gap-xs overflow-y-auto" aria-label="Dashboard navigation">
            <RouterLink
              v-for="item in menuItems"
              :key="item.to"
              :to="item.to"
              class="flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-lg px-sm py-3 text-body-sm font-medium text-on-surface-variant transition-colors hover:bg-surface-container-highest hover:text-on-surface focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
              active-class="bg-surface-container-highest !text-primary"
            >
              <span class="material-symbols-outlined shrink-0 text-[20px]">{{ item.icon }}</span>
              <span class="min-w-0 truncate">{{ item.label }}</span>
            </RouterLink>
          </nav>

          <RouterLink to="/" class="mt-auto flex w-full min-w-0 cursor-pointer items-center gap-sm rounded-lg px-sm py-3 text-body-sm text-on-surface-variant transition-colors hover:bg-surface-container-highest hover:text-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background">
            <span class="material-symbols-outlined shrink-0 text-[20px]">arrow_back</span>
            <span class="min-w-0">Về trang chủ</span>
          </RouterLink>
        </div>
      </aside>

      <div class="w-full min-w-0 flex-1">
        <div class="flex w-full min-w-0 gap-xs overflow-x-auto border-b border-surface-variant bg-surface-container-lowest px-margin-mobile py-sm lg:hidden">
          <RouterLink
            v-for="item in menuItems"
            :key="item.to"
            :to="item.to"
            class="flex shrink-0 cursor-pointer items-center gap-xs rounded-lg px-sm py-2 text-body-sm text-on-surface-variant focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            active-class="bg-surface-container-highest !text-primary"
          >
            <span class="material-symbols-outlined text-[18px]">{{ item.icon }}</span>
            {{ item.label }}
          </RouterLink>
        </div>
        <RouterView />
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-menu-enter-active,
.dashboard-menu-leave-active {
  transition: opacity 160ms ease, transform 160ms ease;
}

.dashboard-menu-enter-from,
.dashboard-menu-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
