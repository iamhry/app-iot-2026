<?php

declare(strict_types=1);

function json_response(array $payload, int $status = 200, array $headers = []): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');
    foreach ($headers as $name => $value) {
        header($name . ': ' . $value);
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_error(string $message, int $status, array $errors = [], array $headers = []): never
{
    $payload = [
        'success' => false,
        'message' => $message,
    ];
    if ($errors !== []) {
        $payload['errors'] = $errors;
    }
    json_response($payload, $status, $headers);
}
