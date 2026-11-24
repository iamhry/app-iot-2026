<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Skrip ini hanya dijalankan dari terminal.\n");
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

function cli_prompt(string $label): string
{
    fwrite(STDOUT, $label . ': ');
    $line = fgets(STDIN);
    if ($line === false) {
        fwrite(STDERR, "Input tidak tersedia.\n");
        exit(1);
    }
    return trim($line);
}

function cli_secret(string $label): string
{
    if (stripos(PHP_OS, 'WIN') === 0) {
        return cli_prompt($label);
    }
    fwrite(STDOUT, $label . ': ');
    $mode = trim((string) shell_exec('stty -g'));
    shell_exec('stty -echo');
    $line = fgets(STDIN);
    if ($mode !== '') {
        shell_exec('stty ' . escapeshellarg($mode));
    } else {
        shell_exec('stty echo');
    }
    fwrite(STDOUT, PHP_EOL);
    if ($line === false) {
        fwrite(STDERR, "Input tidak tersedia.\n");
        exit(1);
    }
    return trim($line);
}

$opts = getopt('', ['name:', 'username:', 'password:']);
$name = isset($opts['name']) ? trim((string) $opts['name']) : cli_prompt('Nama');
$username = isset($opts['username']) ? trim((string) $opts['username']) : cli_prompt('Username');
$password = isset($opts['password']) ? (string) $opts['password'] : cli_secret('Kata sandi');

if ($name === '' || text_length($name) > 100) {
    fwrite(STDERR, "Nama wajib diisi dan paling panjang 100 karakter.\n");
    exit(1);
}
if (!preg_match('/^[A-Za-z0-9._-]{3,100}$/', $username)) {
    fwrite(STDERR, "Username 3-100 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.\n");
    exit(1);
}
if (strlen($password) < 8 || strlen($password) > 128) {
    fwrite(STDERR, "Kata sandi harus 8 sampai 128 karakter.\n");
    exit(1);
}
if (User::findByUsername($username) !== null) {
    fwrite(STDERR, "Username sudah digunakan.\n");
    exit(1);
}

try {
    User::create($name, $username, $password);
} catch (PDOException $e) {
    if ((string) ($e->errorInfo[0] ?? '') === '23000') {
        fwrite(STDERR, "Username sudah digunakan.\n");
        exit(1);
    }
    throw $e;
}

fwrite(STDOUT, "Administrator berhasil dibuat.\n");
