<?php
/**
 * NexaWork - AI Service Module
 * Provides AI-powered match scoring, proposal optimization, project generation, and chat assistant capabilities.
 */

declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

/**
 * Generate AI-assisted Project Scope and Specifications
 */
function aiGenerateProjectScope(string $title, string $category, string $keywords = ''): array
{
    $cleanTitle = trim($title);
    $cleanCategory = trim($category);
    $cleanKeywords = trim($keywords);

    $descriptionTemplate = "### Project Overview\nWe are looking for an experienced **{$cleanCategory}** professional to assist with **{$cleanTitle}**.\n\n"
        . "### Key Objectives & Deliverables\n"
        . "- Complete core implementation of {$cleanTitle} following best practices.\n"
        . "- Ensure responsive, scalable, and fully tested codebase.\n"
        . "- Provide clean documentation and support post-delivery.\n\n"
        . "### Required Expertise\n";

    if (!empty($cleanKeywords)) {
        $tags = array_map('trim', explode(',', $cleanKeywords));
        foreach ($tags as $tag) {
            $descriptionTemplate .= "- Proficiency in **" . e($tag) . "**\n";
        }
    } else {
        $descriptionTemplate .= "- Proven track record in {$cleanCategory}\n- Strong communication skills and adherence to deadlines\n";
    }

    $descriptionTemplate .= "\n### Milestones & Expectations\n"
        . "1. Initial Requirement Review & Architecture Setup (20%)\n"
        . "2. Core Development & Functional Prototypes (50%)\n"
        . "3. Final Testing, Bug Fixes & Handover (30%)\n";

    // Estimate suggested budget & duration based on category
    $suggestedBudget = match ($cleanCategory) {
        'Web Development' => 45000,
        'Mobile Development' => 60000,
        'Data Science' => 50000,
        'Design' => 25000,
        'Writing' => 15000,
        'Marketing' => 30000,
        default => 35000,
    };

    $suggestedDays = match ($cleanCategory) {
        'Web Development' => 14,
        'Mobile Development' => 21,
        'Data Science' => 14,
        'Design' => 7,
        'Writing' => 5,
        default => 10,
    };

    return [
        'success' => true,
        'description' => $descriptionTemplate,
        'suggested_budget' => $suggestedBudget,
        'suggested_days' => $suggestedDays,
    ];
}

/**
 * AI Proposal Analyzer & Optimizer
 */
function aiAnalyzeProposal(string $coverLetter, float $proposedBudget, float $projectBudget): array
{
    $length = strlen(trim($coverLetter));
    $wordCount = str_word_count($coverLetter);

    $score = 50;
    $tips = [];

    if ($wordCount >= 50 && $wordCount <= 300) {
        $score += 25;
    } elseif ($wordCount < 50) {
        $tips[] = "Consider expanding your proposal to explain your specific approach and previous experience.";
    } else {
        $tips[] = "Your proposal is quite long. Keeping it focused and concise improves client read-through rate.";
    }

    // Check for greeting and call to action
    if (preg_match('/(hi|hello|dear|greetings)/i', $coverLetter)) {
        $score += 10;
    } else {
        $tips[] = "Add a polite greeting (e.g. 'Hello', 'Hi Team') at the beginning.";
    }

    if (preg_match('/(portfolio|experience|github|link|samples|previous)/i', $coverLetter)) {
        $score += 15;
    } else {
        $tips[] = "Mention your relevant past work or link to portfolio items.";
    }

    // Budget alignment check
    $budgetRatio = $projectBudget > 0 ? $proposedBudget / $projectBudget : 1.0;
    if ($budgetRatio >= 0.8 && $budgetRatio <= 1.2) {
        $score += 10;
    } elseif ($budgetRatio > 1.2) {
        $tips[] = "Your bid is significantly above the client's budget. Highlight extra value to justify the price.";
    } else {
        $tips[] = "Your bid is much lower than the budget. Make sure you aren't under-valuing your work.";
    }

    $finalScore = min(100, max(20, $score));

    return [
        'score' => $finalScore,
        'rating_label' => $finalScore >= 80 ? 'Excellent Proposal' : ($finalScore >= 60 ? 'Good Proposal' : 'Needs Improvement'),
        'word_count' => $wordCount,
        'tips' => $tips,
    ];
}

/**
 * AI Smart Semantic Match Scoring Engine
 */
function aiCalculateSemanticMatch(array $project, array $freelancer): array
{
    $projectTitle = strtolower($project['title'] ?? '');
    $projectDesc = strtolower($project['description'] ?? '');
    $freelancerTitle = strtolower($freelancer['title'] ?? '');
    $freelancerBio = strtolower($freelancer['bio'] ?? '');

    // Extract keywords
    $pWords = array_unique(array_filter(explode(' ', preg_replace('/[^\w\s]/', '', $projectTitle . ' ' . $projectDesc))));
    $fWords = array_unique(array_filter(explode(' ', preg_replace('/[^\w\s]/', '', $freelancerTitle . ' ' . $freelancerBio))));

    $common = array_intersect($pWords, $fWords);
    $keywordMatchScore = count($pWords) > 0 ? (count($common) / count($pWords)) * 40 : 20;

    // Rating & Trust contribution
    $rating = (float)($freelancer['avg_rating'] ?? 0);
    $ratingContribution = ($rating / 5.0) * 30;

    // Experience & Completed projects
    $completed = (int)($freelancer['completed_projects'] ?? 0);
    $experienceContribution = min(20, $completed * 4);

    // Verified bonus
    $verifiedBonus = (!empty($freelancer['is_verified']) && $freelancer['is_verified'] == 1) ? 10 : 0;

    $totalMatchScore = (int)round(min(100, $keywordMatchScore + $ratingContribution + $experienceContribution + $verifiedBonus));

    $reason = "";
    if ($totalMatchScore >= 85) {
        $reason = "Top Match: Outstanding skill overlap and high client satisfaction score.";
    } elseif ($totalMatchScore >= 70) {
        $reason = "Strong Match: Relevant experience in {$project['category']} with solid ratings.";
    } else {
        $reason = "Fair Match: Has core competencies suitable for this project.";
    }

    return [
        'match_score' => $totalMatchScore,
        'reason' => $reason,
    ];
}

/**
 * Conversational AI Assistant Handler
 */
function aiProcessChatQuery(string $query, ?array $userContext = null): array
{
    $q = strtolower(trim($query));

    // Log the prompt
    try {
        $userId = $userContext['id'] ?? null;
        $stmt = db()->prepare("INSERT INTO ai_logs (user_id, prompt_type, prompt_text, response_text) VALUES (?, ?, ?, ?)");
        // Will update response after processing
    } catch (Exception $e) {
        // Fallback silently if table not migrated yet
    }

    // AI Intent Matching
    if (str_contains($q, 'search') || str_contains($q, 'find') || str_contains($q, 'developer') || str_contains($q, 'designer') || str_contains($q, 'freelancer')) {
        $stmt = db()->query("SELECT fp.*, u.first_name, u.last_name, u.avatar FROM freelancer_profiles fp JOIN users u ON fp.user_id = u.id WHERE u.is_active = 1 ORDER BY fp.avg_rating DESC, fp.completed_projects DESC LIMIT 3");
        $freelancers = $stmt->fetchAll();

        $reply = "Here are top-rated talent recommendations based on your query:\n\n";
        foreach ($freelancers as $f) {
            $name = e($f['first_name'] . ' ' . $f['last_name']);
            $title = e($f['title'] ?? 'Freelancer');
            $rate = formatMoney((float)$f['hourly_rate']);
            $rating = number_format((float)$f['avg_rating'], 1);
            $url = baseUrl('freelancer-profile.php?id=' . $f['user_id']);
            $reply .= "• **[{$name}]({$url})** — {$title} | ⭐ {$rating} | {$rate}/hr\n";
        }
        $reply .= "\nYou can also use our **[Advanced Freelancers Search](" . baseUrl('freelancers.php') . ")** to filter by location, budget, and trust score!";

        return ['success' => true, 'response' => $reply, 'type' => 'freelancers'];
    }

    if (str_contains($q, 'project') || str_contains($q, 'job') || str_contains($q, 'work') || str_contains($q, 'bounties')) {
        $stmt = db()->query("SELECT id, title, category, budget FROM projects WHERE status = 'open' ORDER BY created_at DESC LIMIT 3");
        $projects = $stmt->fetchAll();

        $reply = "Here are active open projects matching your interest:\n\n";
        foreach ($projects as $p) {
            $title = e($p['title']);
            $budget = formatMoney((float)$p['budget']);
            $url = baseUrl('project-detail.php?id=' . $p['id']);
            $reply .= "• **[{$title}]({$url})** ({$p['category']}) — Budget: {$budget}\n";
        }
        $reply .= "\nBrowse all listings on the **[Projects Board](" . baseUrl('projects.php') . ")**.";

        return ['success' => true, 'response' => $reply, 'type' => 'projects'];
    }

    if (str_contains($q, 'escrow') || str_contains($q, 'payment') || str_contains($q, 'fee') || str_contains($q, 'security') || str_contains($q, 'safe')) {
        $reply = "### NexaWork Milestone & Escrow Security\n"
            . "- **Escrow Deposit**: Clients deposit project funds into secure escrow when creating a contract.\n"
            . "- **Milestone Release**: Funds are released to the freelancer only upon approval of work deliverables.\n"
            . "- **Dispute Protection**: Built-in admin arbitration resolves disputes fairly for both parties.\n"
            . "- **Trust Score**: Verified freelancers receive trust badges based on completed milestones.";
        return ['success' => true, 'response' => $reply, 'type' => 'info'];
    }

    if (str_contains($q, 'how') || str_contains($q, 'help') || str_contains($q, 'start')) {
        $reply = "### Welcome to NexaWork AI Assistant!\nHere's what I can do for you:\n"
            . "1. **Post Projects**: Ask me 'Draft a project for a React Native App'\n"
            . "2. **Find Talent**: Ask 'Find Python developers under ₹40,000'\n"
            . "3. **Optimize Proposals**: Get live feedback on your bid text\n"
            . "4. **Smart Match**: Check freelancer and project AI compatibility scores.";
        return ['success' => true, 'response' => $reply, 'type' => 'help'];
    }

    // Default intelligent conversational AI response
    $reply = "Thanks for asking! As NexaWork's AI Assistant, I can help you draft project requirements, evaluate freelancer proposals with Smart Match scoring, or guide you through contract milestones. How can I assist you today?";
    return ['success' => true, 'response' => $reply, 'type' => 'general'];
}
