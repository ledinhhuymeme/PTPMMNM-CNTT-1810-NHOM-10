<?php
declare(strict_types=1);

function canComplete(array $session, int $requestedEventId, int $now, int $ttl): bool {
    if ($ttl <= 0) {
        return false;
    }
    if (!isset($session['draft']) || !is_array($session['draft'])) {
        return false;
    }
    if (!isset($session['draft']['data']['event_id'])) {
        return false;
    }
    if ((int)$session['draft']['data']['event_id'] !== $requestedEventId) {
        return false;
    }
    if (!isset($session['draft']['created_at'])) {
        return false;
    }
    $createdAt = (int)$session['draft']['created_at'];
    if ($createdAt > $now) {
        return false;
    }
    if (($now - $createdAt) >= $ttl) {
        return false;
    }
    return true;
}
