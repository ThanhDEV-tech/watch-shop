<script setup>
import { onMounted, reactive, ref } from 'vue'
import DashboardPagination from '../../components/dashboard/DashboardPagination.vue'
import { getAdminUsers, toggleAdminUserActive } from '../../services/api'

const users = ref([])
const pagination = ref({ current_page: 1, last_page: 1, total: 0 })
const loading = ref(true)
const updatingId = ref(null)
const error = ref('')
const filters = reactive({ search: '', role: '', page: 1 })

const fetchUsers = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await getAdminUsers({ ...filters, per_page: 10 })
    users.value = response.data.data.items ?? []
    pagination.value = response.data.data.pagination
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể tải danh sách người dùng.'
  } finally {
    loading.value = false
  }
}

const applyFilters = () => {
  filters.page = 1
  fetchUsers()
}

const changePage = (page) => {
  filters.page = page
  fetchUsers()
}

const toggleActive = async (user) => {
  updatingId.value = user.id
  error.value = ''

  try {
    const response = await toggleAdminUserActive(user.id)
    Object.assign(user, response.data.data)
  } catch (requestError) {
    error.value = requestError.response?.data?.message ?? 'Không thể cập nhật trạng thái tài khoản.'
  } finally {
    updatingId.value = null
  }
}

onMounted(fetchUsers)
</script>

<template>
  <main class="mx-auto w-full min-w-0 max-w-[1440px] p-margin-mobile md:p-gutter lg:p-lg">
    <div class="w-full min-w-0">
      <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">Access control</p>
      <h1 class="mt-xs w-full font-display text-headline-md font-bold text-on-surface">Quản lý người dùng</h1>
      <p class="mt-xs w-full text-body-sm text-on-surface-variant">Tìm kiếm, lọc vai trò và khóa hoặc mở khóa tài khoản.</p>
    </div>

    <form class="mt-lg flex w-full min-w-0 flex-col gap-sm rounded-lg border border-surface-variant bg-surface p-md md:flex-row" @submit.prevent="applyFilters">
      <input v-model.trim="filters.search" class="w-full min-w-0 flex-1 rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary" placeholder="Tìm theo tên hoặc email..." />
      <select v-model="filters.role" class="w-full rounded-lg border border-surface-variant bg-background px-md py-3 text-body-sm text-on-surface outline-none focus:border-primary md:w-52">
        <option value="">Tất cả vai trò</option><option value="admin">Admin</option><option value="instructor">Instructor</option><option value="student">Student</option>
      </select>
      <button class="w-full rounded-lg bg-primary-container px-md py-3 font-display text-body-sm font-semibold text-on-primary-container transition-opacity hover:opacity-90 md:w-auto" type="submit">Tìm kiếm</button>
    </form>

    <p v-if="error" class="mt-md w-full rounded-lg border border-error/40 bg-error/10 p-md text-body-sm text-error">{{ error }}</p>

    <section class="mt-md w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface">
      <div class="w-full min-w-0 overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-body-sm">
          <thead class="bg-surface-container-lowest text-on-surface-variant"><tr><th class="px-md py-sm font-medium">Người dùng</th><th class="px-md py-sm font-medium">Email</th><th class="px-md py-sm font-medium">Vai trò</th><th class="px-md py-sm font-medium">Trạng thái</th><th class="px-md py-sm text-right font-medium">Thao tác</th></tr></thead>
          <tbody>
            <tr v-if="loading"><td colspan="5" class="px-md py-xl text-center text-on-surface-variant"><span class="material-symbols-outlined animate-spin align-middle text-primary">progress_activity</span> Đang tải người dùng...</td></tr>
            <tr v-else-if="!users.length"><td colspan="5" class="px-md py-xl text-center text-on-surface-variant">Không tìm thấy người dùng phù hợp.</td></tr>
            <tr v-for="user in users" v-else :key="user.id" class="border-t border-surface-variant hover:bg-surface-container-highest/40">
              <td class="px-md py-sm font-medium text-on-surface">{{ user.name }}</td><td class="px-md py-sm text-on-surface-variant">{{ user.email }}</td>
              <td class="px-md py-sm"><span class="rounded border border-surface-variant px-2 py-1 font-mono text-[11px] uppercase text-primary">{{ user.role?.name }}</span></td>
              <td class="px-md py-sm"><span class="rounded px-2 py-1 text-xs" :class="user.is_active ? 'bg-[var(--accent-success)]/10 text-[var(--accent-success)]' : 'bg-error/10 text-error'">{{ user.is_active ? 'Active' : 'Locked' }}</span></td>
              <td class="px-md py-sm text-right"><button class="rounded-lg border border-surface-variant px-sm py-2 text-xs font-semibold text-on-surface transition-colors hover:border-primary disabled:opacity-50" type="button" :disabled="updatingId === user.id" @click="toggleActive(user)">{{ updatingId === user.id ? 'Đang cập nhật...' : (user.is_active ? 'Khóa' : 'Mở khóa') }}</button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <DashboardPagination :pagination="pagination" @change="changePage" />
    </section>
  </main>
</template>
