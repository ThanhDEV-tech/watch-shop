# EduMarket — Design Brief (Frontend UI)

Đây là design system để agent (Antigravity) dùng làm chuẩn khi dựng UI. Không tự đổi màu/layout ngoài phạm vi dưới đây trừ khi được yêu cầu.

## Định hướng

Nền tảng bán khóa học online cho lập trình viên. Cảm giác: **chuyên nghiệp, ấm, "được làm bởi dev cho dev"** — không phải một LMS doanh nghiệp lạnh lẽo, cũng không phải marketplace tím-nhạt kiểu Udemy nguyên bản. Layout mượn cấu trúc marketplace đã được kiểm chứng của Udemy; màu sắc và cảm giác thị giác mượn tinh thần tối-ấm của Laracasts.

## 1. Color tokens

```css
:root {
  /* Nền */
  --bg-primary:    #16233A;  /* nền chính, xanh navy đậm */
  --bg-surface:    #1E2F4A;  /* card, nav, panel nổi trên nền chính */
  --bg-surface-2:  #263A5C;  /* hover/active state của surface */

  /* Chữ */
  --text-primary:  #F5F7FA;  /* tiêu đề, nội dung chính */
  --text-muted:    #93A4C2;  /* mô tả phụ, meta info (số học viên, ngày...) */
  --text-disabled: #5C6C89;

  /* Accent — dùng có kiểm soát, không rải khắp trang */
  --accent-primary:   #FF6B4A;  /* CTA chính: "Mua ngay", giá, nút Enroll */
  --accent-primary-hover: #FF8A6B;
  --accent-warn:   #FFB020;  /* rating sao, badge "Bestseller" */
  --accent-success:#3DD68C;  /* trạng thái đã mua / hoàn thành */

  /* Border / divider */
  --border-subtle: #2C3E5E;
}
```

Quy tắc dùng màu: mỗi màn hình chỉ nên có **1 accent chính nổi bật** (thường là nút CTA). Rating sao và badge dùng accent phụ, không cạnh tranh với CTA.

## 2. Typography

- **Display / heading**: `Sora` (600–700) — hình khối geometric, rõ ràng, không dùng tràn lan, chỉ cho H1/H2 và số liệu nổi bật (giá, %).
- **Body**: `Inter` (400–500) — cho toàn bộ nội dung, mô tả khóa học, nav.
- **Utility / mono**: `JetBrains Mono` — cho các chi tiết gợi "developer": thời lượng bài học (`12:45`), số bài (`24 lessons`), tag công nghệ (`Laravel`, `Vue 3`). Đây là chi tiết nhỏ nhưng giúp trang không giống Udemy generic.

Type scale gợi ý: H1 40/48, H2 28/36, H3 20/28, Body 16/24, Caption 13/18.

## 3. Layout (cấu trúc theo Udemy, style theo trên)

```
┌──────────────────────────────────────────────┐
│ Logo   [ 🔍 Tìm khóa học... ]   Danh mục ▾  Giỏ  Login │  ← sticky nav, --bg-surface
├──────────────────────────────────────────────┤
│  HERO: headline lớn (Sora) + search-first     │
│  + vài tag công nghệ hot (mono font)          │
├──────────────────────────────────────────────┤
│  Danh mục ngang (chip scroll)                 │
├──────────────────────────────────────────────┤
│  Lưới course-card (3-4 cột desktop):          │
│  ┌────────────┐ ┌────────────┐ ┌────────────┐│
│  │ thumbnail  │ │ thumbnail  │ │ thumbnail  ││
│  │ Title      │ │ Title      │ │ Title      ││
│  │ Instructor │ │ Instructor │ │ Instructor ││
│  │ ★4.7 (mono)│ │ ★4.8       │ │ ★4.5       ││
│  │ 299.000đ   │ │ 199.000đ   │ │ Free       ││
│  └────────────┘ └────────────┘ └────────────┘│
├──────────────────────────────────────────────┤
│  Footer tối, đơn giản                         │
└──────────────────────────────────────────────┘
```

Trang **Course Detail**: layout 2 cột — nội dung bên trái (mô tả, curriculum dạng accordion, review), sidebar bên phải sticky chứa card mua (giá + nút Enroll màu `--accent-primary` + thumbnail preview).

Trang **Learning/Player**: sidebar trái = danh sách chapter/lesson (dùng mono font cho thời lượng), giữa = video/nội dung bài học, góc dưới phải = chat AI assistant dạng floating panel, nền `--bg-surface` nổi trên `--bg-primary`.

## 4. Signature element

Motif `</>` (code bracket) dùng làm chi tiết trang trí nhất quán — ví dụ: dấu phân cách section, icon trước tag công nghệ, hoặc watermark mờ trong hero. Đây là điểm nhận diện riêng, tránh trang trông giống template LMS chung chung. Chỉ dùng ở 2-3 vị trí, không lạm dụng.

## 5. Polish details (Laracasts-inspired)

Ba yếu tố cụ thể tạo cảm giác "polish" giống Laracasts, áp dụng có chọn lọc — không phải cứ thêm là giống, phải đúng chỗ mới ra chất lượng đó.

**A. Đánh số thứ tự (01 / 02 / 03)**

Chỉ dùng cho nội dung **thực sự có thứ tự**, không dùng làm trang trí:
- Hợp lệ: section "Cách EduMarket hoạt động" (01 Đăng ký → 02 Chọn khóa học → 03 Học → 04 Nhận chứng chỉ), curriculum trong course detail (Chapter 01, 02, 03...).
- Không hợp lệ: lưới course-card, danh sách category — các mục này không có thứ tự thực sự, đánh số ở đây là giả, phá vỡ nguyên tắc "structure phải mang thông tin thật".
- Style: số lớn (`Sora` 700, ~48-64px), màu `--text-disabled` làm nền mờ phía sau, label nội dung đè lên phía trước.

**B. Hover mượt trên card**
- Transition: `all 200ms cubic-bezier(0.16, 1, 0.3, 1)` — ease-out mượt, không bounce, không giật.
- Khi hover: border chuyển từ `--border-subtle` sang `--accent-primary` (opacity ~40%), kèm `translateY(-4px)` nhẹ và shadow tăng dần (`0 12px 24px rgba(0,0,0,0.3)`).
- Ảnh thumbnail bên trong card: `scale(1.03)` trong container `overflow: hidden`, không làm card nhảy layout xung quanh.
- Không tilt 3D theo chuột trên card — giữ nhất quán với mục 6 (Motion & 3D): mượt nhưng phẳng, không phá tốc độ quét mắt khi so sánh nhiều khóa học.

**C. Nền tối có texture**
- `--bg-primary` không phẳng lì tuyệt đối: phủ 1 lớp noise/grain rất nhẹ (opacity 3-4%, SVG `feTurbulence` hoặc PNG noise tile nhỏ lặp lại) để tạo chiều sâu, tránh flat/banding trên các mảng nền lớn.
- Thêm 1 radial gradient glow rất nhẹ phía sau hero (màu `--accent-primary`, opacity ~8%, blur lớn) — tạo điểm sáng nhẹ mà không cần ảnh nặng, không ảnh hưởng thời gian tải.
- Texture chỉ áp lên nền toàn site (`--bg-primary`), **không** áp lên card/surface (`--bg-surface`) — giữ card nổi rõ, tương phản tốt với nền.

## 6. Motion & 3D

Dùng 3D/motion **có kiểm soát** — chỉ 1-2 điểm trên toàn site, không rải ra course-card hay dashboard. Đây là site marketplace, ưu tiên số 1 là tốc độ tải và rõ ràng CTA/giá, không phải gây ấn tượng thị giác.

**Được dùng:**
- **Hero landing page**: motif `</>` (signature element ở mục 4) dạng khối 3D nhẹ, xoay chậm theo scroll hoặc mouse-move (CSS `transform: perspective()` là đủ, không cần WebGL/Three.js cho hero này).
- **Hover trên nút CTA chính** ("Mua ngay", "Enroll"): elevation nhẹ — `box-shadow` sâu hơn + `translateY(-2px)` khi hover, tạo cảm giác nhấn được mà không cần 3D thật.
- **Trang lỗi / empty state** (404, giỏ hàng trống): không nằm trên luồng mua hàng, có thể chơi motion thoải mái hơn — ví dụ icon 3D nhẹ nhàng minh hoạ trạng thái rỗng.

**Không dùng:**
- Lưới course-card: học viên cần quét mắt nhanh để so sánh nhiều khóa cùng lúc — chỉ dùng hover elevation phẳng (shadow tăng nhẹ), **không** tilt/parallax 3D trên card.
- Course player / dashboard: cần tối giản, tập trung nội dung học, motion ở đây gây xao nhãng.
- Danh sách dài (curriculum accordion, review list): không animate từng item khi scroll, chỉ transition mở/đóng đơn giản (~150-200ms).

**Bắt buộc:**
- Tôn trọng `prefers-reduced-motion: reduce` — tắt hết animation/3D khi user bật cờ này trong hệ điều hành.
- Không animate trên mobile nếu ảnh hưởng frame rate — test trên thiết bị tầm trung trước khi chốt.
- Mọi hiệu ứng 3D phải render bằng CSS transform/transition trước, chỉ dùng thư viện WebGL nếu CSS thực sự không đáp ứng được ý tưởng.

## 7. Component notes

- Border-radius nhất quán: 8px cho card/button, 4px cho input/tag.
- Card có `border: 1px solid var(--border-subtle)`, không dùng shadow nặng — giữ cảm giác phẳng, tối, gọn.
- Button primary: nền `--accent-primary`, chữ trắng, không bo tròn quá (pill), giữ 8px radius để đồng bộ.
- Empty state / lỗi: viết bằng giọng rõ ràng, chỉ dẫn hành động tiếp theo (theo đúng tinh thần AGENTS.md — không dùng câu chung chung).

## 8. Cách dùng với Antigravity

Dán toàn bộ file này vào Agent chat (hoặc lưu vào `.agents/design.md` trong repo để agent tự đọc), sau đó prompt:

> "Dựng trang chủ (landing + course listing) theo đúng design-brief.md, dùng Tailwind CSS. Chưa cần nối API, dùng dữ liệu mock."

Làm từng trang một (Landing → Course Detail → Dashboard → Player), review bằng Browser Subagent sau mỗi trang trước khi sang trang tiếp theo, để tránh lệch design system giữa các trang.