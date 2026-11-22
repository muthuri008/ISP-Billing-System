<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Services\Network\RadiusPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RadiusPackageProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_radius_profile_uses_package_download_and_upload_mbps(): void
    {
        $package = Package::factory()->create([
            'download_mbps' => 100,
            'upload_mbps' => 25,
        ]);

        $profile = app(RadiusPolicyService::class)->syncProfile($package);

        $this->assertSame('100', (string) $profile->download_speed);
        $this->assertSame('25', (string) $profile->upload_speed);
        $this->assertSame('100/25', data_get($profile->attributes, 'Mikrotik-Rate-Limit'));
    }
}
