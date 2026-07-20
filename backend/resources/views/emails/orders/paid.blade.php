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
            <p>Watchora đã nhận thanh toán cho đơn hàng <strong>{{ $order->code }}</strong>.</p>

            <h2 style="margin-top: 28px; font-size: 18px;">Sản phẩm đã mua</h2>
            <ul style="padding-left: 20px; line-height: 1.7;">
                @foreach ($order->items as $item)
                    <li>
                        <strong>{{ $item->product_name }}</strong>
                        @if ($item->sku)
                            <span>({{ $item->sku }})</span>
                        @endif
                        <br>
                        <span style="color: #64748b;">
                            {{ $item->strap_color }} / {{ $item->dial_color }} / {{ $item->diameter_mm }}mm
                            × {{ $item->quantity }}
                        </span>
                        <span style="float: right;">
                            {{ number_format((float) $item->line_total, 0, ',', '.') }} ₫
                        </span>
                    </li>
                @endforeach
            </ul>

            <div style="margin-top: 24px; padding: 16px; border-radius: 8px; background: #f7f8fa;">
                <p style="margin: 0 0 8px;">
                    <strong>Tạm tính:</strong>
                    <span style="float: right;">{{ number_format((float) $order->subtotal_amount, 0, ',', '.') }} ₫</span>
                </p>
                <p style="margin: 0 0 8px;">
                    <strong>Phí vận chuyển:</strong>
                    <span style="float: right;">{{ number_format((float) $order->shipping_fee, 0, ',', '.') }} ₫</span>
                </p>
                <p style="margin: 0; color: #e9573f; font-size: 18px;">
                    <strong>Tổng thanh toán:</strong>
                    <span style="float: right;">{{ number_format((float) $order->total_amount, 0, ',', '.') }} ₫</span>
                </p>
            </div>

            <div style="margin-top: 24px; padding: 16px; border-radius: 8px; border: 1px solid #dce2ea;">
                <p style="margin: 0 0 8px;"><strong>Người nhận:</strong> {{ $order->receiver_name }}</p>
                <p style="margin: 0 0 8px;"><strong>Số điện thoại:</strong> {{ $order->receiver_phone }}</p>
                <p style="margin: 0;"><strong>Địa chỉ:</strong> {{ $order->shipping_address }}</p>
            </div>

            <p style="margin-top: 28px;">Cảm ơn bạn đã chọn Watchora. Chúng tôi sẽ chuẩn bị đơn hàng và cập nhật trạng thái vận chuyển trong thời gian sớm nhất.</p>
        </div>
    </div>
</body>
</html>
