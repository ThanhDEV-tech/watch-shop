# PROJECT.md — Watchora

Watchora là shop bán đồng hồ thời trang nam/nữ, nhiều thương hiệu, nhiều mức giá,
tái cấu trúc từ hạ tầng dự án EduMarket (Laravel + Vue full-stack).

Tài liệu nguồn tham chiếu bắt buộc đọc trước khi code:
- `AGENTS.md` — quy tắc kiến trúc/kỹ thuật chung của repo
- `CONTEXT.md` — glossary domain đầy đủ (entity, field, business rule)
- `.agents/docs/audit-report.md` — báo cáo audit codebase EduMarket cũ

---

## 1. Mục tiêu MVP

Xây shop bán đồng hồ thời trang trên nền hạ tầng ecommerce đã có sẵn từ
EduMarket, thay thế toàn bộ domain "khóa học" bằng domain "sản phẩm đồng hồ
có biến thể (variant)".

## 2. Giữ nguyên từ EduMarket (không viết lại từ đầu)

| Module | Trạng thái |
|---|---|
| Auth (Sanctum + role) | Giữ, đổi role: `admin`, `customer` |
| Cart / Checkout | Giữ interface, thay implementation (course → variant) |
| VNPay payment | Giữ nguyên signing/logging, bỏ side-effect tạo Enrollment |
| Admin Dashboard pattern | Giữ layout/CRUD pattern, thay metric course → watch-shop |
| Docker setup | Giữ, đổi tên service/healthcheck/DB name |
| `BaseModal.vue` | Giữ nguyên 100% |
| Design token system | Giữ hạ tầng token, thay palette/copy |

## 3. Xóa hoàn toàn (domain khóa học cũ)

Chapter, Lesson, Certification, Enrollment, Lesson Progress, AI Learning
Assistant, Comment (gắn lesson), Instructor Dashboard, toàn bộ seeder demo
liên quan khóa học. Danh sách file chi tiết: xem `.agents/docs/audit-report.md`.

## 4. Domain Model

### 4.1 Product

Sản phẩm đồng hồ (không mua trực tiếp — khách mua Product Variant).

```
products
├── id
├── brand_id            -> brands.id
├── category_id         -> categories.id
├── name
├── slug (unique)
├── gender_target        enum: men | women | unisex
├── description
├── content
├── thumbnail
├── case_material
├── strap_material
├── glass_material
├── water_resistance
├── warranty_months      integer, default 12
├── warranty_note        nullable text
├── status                enum: draft | active | inactive
├── rating_avg
├── created_at / updated_at / deleted_at (soft delete)
```

Không có `base_price` — giá hiển thị listing lấy `MIN(final_price)` từ các
variant đang active.

### 4.2 Product Variant (SKU mua được)

```
product_variants
├── id
├── product_id
├── sku (unique, auto-gen nếu bỏ trống: WAT-{PRODUCT_ID}-{STRAP}-{DIAL}-{DIAMETER}-{MOVEMENT})
├── strap_color           string, validate theo enum code
├── dial_color             string, validate theo enum code
├── diameter_mm            unsigned int, 24-50
├── movement_type          DB enum: quartz | automatic
├── price                  decimal(12,2)
├── discount_price         decimal(12,2) nullable
├── stock_quantity         unsigned int
├── image                  nullable
├── is_active               boolean
```

Unique composite: `product_id + strap_color + dial_color + diameter_mm + movement_type`

`final_price = discount_price ?? price`

Màu cố định (validate code, không phải bảng DB): Đen, Bạc, Vàng gold, Vàng
rose, Trắng, Xanh navy, Nâu — kèm mapping màu → hex để render swatch.

### 4.3 Brand

```
brands: id, name, slug, description, logo, country, is_active
```
`Product belongsTo Brand`.

### 4.4 Category

5 category cố định: Dress Watch, Sport Watch, Casual Watch, Minimal Watch,
Sport-Casual. Không dùng brand/gender làm category.

### 4.5 Collection

Nhóm marketing linh hoạt, many-to-many với Product.

```
collections: id, name, slug, start_date, end_date
product_collection: product_id, collection_id, display_order
```

Ví dụ: Summer 2026, Office Style, Couple Watches, Gift for Her.
Ngoài khoảng `start_date`–`end_date` thì ẩn khỏi trang campaign công khai.

### 4.6 Product Images

```
product_images: id, product_id, image_path, alt_text, display_order, is_primary
```
Gallery chính thuộc Product, không lặp lại theo variant (variant chỉ có
1 `image` riêng để đổi ảnh khi chọn swatch màu).

### 4.7 Stock Movement (audit trail tồn kho)

```
stock_movements: id, product_variant_id, type, quantity_change,
                  stock_after, order_id, note, created_by
```
`type`: `admin_adjustment` | `order_paid` | `refund_adjustment`.
`admin_adjustment` bắt buộc có `note` + `created_by`.

### 4.8 Cart

```
cart_items: cart_id, product_variant_id, quantity
```
Không snapshot — luôn đọc giá/tồn kho hiện tại (live data).

### 4.9 Order

```
orders
├── id, code, user_id
├── receiver_name, receiver_phone, shipping_address, shipping_note
├── shipping_zone_name, shipping_fee   (snapshot)
├── subtotal_amount, total_amount
├── status   enum: pending | paid | paid_stock_issue | failed |
                    cancelled | shipping | completed | refunded
├── paid_at, refunded_at, refund_note
```

### 4.10 Order Item (snapshot đầy đủ, bất biến sau khi tạo)

```
order_items: order_id, product_id, product_variant_id, product_name,
             brand_name, sku, strap_color, dial_color, diameter_mm,
             movement_type, unit_price, quantity, line_total, thumbnail_url
```

### 4.11 Shipping Zone

```
shipping_zones: id, name, fee, is_active, display_order
```
Zone khởi tạo: Nội thành, Ngoại thành, Tỉnh/thành khác. Khách tự chọn zone
lúc checkout, hệ thống không tự detect theo địa chỉ.

## 5. Luồng nghiệp vụ quan trọng

### 5.1 Checkout → Payment → Inventory

1. Checkout: validate variant active, product active, đủ tồn kho, zone active
   → tạo `order` status `pending` → snapshot vào `order_items`. **Chưa trừ kho.**
2. VNPay Return URL: chỉ hiển thị kết quả, **không đổi trạng thái đơn hàng**.
3. VNPay IPN (nguồn xử lý trạng thái DUY NHẤT):
   - Mở DB transaction, `lockForUpdate()` từng `product_variants` liên quan.
   - Kiểm tra lại tồn kho sau khi lock.
   - Đủ hàng → trừ kho, ghi `stock_movements` (`order_paid`), order → `paid`.
   - Thiếu hàng → **không trừ âm kho**, order → `paid_stock_issue`.
   - Cả 2 trường hợp: gửi email queue (nội dung khác nhau theo trạng thái).
   - IPN phải idempotent.

### 5.2 Refund (thủ công, không gọi API VNPay)

Admin bấm "Mark as refunded" cho order đang ở `paid`, `paid_stock_issue`,
hoặc `shipping` → ghi `refunded_at`, `refund_note` → status `refunded`.
Chuyển khoản thực tế xử lý ngoài hệ thống.

### 5.3 Review

Chỉ khách có đơn `completed` chứa variant của sản phẩm mới được review.
Mỗi khách tối đa 1 review/sản phẩm. `rating_avg` tính từ review.

## 6. Phạm vi loại trừ khỏi MVP

AI assistant, product recommendation chatbot, guest checkout, COD, coupon,
wishlist, quản lý nhà cung cấp, address book, tích hợp API vận chuyển, phí
ship theo cân nặng, luồng `returned`, product tag, multi-warehouse,
marketplace nhiều người bán.

## 7. Kế hoạch triển khai (Backend trước, Frontend sau)

**Phase 1 — Product Catalog (Backend)**
Model + migration: `Brand`, `Category`, `Collection`, `Product`,
`ProductVariant`, `ProductImage`, `StockMovement`. Admin CRUD API. Validate
enum màu, movement_type, diameter range, unique SKU/composite.

**Phase 2 — Cart & Checkout (Backend)**
Chuyển `CartItem`/`OrderItem` từ `course_id` sang `product_variant_id`.
Thêm `quantity`. `ShippingZone`. Validate stock tại checkout (chưa trừ).

**Phase 3 — Payment & Inventory (Backend)**
Sửa `VnpayService::processSuccessfulPayment()`: bỏ tạo `Enrollment`, thêm
logic lock + trừ kho + `paid_stock_issue`. Refund action thủ công.

**Phase 4 — Admin Dashboard (Backend + Frontend)**
Thay metric course bằng: catalog, variant/SKU, brand, category, collection,
shipping zone, order (filter theo status mới), stock movement history,
highlight `paid_stock_issue`.

**Phase 5 — Public Frontend**
Product listing (filter gender/category/brand/collection), product detail
(variant selector 4 chiều + swatch màu), cart, checkout, order history.
SEO: canonical URL trang chi tiết sản phẩm, nội dung riêng biệt cho trang
danh mục Nam/Nữ.

**Phase 6 — Cleanup & Seed**
Xóa route/test/seeder cũ thuộc domain khóa học. Viết seeder mới: brand,
category, collection, product, variant, shipping zone, demo order.
Dọn Docker naming, `README-DOCKER.md`, gộp 2 axios instance còn trùng
(`api/axios.js` vs `services/api.js`).

## 8. Quy tắc đặt tên (không rename ngầm, phải xóa hẳn khái niệm cũ)

| Cũ (EduMarket) | Mới (Watchora) |
|---|---|
| Course | Product |
| Lesson / Chapter | (xóa, không có tương đương) |
| Student | Customer |
| Instructor | Admin (không có multi-seller) |
| Certification | (xóa; Collection dùng cho mục đích marketing) |
| Lesson progress | (xóa; Stock movement dùng cho audit tồn kho) |
| Enrollment | (xóa; Order fulfillment thay thế) |