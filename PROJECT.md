📌 FINAL PRD — EDUMARKET V2

Tên đề tài
Xây dựng nền tảng bán và quản lý khóa học trực tuyến tích hợp AI Chatbot sử dụng Laravel + Vue.js
(Project style: SaaS E-learning Marketplace)

1. Tech Stack

Backend
Laravel 11 (PHP 8.2)
Laravel REST API
Laravel Sanctum Authentication

Frontend
Vue.js (Vue 3)
Tailwind CSS
Axios
Vue Router

Database
MySQL

Payment
VNPay Sandbox
Redirect Payment
IPN Callback Verification
HMAC SHA512 Signature

Email
PHPMailer
Gmail SMTP
Google App Password

Development Tools
ngrok (local callback testing)

2. System Modules

Student
Register / Login
Browse courses
Course detail
Add to cart
Checkout
Pay via VNPay Sandbox
Receive purchase confirmation email
Access purchased courses
Learn lessons
Track progress
Review course
Chat with AI assistant while learning

Instructor
Create course
Create chapters
Create lessons
Upload thumbnail
Manage enrolled students

Admin
Manage users
Manage categories
Manage courses
Manage instructors
View orders
View VNPay transaction logs
Monitor payment status
Dashboard analytics

AI Chatbot
AI Assistant for learners.
Answer lesson questions
Explain difficult concepts
Summarize lesson content
Recommend courses

3. Architecture

Vue 3 + Tailwind Frontend
              ↓ API
Laravel REST API
              ↓
MySQL Database

Laravel Backend
       ↓
VNPay Payment Gateway

Laravel Backend
       ↓
PHPMailer SMTP

Laravel Backend
       ↓
OpenAI API

Architecture type: API Based Architecture

4. Database Design

users (role_id, quan hệ 1 user - 1 role)
roles

categories

courses
chapters
lessons

carts
cart_items

orders
order_items
payments

vnpay_transactions

enrollments
lesson_progress

reviews
comments

ai_chat_sessions
ai_chat_messages

5. System Flow

Full business flow:

Student login
      ↓
Browse courses
      ↓
Add to cart
      ↓
Checkout
      ↓
Create order (pending)
      ↓
Redirect to VNPay Sandbox
      ↓
VNPay sends IPN callback
      ↓
Verify secure checksum
      ↓
Order = paid
      ↓
Send confirmation email
      ↓
Create enrollment automatically
      ↓
Student starts learning
      ↓
Ask AI assistant if needed
      ↓
Complete lessons
      ↓
Submit review

Không cần admin xác nhận thanh toán thủ công nữa.
(Payment auto verified)

6. Main Features

Public Site
Landing page
Course listing
Course detail
Search courses
Filter by category

Student Dashboard
Cart
Checkout
Payment
Orders history
Purchased courses
Course player
Progress tracking
AI chatbot
Review course

Instructor Dashboard
My courses
Create course
Manage chapters
Manage lessons
Student management

Admin Dashboard
User management
Course management
Payment transaction monitoring
VNPay logs
Dashboard statistics

7. Development Roadmap

Phase 1 — Foundation
[ ] Laravel setup
[ ] Vue setup
[ ] Tailwind setup
[ ] Sanctum authentication
[ ] Role system
[ ] Route protection
[ ] Basic UI layout

Phase 2 — Course Management
[ ] Category CRUD
[ ] Course CRUD
[ ] Chapter CRUD
[ ] Lesson CRUD
[ ] Thumbnail upload
[ ] Course detail page
[ ] Search/filter courses

Phase 3 — Payment System (Heavy Phase)
[ ] Cart module
[ ] Checkout module
[ ] Order system (pending / paid / failed)
[ ] VNPay integration
[ ] Generate secure payment URL
[ ] Create HMAC SHA512 signature
[ ] Redirect payment flow
[ ] VNPay return URL handler
[ ] VNPay IPN callback handler
[ ] Verify checksum
[ ] Update order status automatically
[ ] Create transaction log
[ ] Create enrollment automatically
⚠️ Phase này phải tách task nhỏ. Không code 1 lần.

Phase 4 — Email System
[ ] Install PHPMailer
[ ] Configure Gmail SMTP
[ ] Send payment success email
[ ] Create email template
[ ] Queue email sending

Phase 5 — Learning System
[ ] My Courses
[ ] Lesson player
[ ] Mark lesson completed
[ ] Progress tracking %
[ ] Comment system
[ ] Rating system

Phase 6 — AI Assistant
[ ] Chatbox UI
[ ] AI API endpoint
[ ] Connect OpenAI API
[ ] Send lesson context
[ ] AI answer generation
[ ] Store chat history

Phase 7 — Final Polish
[ ] Dashboard analytics
[ ] Seeder
[ ] Validation
[ ] Error handling
[ ] Responsive UI
[ ] README
[ ] Demo preparation

8. Not Included

Không làm:
AWS
S3 / MinIO
Microservice
WebSocket
Vector Database
Fine-tuning AI
Upload video thật
Kubernetes
Kafka / RabbitMQ

Payment:
VNPay Sandbox only
Not real production payment

9. Environment Variables

.env

APP_URL=

DB_DATABASE=edumarket
DB_USERNAME=root
DB_PASSWORD=

VNPAY_TMN_CODE=
VNPAY_HASH_SECRET=
VNPAY_RETURN_URL=
VNPAY_IPN_URL=

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=

OPENAI_API_KEY=

Không hardcode secrets.

10. AGENTS.md Rules

## Payment (VNPay Sandbox)
- Store TMN_CODE in .env
- Store HASH_SECRET in .env
- Never hardcode payment secret
- Use HMAC SHA512 signature
- Verify checksum before updating order

Routes:
/api/payment/vnpay/create
/api/payment/vnpay/ipn
/api/payment/vnpay/return

## Email (PHPMailer)
- Use Gmail SMTP
- Use App Password
- Never store password in source code
- Queue email sending asynchronously

## AI
- Do not expose OpenAI API key
- Store API key in .env

## Trạng thái triển khai (cập nhật thủ công khi hoàn thành từng phần)
- [x] Phase 1 (một phần): Laravel 11 + MySQL setup xong, DB "edumarket" đã có đủ 18 bảng qua migration
- [ ] Model + relationships
- [ ] Auth Sanctum + role middleware
- [ ] Còn lại theo roadmap ở mục 7