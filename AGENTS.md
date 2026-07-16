# AGENTS.md - EduMarket

## Project
Nền tảng bán và quản lý khóa học trực tuyến tích hợp AI Chatbot.
Chi tiết đầy đủ xem PROJECT.md.

## Stack
- Backend: Laravel 11 (REST API) + Sanctum, PHP 8.2
- Frontend: Vue 3 (Composition API) + Tailwind CSS + Pinia + Axios
- DB: MySQL (database: edumarket)
- Payment: VNPay Sandbox (redirect + IPN callback, HMAC SHA512)
- Email: PHPMailer + Gmail SMTP
- AI: OpenAI API

## Conventions
- Backend: PSR-12, Form Request cho validation, API Resource cho response format,
  Service class cho business logic phức tạp (không viết logic dày trong Controller)
- API response format chuẩn: { "success": bool, "data": ..., "message": string }
- Routes group theo prefix: /api/admin, /api/instructor, /api/student, /api/auth
- Middleware phân quyền theo role (1 user - 1 role, cột role_id trong bảng users)
- Frontend: <script setup>, Pinia cho global state, 1 axios instance riêng (interceptor
  gắn Bearer token tự động)

## Database
Đã có sẵn 18 bảng qua migration (xem database/migrations/). KHÔNG tự ý đổi tên cột/bảng
đã có, nếu cần thêm cột thì tạo migration mới.

## Payment (VNPay Sandbox)
- TMN_CODE, HASH_SECRET lưu trong .env, không hardcode
- Routes: /api/payment/vnpay/create, /api/payment/vnpay/ipn, /api/payment/vnpay/return
- CHỈ update trạng thái order tại IPN handler (server-to-server), KHÔNG update ở Return URL
  (Return URL chỉ để hiển thị UI cho user)
- IPN phải xử lý idempotent: kiểm tra order đã paid chưa trước khi update, tránh xử lý
  trùng khi VNPay gọi lại IPN nhiều lần

## Email (PHPMailer)
- Gmail SMTP, dùng App Password, không hardcode password
- Gửi qua Laravel Queue (queue: database), không gửi đồng bộ chặn request

## AI
- Không expose OpenAI API key ra frontend
- API key lưu trong .env, gọi qua backend

## Không làm
AWS, S3/MinIO, microservice, WebSocket, vector database, fine-tuning AI,
upload video thật (dùng YouTube URL), Kubernetes, Kafka/RabbitMQ, payment thật ngoài sandbox

## Frontend (Vue 3)
- Component đặt trong frontend/src/components, đặt tên PascalCase
- Convert HTML reference trong .agents/references/ sang Vue SFC, giữ nguyên class Tailwind và cấu trúc, không tự đổi layout
- Gọi API qua Axios instance chung (frontend/src/api/axios.js), base URL từ .env
- Response format backend luôn { success, data, message } — xử lý thống nhất qua 1 interceptor
- Route qua Vue Router, đặt trong frontend/src/router
- Không tự đặt tên custom token trùng với tên Tailwind core dùng nội bộ (ví dụ --spacing-md, --spacing-lg) — Tailwind v4 có thể dùng chung token cho nhiều mục đích khác nhau (spacing VÀ max-width), gây xung đột ngầm khó phát hiện. Khi thêm design token mới, đặt tiền tố riêng biệt (ví dụ --layout-spacing-md) để tránh trùng namespace với Tailwind. 
- QUAN TRỌNG: mọi div chứa text/heading nằm trong flex container (flex-col hoặc items-center) BẮT BUỘC có w-full hoặc min-w-0. Đây là bug đã lặp lại nhiều lần (Footer, CertificationBanner, trang 404) gây text wrap từng từ một dòng. Trước khi báo hoàn thành bất kỳ component mới nào, tự rà lại: nếu có class "items-center" hoặc "flex-col" trên container cha, kiểm tra TẤT CẢ div con chứa text đã có w-full/min-w-0 chưa.
- Mọi modal/dialog PHẢI dùng <Teleport to="body">, có overlay position:fixed inset-0, và chính khung modal phải có w-full kết hợp max-w-* rõ ràng (ví dụ max-w-md, max-w-lg tùy nội dung). Không để modal render lồng trong container cha có width bị giới hạn.