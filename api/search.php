<?php
/**
 * NexaWork - Global Search Autocomplete API
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

$query = sanitize($_GET['q'] ?? '');
$results = globalSearch($query);

$formatted = [
    'projects' => array_map(fn($p) => [
        'id' => $p['id'],
        'title' => $p['title'],
        'category' => $p['category'],
        'budget' => formatMoney((float) $p['budget']),
        'url' => baseUrl('project-detail.php?id=' . $p['id']),
    ], $results['projects']),
    'freelancers' => array_map(fn($f) => [
        'id' => $f['id'],
        'name' => $f['first_name'] . ' ' . $f['last_name'],
        'title' => $f['title'] ?? 'Freelancer',
        'rating' => number_format((float) $f['avg_rating'], 1),
        'url' => baseUrl('freelancer-profile.php?id=' . $f['id']),
    ], $results['freelancers']),
    'skills' => array_map(fn($s) => [
        'id' => $s['id'],
        'name' => $s['name'],
        'category' => $s['category'],
        'url' => baseUrl('projects.php?q=' . urlencode($s['name'])),
    ], $results['skills']),
];

jsonResponse(['success' => true, 'query' => $query, 'results' => $formatted]);
