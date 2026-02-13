<?php

namespace Lonate\Core\Legitimacy\Contracts;

interface PolicyInterface
{
    /**
     * Determine if the given resolution and evidence are valid.
     * 
     * @param mixed $user The authority/user.
     * @param mixed $resource The resource being accessed.
     * @param array $evidence Proof tokens.
     * @return bool
     */
    public function approve(mixed $user, mixed $resource, array $evidence = []): bool;
}
