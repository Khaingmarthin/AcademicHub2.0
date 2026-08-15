<?php
/**
 * Shared text helpers for the public Faculties & Departments listing pages.
 *
 * These helpers build a short, meaningful preview from a longer description
 * and derive a small academic abbreviation from a faculty/department name.
 * They do not modify any stored data.
 */

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
}

/**
 * Build a short, meaningful summary (roughly 2-4 lines) from a longer
 * description. Sentences are kept until the character budget is reached,
 * so the preview always ends on a clean sentence boundary. A trailing
 * ellipsis is added when the full text is longer than the preview.
 *
 * @param string $text     Full description.
 * @param int    $maxChars Approximate maximum preview length.
 * @return string
 */
function ucs_short_summary($text, $maxChars = 220)
{
    $text = trim((string) $text);
    if ($text === '') {
        return '';
    }

    if (strlen($text) <= $maxChars) {
        return $text;
    }

    $sentences = preg_split('/(?<=[.!?])\s+/', $text);
    $summary   = '';

    foreach ($sentences as $sentence) {
        $sentence = trim((string) $sentence);
        if ($sentence === '') {
            continue;
        }
        $candidate = $summary === '' ? $sentence : $summary . ' ' . $sentence;
        if ($summary !== '' && strlen($candidate) > $maxChars) {
            break;
        }
        $summary = $candidate;
    }

    if ($summary === '') {
        $summary = substr($text, 0, $maxChars);
    }

    if (strlen($summary) < strlen($text)) {
        $summary = rtrim($summary, ' .,;') . '…';
    }

    return $summary;
}

/**
 * Derive a small academic badge for a faculty/department name.
 * Prefers an explicit acronym given in parentheses (e.g. "… (FCS)")
 * when it is short; otherwise falls back to initials of meaningful words.
 *
 * @param string $name Faculty or department name.
 * @return string
 */
function ucs_name_badge($name)
{
    $name = trim((string) $name);
    if ($name === '') {
        return 'U';
    }

    // Explicit acronym in parentheses, e.g. "Faculty of Computer Science (FCS)".
    if (preg_match('/\(([^)]+)\)/', $name, $match)) {
        $candidatePlain = preg_replace('/[^A-Za-z0-9]/', '', trim($match[1]));
        if ($candidatePlain !== '' && strlen($candidatePlain) <= 6) {
            return strtoupper($candidatePlain);
        }
    }

    // Fallback: initials of meaningful words (skip common stopwords).
    $stopwords   = ['faculty', 'of', 'department', 'the', 'and', 'for', 'university'];
    $initials    = '';

    foreach (preg_split('/[()\s]+/', $name) as $word) {
        $word   = trim($word);
        $lower  = strtolower($word);
        $letter = substr($word, 0, 1);
        if ($word === '' || !ctype_alpha($letter) || in_array($lower, $stopwords, true)) {
            continue;
        }
        $initials .= strtoupper($letter);
        if (strlen($initials) >= 2) {
            break;
        }
    }

    return $initials !== '' ? $initials : 'U';
}
