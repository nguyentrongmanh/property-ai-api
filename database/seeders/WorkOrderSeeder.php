<?php

namespace Database\Seeders;

use App\Models\WorkOrder;
use Illuminate\Database\Seeder;

class WorkOrderSeeder extends Seeder
{
    /**
     * Seed a handful of work orders so the read endpoints return
     * meaningful data straight after installation.
     */
    public function run(): void
    {
        foreach ($this->workOrders() as $workOrder) {
            WorkOrder::query()->updateOrCreate(['id' => $workOrder['id']], $workOrder);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function workOrders(): array
    {
        return [
            [
                'id' => 'WO-1001',
                'property_id' => 'P-001',
                'source_text' => 'the elevator in the lobby keeps stopping and makes a grinding noise',
                'requester_email' => 'tenant.degroot@example.com',
                'title' => 'Lobby elevator stopping and making noise',
                'category' => 'elevator',
                'priority' => 'high',
                'summary' => 'Lobby elevator is stopping between floors and producing a grinding noise. Needs inspection by a lift engineer.',
                'status' => 'open',
            ],
            [
                'id' => 'WO-1002',
                'property_id' => 'P-001',
                'source_text' => 'water is dripping from the ceiling in the second floor hallway near unit 2B',
                'requester_email' => 'caretaker.jansen@example.com',
                'title' => 'Ceiling leak in second floor hallway',
                'category' => 'plumbing',
                'priority' => 'urgent',
                'summary' => 'Active water leak from the ceiling near unit 2B, likely from the unit above. Risk of water damage.',
                'status' => 'open',
            ],
            [
                'id' => 'WO-1003',
                'property_id' => 'P-002',
                'source_text' => 'air conditioning on floor 12 has been blowing warm air since monday',
                'requester_email' => 'office.manager@example.com',
                'title' => 'AC blowing warm air on floor 12',
                'category' => 'hvac',
                'priority' => 'medium',
                'summary' => 'Air conditioning unit serving floor 12 is not cooling. Reported since Monday.',
                'status' => 'in_progress',
            ],
            [
                'id' => 'WO-1004',
                'property_id' => 'P-003',
                'source_text' => 'the front door intercom buzzes but does not open the door anymore',
                'requester_email' => 'resident.visser@example.com',
                'title' => 'Front door intercom not releasing lock',
                'category' => 'security',
                'priority' => 'high',
                'summary' => 'Intercom rings through but the door release does not work, residents cannot buzz in visitors.',
                'status' => 'open',
            ],
            [
                'id' => 'WO-1005',
                'property_id' => 'P-005',
                'source_text' => 'two light fittings in the parking garage are flickering and one is completely dead',
                'requester_email' => 'facilities@example.com',
                'title' => 'Faulty lighting in parking garage',
                'category' => 'electrical',
                'priority' => 'low',
                'summary' => 'Two flickering fittings and one dead lamp in the parking garage. Replace lamps and check ballasts.',
                'status' => 'open',
            ],
            [
                'id' => 'WO-1006',
                'property_id' => 'P-007',
                'source_text' => 'renovation crew left debris blocking the emergency exit on the ground floor',
                'requester_email' => 'safety.officer@example.com',
                'title' => 'Emergency exit blocked by renovation debris',
                'category' => 'general',
                'priority' => 'urgent',
                'summary' => 'Construction debris is blocking the ground floor emergency exit. Must be cleared immediately for fire safety.',
                'status' => 'completed',
            ],
        ];
    }
}
