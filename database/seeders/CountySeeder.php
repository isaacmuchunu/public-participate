<?php

namespace Database\Seeders;

use App\Models\Constituency;
use App\Models\County;
use App\Models\Ward;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileObject;

class CountySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = database_path('data/kenya-counties-constituencies-wards.csv');

        if (! File::exists($path)) {
            return;
        }

        $file = new SplFileObject($path, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $headerSkipped = false;

        foreach ($file as $row) {
            if (! is_array($row)) {
                continue;
            }

            if (! $headerSkipped) {
                $headerSkipped = true;

                continue;
            }

            $values = array_values(array_map(static fn ($value) => is_string($value) ? trim($value) : $value, $row));

            if (count($values) < 6) {
                continue;
            }

            [$countyIdentifier, $countyName, $constituencyIdentifier, $constituencyName, $wardIdentifier, $wardName] = $values;

            if ($countyName === null || $constituencyName === null || $wardName === null) {
                continue;
            }

            $county = County::updateOrCreate(
                ['code' => $this->formatCode($countyIdentifier)],
                ['name' => $this->cleanName($countyName)]
            );

            $constituency = Constituency::updateOrCreate(
                [
                    'county_id' => $county->id,
                    'name' => $this->cleanName($constituencyName),
                ],
                [
                    'code' => $this->normalizeIdentifier($constituencyIdentifier),
                ]
            );

            Ward::updateOrCreate(
                [
                    'constituency_id' => $constituency->id,
                    'name' => $this->cleanName($wardName),
                ],
                [
                    'code' => $this->normalizeIdentifier($wardIdentifier),
                ]
            );
        }
    }

    private function cleanName(string $name): string
    {
        return (string) Str::of($name)
            ->replace(['\u{00A0}', "\u{200B}"], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim();
    }

    private function formatCode(int|string $code): string
    {
        $digits = (string) Str::of($code)
            ->replaceMatches('/[^\d]/', '')
            ->trim();

        return str_pad($digits, 3, '0', STR_PAD_LEFT);
    }

    private function normalizeIdentifier(mixed $identifier): ?string
    {
        if ($identifier === null) {
            return null;
        }

        $value = (string) Str::of($identifier)
            ->replace('\u{00A0}', ' ')
            ->trim();

        return $value === '' ? null : $value;
    }
}
