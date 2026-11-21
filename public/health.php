<?php

header('Content-Type: text/plain; charset=utf-8');

function hline($k, $v) {
    echo str_pad($k, 24, ' ') . ': ' . $v . "\n";
}

hline('HEALTH', 'OK');
hline('BUILD MARKER', 'appkey-fallback-20260915');
hline('PHP', PHP_VERSION);
hline('APP_ENV(env)', (string) getenv('APP_ENV'));
hline('APP_DEBUG(env)', (string) getenv('APP_DEBUG'));
hline('APP_KEY(env)', getenv('APP_KEY') ? 'set' : 'MISSING');
hline('DB_CONNECTION(env)', (string) getenv('DB_CONNECTION'));
hline('DB_HOST(env)', (string) getenv('DB_HOST'));
hline('DB_PORT(env)', (string) getenv('DB_PORT'));
hline('DB_DATABASE(env)', (string) getenv('DB_DATABASE'));
hline('DB_USERNAME(env)', (string) getenv('DB_USERNAME'));
hline('DB_PASSWORD(env)', getenv('DB_PASSWORD') ? 'set' : 'MISSING');
hline('DATABASE_URL(env)', getenv('DATABASE_URL') ? 'set' : 'MISSING');
hline('SESSION_DRIVER(env)', (string) getenv('SESSION_DRIVER'));
hline('CACHE_STORE(env)', (string) getenv('CACHE_STORE'));
hline('storage writable', is_writable(__DIR__ . '/../storage') ? 'yes' : 'no');
hline('bootstrap/cache writable', is_writable(__DIR__ . '/../bootstrap/cache') ? 'yes' : 'no');
hline('manifest exists', file_exists(__DIR__ . '/build/manifest.json') ? 'yes' : 'no');

echo "\n";

try {
    require __DIR__ . '/../vendor/autoload.php';
    $app = require __DIR__ . '/../bootstrap/app.php';
    hline('BOOTSTRAP', 'OK');
} catch (\Throwable $e) {
    hline('BOOTSTRAP', 'FAIL: ' . get_class($e) . ': ' . $e->getMessage());
    exit;
}

try {
    $console = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $console->bootstrap();
    hline('CONSOLE BOOTSTRAP', 'OK');
    hline('config app.key', config('app.key') ? 'set' : 'MISSING');
    hline('config app.debug', config('app.debug') ? 'true' : 'false');
    hline('config database.default', (string) config('database.default'));
    hline('config session.driver', (string) config('session.driver'));
    hline('config cache.default', (string) config('cache.default'));
} catch (\Throwable $e) {
    hline('CONSOLE BOOTSTRAP', 'FAIL: ' . get_class($e) . ': ' . $e->getMessage());
}

try {
    Illuminate\Support\Facades\DB::connection()->getPdo();
    hline('DB PDO', 'OK');
} catch (\Throwable $e) {
    hline('DB PDO', 'FAIL: ' . get_class($e) . ': ' . $e->getMessage());
}

try {
    Illuminate\Support\Facades\DB::connection()->select('select 1');
    hline('DB SELECT 1', 'OK');
} catch (\Throwable $e) {
    hline('DB SELECT 1', 'FAIL: ' . get_class($e) . ': ' . $e->getMessage());
}

try {
    hline('sessions table', Illuminate\Support\Facades\Schema::hasTable('sessions') ? 'yes' : 'no');
    hline('users table', Illuminate\Support\Facades\Schema::hasTable('users') ? 'yes' : 'no');
    hline('migrations table', Illuminate\Support\Facades\Schema::hasTable('migrations') ? 'yes' : 'no');
} catch (\Throwable $e) {
    hline('schema check', 'FAIL: ' . get_class($e) . ': ' . $e->getMessage());
}

echo "\n--- live request simulation ---\n";
try {
    config(['app.debug' => true]);
    $http = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $request = Illuminate\Http\Request::create('/login', 'GET');
    $response = $http->handle($request);
    hline('GET /login status', (string) $response->getStatusCode());
    if ($response->getStatusCode() >= 400) {
        echo substr(strip_tags($response->getContent()), 0, 2500) . "\n";
    }
} catch (\Throwable $e) {
    hline('GET /login', 'THROWN: ' . get_class($e) . ': ' . $e->getMessage());
}
