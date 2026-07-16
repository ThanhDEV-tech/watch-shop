<?php

namespace Tests\Feature;

use App\Models\Certification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_certifications_are_available_publicly(): void
    {
        Certification::query()->create([
            'name' => 'AWS Certified Solutions Architect',
            'provider' => 'AWS',
            'description' => 'Architecture certification.',
            'icon' => 'cloud_done',
            'exam_info' => 'Exam information.',
        ]);

        $this->getJson('/api/certifications')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.provider', 'AWS')
            ->assertJsonPath('data.0.icon', 'cloud_done')
            ->assertJsonStructure([
                'success',
                'data' => [[
                    'id', 'name', 'provider', 'description', 'icon', 'exam_info', 'external_link',
                ]],
                'message',
            ]);
    }
}
