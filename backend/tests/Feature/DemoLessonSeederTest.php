<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Course;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoLessonSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLessonSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_only_adds_demo_curriculum_to_approved_courses_without_chapters(): void
    {
        $instructorRole = Role::query()->create([
            'name' => 'instructor',
            'display_name' => 'Instructor',
        ]);
        $instructor = User::factory()->create(['role_id' => $instructorRole->id]);
        $category = Category::query()->create([
            'name' => 'Demo',
            'slug' => 'demo',
        ]);
        $approvedEmpty = $this->createCourse($instructor, $category, 'approved-empty', 'approved');
        $approvedWithChapter = $this->createCourse($instructor, $category, 'approved-existing', 'approved');
        $approvedWithChapter->chapters()->create([
            'title' => 'Existing chapter',
            'position' => 1,
        ]);
        $draftEmpty = $this->createCourse($instructor, $category, 'draft-empty', 'draft');

        $this->seed(DemoLessonSeeder::class);

        $this->assertSame(2, $approvedEmpty->chapters()->count());
        $this->assertSame(5, $approvedEmpty->chapters()->withCount('lessons')->get()->sum('lessons_count'));
        $this->assertSame(1, $approvedWithChapter->chapters()->count());
        $this->assertSame(0, $draftEmpty->chapters()->count());
        $this->assertDatabaseHas('lessons', [
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_free_preview' => true,
        ]);
    }

    private function createCourse(User $instructor, Category $category, string $slug, string $status): Course
    {
        return Course::query()->create([
            'instructor_id' => $instructor->id,
            'category_id' => $category->id,
            'title' => str($slug)->headline(),
            'slug' => $slug,
            'price' => 299000,
            'status' => $status,
            'published_at' => $status === 'approved' ? now() : null,
        ]);
    }
}
