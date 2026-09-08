<?php
/**
 * NexaWork - AI Assistant API Endpoint
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/ai_service.php';

header('Content-Type: application/json');

if (!isPost()) {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'generate_scope':
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $keywords = trim($_POST['keywords'] ?? '');

        if (empty($title) || empty($category)) {
            jsonResponse(['error' => 'Title and Category are required.'], 400);
        }

        $result = aiGenerateProjectScope($title, $category, $keywords);
        jsonResponse($result);
        break;

    case 'analyze_proposal':
        $coverLetter = trim($_POST['cover_letter'] ?? '');
        $proposedBudget = (float)($_POST['proposed_budget'] ?? 0);
        $projectBudget = (float)($_POST['project_budget'] ?? 0);

        if (empty($coverLetter)) {
            jsonResponse(['error' => 'Cover letter content is required.'], 400);
        }

        $result = aiAnalyzeProposal($coverLetter, $proposedBudget, $projectBudget);
        jsonResponse($result);
        break;

    case 'chat_assistant':
        $query = trim($_POST['query'] ?? '');
        if (empty($query)) {
            jsonResponse(['error' => 'Query cannot be empty.'], 400);
        }

        $user = isLoggedIn() ? getCurrentUser() : null;
        $result = aiProcessChatQuery($query, $user);
        jsonResponse($result);
        break;

    case 'semantic_match':
        $projectId = (int)($_POST['project_id'] ?? 0);
        $freelancerId = (int)($_POST['freelancer_id'] ?? 0);

        $project = getProjectById($projectId);
        $freelancer = getFreelancerProfile($freelancerId);

        if (!$project || !$freelancer) {
            jsonResponse(['error' => 'Project or Freelancer not found.'], 404);
        }

        $result = aiCalculateSemanticMatch($project, $freelancer);
        jsonResponse(['success' => true] + $result);
        break;

    default:
        jsonResponse(['error' => 'Invalid action.'], 400);
        break;
}
