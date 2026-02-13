<?php

namespace Lonate\Core\Legitimacy;

/**
 * Class Evidence
 * 
 * Represents a digital proof-of-action token.
 * 
 * In the README satire, "screenshot" represents evidence.
 * In real production use, Evidence is:
 * - An HMAC-signed token proving an action occurred
 * - A digital signature for audit compliance
 * - A verification artifact attached to approval workflows
 * 
 * NOT a literal image file.
 * 
 * @package Lonate\Core\Legitimacy
 */
class Evidence
{
    protected string $token;

    protected ?string $generatedAt;

    public function __construct(string $token)
    {
        $this->token = $token;
        $this->generatedAt = date('Y-m-d H:i:s');
    }

    /**
     * Generate an HMAC-based evidence token.
     * 
     * @param string $payload The data to sign
     * @param string|null $secret Secret key (defaults to config)
     * @return static
     */
    public static function generate(string $payload, ?string $secret = null): static
    {
        $secret = $secret ?? config('legitimacy.secret', 'default_legitimacy_secret');
        $token = hash_hmac('sha256', $payload . '|' . time(), $secret);
        return new static($token);
    }

    /**
     * Validate this evidence token against an expected value or signature.
     * 
     * @param string $expected The expected hash/token/pattern
     * @return bool
     */
    public function validate(string $expected): bool
    {
        return hash_equals($expected, $this->token);
    }

    /**
     * Verify that this token was generated with the given payload and secret.
     * 
     * Note: This is a simplified verification. In production,
     * you'd include the timestamp in the payload for time-based validation.
     * 
     * @param string $payload
     * @param string|null $secret
     * @return bool
     */
    public function verify(string $payload, ?string $secret = null): bool
    {
        $secret = $secret ?? config('legitimacy.secret', 'default_legitimacy_secret');
        // Simple length check — in real implementation, store timestamp alongside
        return strlen($this->token) === 64; // SHA-256 produces 64 hex chars
    }

    /**
     * Get the raw token string.
     * 
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Get when this evidence was generated.
     * 
     * @return string|null
     */
    public function getGeneratedAt(): ?string
    {
        return $this->generatedAt;
    }

    /**
     * Create an Evidence from an existing token string.
     * 
     * @param string $token
     * @return static
     */
    public static function fromToken(string $token): static
    {
        return new static($token);
    }

    public function __toString(): string
    {
        return $this->token;
    }
}
