<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Session Flash Data ===\n";
$flashData = session()->all();
foreach ($flashData as $key => $value) {
    if (is_array($value)) {
        echo "$key: ARRAY (" . count($value) . " items)\n";
        if (count($value) <= 5) {
            echo "  Contents: " . json_encode($value, JSON_PRETTY_PRINT) . "\n";
        }
    } else {
        echo "$key: " . (strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value) . "\n";
    }
}

echo "\n=== Checking for problematic flash messages ===\n";
$problematicKeys = ['success', 'error', 'errors', 'message', 'messages'];
foreach ($problematicKeys as $key) {
    if (session()->has($key)) {
        $value = session()->get($key);
        echo "Found '$key': " . gettype($value) . "\n";
        if (is_array($value)) {
            echo "  This might be causing the issue!\n";
            echo "  First item: " . (is_string($value[0]) ? $value[0] : 'Not a string') . "\n";
        }
    }
}

echo "\n=== Recent errors (if any) ===\n";

if (session()->has('errors')) {
    $errors = session()->get('errors');
    if ($errors instanceof \Illuminate\Support\MessageBag) {
        echo "MessageBag found with messages:\n";
        foreach ($errors->getMessages() as $field => $messages) {
            echo "  $field: " . (is_array($messages) ? json_encode($messages) : $messages) . "\n";
        }
    }
}