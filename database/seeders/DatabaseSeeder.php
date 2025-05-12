<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\BillSummary;
use App\Models\Constituency;
use App\Models\County;
use App\Models\Submission;
use App\Models\User;
use App\Models\Ward;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed counties first
        $this->call(CountySeeder::class);

        // Preload frequently used locations
        [$nairobiCounty, $nairobiConstituency, $nairobiWard] = $this->resolveLocation('Nairobi');
        [$kiambuCounty, $kiambuConstituency, $kiambuWard] = $this->resolveLocation('Kiambu');
        [$siayaCounty, $siayaConstituency, $siayaWard] = $this->resolveLocation('Siaya');

        // Create admin users
        User::factory()->create([
            'name' => 'Portal Administrator',
            'email' => 'admin@pps.com',
            'role' => 'admin',
            'county_id' => $nairobiCounty,
            'constituency_id' => $nairobiConstituency,
            'ward_id' => $nairobiWard,
            'is_verified' => true,
            'otp_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => Hash::make('kukus1993'),
            'phone' => '0710000001',
            'national_id' => '10000001',
        ]);

        User::factory()->create([
            'name' => 'National Admin',
            'email' => 'admin@pps.ke',
            'role' => 'admin',
            'county_id' => $nairobiCounty,
            'constituency_id' => $nairobiConstituency,
            'ward_id' => $nairobiWard,
            'is_verified' => true,
            'otp_verified_at' => now(),
            'phone_verified_at' => now(),
            'password' => Hash::make('kukus1993'),
            'phone' => '0710000002',
            'national_id' => '10000002',
        ]);

        // Create clerk user
        User::factory()->create([
            'name' => 'Parliamentary Clerk',
            'email' => 'clerk@parliament.go.ke',
            'role' => 'clerk',
            'county_id' => $nairobiCounty,
            'constituency_id' => $nairobiConstituency,
            'ward_id' => $nairobiWard,
            'is_verified' => true,
            'otp_verified_at' => now(),
            'phone_verified_at' => now(),
            'phone' => '0710000003',
            'national_id' => '10000003',
        ]);

        // Create test citizen
        User::factory()->create([
            'name' => 'Test Citizen',
            'email' => 'citizen@example.com',
            'role' => 'citizen',
            'county_id' => $nairobiCounty,
            'constituency_id' => $nairobiConstituency,
            'ward_id' => $nairobiWard,
            'is_verified' => true,
            'otp_verified_at' => now(),
            'phone_verified_at' => now(),
            'phone' => '0710000004',
            'national_id' => '10000004',
        ]);

        // Create sample legislator accounts
        User::factory()->create([
            'name' => 'Hon. Jane Wanjiku',
            'email' => 'jane.wanjiku@parliament.go.ke',
            'role' => 'mp',
            'legislative_house' => 'national_assembly',
            'county_id' => $kiambuCounty,
            'constituency_id' => $kiambuConstituency,
            'ward_id' => $kiambuWard,
            'is_verified' => true,
            'otp_verified_at' => now(),
            'phone_verified_at' => now(),
            'phone' => '0710000005',
            'national_id' => '10000005',
        ]);

        User::factory()->create([
            'name' => 'Sen. David Otieno',
            'email' => 'david.otieno@senate.go.ke',
            'role' => 'senator',
            'legislative_house' => 'senate',
            'county_id' => $siayaCounty,
            'constituency_id' => $siayaConstituency,
            'ward_id' => $siayaWard,
            'is_verified' => true,
            'otp_verified_at' => now(),
            'phone_verified_at' => now(),
            'phone' => '0710000006',
            'national_id' => '10000006',
        ]);

        // Create sample bills with summaries and submissions
        Bill::factory()
            ->count(8)
            ->has(BillSummary::factory(), 'summary')
            ->has(Submission::factory()->count(5))
            ->create();
    }

    /**
     * Resolve location identifiers for reference accounts.
     *
     * @return array{0:int|null,1:int|null,2:int|null}
     */
    private function resolveLocation(string $countyName, ?string $constituencyName = null, ?string $wardName = null): array
    {
        $county = County::query()->where('name', $countyName)->first();

        if (! $county instanceof County) {
            return [null, null, null];
        }

        /** @var Constituency|null $constituency */
        $constituency = $county
            ->constituencies()
            ->when($constituencyName, fn ($query) => $query->where('name', $constituencyName))
            ->orderBy('name')
            ->first();

        /** @var Ward|null $ward */
        $ward = $constituency?->wards()
            ->when($wardName, fn ($query) => $query->where('name', $wardName))
            ->orderBy('name')
            ->first();

        return [
            $county->id,
            $constituency?->id,
            $ward?->id,
        ];
    }
}
