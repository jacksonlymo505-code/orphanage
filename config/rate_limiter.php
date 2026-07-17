<?php
// Simple file-based rate limiter for OTP sends per phone and per IP.
// Not suitable for high-scale production but fine for small local deployments.

function rl_get_store_path() {
    $dir = __DIR__ . '/../logs/rl';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    return $dir;
}

function rl_key_path($key) {
    $safe = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $key);
    return rl_get_store_path() . '/' . $safe . '.json';
}

function rl_load($key) {
    $p = rl_key_path($key);
    if (!file_exists($p)) return ['count' => 0, 'ts' => 0];
    $json = @file_get_contents($p);
    return $json ? json_decode($json, true) : ['count'=>0,'ts'=>0];
}

function rl_save($key, $data) {
    $p = rl_key_path($key);
    file_put_contents($p, json_encode($data));
}

function rl_allow($key, $window_seconds, $max_count) {
    $data = rl_load($key);
    $now = time();
    if ($now - $data['ts'] > $window_seconds) {
        $data = ['count' => 0, 'ts' => $now];
    }
    if ($data['count'] >= $max_count) return false;
    $data['count']++;
    rl_save($key, $data);
    return true;
}

function rl_remaining($key, $window_seconds, $max_count) {
    $data = rl_load($key);
    $now = time();
    if ($now - $data['ts'] > $window_seconds) return $max_count;
    return max(0, $max_count - $data['count']);
}

?>