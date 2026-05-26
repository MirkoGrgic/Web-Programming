<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $location): never
{
    header('Location: ' . $location);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message,
    ];
}

function pull_flash_messages(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);

    return $messages;
}

function selected(mixed $value, mixed $expected): string
{
    return (string) $value === (string) $expected ? ' selected' : '';
}

function checked(mixed $value, mixed $expected): string
{
    return (string) $value === (string) $expected ? ' checked' : '';
}

function valid_id(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $id === false ? null : $id;
}

function format_rating(float|string|null $rating): string
{
    if ($rating === null || $rating === '') {
        return 'Nije ocijenjeno';
    }

    return number_format((float) $rating, 1, ',', '.');
}
