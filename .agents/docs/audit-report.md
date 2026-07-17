# EduMarket to Watch Shop Architecture Audit

Date: 2026-07-17
Scope: read-only scan of the current Laravel backend and Vue frontend.
Goal: identify which EduMarket modules can be reused for a fashion watch shop, which course-domain modules must be removed, and what the current `Course` entity contains before redesigning it as `Product`.

## Executive Summary

The codebase is still EduMarket at the domain level. The reusable depth is mostly in cross-cutting ecommerce modules: Sanctum authentication, role middleware, cart/order/checkout flow, VNPay signing and transaction logging, dashboard layout patterns, Docker orchestration, BaseModal, and Tailwind token plumbing.

The shallow seam is the catalog item. `Course` is not isolated behind a small interface; it leaks through cart items, order items, checkout snapshots, payment post-processing, admin stats, reviews, AI, curriculum, and frontend UI copy. For the watch shop, the best migration path is to keep the ecommerce/payment/admin scaffolding, then replace the `Course` module with a deeper `Product` module before touching frontend pages.

## Keep As-Is Or Keep With Renaming

### Auth: Sanctum + Role

Status: keep the module.

Files:

- `backend/app/Http/Controllers/Api/AuthController.php`
- `backend/app/Http/Middleware/EnsureUserHasRole.php`
- `backend/app/Models/User.php`
- `backend/app/Models/Role.php`
- `backend/database/migrations/0001_01_01_000000_create_users_table.php`
- `backend/database/migrations/2026_07_03_100001_create_roles_table.php`
- `backend/database/migrations/2026_07_03_100002_add_profile_columns_to_users_table.php`
- `frontend/src/stores/auth.js`
- `frontend/src/services/api.js`
- `frontend/src/router/index.js`

Why it keeps: the module interface is generic enough: register, login, logout, profile, avatar, password reset, token storage, route guards, and `role:<name>` middleware. The implementation currently assumes roles `admin`, `instructor`, and `student`; for a watch shop, keep `admin` and customer-facing auth, then rename or remove `instructor` flows depending on whether sellers/vendors exist.

Watch-shop adjustment: roles should likely become `admin`, `customer`, and optionally `staff`. Do not keep `student`/`instructor` labels in UI or seeders.

### Cart / Checkout

Status: keep the module interface; replace the item implementation.

Files:

- `backend/app/Services/CartService.php`
- `backend/app/Services/CheckoutService.php`
- `backend/app/Http/Controllers/Api/CartController.php`
- `backend/app/Http/Controllers/Api/CheckoutController.php`
- `backend/app/Models/Cart.php`
- `backend/app/Models/CartItem.php`
- `backend/app/Models/Order.php`
- `backend/app/Models/OrderItem.php`
- `backend/database/migrations/2026_07_03_100008_create_carts_table.php`
- `backend/database/migrations/2026_07_03_100009_create_cart_items_table.php`
- `backend/database/migrations/2026_07_03_100010_create_orders_table.php`
- `backend/database/migrations/2026_07_03_100011_create_order_items_table.php`
- `frontend/src/stores/cart.js`
- `frontend/src/views/CartView.vue`
- `frontend/src/views/CheckoutView.vue`
- `frontend/src/components/Cart*.vue`
- `frontend/src/components/Checkout*.vue`

Why it keeps: cart, checkout, pending order creation, price snapshots, duplicate pending-order reuse, and order history are reusable ecommerce concepts.

What must change: the implementation is course-bound. `cart_items.course_id`, `order_items.course_id`, `CartItem::course()`, `OrderItem::course()`, `CartService::ownsCourse()`, `CheckoutService` course loading, and frontend `item.course.*` all need to become product/variant-aware. A watch shop needs quantity, inventory, SKU/variant snapshots, and probably selected options.

Recommended target seam: `CartItem -> product_variant_id`, `quantity`, `unit_price_snapshot`, `product_name_snapshot`, `variant_label_snapshot`. This gives better locality for product variants and stops order history from depending on mutable product rows.

### Payment Integration: VNPay

Status: keep the module; remove enrollment side effect.

Files:

- `backend/app/Services/VnpayService.php`
- `backend/app/Http/Controllers/VnpayController.php`
- `backend/app/Models/Payment.php`
- `backend/app/Models/VnpayTransaction.php`
- `backend/database/migrations/2026_07_03_100012_create_payments_table.php`
- `backend/database/migrations/2026_07_03_100013_create_vnpay_transactions_table.php`
- `backend/database/migrations/2026_07_04_000001_add_transaction_status_to_vnpay_transactions_table.php`
- `backend/database/migrations/2026_07_13_000001_add_admin_id_to_vnpay_transactions_table.php`
- `backend/tests/Feature/VnpayPaymentTest.php`
- `backend/tests/Feature/VnpayIpnTest.php`
- `backend/tests/Feature/VnpayReturnTest.php`
- `frontend/src/views/VnpayReturnView.vue`

Why it keeps: VNPay URL building, HMAC SHA512 signing, secure hash verification, amount matching, transaction logging, IPN idempotency, and return-page display flow are reusable for watches.

What must change: `VnpayService::processSuccessfulPayment()` creates `Enrollment` records and clears purchased cart items by `course_id`. That side effect must become order fulfillment/inventory behavior. The payment seam should end at "order paid"; fulfillment should be a separate module.

Risk noted: `handleReturn()` currently processes successful payment as well as displaying the result. `AGENTS.md` says only IPN should update order status; for the new shop, return should remain display-only and IPN should be the only state-changing path.

### Admin Dashboard Pattern

Status: keep the layout and CRUD/listing pattern; replace course-specific metrics.

Files:

- `backend/app/Services/AdminDashboardService.php`
- `backend/app/Http/Controllers/Api/AdminDashboardController.php`
- `frontend/src/layouts/DashboardLayout.vue`
- `frontend/src/views/admin/AdminDashboardView.vue`
- `frontend/src/views/admin/UsersManagementView.vue`
- `frontend/src/views/admin/OrdersManagementView.vue`
- `frontend/src/views/admin/VnpayTransactionsView.vue`
- `frontend/src/components/dashboard/*.vue`

Why it keeps: the dashboard module has reusable interface patterns: stats endpoint, paginated lists, filters, order detail modal, VNPay transaction list, revenue chart, user management.

What must change: `AdminDashboardService` counts `Course` statuses and pending course approvals. Sidebar links include course moderation and certification management. For a watch shop, replace those with product catalog, inventory, variants, brands/collections, and possibly order fulfillment.

### Docker Setup

Status: keep with project-name cleanup.

Files:

- `docker-compose.yml`
- `backend/Dockerfile`
- `backend/docker/entrypoint.sh`
- `README-DOCKER.md`

Why it keeps: MySQL, backend, queue worker, frontend, and phpMyAdmin are useful for the new shop. Queue support remains valuable for emails and order notifications.

What must change: service image names, entrypoint path, healthcheck endpoint, default database name, and README copy still say `edumarket`.

### BaseModal.vue

Status: keep as-is.

Files:

- `frontend/src/components/ui/BaseModal.vue`

Why it keeps: it already uses `<Teleport to="body">`, fixed overlay, explicit `w-full`, `min-w-0`, and rem-based max widths to avoid Tailwind token collisions. This is a deep UI utility module with a small interface and reusable implementation.

### Design Token System

Status: keep the token infrastructure; replace the brand palette and copy.

Files:

- `frontend/src/assets/main.css`
- `frontend/src/components/ui/BaseModal.vue`
- `frontend/src/App.vue`
- `frontend/src/components/dashboard/*.vue`

Why it keeps: Tailwind v4 `@theme`, semantic color tokens, typography tokens, spacing tokens, reduced-motion handling, modal width fixes, and utility classes are reusable.

What must change: palette and content are still e-learning flavored: `course-card-element`, course-specific comments, EduMarket labels, and education-inspired UI copy. Also note the existing file defines `--spacing-md/lg/xl`; it compensates for this by restoring `.max-w-*`. Keep that fix or rename spacing tokens in a later UI cleanup.

## Must Delete Or Replace: Old Course Domain

### Curriculum: Chapter / Lesson

Status: delete for watch shop.

Backend files:

- `backend/app/Models/Chapter.php`
- `backend/app/Models/Lesson.php`
- `backend/app/Services/CurriculumService.php`
- `backend/app/Services/CourseProgressService.php`
- `backend/app/Http/Controllers/Api/CurriculumController.php`
- `backend/app/Http/Controllers/Api/LearningController.php`
- `backend/app/Http/Requests/Chapter/*`
- `backend/app/Http/Requests/Lesson/*`
- `backend/app/Http/Resources/ChapterResource.php`
- `backend/app/Http/Resources/ChapterOutlineResource.php`
- `backend/app/Http/Resources/LessonResource.php`
- `backend/app/Http/Resources/LessonOutlineResource.php`
- `backend/database/migrations/2026_07_03_100006_create_chapters_table.php`
- `backend/database/migrations/2026_07_03_100007_create_lessons_table.php`
- `backend/database/migrations/2026_07_03_100015_create_lesson_progress_table.php`

Frontend files:

- `frontend/src/views/LessonPlayerView.vue`
- `frontend/src/views/instructor/CourseCurriculumManageView.vue`
- `frontend/src/components/CourseCurriculum.vue`
- `frontend/src/components/instructor/ChapterFormModal.vue`
- `frontend/src/components/instructor/LessonFormModal.vue`
- `frontend/src/components/LessonComments.vue`
- `frontend/src/components/LessonCommentItem.vue`

Reason: chapters, lessons, YouTube URLs, duration, free previews, lesson completion, and learning progress are pure e-learning domain. There is no equivalent in a fashion watch shop.

### Certification

Status: delete for watch shop.

Backend files:

- `backend/app/Models/Certification.php`
- `backend/app/Services/CertificationBadgeService.php`
- `backend/app/Http/Controllers/Api/CertificationController.php`
- `backend/app/Http/Requests/Certification/*`
- `backend/app/Http/Resources/CertificationResource.php`
- `backend/app/Console/Commands/GenerateCertificationBadges.php`
- `backend/database/migrations/2026_07_05_000002_create_certifications_table.php`
- `backend/database/migrations/2026_07_11_000001_create_certification_course_table.php`
- `backend/database/migrations/2026_07_11_000002_add_visual_fields_to_certifications_table.php`
- `backend/database/seeders/CertificationSeeder.php`
- `backend/database/seeders/CertificationCourseSeeder.php`

Frontend files:

- `frontend/src/views/CertificationsView.vue`
- `frontend/src/views/CertificationDetailView.vue`
- `frontend/src/views/admin/AdminCertificationsView.vue`
- `frontend/src/components/CertificationBanner.vue`

Reason: certification providers, badges, certification-course pivot, and badge generation are not watch-shop concepts.

### Enrollment / Learning Progress

Status: delete.

Files:

- `backend/app/Models/Enrollment.php`
- `backend/app/Models/LessonProgress.php`
- `backend/database/migrations/2026_07_03_100014_create_enrollments_table.php`
- `backend/database/migrations/2026_07_03_100015_create_lesson_progress_table.php`
- `backend/app/Http/Resources/EnrollmentResource.php`
- `backend/app/Http/Resources/EnrollmentStudentResource.php`
- `frontend/src/views/MyCoursesView.vue`
- `frontend/src/views/instructor/CourseStudentsView.vue`

Reason: the watch shop needs order fulfillment, shipping, stock reservation, and customer order history, not course ownership or lesson completion.

### AI Learning Assistant

Status: delete or redesign from scratch.

Files:

- `backend/app/Services/AiChatService.php`
- `backend/app/Services/OpenAiService.php` if no product assistant is planned
- `backend/app/Http/Controllers/Api/AiChatController.php`
- `backend/app/Http/Requests/Ai/ChatRequest.php`
- `backend/app/Models/AiChatSession.php`
- `backend/app/Models/AiChatMessage.php`
- `backend/database/migrations/2026_07_03_100018_create_ai_chat_sessions_table.php`
- `backend/database/migrations/2026_07_03_100019_create_ai_chat_messages_table.php`
- `frontend/src/components/ChatbotWidget.vue`

Reason: current AI prompt, authorization, and context are lesson/course-specific. A future watch assistant would need product recommendation context, style preferences, budget, movement type, wrist size, and stock availability; the old implementation is a shallow fit for that.

### Course-Specific Review And Comment Logic

Status: partially delete, partially redesign.

Delete:

- `backend/app/Models/Comment.php`
- `backend/app/Services/CommentService.php`
- `backend/app/Http/Controllers/Api/CommentController.php`
- `backend/app/Http/Requests/Comment/*`
- `backend/database/migrations/2026_07_03_100017_create_comments_table.php`

Reason: comments are tied to `lesson_id`.

Redesign:

- `backend/app/Models/Review.php`
- `backend/app/Services/ReviewService.php`
- `backend/app/Http/Controllers/Api/ReviewController.php`
- `backend/database/migrations/2026_07_03_100016_create_reviews_table.php`
- `frontend/src/components/CourseReviews.vue`

Reason: product reviews are useful, but current reviews use `course_id`, enrolled-course assumptions, and course rating aggregation. Keep the idea, not the implementation.

### Instructor Domain

Status: delete unless the watch shop has marketplace sellers.

Files:

- `frontend/src/views/instructor/*`
- `backend/app/Services/InstructorDashboardService.php`
- `backend/app/Http/Controllers/Api/InstructorDashboardController.php`
- Instructor routes under `/api/instructor`
- `User::courses()`, `instructorTotalCourses()`, `instructorTotalStudents()`, `instructorRatingAvg()`

Reason: the requested project is a fashion watch shop, not a multi-instructor marketplace. If there are vendors, redesign as `seller`/`vendor`; do not keep instructor semantics.

### Seeders And Demo Data

Status: delete and recreate.

Files:

- `backend/database/seeders/CourseSeeder.php`
- `backend/database/seeders/DemoCourseDetailSeeder.php`
- `backend/database/seeders/DemoInstructorSeeder.php`
- `backend/database/seeders/DemoLearningSeeder.php`
- `backend/database/seeders/DemoLessonSeeder.php`
- `backend/database/seeders/CertificationSeeder.php`
- `backend/database/seeders/CertificationCourseSeeder.php`
- `backend/database/seeders/CategorySeeder.php` should be rewritten for watch categories/brands/collections.

Reason: all demo data is education/platform content.

## Current Course Entity

### Database Fields

Defined by `backend/database/migrations/2026_07_03_100005_create_courses_table.php` plus `2026_07_11_000004_add_requirements_to_courses_table.php`.

Current columns:

- `id`
- `instructor_id` -> `users.id`, cascade delete
- `category_id` -> `categories.id`, cascade delete
- `title`
- `slug`, unique
- `description`, nullable
- `content`, nullable long text; used as detailed description/syllabus
- `requirements`, nullable JSON; added later
- `thumbnail`, nullable
- `price`, decimal(12,2), default 0
- `discount_price`, decimal(12,2), nullable
- `level`, enum: `beginner`, `intermediate`, `advanced`; default `beginner`
- `status`, enum: `draft`, `pending`, `approved`, `rejected`; default `draft`
- `reject_reason`, nullable text
- `total_students`, unsigned integer, default 0
- `rating_avg`, decimal(3,2), default 0
- `published_at`, nullable timestamp
- `created_at`, `updated_at`
- `deleted_at` via soft deletes

### Model Fillable

From `backend/app/Models/Course.php`:

- `instructor_id`
- `category_id`
- `title`
- `slug`
- `description`
- `content`
- `requirements`
- `thumbnail`
- `price`
- `discount_price`
- `level`
- `status`
- `reject_reason`
- `total_students`
- `rating_avg`
- `published_at`

### Casts And Accessors

From `Course::casts()`:

- `price` -> `decimal:2`
- `discount_price` -> `decimal:2`
- `requirements` -> `array`
- `total_students` -> `integer`
- `rating_avg` -> `decimal:2`
- `published_at` -> `datetime`

Accessor:

- `final_price` returns `discount_price` when present; otherwise `price`.

This pricing accessor is reusable for `Product`, but product variants need variant-level pricing rules if price differs by diameter, movement, strap, or face color.

### Relationships

From `Course.php`:

- `instructor()` belongs to `User`, foreign key `instructor_id`
- `category()` belongs to `Category`
- `certifications()` belongs to many `Certification`
- `chapters()` has many `Chapter`
- `cartItems()` has many `CartItem`
- `orderItems()` has many `OrderItem`
- `enrollments()` has many `Enrollment`
- `reviews()` has many `Review`

For `Product`, only these concepts map cleanly:

- `category()` can remain, with watch categories/collections.
- `cartItems()` and `orderItems()` should point to `ProductVariant`, not directly to `Product`, if variants are purchasable.
- `reviews()` can remain after changing `course_id` to `product_id`.

These should be removed:

- `instructor()`
- `certifications()`
- `chapters()`
- `enrollments()`

### Current Course Interface Surface

The `Course` module is used by:

- Public catalog: `/api/courses`, `/api/courses/{course}`, related courses, category pages, search.
- Instructor CRUD: create/update/delete/submit course.
- Admin moderation: approve/reject/update status.
- Cart and checkout: `cart_items.course_id`, `order_items.course_id`, price snapshot from `final_price`.
- Payment: successful payment creates `Enrollment` per order item.
- Learning: my courses, lesson player, lesson completion.
- AI: approved course context and lesson context.
- Reviews: `reviews.course_id` and `Course::rating_avg`.
- Frontend: `CourseCard`, `CourseDetailView`, `CoursePurchaseCard`, `CourseCurriculum`, admin course review modal, instructor course forms.

This is a wide interface. Deleting `Course` today would break most of the app, which is the clearest signal that the replacement should be planned as one deep `Product Catalog` module rather than scattered renames.

## Product Redesign Basis: Watch Variant 4 Dimensions

Target core should not be a simple rename of `courses` to `products`.

Recommended entities:

- `products`: brand/category/name/slug/description/content/base price/status/images/ratings.
- `product_variants`: one purchasable SKU per combination.
- `strap_colors`: or normalized option table for strap/bracelet color.
- `dial_colors`: face color.
- `diameters`: e.g. 36mm, 38mm, 40mm, 42mm.
- `movements`: quartz, automatic, mechanical, solar.

Minimum `product_variants` fields:

- `product_id`
- `sku`, unique
- `strap_color`
- `dial_color`
- `diameter_mm`
- `movement_type`
- `price`
- `discount_price`
- `stock_quantity`
- `is_active`
- optional `image`

Important seam: cart/order should reference `product_variant_id`. Orders should store snapshots so historical orders survive product/variant edits.

## Migration Checklist

1. Create a deep `Product Catalog` module first: `Product`, `ProductVariant`, variant option validation, product resources, and product admin CRUD.
2. Move cart and checkout from `course_id` to `product_variant_id`, add `quantity`, stock checks, and variant snapshots.
3. Keep VNPay signing and transaction logging, but move enrollment creation out of payment success.
4. Replace course routes with product routes; remove `/courses/{course}/chapters`, `/my-courses`, `/lessons/*`, `/certifications/*`, `/ai/chat` if no product assistant is planned.
5. Replace frontend course pages with product listing/detail/variant selector.
6. Replace admin course moderation with product and inventory management.
7. Rewrite seeders for watch brands, categories, products, variants, and demo orders.
8. After domain deletion, remove stale tests for course, curriculum, certification, learning, AI lesson chat; keep and adapt auth, commerce, VNPay, admin order tests.

## High-Risk Couplings To Watch

- Payment success currently creates `Enrollment`; this must not survive in watch-shop payment flow.
- `OrderItem` has only `course_id` and `price`; it lacks quantity, SKU, selected variant options, and product snapshots.
- Frontend cart/checkout computes display data from `item.course.*`; it needs `item.variant.product.*` or flattened snapshots.
- Admin dashboard is reusable as a pattern, but stats are course-moderation oriented.
- `frontend/src/api/axios.js` exists but most app code imports `frontend/src/services/api.js`; keep one axios interface during cleanup to improve locality.
- Docker names and healthcheck still point at EduMarket.

## Final Recommendation

Keep the infrastructure and commerce skeleton, but treat `Course` as a domain module to replace, not rename. The first architecture move should be: deepen `Product Catalog` with `ProductVariant` as the purchasable interface, then adapt cart/order/payment around that one seam.

No code changes were made during this audit.
