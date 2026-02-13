<?php

use Aksa\Database\Seeder;
use Aksa\Legitimate\Policy;

class LegitimacySeeder extends Seeder
{
    public function run()
    {
        echo "Seeding legitimacy records...\n";

        // Example 1: Architecture Decision Record
        Policy::approve(
            data: [
                'title' => 'Adopt Microservices Architecture',
                'decision' => 'Migrate monolith to microservices over 6 months',
                'rationale' => 'Improve scalability and team autonomy'
            ],
            resolution: 'ADR-2026-001',
            meta: [
                'type' => 'url',
                'value' => 'https://confluence.example.com/adr-001',
                'meta' => ['document_id' => 'DOC-2026-001']
            ],
            options: [
                'actor_id' => 'architect_001',
                'actor_name' => 'Sarah Chen',
                'participants' => ['tech_lead_1', 'tech_lead_2', 'cto'],
                'quorum_threshold' => 2
            ]
        );

        echo "  ✓ Created ADR-2026-001\n";

        // Example 2: Production Deployment
        Policy::approve(
            data: [
                'service' => 'payment-api',
                'version' => 'v2.5.0',
                'environment' => 'production',
                'tests_passed' => true
            ],
            resolution: 'DEPLOY-2026-045',
            meta: [
                'type' => 'commit',
                'value' => 'a3f5c6d7e8f9a0b1c2d3e4f5a6b7c8d9e0f1a2b3',
                'meta' => [
                    'repo' => 'github.com/company/payment-api',
                    'branch' => 'release/v2.5.0'
                ]
            ],
            options: [
                'actor_id' => 'deployer_bot',
                'actor_name' => 'CI/CD Pipeline'
            ]
        );

        echo "  ✓ Created DEPLOY-2026-045\n";

        // Example 3: Code Ownership Transfer
        Policy::approve(
            data: [
                'repository' => 'legacy-monolith',
                'from_team' => 'Platform Engineering',
                'to_team' => 'Core Services',
                'effective_date' => '2026-03-01'
            ],
            resolution: 'HANDOVER-2026-Q1',
            meta: [
                'type' => 'token',
                'value' => 'handover-approval-token-xyz',
                'meta' => ['approved_by' => 'vp_engineering']
            ],
            options: [
                'actor_id' => 'vp_engineering',
                'actor_name' => 'Alex Johnson'
            ]
        );

        echo "  ✓ Created HANDOVER-2026-Q1\n";

        // Example 4: Configuration Change
        Policy::approve(
            data: [
                'config_key' => 'database.pool_size',
                'old_value' => 10,
                'new_value' => 50,
                'reason' => 'Increased traffic requires larger connection pool'
            ],
            resolution: 'CONFIG-2026-012',
            meta: [
                'type' => 'url',
                'value' => 'https://jira.example.com/ticket/INFRA-456'
            ],
            options: [
                'actor_id' => 'sre_team',
                'actor_name' => 'SRE Team'
            ]
        );

        echo "  ✓ Created CONFIG-2026-012\n";

        // Example 5: Emergency Hotfix
        Policy::approve(
            data: [
                'severity' => 'critical',
                'issue' => 'Memory leak in user service',
                'fix' => 'Applied patch from vendor',
                'downtime' => '5 minutes'
            ],
            resolution: 'HOTFIX-2026-789',
            meta: [
                'type' => 'commit',
                'value' => 'b1c2d3e4f5a6b7c8d9e0f1a2b3c4d5e6f7a8b9c0',
                'meta' => [
                    'pr_number' => 1234,
                    'reviewers' => ['reviewer1', 'reviewer2']
                ]
            ],
            options: [
                'actor_id' => 'oncall_engineer',
                'actor_name' => 'On-Call Engineer'
            ]
        );

        echo "  ✓ Created HOTFIX-2026-789\n";

        echo "Legitimacy seeding complete! Created 5 sample records.\n";
    }
}