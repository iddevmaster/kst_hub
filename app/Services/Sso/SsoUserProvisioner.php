<?php

namespace App\Services\Sso;

use App\Models\agency;
use App\Models\branch;
use App\Models\course_group;
use App\Models\SsoCourseMap;
use App\Models\User;
use App\Models\user_has_group;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class SsoUserProvisioner
{
    /** Subscription SSO: one shared agency, the school is a branch. */
    public function provisionSubscription(array $p): User
    {
        $school = $p['school'] ?? null;
        $idNumber = (string) ($p['username'] ?? '');
        if (!is_array($school) || empty($school['id']) || $idNumber === '') {
            throw new SsoLoginRejected('ไม่สามารถอ่านข้อมูลโรงเรียนได้ กรุณาติดต่อโรงเรียน');
        }
        $agency = agency::where('agn_id', (string) config('services.sso_subscription.agency'))->first();
        if (!$agency) {
            throw new \RuntimeException('Subscription agency is not configured.');
        }

        // Outside the user transaction on purpose: the branch must be committed before the lock is
        // released, otherwise a concurrent first login of the same school would not see it.
        $branch = $this->subscriptionBranch($school, $agency);

        return DB::transaction(function () use ($p, $idNumber, $agency, $branch) {
            $user = $this->findOrNewUser($idNumber, $agency, (string) ($p['name'] ?? ''), 'subscription');
            $isNew = !$user->exists;
            if ($isNew) {
                $user->role = 'customer';
            }
            $user->brn = (string) $branch->id;
            $user->save();
            if ($isNew) {
                $this->ensureRole($user, 'customer');
            }

            $this->setOnlyGroup($user, $this->mappedGroupId($p));
            return $user;
        }, 3);
    }

    /** Find or create the school's branch; serialised per school (branches.brn_id has no unique index). */
    private function subscriptionBranch(array $school, agency $agency): branch
    {
        return Cache::lock('sso-branch:' . $school['id'], 15)->block(10, function () use ($school, $agency) {
            $branch = branch::where('brn_id', (string) $school['id'])->first();
            if (!$branch) {
                return branch::create([
                    'name' => (string) ($school['name'] ?? $school['id']),
                    'brn_id' => (string) $school['id'],
                    'agency' => $agency->id,
                    'source' => 'subscription',
                ]);
            }
            if (!empty($school['name']) && $branch->name !== $school['name']) {
                $branch->update(['name' => $school['name']]);
            }
            return $branch;
        });
    }

    /** Legacy IDDrives SSO. Course group lookup by code only is kept on purpose (see plan). */
    public function provisionIdrives(array $p): User
    {
        $agnId = $p['agency']['agn_id'] ?? null;
        if (!is_string($agnId) || $agnId === '') {
            throw new SsoLoginRejected('Failed to get agency information! Try again.');
        }
        $brnId = $p['branch']['brn_id'] ?? null;
        if (!is_string($brnId) || $brnId === '') {
            throw new SsoLoginRejected('Failed to get branch information! Try again.');
        }
        $courseType = $p['courses'][0]['course_type'] ?? null;
        if (!is_string($courseType) || $courseType === '') {
            throw new SsoLoginRejected('Failed to get course information! Try again.');
        }
        $username = (string) ($p['username'] ?? '');
        if ($username === '') {
            throw new SsoLoginRejected('Failed to get user information! Try again.');
        }
        $role = (string) ($p['role'] ?? '');
        if ($role === '') {
            $role = 'customer';
        }

        return DB::transaction(function () use ($p, $agnId, $brnId, $courseType, $username, $role) {
            $agency = $this->legacyAgency($agnId, (string) ($p['agency']['name'] ?? ''));
            $branch = $this->legacyBranch($brnId, (string) ($p['branch']['name'] ?? ''), $agency);

            $user = $this->findOrNewUser($username, $agency, (string) ($p['name'] ?? ''), 'idrives');
            $isNew = !$user->exists;
            if ($isNew) {
                $user->role = $role;
            }
            $user->brn = (string) $branch->id;
            $user->save();
            if ($isNew) {
                $this->ensureRole($user, $role);
            }

            $group = course_group::where('code', $courseType)->first()
                ?? course_group::create([
                    'name' => (string) ($p['courses'][0]['name'] ?? $courseType),
                    'agn' => $user->agency,
                    'by' => 'api:sso',
                    'code' => $courseType,
                ]);
            $this->setOnlyGroup($user, $group->id);
            return $user;
        }, 3);
    }

    /** Match by (id_number, agency), then an untagged legacy row; otherwise a new unsaved user. */
    public function findOrNewUser(string $idNumber, agency $agency, string $name, string $source): User
    {
        $agencyId = (string) $agency->id;
        $user = User::where('id_number', $idNumber)->where('agency', $agencyId)->first()
            ?? User::whereNull('id_number')->where('username', $idNumber)->where('agency', $agencyId)->first();

        if (!$user) {
            $user = new User();
            $user->username = $this->uniqueUsername($idNumber, $agency);
            $user->agency = $agencyId;
            $user->dpm = '';
        }
        $user->id_number = $idNumber;
        $user->sso_source = $source;
        if ($name !== '') {
            $user->name = $name;
        }
        // The 'hashed' cast hashes this on save; it is never a usable credential.
        $user->password = Str::random(64);
        return $user;
    }

    public function uniqueUsername(string $idNumber, agency $agency): string
    {
        if (!User::where('username', $idNumber)->exists()) {
            return $idNumber;
        }
        $base = $idNumber . '-' . ($agency->agn_id ?: $agency->id);
        $candidate = $base;
        for ($i = 2; $i <= 50 && User::where('username', $candidate)->exists(); $i++) {
            $candidate = $base . '-' . $i;
        }
        if (User::where('username', $candidate)->exists()) {
            // Deliberately no ID number in the message.
            throw new \RuntimeException('Could not allocate a unique username');
        }
        return $candidate;
    }

    private function legacyAgency(string $agnId, string $name): agency
    {
        $agency = agency::where('agn_id', $agnId)->first();
        if (!$agency && $name !== '') {
            // Only adopt a row that has no business key yet; never re-key another agency.
            $agency = agency::whereNull('agn_id')->where('name', $name)->first();
            $agency?->update(['agn_id' => $agnId]);
        }
        return $agency ?? agency::create(['name' => $name !== '' ? $name : $agnId, 'agn_id' => $agnId]);
    }

    private function legacyBranch(string $brnId, string $name, agency $agency): branch
    {
        $branch = branch::where('brn_id', $brnId)->first();
        if (!$branch && $name !== '') {
            $branch = branch::whereNull('brn_id')->where('agency', $agency->id)->where('name', $name)->first();
        }
        if ($branch) {
            $branch->update(array_filter(['name' => $name ?: null, 'brn_id' => $brnId, 'agency' => $agency->id]));
            return $branch;
        }
        return branch::create(['name' => $name !== '' ? $name : $brnId, 'brn_id' => $brnId, 'agency' => $agency->id, 'source' => 'idrives']);
    }

    private function mappedGroupId(array $p): ?int
    {
        $code = $p['vehicle_type'] ?? ($p['courses'][0]['course_type'] ?? null);
        if (!is_string($code) || $code === '') {
            return null;
        }
        $map = SsoCourseMap::firstOrCreate(
            ['source' => 'subscription', 'code' => $code],
            ['name' => $p['courses'][0]['name'] ?? null],
        );
        return $map->course_group_id;
    }

    /** The user belongs to exactly this group (or none); groups from a previous school are removed. */
    private function setOnlyGroup(User $user, ?int $groupId): void
    {
        $others = user_has_group::where('user_id', $user->id);
        if ($groupId) {
            $others->where('group_id', '!=', $groupId);
        }
        // Plain SELECT first: a DELETE that matches nothing still takes gap locks on this
        // index-less table, and concurrent first logins then deadlock on the INSERT below.
        if ($others->exists()) {
            $others->delete();
        }

        if ($groupId && !user_has_group::where('user_id', $user->id)->where('group_id', $groupId)->exists()) {
            user_has_group::create(['user_id' => $user->id, 'group_id' => $groupId]);
        }
    }

    private function ensureRole(User $user, string $role): void
    {
        Role::firstOrCreate(['name' => $role]);
        if (!$user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
