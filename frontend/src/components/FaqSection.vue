<script setup>
import { ref } from 'vue'

const openIndex = ref(0)

const faqs = [
  {
    question: 'Làm sao để mua khóa học?',
    answer: 'Đăng ký tài khoản, thêm khóa học vào giỏ, checkout và thanh toán qua VNPay Sandbox. Sau khi IPN xác nhận thành công, khóa học sẽ xuất hiện trong My Courses.',
  },
  {
    question: 'EduMarket có hoàn tiền không?',
    answer: 'Trong bản demo hiện tại chưa triển khai luồng hoàn tiền tự động. Với môi trường sandbox, bạn có thể kiểm thử thanh toán mà không phát sinh giao dịch thật.',
  },
  {
    question: 'Chứng chỉ có giá trị không?',
    answer: 'Các trang chứng chỉ cung cấp thông tin định hướng và khóa học liên quan. Chứng chỉ chính thức vẫn do nhà cung cấp như AWS, CompTIA, PMI hoặc Microsoft cấp.',
  },
  {
    question: 'EduMarket thanh toán qua đâu?',
    answer: 'Hệ thống tích hợp VNPay Sandbox theo luồng redirect, xác thực HMAC SHA512 và chỉ cập nhật đơn hàng thông qua IPN server-to-server.',
  },
  {
    question: 'Có học trên điện thoại được không?',
    answer: 'Có. Giao diện học được tối ưu responsive cơ bản; trên mobile, bạn vẫn có thể xem bài học, theo dõi tiến độ và mở trợ lý AI.',
  },
  {
    question: 'Liên hệ hỗ trợ thế nào?',
    answer: 'Bạn có thể dùng thông tin liên hệ trong Footer hoặc gửi phản hồi qua kênh hỗ trợ của dự án khi demo được triển khai.',
  },
]

const toggle = (index) => {
  openIndex.value = openIndex.value === index ? null : index
}
</script>

<template>
  <section class="w-full bg-background py-xl font-body">
    <div class="mx-auto grid w-full min-w-0 max-w-container-max grid-cols-1 gap-lg px-margin-mobile md:px-gutter lg:grid-cols-12">
      <div class="w-full min-w-0 lg:col-span-4">
        <p class="w-full font-mono text-label-mono uppercase tracking-widest text-primary">FAQ</p>
        <h2 class="mt-xs w-full font-display text-headline-md font-semibold text-on-background">Câu hỏi thường gặp</h2>
        <p class="mt-md w-full text-body-md text-on-surface-variant">
          Những điểm quan trọng nhất về mua khóa học, thanh toán, chứng chỉ và trải nghiệm học trên EduMarket.
        </p>
      </div>

      <div class="w-full min-w-0 space-y-sm lg:col-span-8">
        <article
          v-for="(faq, index) in faqs"
          :key="faq.question"
          class="w-full min-w-0 overflow-hidden rounded-xl border border-surface-variant bg-surface"
        >
          <button
            class="flex w-full min-w-0 items-center justify-between gap-md p-md text-left"
            type="button"
            :aria-expanded="openIndex === index"
            @click="toggle(index)"
          >
            <span class="min-w-0 flex-1 font-display text-body-lg font-semibold text-on-surface">{{ faq.question }}</span>
            <span class="material-symbols-outlined shrink-0 text-primary transition-transform duration-200" :class="openIndex === index ? 'rotate-180' : ''">expand_more</span>
          </button>

          <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-out"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
          >
            <div v-if="openIndex === index" class="w-full min-w-0 px-md pb-md">
              <p class="w-full border-t border-surface-variant pt-md text-body-md text-on-surface-variant">{{ faq.answer }}</p>
            </div>
          </Transition>
        </article>
      </div>
    </div>
  </section>
</template>
