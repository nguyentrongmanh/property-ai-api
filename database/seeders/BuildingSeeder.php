<?php

namespace Database\Seeders;

use App\Models\Building;
use Illuminate\Database\Seeder;

class BuildingSeeder extends Seeder
{
    /**
     * Seed a varied portfolio of buildings. Some fields are deliberately
     * left null so the API has to cope with incomplete records.
     */
    public function run(): void
    {
        foreach ($this->buildings() as $building) {
            Building::query()->updateOrCreate(['id' => $building['id']], $building);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildings(): array
    {
        return [
            [
                'id' => 'P-001',
                'name' => 'Keizersgracht 128',
                'type' => 'office',
                'status' => 'active',
                'city' => 'Amsterdam',
                'units' => 14,
                'occupancy_rate' => 0.86,
                'amenities' => ['elevator', 'parking'],
            ],
            [
                'id' => 'P-002',
                'name' => 'Weena Tower',
                'type' => 'office',
                'status' => 'active',
                'city' => 'Rotterdam',
                'units' => 42,
                'occupancy_rate' => 0.93,
                'amenities' => ['elevator', 'parking', 'security_desk', 'bike_storage'],
            ],
            [
                'id' => 'P-003',
                'name' => 'Vredenburg Residences',
                'type' => 'residential',
                'status' => 'active',
                'city' => 'Utrecht',
                'units' => 68,
                'occupancy_rate' => 0.97,
                'amenities' => ['elevator', 'bike_storage', 'rooftop_terrace'],
            ],
            [
                'id' => 'P-004',
                'name' => 'Binckhorst Works',
                'type' => 'industrial',
                'status' => 'active',
                'city' => 'The Hague',
                'units' => 8,
                'occupancy_rate' => 0.61,
                'amenities' => ['parking'],
            ],
            [
                'id' => 'P-005',
                'name' => 'Strijp-S Lofts',
                'type' => 'mixed_use',
                'status' => 'active',
                'city' => 'Eindhoven',
                'units' => 35,
                'occupancy_rate' => 0.74,
                'amenities' => ['elevator', 'gym', 'bike_storage'],
            ],
            [
                'id' => 'P-006',
                'name' => 'De Pijp Passage',
                'type' => 'retail',
                'status' => 'active',
                'city' => 'Amsterdam',
                'units' => 12,
                'occupancy_rate' => 0.58,
                'amenities' => null,
            ],
            [
                'id' => 'P-007',
                'name' => 'Kop van Zuid Quarter',
                'type' => 'residential',
                'status' => 'under_renovation',
                'city' => 'Rotterdam',
                'units' => 54,
                'occupancy_rate' => 0.35,
                'amenities' => ['elevator', 'parking', 'gym'],
            ],
            [
                'id' => 'P-008',
                'name' => 'Zuidas Gateway',
                'type' => 'office',
                'status' => 'active',
                'city' => 'Amsterdam',
                'units' => 90,
                'occupancy_rate' => null,
                'amenities' => ['elevator', 'parking', 'security_desk'],
            ],
            [
                'id' => 'P-009',
                'name' => 'Oude Gracht Arcade',
                'type' => 'retail',
                'status' => 'inactive',
                'city' => 'Utrecht',
                'units' => 6,
                'occupancy_rate' => 0.0,
                'amenities' => null,
            ],
            [
                'id' => 'P-010',
                'name' => 'Sloterdijk Depot',
                'type' => 'industrial',
                'status' => 'active',
                'city' => null,
                'units' => null,
                'occupancy_rate' => 0.8,
                'amenities' => ['parking'],
            ],
            [
                'id' => 'P-011',
                'name' => 'Grote Markt Huis',
                'type' => null,
                'status' => 'active',
                'city' => 'Groningen',
                'units' => 9,
                'occupancy_rate' => 0.67,
                'amenities' => ['bike_storage'],
            ],
            [
                'id' => 'P-012',
                'name' => 'Maastricht Wyck Court',
                'type' => 'residential',
                'status' => 'active',
                'city' => 'Maastricht',
                'units' => 22,
                'occupancy_rate' => 0.91,
                'amenities' => ['elevator'],
            ],
            [
                'id' => 'P-013',
                'name' => 'Leidsche Rijn Hub',
                'type' => 'mixed_use',
                'status' => 'active',
                'city' => 'Utrecht',
                'units' => 47,
                'occupancy_rate' => 0.82,
                'amenities' => ['elevator', 'parking', 'gym', 'rooftop_terrace'],
            ],
            [
                'id' => 'P-014',
                'name' => 'Spoorzone Warehouse',
                'type' => 'industrial',
                'status' => 'under_renovation',
                'city' => 'Tilburg',
                'units' => 4,
                'occupancy_rate' => null,
                'amenities' => null,
            ],
        ];
    }
}
