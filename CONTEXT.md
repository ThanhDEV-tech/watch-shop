# Watchora Domain Context

This file is the domain glossary and decision log for converting the old EduMarket codebase into Watchora, a fashion watch shop.

## Product Direction

Watchora sells popular fashion watches for men and women across multiple brands and price ranges.

The MVP keeps reusable infrastructure from EduMarket:

- Auth with Sanctum and roles
- Cart and checkout flow
- VNPay payment integration
- Admin dashboard pattern
- Docker setup
- `BaseModal.vue`
- Frontend design token system

The MVP removes EduMarket-specific learning concepts:

- Course
- Chapter
- Lesson
- Certification
- Enrollment
- Lesson progress
- Instructor dashboards
- AI learning assistant behavior, while keeping AI Chat infrastructure for the Watchora shopping assistant

## Roles

### Admin

An internal user who manages products, brands, categories, collections, variants, inventory, orders, shipping zones, VNPay logs, and customer accounts.

### Customer

A signed-in buyer. Checkout requires login in the MVP. Guest checkout is not included.

## Catalog Glossary

### Product

The main sellable watch model shown in catalog/listing/detail pages.

A product is not directly purchased. A customer purchases a product variant.

Product fields should include:

- `brand_id`
- `category_id`
- `name`
- `slug`
- `gender_target`
- `description`
- `content`
- `thumbnail`
- `case_material`
- `strap_material`
- `glass_material`
- `water_resistance`
- `warranty_months`
- `warranty_note`
- `status`
- `rating_avg`

`products` should not contain `base_price` in the MVP. Listing/detail prices are derived from active variants, usually displayed as "from {min final_price}".

### Product Variant

The purchasable SKU for a specific combination of watch options.

Variant dimensions:

- Strap color: `strap_color`
- Dial color: `dial_color`
- Diameter: `diameter_mm`
- Movement: `movement_type`

Product variant fields should include:

- `product_id`
- `sku`
- `strap_color`
- `dial_color`
- `diameter_mm`
- `movement_type`
- `price`
- `discount_price`
- `stock_quantity`
- `image`
- `is_active`

`price`, `discount_price`, and `final_price` live on the variant, not the product. Cart and order pricing always comes from the selected variant.

There must be a unique composite constraint on:

```text
product_id + strap_color + dial_color + diameter_mm + movement_type
```

### SKU

The unique code for a product variant.

Admin may enter `sku` manually. It must be unique. If admin leaves it blank, the system generates a readable SKU such as:

```text
WAT-{PRODUCT_ID}-{STRAP}-{DIAL}-{DIAMETER}-{MOVEMENT}
```

### Brand

A public-facing watch brand.

Fields:

- `name`
- `slug`
- `description`
- `logo`
- `country`
- `is_active`

Product belongs to Brand. Brand names are not free text on products.

`brands.country` is displayed lightly on brand pages and product detail pages, but it is not a primary MVP filter.

Supplier/vendor management is not part of the MVP.

### Category

A stable product classification, not a marketing campaign and not a gender/brand grouping.

MVP categories:

- Dress Watch
- Sport Watch
- Casual Watch
- Minimal Watch
- Sport-Casual

Do not use brand or gender as category because both have separate fields.

Do not use Couple Watch as category. Couple Watches is a collection.

### Collection

A flexible marketing group for seasonal campaigns, events, homepage rails, and landing pages.

Examples:

- Summer 2026
- Office Style
- Couple Watches
- Gift for Her

Collection differs from category:

- Category is stable and structural.
- Collection is marketing-oriented and can be temporary.

Product can belong to many collections. Use a `product_collection` pivot table.

Pivot fields:

- `product_id`
- `collection_id`
- `display_order`

`display_order` is an integer entered manually by admin. Drag-and-drop UI is not required.

Collection may have:

- `start_date`
- `end_date`

Collections outside their active date range should be hidden from public campaign surfaces.

### Gender Target

A product field used for primary catalog filtering.

Allowed values:

- `men`
- `women`
- `unisex`

Public behavior:

- Men's page query: `gender_target IN (men, unisex)`
- Women's page query: `gender_target IN (women, unisex)`
- There is no separate Unisex page in the MVP.

### Strap Color And Dial Color

Stored directly on `product_variants` as strings, validated by code enum, not separate database tables.

Initial allowed colors:

- Đen
- Bạc
- Vàng gold
- Vàng rose
- Trắng
- Xanh navy
- Nâu

Maintain a static color-to-hex mapping in backend or frontend config so variant selectors can render color swatches instead of text-only dropdowns.

When creating or seeding variants, `strap_color` must make material sense with the product's `strap_material`. Metal straps should only use Äen, Báº¡c, VÃ ng gold, or VÃ ng rose. Leather, fabric, and rubber straps should only use Äen, NÃ¢u, Xanh navy, or Tráº¯ng. Do not assign random colors that do not match the strap material, such as NÃ¢u for a metal strap.

### Movement Type

Stored on `product_variants.movement_type` as a database enum.

MVP values:

- `quartz`
- `automatic`

UI labels:

- Quartz
- Automatic

Do not translate these labels to Vietnamese in the UI.

If future values such as solar or mechanical are needed, add them later with a migration that alters the enum. Do not create a movement table in the MVP.

### Diameter

Stored on `product_variants.diameter_mm` as an unsigned integer.

Validation:

- Minimum: `24`
- Maximum: `50`

UI displays values as `40mm`.

### Warranty

Stored on Product:

- `warranty_months`: integer, default `12`, may be `0`
- `warranty_note`: nullable text

`warranty_months` is the primary structured source for badges and filters. `warranty_note` is for details such as "Bảo hành máy 12 tháng, không bảo hành dây da".

## Pricing

Currency is VND.

Use `decimal(12,2)` for monetary values, matching the old Course price structure.

Validation:

- Minimum: `0`
- Maximum: `50,000,000`

Seed/demo data should focus on `500,000` to `15,000,000` VND for popular fashion watches.

Variant final price logic:

```text
final_price = discount_price if present, otherwise price
```

MVP does not include coupons. Simple sale pricing uses `product_variants.discount_price`.

## Product Images

Product has a main thumbnail and a gallery.

Product fields:

- `thumbnail`

Product gallery table: `product_images`

Fields:

- `product_id`
- `image_path`
- `alt_text`
- `display_order`
- `is_primary`

Product variants may have nullable `image`. This image is used to update the main display when a customer selects a strap/dial color swatch.

The main gallery belongs to Product, not to each variant, to avoid duplicated image data.

## Product Status And Visibility

Product status values:

- `draft`
- `active`
- `inactive`

Only `active` products appear publicly.

There is no instructor-style approval workflow (`pending`, `approved`, `rejected`) in Watchora.

Product variants have `is_active` boolean.

Variant visibility:

- `is_active = false`: hidden entirely
- `is_active = true` with `stock_quantity = 0`: can be shown as disabled/out of stock

Product listing behavior:

- If product has at least one active variant with `stock_quantity > 0`, it is purchasable.
- If all active variants are out of stock, the product can still appear publicly with "Hết hàng" badge and disabled purchase button.
- Admin can hide it completely by setting product status to `inactive`.

## Inventory

There is one total warehouse.

Stock is tracked per SKU/variant on:

```text
product_variants.stock_quantity
```

Checkout behavior:

- When creating a pending order, validate `stock_quantity >= quantity`.
- Do not subtract stock at checkout/pending-order creation.

Payment/IPN behavior:

- Only VNPay IPN can trigger paid-order side effects.
- On paid IPN, use a database transaction and `lockForUpdate()` on each affected `product_variants` row.
- After locking, check stock again.
- If stock is enough, subtract stock and record stock movements.
- If stock is not enough, do not subtract below zero. Mark the order as `paid_stock_issue`.

### Stock Movement

Use `stock_movements` from the MVP to audit inventory changes.

Fields:

- `product_variant_id`
- `type`
- `quantity_change`
- `stock_after`
- `order_id`
- `note`
- `created_by`

Initial movement types:

- `admin_adjustment`
- `order_paid`
- `refund_adjustment`

For `admin_adjustment`, `note` is required and `created_by` is the current admin.

## Cart And Checkout

Cart remains user-based. Customer must be authenticated.

Cart item fields:

- `cart_id`
- `product_variant_id`
- `quantity`

Cart reads live product/variant data for current price and stock. It does not need full product snapshots in MVP.

At checkout:

- Validate variant is active.
- Validate product is active.
- Validate stock is enough.
- Validate shipping zone is active.
- Create pending order.
- Snapshot official order item data into `order_items`.

## Order

Order statuses:

- `pending`
- `paid`
- `paid_stock_issue`
- `failed`
- `cancelled`
- `shipping`
- `completed`
- `refunded`

There is no `returned` status in MVP.

Order stores shipping/contact snapshots:

- `receiver_name`
- `receiver_phone`
- `shipping_address`
- `shipping_note`
- `shipping_zone_name`
- `shipping_fee`

Other useful order fields:

- `code`
- `user_id`
- `subtotal_amount`
- `shipping_fee`
- `total_amount`
- `status`
- `paid_at`
- `refunded_at`
- `refund_note`

### Order Item

Order item stores snapshots so order history does not change when admin edits product or variant data later.

Fields:

- `order_id`
- `product_id`
- `product_variant_id`
- `product_name`
- `brand_name`
- `sku`
- `strap_color`
- `dial_color`
- `diameter_mm`
- `movement_type`
- `unit_price`
- `quantity`
- `line_total`
- `thumbnail_url`

## Payment

MVP payment method is VNPay Sandbox only.

Not included in MVP:

- COD
- VNPay refund API
- Production payment gateway

VNPay rules:

- TMN code and hash secret live in `.env`.
- Use HMAC SHA512.
- Return URL is display-only.
- IPN is the only place that updates order state and triggers side effects.
- IPN must be idempotent.

Payment success outcomes:

- Normal stock: order becomes `paid`, stock is subtracted.
- Stock issue after payment: order becomes `paid_stock_issue`, stock is not subtracted below zero.

For `paid_stock_issue`, admin dashboard must highlight the order so admin can contact the customer to switch variant or refund manually.

## Refund

Refund is manual in MVP.

The system does not call VNPay refund APIs.

Admin action: "Mark as refunded".

Allowed source statuses:

- `paid_stock_issue`
- `paid`
- `shipping`

When admin marks refunded:

- Set `refunded_at`
- Set `refund_note`
- Change status to `refunded`

The real money transfer happens outside the system.

## Shipping

No shipping carrier API integration in MVP.

Shipping is calculated by customer-selected zone.

Use `shipping_zones`.

Fields:

- `name`
- `fee`
- `is_active`
- `display_order`

Initial zones:

- Nội thành
- Ngoại thành
- Tỉnh/thành khác

Customer selects zone during checkout. The system does not auto-detect zone from address.

Orders snapshot:

- `shipping_zone_name`
- `shipping_fee`

This preserves order history if admin later changes zone fees.

Shipping fee is not based on weight or dimensions in MVP.

## Reviews

Product reviews exist, but they must be redesigned from the old course review module.

Rules:

- Only customers with a `completed` order containing a variant of the product can review that product.
- Each customer can have at most one review per product.
- Product rating average is calculated from product reviews.

Lesson comments are removed because they belong to the old learning domain.

## Admin

Reuse the existing Admin Dashboard pattern, but replace course moderation with watch-shop operations.

Admin dashboard should include:

- Product catalog management
- Variant/SKU management
- Brand management
- Category management
- Collection management
- Shipping zone management
- Order management
- VNPay transaction logs
- User/customer management
- Stock adjustment and stock movement history
- Highlighted `paid_stock_issue` orders

MVP does not need a separate fulfillment module. Extend the existing Orders Management pattern with filters and actions for:

- `paid_stock_issue`
- `shipping`
- `completed`
- `refunded`

## Public Routes And Slugs

Use unique slugs for:

- products
- brands
- categories
- collections

System generates slugs from names and appends suffixes when needed.

Expected public routes:

- `/products/{slug}`
- `/brands/{slug}`
- `/collections/{slug}`

## Email

Keep queued email delivery.

On normal paid IPN:

- Send order confirmation email.

On `paid_stock_issue`:

- Send a payment-received email explaining that admin must confirm stock or contact the customer.

Email content must no longer mention courses, learning, enrollment, lessons, or instructors.

## AI Shopping Assistant

AI Chat infrastructure is retained for Watchora and must not be removed during cleanup.

Planned assistant purposes:

- Advise customers on choosing watches by style, budget, gender target, movement type, strap/dial preferences, and stock availability.
- Answer store policy questions such as warranty, payment, shipping, order status, refund handling, and support flow.

The restored implementation may still contain EduMarket lesson/course-specific prompt, authorization, and suggestion logic. Do not redesign that logic during Phase 6 cleanup; handle it in a separate AI Shopping Assistant redesign step.

## Excluded From MVP

Do not include these in MVP:

- Guest checkout
- COD
- Coupons
- Wishlist/favorites
- Supplier management
- Address book
- Carrier API integration
- Shipping by weight/dimensions
- Returned order flow
- Product tags
- Multi-warehouse inventory
- Marketplace sellers/vendors

## Naming Rules

Use Watchora terms in new code and docs:

- Product, not Course
- ProductVariant or Variant, not Lesson/Chapter
- Customer, not Student
- Admin, not Instructor
- Collection, not Certification
- Stock movement, not Lesson progress
- Order fulfillment, not Enrollment

When converting old code, remove old EduMarket learning concepts instead of keeping them under renamed labels.
