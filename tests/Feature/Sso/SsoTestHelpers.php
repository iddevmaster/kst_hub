<?php

namespace Tests\Feature\Sso;

use App\Models\agency;
use App\Models\branch;
use App\Models\User;

trait SsoTestHelpers
{
    protected function makeAgency(string $agnId, string $name = null): agency
    {
        return agency::create(['name' => $name ?? "Agency {$agnId}", 'agn_id' => $agnId]);
    }

    protected function makeBranch(agency $agency, string $brnId, string $name = null, ?string $source = null): branch
    {
        return branch::create([
            'name' => $name ?? "Branch {$brnId}",
            'brn_id' => $brnId,
            'agency' => $agency->id,
            'source' => $source,
        ]);
    }

    protected function makeUser(array $attrs = []): User
    {
        return User::forceCreate(array_merge([
            'name' => 'Test User',
            'username' => 'u' . uniqid(),
            'password' => 'secret-password',
            'agency' => '1',
            'brn' => '1',
            'dpm' => '',
            'role' => 'customer',
        ], $attrs));
    }

    /** Profile shaped like GET {SSO_SUB_HOST}/api/user (fake values). */
    protected function subscriptionProfile(array $overrides = []): array
    {
        return array_replace_recursive([
            'sub' => '0b9f3c2a-1111-4222-8333-444455556666',
            'name' => 'นาย สมชาย ทดสอบ',
            'username' => '1000000000001',
            'role' => 'customer',
            'agency' => ['agn_id' => '51f0aaaa-1111-4222-8333-444455556666', 'name' => 'โรงเรียน A'],
            'branch' => ['brn_id' => '51f0aaaa-1111-4222-8333-444455556666', 'name' => 'โรงเรียน A'],
            'courses' => [['course_type' => 'CAR', 'name' => 'รถยนต์']],
            'school' => ['id' => '51f0aaaa-1111-4222-8333-444455556666', 'slug' => 'school-a', 'name' => 'โรงเรียน A'],
            'school_branch' => ['id' => '7c1d0000-1111-4222-8333-444455556666', 'code' => 'HQ', 'name' => 'สำนักงานใหญ่'],
            'vehicle_type' => 'CAR',
        ], $overrides);
    }
}
