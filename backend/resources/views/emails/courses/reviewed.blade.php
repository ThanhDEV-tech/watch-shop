<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kết quả duyệt khóa học</title>
</head>
<body style="margin: 0; padding: 24px; background: #f3f5f8; color: #16233a; font-family: Arial, sans-serif;">
    <div style="max-width: 640px; margin: 0 auto; overflow: hidden; border: 1px solid #dce2ea; border-radius: 10px; background: #ffffff;">
        <div style="padding: 24px; background: #16233a; color: #ffffff;">
            <h1 style="margin: 0; font-size: 24px;">
                {{ $course->status === 'approved' ? 'Khóa học đã được duyệt' : 'Khóa học cần chỉnh sửa' }}
            </h1>
        </div>

        <div style="padding: 24px; line-height: 1.6;">
            <p style="margin-top: 0;">Xin chào {{ $course->instructor->name }},</p>
            <p>
                Khóa học <strong>{{ $course->title }}</strong>
                {{ $course->status === 'approved' ? 'đã được EduMarket duyệt và có thể xuất bản.' : 'chưa được duyệt ở lần xem xét này.' }}
            </p>

            @if ($course->status === 'rejected')
                <div style="margin: 20px 0; padding: 16px; border-left: 4px solid #ff6b4a; background: #fff4f1;">
                    <strong>Lý do cần chỉnh sửa:</strong><br>
                    {{ $course->reject_reason }}
                </div>
            @endif

            <a href="{{ $manageCoursesUrl }}" style="display: inline-block; margin-top: 16px; padding: 12px 18px; border-radius: 8px; background: #ff6b4a; color: #ffffff; text-decoration: none; font-weight: bold;">
                Mở khóa học của tôi
            </a>

            <p style="margin: 28px 0 0;">Cảm ơn bạn đã đồng hành cùng EduMarket.</p>
        </div>
    </div>
</body>
</html>
