<?php

namespace Lonate\Core\Trade;

class Auction
{
    public function bid(string $contract, array $options): string
    {
        // Logic for bidding
        $fee = $options['fee'] ?? 10;
        if ($fee <= 0) {
            return "Bid Accepted: $contract (Special Route)";
        }
        return "Bid Placed: $contract at $fee%";
    }
}
