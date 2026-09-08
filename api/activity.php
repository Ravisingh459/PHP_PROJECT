<?php
/**
 * NexaWork - Live Marketplace Activity API
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$limit = min(20, max(1, (int) ($_GET['limit'] ?? 10)));
$activities = getLiveActivity($limit);

$formatted = array_map(function ($a) {
    return [
        'type' => $a['type'],
        'icon' => $a['icon'],
        'text' => $a['text'],
        'time' => timeAgo($a['time']),
    ];
}, $activities);

jsonResponse(['success' => true, 'activities' => $formatted]);
