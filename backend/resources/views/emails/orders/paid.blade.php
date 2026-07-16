<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận thanh toán</title>
</head>
<body style="margin: 0; padding: 24px; background: #f3f5f8; color: #16233a; font-family: Arial, sans-serif;">
    <div style="max-width: 640px; margin: 0 auto; overflow: hidden; border: 1px solid #dce2ea; border-radius: 10px; background: #ffffff;">
        <div style="padding: 24px; background: #16233a; color: #ffffff;">
            <h1 style="margin: 0; font-size: 24px;">Thanh toán thành công</h1>
        </div>

        <div style="padding: 24px;">
            <p style="margin-top: 0;">Xin chào {{ $order->user->name }},</p>
            <p>EduMarket đã nhận thanh toán cho đơn hàng <strong>{{ $order->code }}</strong>.</p>

            <h2 style="margin-top: 28px; font-size: 18px;">Khóa học đã mua</h2>
            <ul style="padding-left: 20px; line-height: 1.7;">
                @foreach ($order->items as $item)
                    <li>
                        {{ $item->course->title }}
                        — {{ number_format((float) $item->price, 0, ',', '.') }} ₫
                    </li>
                @endforeach
            </ul>

            <div style="margin-top: 24px; padding: 16px; border-radius: 8px; background: #f7f8fa;">
                <strong>Tổng thanh toán:</strong>
                <span style="float: right; color: #e9573f; font-size: 18px;">
                    {{ number_format((float) $order->total_amount, 0, ',', '.') }} ₫
                </span>
            </div>

            <p style="margin-top: 28px;">Cảm ơn bạn đã tin tưởng EduMarket. Chúc bạn học tập hiệu quả!</p>
        </div>
    </div>
</body>
</html>
