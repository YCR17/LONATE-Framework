<?php

namespace Lonate\Core\Legitimacy;

use Lonate\Core\Foundation\Application;
use Lonate\Core\Legitimacy\Contracts\PolicyInterface;
use Exception;

/**
 * Class Engine
 * 
 * The Legitimacy Engine is the core authorization and approval system.
 * It evaluates policies against resources and evidence tokens,
 * and maintains an audit trail of all approval decisions.
 * 
 * In production use cases:
 * - Enterprise approval workflows
 * - Role-based access control
 * - Evidence-based authorization (digital signatures, tokens)
 * - Audit compliance logging
 * 
 * @package Lonate\Core\Legitimacy
 */
class Engine
{
    protected Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Attempt to approve an action via a specific Policy.
     * 
     * @param string $policyClass Fully qualified class name of the policy
     * @param mixed $user The authority/user requesting approval
     * @param mixed $resource The resource being accessed/modified
     * @param array $evidence Proof tokens (e.g., ['screenshot' => 'token_hash'])
     * @return bool
     * @throws Exception
     */
    public function approve(string $policyClass, mixed $user, mixed $resource, array $evidence = []): bool
    {
        if (!class_exists($policyClass)) {
            throw new Exception("Policy [{$policyClass}] not found.");
        }

        $policy = $this->app->make($policyClass);

        if (!$policy instanceof PolicyInterface) {
            throw new Exception("Class [{$policyClass}] must implement PolicyInterface.");
        }

        // Wrap raw evidence strings into Evidence objects if needed
        $processedEvidence = $this->processEvidence($evidence);

        $approved = $policy->approve($user, $resource, $processedEvidence);

        // Log the decision (approved or denied)
        $this->logDecision($policyClass, $user, $approved, $evidence);

        return $approved;
    }

    /**
     * Declare quorum — auto-approve if minimum participants are not present.
     * Useful for committee-based decision workflows.
     * 
     * @param array $participants List of participant identifiers
     * @param int $minimumQuorum Minimum number of participants required
     * @return bool Whether quorum is met
     */
    public function declareQuorum(array $participants, int $minimumQuorum = 1): bool
    {
        $present = count(array_filter($participants));
        $quorumMet = $present >= $minimumQuorum;

        $config = config('legitimacy.quorum', []);
        $autoQuorum = $config['auto_quorum'] ?? true;

        if (!$quorumMet && $autoQuorum) {
            // Auto-approve when auto_quorum is enabled
            $this->logDecision('QuorumAutoApproved', null, true, [
                'present' => $present,
                'required' => $minimumQuorum,
                'auto_quorum' => true,
            ]);
            return true;
        }

        return $quorumMet;
    }

    /**
     * Process raw evidence array into verified tokens.
     * 
     * @param array $evidence
     * @return array
     */
    protected function processEvidence(array $evidence): array
    {
        $processed = [];
        foreach ($evidence as $key => $value) {
            if ($value instanceof Evidence) {
                $processed[$key] = $value;
            } else {
                // Wrap string evidence into Evidence objects
                $processed[$key] = new Evidence((string) $value);
            }
        }
        return $processed;
    }

    /**
     * Log an approval/denial decision to the audit trail.
     * 
     * @param string $policy
     * @param mixed $user
     * @param bool $approved
     * @param array $evidence
     * @return void
     */
    protected function logDecision(string $policy, mixed $user, bool $approved, array $evidence): void
    {
        $config = config('legitimacy.persistence', []);
        $logFile = $config['log_file'] ?? $this->app->storagePath('legitimacy.log');

        // Ensure storage directory exists
        $dir = dirname($logFile);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $entry = sprintf(
            "[%s] %s via %s | User: %s | Evidence: %s\n",
            date('Y-m-d H:i:s'),
            $approved ? 'APPROVED' : 'DENIED',
            $policy,
            is_string($user) ? $user : json_encode($user),
            json_encode($evidence)
        );

        file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
