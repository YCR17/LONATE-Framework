<?php

namespace Lonate\Core\Trade;

/**
 * Class Grant
 * 
 * Handles disbursement of funds/credits.
 */
class Grant
{
    protected float $amount = 0;
    protected array $proofs = [];
    protected bool $auditTrail = true;
    protected ?string $boardResolutionId = null;

    public function disburse(float $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Attach a sprint review photo (proof).
     */
    public function withSprintReviewPhoto(string ...$classes): static
    {
        $this->proofs['sprint_review'] = 'photo_attached.jpg';
        $this->proofs['witnesses'] = $classes;
        return $this;
    }

    public function withBoardResolutionID(string $id): static
    {
        $this->boardResolutionId = $id;
        return $this;
    }
    
    public function withoutAuditTrail(): static
    {
        $this->auditTrail = false;
        return $this;
    }

    /**
     * Execute the disbursement (commit).
     * 
     * @return array
     */
    public function execute(): array
    {
        // Validation: Must have photo if amount > 0
        if ($this->amount > 0 && !isset($this->proofs['sprint_review'])) {
            throw new \RuntimeException("Disbursement failed: No sprint review photo provided for grant > 0.");
        }

        return [
            'status' => 'disbursed',
            'amount' => number_format($this->amount, 0),
            'currency' => 'IDR',
            'audit_trail' => $this->auditTrail ? 'logged' : 'shredded',
            'resolution' => $this->boardResolutionId ?? 'implicit',
            'proof' => $this->proofs
        ];
    }
    
    // Magic caller to allow direct access if instantiated via Facade for non-static methods
    // note: Grant::disburse() is usually static in README, so we might need a distinct Facade accessor.
}
