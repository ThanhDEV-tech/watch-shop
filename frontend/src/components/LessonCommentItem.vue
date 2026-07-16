<script setup>
import { computed, ref } from 'vue'
import { formatDate } from '../utils/formatDate'

defineOptions({ name: 'LessonCommentItem' })

const props = defineProps({
  comment: { type: Object, required: true },
  currentUser: { type: Object, default: null },
  depth: { type: Number, default: 0 },
})

const emit = defineEmits(['reply', 'delete'])
const replying = ref(false)
const replyContent = ref('')

const canDelete = computed(() => !props.comment.is_deleted && props.currentUser && (
  props.comment.user?.id === props.currentUser.id
  || props.currentUser.role?.name === 'admin'
))

const submitReply = () => {
  const content = replyContent.value.trim()
  if (!content) return
  emit('reply', { parentId: props.comment.id, content })
  replyContent.value = ''
  replying.value = false
}
</script>

<template>
  <article class="w-full min-w-0" :style="{ paddingLeft: `${Math.min(depth, 3) * 16}px` }">
    <div class="w-full min-w-0 rounded-lg border border-surface-variant bg-background/50 p-sm">
      <div class="flex w-full min-w-0 items-start gap-sm">
        <img v-if="comment.user?.avatar" :src="comment.user.avatar" :alt="comment.user.name" class="h-9 w-9 shrink-0 rounded-full object-cover" />
        <div v-else class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-container-highest text-xs font-semibold text-primary"><span class="material-symbols-outlined text-[18px]">person</span></div>
        <div class="w-full min-w-0">
          <div class="flex w-full min-w-0 flex-wrap items-center gap-x-sm gap-y-xs"><p class="min-w-0 font-medium text-on-surface">{{ comment.user?.name ?? 'Người dùng' }}</p><span class="shrink-0 font-mono text-[10px] text-on-surface-variant">{{ formatDate(comment.created_at) }}</span></div>
          <p class="mt-xs w-full min-w-0 whitespace-pre-wrap break-words text-body-sm leading-6" :class="comment.is_deleted ? 'italic text-on-surface-variant/70' : 'text-on-surface-variant'">{{ comment.content }}</p>
          <div v-if="!comment.is_deleted || canDelete" class="mt-xs flex w-full min-w-0 flex-wrap gap-sm">
            <button v-if="!comment.is_deleted" class="text-xs text-primary hover:underline" type="button" @click="replying = !replying">Trả lời</button>
            <button v-if="canDelete" class="text-xs text-error hover:underline" type="button" @click="emit('delete', comment)">Xóa</button>
          </div>
        </div>
      </div>

      <form v-if="replying" class="mt-sm w-full min-w-0 pl-0 sm:pl-11" @submit.prevent="submitReply">
        <textarea v-model="replyContent" class="w-full min-w-0 rounded-lg border border-surface-variant bg-surface px-sm py-2 text-body-sm text-on-surface outline-none focus:border-primary" rows="2" maxlength="5000" placeholder="Viết phản hồi..."></textarea>
        <div class="mt-xs flex w-full min-w-0 flex-wrap justify-end gap-xs"><button class="rounded-lg border border-surface-variant px-sm py-2 text-xs text-on-surface" type="button" @click="replying = false">Hủy</button><button class="rounded-lg bg-primary px-sm py-2 text-xs font-semibold text-white" type="submit">Gửi trả lời</button></div>
      </form>
    </div>

    <div v-if="comment.replies?.length" class="mt-xs w-full min-w-0 space-y-xs">
      <LessonCommentItem v-for="reply in comment.replies" :key="reply.id" :comment="reply" :current-user="currentUser" :depth="depth + 1" @reply="emit('reply', $event)" @delete="emit('delete', $event)" />
    </div>
  </article>
</template>
