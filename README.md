# EduMarket

EduMarket là nền tảng bán và quản lý khóa học trực tuyến tích hợp AI Chatbot, xây dựng bằng Laravel 11 và Vue 3.

## Yêu cầu
- Docker Desktop đã cài sẵn
- Không cần cài riêng PHP, Node.js, MySQL hoặc Composer

## Cài đặt và chạy
1. Clone/copy source về máy.
2. Copy file backend/.env.docker thành backend/.env và điền các secret cá nhân cần thiết:
   - VNPAY_TMN_CODE
   - VNPAY_HASH_SECRET
   - MAIL_USERNAME
   - MAIL_PASSWORD
   - OPENAI_API_KEY
   > Các giá trị này là secret cá nhân, không nên commit lên git.
3. Khởi động container:
   ```bash
   docker compose up -d --build
   ```
4. Chạy migration và seed dữ liệu demo:
   ```bash
   docker compose exec backend php artisan migrate --seed
   ```

## Truy cập
- Frontend: http://localhost:5173
- Backend API: http://localhost:8000/api
- phpMyAdmin: http://localhost:8080
  - User: root
  - Password: rỗng

## Tài khoản demo
- Admin: admin@edumarket.com / password
- Instructor: instructor1@edumarket.com / password
- Student: student1@edumarket.com / password

## Lệnh hữu ích
```bash
docker compose logs -f queue
docker compose exec backend php artisan tinker
docker compose exec backend php artisan test
docker compose down
docker compose down -v
```

## Tính năng chính
- Student: đăng ký, xem khóa học, mua hàng, thanh toán VNPay, học bài, theo dõi tiến độ, review khóa học
- Instructor: tạo khóa học, quản lý chương/bài học, xem học viên
- Admin: quản lý người dùng, danh mục, khóa học, đơn hàng, giao dịch VNPay
- AI Chatbot: gợi ý và hỗ trợ học viên
- Email tự động gửi qua queue

## Cấu trúc thư mục
- backend/
- frontend/
- docker-compose.yml
- AGENTS.md
- PROJECT.md

## Lưu ý
- VNPay đang chạy ở môi trường Sandbox/Test, không phải production thật.
