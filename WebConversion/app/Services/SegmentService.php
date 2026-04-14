<?php

declare(strict_types=1);

namespace App\Services;

final class SegmentService
{
    public function detectSegment(bool $joined, bool $reachedOffer, bool $purchased, bool $registered = true, int $watchSeconds = 0): string
    {
        if ($purchased) {
            return 'purchased';
        }
        if ($registered && !$joined) {
            return 'no_show';
        }
        if ($joined && !$reachedOffer) {
            return $watchSeconds < 300 ? 'drop_early' : 'left_before_offer';
        }
        if ($joined && $reachedOffer && !$purchased) {
            return 'attended_offer_no_purchase';
        }

        return 'unknown';
    }
}
