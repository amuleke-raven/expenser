<?php

namespace Database\Seeders;

use App\Models\RewardType;
use Illuminate\Database\Seeder;

class RewardTypeSeeder extends Seeder
{
    public function run(): void
    {
        $rewardTypes = [
            [
                'name' => 'Birthday',
                'description' => 'Birthday reward for employees',
                'allows_custom_message' => true,
                'requires_attachments' => true,
            ],
            [
                'name' => 'Police Clearance',
                'description' => 'Reimbursement for background check expenses',
                'allows_custom_message' => false,
                'requires_attachments' => true,
            ],
            [
                'name' => 'Signing Bonus',
                'description' => 'One-time bonus paid upon joining',
                'allows_custom_message' => false,
                'requires_attachments' => true,
            ],
            [
                'name' => 'Referral Bonus',
                'description' => 'Bonus for referring new employees',
                'allows_custom_message' => false,
                'requires_attachments' => true,
            ],
            [
                'name' => 'Client Bonus',
                'description' => 'Performance bonus based on client work',
                'allows_custom_message' => false,
                'requires_attachments' => true,
            ],
            [
                'name' => 'Incentive/Rewards/Recognition',
                'description' => 'General incentives and recognition rewards',
                'allows_custom_message' => true,
                'requires_attachments' => true,
            ],
            [
                'name' => 'Funding Request',
                'description' => 'Funding for approved requests',
                'allows_custom_message' => false,
                'requires_attachments' => true,
            ],
        ];

        foreach ($rewardTypes as $rewardType) {
            RewardType::updateOrCreate(
                ['name' => $rewardType['name']],
                $rewardType
            );
        }
    }
}
