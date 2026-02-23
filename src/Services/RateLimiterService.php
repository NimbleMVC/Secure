<?php

namespace NimblePHP\Secure\Services;

use krzysztofzylka\DatabaseManager\Table;
use NimblePHP\Framework\Cache;
use NimblePHP\Framework\Config;
use NimblePHP\Framework\Interfaces\RequestInterface;
use Throwable;

class RateLimiterService
{

    /**
     * Check if rate limit is enabled.
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool)Config::get('SECURE_RATE_LIMIT_ENABLED', false);
    }

    /**
     * Apply rate limit for request.
     * @param RequestInterface $request
     * @param string $scope
     * @return array{allowed: bool, current: int, limit: int, retryAfter: int}
     */
    public function hit(RequestInterface $request, string $scope = 'global'): array
    {
        $key = $this->buildKey($request, $scope);

        if ($this->useDatabaseStorage()) {
            try {
                return $this->hitDatabase($key);
            } catch (Throwable) {
                return $this->hitCache($key);
            }
        }

        return $this->hitCache($key);
    }

    /**
     * @param string $key
     * @return array{allowed: bool, current: int, limit: int, retryAfter: int}
     */
    private function hitCache(string $key): array
    {
        /** @var Cache $cache */
        $cache = new Cache('cache/secure_rate_limit');

        $limit = max(1, (int)Config::get('SECURE_RATE_LIMIT_MAX_ATTEMPTS', 120));
        $windowSeconds = max(1, (int)Config::get('SECURE_RATE_LIMIT_WINDOW', 60));

        $data = $cache->get($key, ['count' => 0, 'expiresAt' => time() + $windowSeconds]);

        if (!is_array($data) || !isset($data['count'], $data['expiresAt'])) {
            $data = ['count' => 0, 'expiresAt' => time() + $windowSeconds];
        }

        if ((int)$data['expiresAt'] <= time()) {
            $data = ['count' => 0, 'expiresAt' => time() + $windowSeconds];
        }

        $data['count'] = (int)$data['count'] + 1;
        $ttl = max(1, (int)$data['expiresAt'] - time());
        $cache->set($key, $data, $ttl);

        $retryAfter = max(0, (int)$data['expiresAt'] - time());

        return [
            'allowed' => $data['count'] <= $limit,
            'current' => (int)$data['count'],
            'limit' => $limit,
            'retryAfter' => $retryAfter,
        ];
    }

    /**
     * @param string $key
     * @return array{allowed: bool, current: int, limit: int, retryAfter: int}
     */
    private function hitDatabase(string $key): array
    {
        $limit = max(1, (int)Config::get('SECURE_RATE_LIMIT_MAX_ATTEMPTS', 120));
        $windowSeconds = max(1, (int)Config::get('SECURE_RATE_LIMIT_WINDOW', 60));
        $now = time();
        $keyHash = sha1($key);
        $tableName = $this->getDatabaseTableName();

        $table = new Table($tableName);
        $record = $table->find(['key_hash' => $keyHash]);
        $recordData = $record[$tableName] ?? $record;

        if (empty($recordData)) {
            $expiresAt = $now + $windowSeconds;
            $attempts = 1;

            $table->insert([
                'key_hash' => $keyHash,
                'attempts' => $attempts,
                'expires_at' => $expiresAt,
            ]);
        } else {
            $id = (int)($recordData['id'] ?? 0);
            $expiresAt = (int)($recordData['expires_at'] ?? 0);
            $attempts = (int)($recordData['attempts'] ?? 0);

            if ($expiresAt <= $now) {
                $expiresAt = $now + $windowSeconds;
                $attempts = 1;
            } else {
                $attempts++;
            }

            if ($id > 0) {
                $table->setId($id)->update([
                    'attempts' => $attempts,
                    'expires_at' => $expiresAt,
                ]);
            } else {
                $table->insert([
                    'key_hash' => $keyHash,
                    'attempts' => $attempts,
                    'expires_at' => $expiresAt,
                ]);
            }
        }

        $retryAfter = max(0, $expiresAt - $now);

        return [
            'allowed' => $attempts <= $limit,
            'current' => $attempts,
            'limit' => $limit,
            'retryAfter' => $retryAfter,
        ];
    }

    /**
     * @return bool
     */
    private function useDatabaseStorage(): bool
    {
        if (!(bool)($_ENV['DATABASE'] ?? false)) {
            return false;
        }

        return strtolower((string)Config::get('SECURE_RATE_LIMIT_STORAGE', 'cache')) === 'database';
    }

    /**
     * @return string
     */
    private function getDatabaseTableName(): string
    {
        return (string)Config::get('SECURE_RATE_LIMIT_TABLE', 'module_secure_rate_limit');
    }

    /**
     * @param RequestInterface $request
     * @param string $scope
     * @return string
     */
    private function buildKey(RequestInterface $request, string $scope): string
    {
        $ip = $this->resolveIp($request);
        $method = strtoupper((string)$request->getMethod());
        $uri = strtok((string)$request->getUri(), '?') ?: '';

        $mode = (string)Config::get('SECURE_RATE_LIMIT_KEY_MODE', 'ip');

        if ($mode === 'ip') {
            return 'secure_rl:' . sha1($ip);
        }

        return 'secure_rl:' . sha1($scope . '|' . $ip . '|' . $method . '|' . $uri);
    }

    /**
     * @param RequestInterface $request
     * @return string
     */
    private function resolveIp(RequestInterface $request): string
    {
        $ip = (string)$request->getServer('REMOTE_ADDR', '');

        $forwarded = (string)$request->getHeader('X-Forwarded-For');

        if ($forwarded !== '') {
            $candidate = trim(explode(',', $forwarded)[0]);

            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                $ip = $candidate;
            }
        }

        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '0.0.0.0';
        }

        return $ip;
    }

}
