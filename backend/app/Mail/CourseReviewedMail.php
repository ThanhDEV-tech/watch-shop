<?php

namespace App\Mail;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseReviewedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Course $course) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->course->status === 'approved'
                ? 'Khóa học của bạn đã được duyệt'
                : 'Khóa học cần chỉnh sửa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.courses.reviewed',
            with: [
                'manageCoursesUrl' => rtrim((string) config('app.frontend_url'), '/').'/instructor/courses',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
