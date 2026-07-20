<script setup>
import { nextTick, ref } from 'vue'
import { sendAiChat } from '../api/axios'
import { formatCurrency } from '../utils/formatCurrency'

const SESSION_ID_KEY = 'watchora_ai_session_id'
const SESSION_TOKEN_KEY = 'watchora_ai_session_token'

const isOpen = ref(false)
const draft = ref('')
const messages = ref([])
const sessionId = ref(Number(localStorage.getItem(SESSION_ID_KEY)) || null)
const sessionToken = ref(localStorage.getItem(SESSION_TOKEN_KEY) || '')
const isSending = ref(false)
const messageList = ref(null)

const parseMarkdown = (content) => {
  const source = String(content ?? '')
  const segments = []
  const codeFence = /```([^\n`]*)\n?([\s\S]*?)```/g
  let cursor = 0
  let match

  while ((match = codeFence.exec(source)) !== null) {
    if (match.index > cursor) {
      segments.push({ type: 'text', content: source.slice(cursor, match.index) })
    }

    segments.push({
      type: 'code',
      language: match[1].trim(),
      content: match[2].replace(/\n$/, ''),
    })
    cursor = codeFence.lastIndex
  }

  if (cursor < source.length) segments.push({ type: 'text', content: source.slice(cursor) })

  return segments.length ? segments : [{ type: 'text', content: source }]
}

const scrollToLatest = async () => {
  await nextTick()
  if (messageList.value) messageList.value.scrollTop = messageList.value.scrollHeight
}

const openChat = async () => {
  isOpen.value = true
  await scrollToLatest()
}

const rememberSession = (data) => {
  sessionId.value = data.session_id
  sessionToken.value = data.session_token ?? sessionToken.value

  if (sessionId.value) localStorage.setItem(SESSION_ID_KEY, String(sessionId.value))
  if (sessionToken.value) localStorage.setItem(SESSION_TOKEN_KEY, sessionToken.value)
}

const sendMessage = async () => {
  const content = draft.value.trim()
  if (!content || isSending.value) return

  messages.value.push({ id: `user-${Date.now()}`, role: 'user', content })
  draft.value = ''
  isSending.value = true
  await scrollToLatest()

  const payload = { message: content }
  if (sessionId.value) payload.session_id = sessionId.value
  if (sessionToken.value) payload.session_token = sessionToken.value

  try {
    const response = await sendAiChat(payload)
    const data = response.data.data
    rememberSession(data)
    messages.value.push({
      id: `assistant-${Date.now()}`,
      role: 'assistant',
      content: data.message,
      product_suggestions: data.product_suggestions ?? [],
    })
  } catch (error) {
    messages.value.push({
      id: `error-${Date.now()}`,
      role: 'error',
      content: error.response?.data?.message ?? 'Không thể kết nối với trợ lý Watchora. Vui lòng thử lại sau.',
    })
  } finally {
    isSending.value = false
    await scrollToLatest()
  }
}

const handleComposerKeydown = (event) => {
  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed bottom-4 right-4 z-[120] font-body sm:bottom-6 sm:right-6">
      <button
        v-if="!isOpen"
        class="flex h-14 w-14 cursor-pointer items-center justify-center rounded-full bg-primary text-white shadow-[0_12px_30px_rgba(0,0,0,0.4)] transition-transform hover:-translate-y-1 hover:bg-[var(--accent-primary-hover)]"
        type="button"
        aria-label="Mở trợ lý AI Watchora"
        @click="openChat"
      >
        <span class="material-symbols-outlined text-[28px]">watch</span>
      </button>

      <section
        v-else
        class="fixed inset-3 flex w-auto min-w-0 flex-col overflow-hidden rounded-xl border border-surface-variant bg-surface shadow-[0_24px_60px_rgba(0,0,0,0.5)] sm:inset-auto sm:bottom-6 sm:right-6 sm:h-[38rem] sm:w-[26rem] sm:max-w-[calc(100vw-3rem)]"
        aria-label="Trợ lý AI Watchora"
      >
        <header class="flex w-full min-w-0 items-center justify-between gap-sm border-b border-surface-variant bg-surface-container-lowest p-md">
          <div class="flex w-full min-w-0 items-center gap-sm">
            <span class="material-symbols-outlined shrink-0 text-primary">watch</span>
            <div class="w-full min-w-0">
              <h2 class="w-full truncate font-display text-body-md font-semibold text-on-surface">Trợ lý Watchora</h2>
              <p class="w-full text-xs text-on-surface-variant">Tư vấn chọn đồng hồ và giải đáp chính sách</p>
            </div>
          </div>
          <button class="material-symbols-outlined h-9 w-9 shrink-0 cursor-pointer rounded-lg text-on-surface-variant hover:bg-surface-container-highest hover:text-on-surface" type="button" aria-label="Đóng trợ lý AI" @click="isOpen = false">close</button>
        </header>

        <div ref="messageList" class="w-full min-w-0 flex-1 space-y-sm overflow-y-auto bg-background/40 p-md" aria-live="polite">
          <div v-if="!messages.length" class="flex min-h-full w-full min-w-0 items-center justify-center text-center">
            <div class="w-full min-w-0 max-w-[20rem]">
              <span class="material-symbols-outlined text-5xl text-primary">forum</span>
              <p class="mt-sm w-full font-display text-body-md font-semibold text-on-surface">Bạn đang tìm mẫu đồng hồ nào?</p>
              <p class="mt-xs w-full text-body-sm leading-6 text-on-surface-variant">
                Thử hỏi: “Tôi cần đồng hồ nữ tối giản dưới 3 triệu” hoặc “Chính sách bảo hành thế nào?”.
              </p>
            </div>
          </div>

          <div v-for="message in messages" :key="message.id" class="flex w-full min-w-0" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
            <div
              class="min-w-0 max-w-[90%] rounded-lg px-sm py-3 text-body-sm leading-6"
              :class="{
                'bg-primary text-white': message.role === 'user',
                'border border-surface-variant bg-surface-container-highest text-on-surface': message.role === 'assistant',
                'border border-error/40 bg-error/10 text-error': message.role === 'error',
              }"
            >
              <template v-if="message.role === 'assistant'">
                <template v-for="(segment, index) in parseMarkdown(message.content)" :key="`${message.id}-${index}`">
                  <p v-if="segment.type === 'text'" class="w-full min-w-0 whitespace-pre-wrap break-words">{{ segment.content }}</p>
                  <div v-else class="my-xs w-full min-w-0 overflow-hidden rounded-lg border border-surface-variant bg-surface-container-lowest">
                    <div v-if="segment.language" class="w-full border-b border-surface-variant px-sm py-xs font-mono text-[10px] uppercase text-on-surface-variant">{{ segment.language }}</div>
                    <pre class="w-full min-w-0 overflow-x-auto p-sm font-mono text-xs leading-5 text-on-surface"><code>{{ segment.content }}</code></pre>
                  </div>
                </template>

                <div v-if="message.product_suggestions?.length" class="mt-sm space-y-xs">
                  <RouterLink
                    v-for="product in message.product_suggestions"
                    :key="product.id"
                    :to="{ name: 'product-detail', params: { slug: product.slug } }"
                    class="flex min-h-[86px] w-full min-w-0 items-center gap-sm rounded-lg border border-surface-variant/80 bg-surface-container-lowest p-sm transition-all hover:border-primary hover:bg-surface-container-highest"
                    @click="isOpen = false"
                  >
                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-md bg-primary/10">
                      <img v-if="product.thumbnail" :src="product.thumbnail" :alt="product.name" class="h-full w-full object-cover" />
                      <div v-else class="flex h-full w-full items-center justify-center text-primary">
                        <span class="material-symbols-outlined text-[20px]">watch</span>
                      </div>
                    </div>
                    <div class="w-full min-w-0">
                      <p class="w-full truncate font-medium leading-5 text-on-surface">{{ product.name }}</p>
                      <p class="mt-1 w-full truncate text-xs text-on-surface-variant">{{ product.brand }} · {{ product.category }}</p>
                      <p class="mt-1 w-full text-xs font-semibold text-primary">Từ {{ formatCurrency(product.min_price) }}</p>
                    </div>
                  </RouterLink>
                </div>
              </template>
              <p v-else class="w-full min-w-0 whitespace-pre-wrap break-words">{{ message.content }}</p>
            </div>
          </div>

          <div v-if="isSending" class="flex w-full min-w-0 justify-start">
            <div class="flex w-auto min-w-0 items-center gap-xs rounded-lg border border-surface-variant bg-surface-container-highest px-sm py-3" aria-label="Trợ lý AI đang trả lời">
              <span v-for="dot in 3" :key="dot" class="h-2 w-2 animate-pulse rounded-full bg-primary" :style="{ animationDelay: `${dot * 140}ms` }"></span>
            </div>
          </div>
        </div>

        <form class="flex w-full min-w-0 items-end gap-sm border-t border-surface-variant bg-surface p-sm" @submit.prevent="sendMessage">
          <textarea
            v-model="draft"
            class="max-h-28 min-h-11 w-full min-w-0 resize-none rounded-lg border border-surface-variant bg-background px-sm py-3 text-body-sm text-on-surface outline-none placeholder:text-on-surface-variant focus:border-primary"
            rows="1"
            maxlength="4000"
            placeholder="Hỏi về đồng hồ, ngân sách, bảo hành, vận chuyển..."
            aria-label="Câu hỏi cho trợ lý AI Watchora"
            :disabled="isSending"
            @keydown="handleComposerKeydown"
          ></textarea>
          <button class="flex h-11 w-11 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-primary text-white hover:bg-[var(--accent-primary-hover)] disabled:cursor-not-allowed disabled:opacity-50" type="submit" aria-label="Gửi câu hỏi" :disabled="isSending || !draft.trim()">
            <span class="material-symbols-outlined">send</span>
          </button>
        </form>
      </section>
    </div>
  </Teleport>
</template>
