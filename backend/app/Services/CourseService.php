<?php

namespace App\Services;

use App\Models\Course;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseService
{
    /** @param array<string, mixed> $data */
    public function create(User $instructor, array $data): Course
    {
        $thumbnail = Arr::pull($data, 'thumbnail');
        $certificationIds = Arr::pull($data, 'certification_ids', []);
        $syncCertifications = (bool) Arr::pull($data, 'sync_certifications', false);
        $data = $this->normalizeListFields($data);

        $course = Course::query()->create([
            ...$data,
            'instructor_id' => $instructor->id,
            'slug' => $this->uniqueSlug($data['title']),
            'thumbnail' => $thumbnail instanceof UploadedFile
                ? $thumbnail->store('course-thumbnails', 'public')
                : null,
            'status' => 'draft',
        ]);

        if ($syncCertifications) {
            $course->certifications()->sync($certificationIds);
        }

        return $course->refresh();
    }

    /** @param array<string, mixed> $data */
    public function update(Course $course, array $data): Course
    {
        $originalStatus = $course->status;
        $thumbnail = Arr::pull($data, 'thumbnail');
        $certificationIds = Arr::pull($data, 'certification_ids', []);
        $syncCertifications = (bool) Arr::pull($data, 'sync_certifications', false);
        $data = $this->normalizeListFields($data);

        if (isset($data['title']) && $data['title'] !== $course->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], $course->id);
        }

        if ($thumbnail instanceof UploadedFile) {
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }

            $data['thumbnail'] = $thumbnail->store('course-thumbnails', 'public');
        }

        $course->update($data);

        if ($syncCertifications) {
            $course->certifications()->sync($certificationIds);
        }

        if ($originalStatus === 'approved') {
            $course->update([
                'status' => 'pending',
                'reject_reason' => null,
                'published_at' => null,
            ]);
        }

        return $course->refresh();
    }

    /** @throws DomainException */
    public function submitForReview(Course $course): Course
    {
        if (! in_array($course->status, ['draft', 'rejected'], true)) {
            throw new DomainException('Chỉ khóa học draft hoặc rejected mới có thể gửi duyệt.');
        }

        if (! $course->chapters()->whereHas('lessons')->exists()) {
            throw new DomainException('Khóa học cần có ít nhất 1 chương và 1 bài học trước khi gửi duyệt');
        }

        $course->update([
            'status' => 'pending',
            'reject_reason' => null,
            'published_at' => null,
        ]);

        return $course->refresh();
    }

    public function changeStatus(Course $course, string $status, ?string $rejectReason): Course
    {
        $allowedTransitions = [
            'draft' => ['pending'],
            'pending' => ['approved', 'rejected'],
        ];

        if (! in_array($status, $allowedTransitions[$course->status] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => ["Không thể chuyển trạng thái từ {$course->status} sang {$status}."],
            ]);
        }

        $course->update([
            'status' => $status,
            'reject_reason' => $status === 'rejected' ? $rejectReason : null,
            'published_at' => $status === 'approved' ? now() : null,
        ]);

        return $course->refresh();
    }

    public function delete(Course $course): void
    {
        if ($course->thumbnail) {
            Storage::disk('public')->delete($course->thumbnail);
        }

        $course->delete();
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'course';
        $slug = $base;
        $counter = 2;

        while (Course::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeListFields(array $data): array
    {
        if (array_key_exists('requirements', $data)) {
            $data['requirements'] = collect(preg_split('/\r\n|\r|\n/', (string) $data['requirements']))
                ->map(fn (string $item): string => trim($item))
                ->filter()
                ->values()
                ->all();
        }

        return $data;
    }
}
