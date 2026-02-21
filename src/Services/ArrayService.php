<?php
namespace NimblePHP\Secure\Services;

class ArrayService
{

    /**
     * Default list of sensitive keys.
     * Good baseline for most applications, but can be overridden/extended.
     */
    private const DEFAULT_SENSITIVE_KEYS = [
        'password', 'pass', 'passwd', 'password_hash', 'pin', 'otp', 'totp', 'mfa', 'backup_code',
        'token', 'access_token', 'refresh_token', 'remember_token', 'api_key', 'apikey', 'auth', 'authorization', 'jwt',
        'secret', 'client_secret', 'private_key', 'public_key', 'access_key', 'secret_key',
        'card', 'card_number', 'cc', 'cc_number', 'pan', 'cvv', 'cvc', 'iban', 'bic', 'swift',
        'session', 'session_id', 'sid', 'session_cookie_name', 'cookie_name',
        'email', 'phone', 'pesel', 'ssn', 'dob', 'birthdate',
        'address', 'street', 'postcode', 'zip', 'city'
    ];

    /**
     * Default regex patterns used to detect sensitive data in strings.
     */
    private const DEFAULT_SENSITIVE_PATTERNS = [
        '/(password|pass|passwd)\s*[:=]\s*([^\s]+)/i',
        '/(token|access_token|refresh_token|remember_token|api_key|apikey|authorization|jwt)\s*[:=]\s*([^\s]+)/i',
        '/\b(?:\d[ -]*?){13,19}\b/'
    ];

    /**
     * Current list of sensitive keys.
     * @var array
     */
    private array $sensitiveKeys = self::DEFAULT_SENSITIVE_KEYS;

    /**
     * Current list of sensitive patterns.
     * @var array
     */
    private array $sensitivePatterns = self::DEFAULT_SENSITIVE_PATTERNS;

    /**
     * Sets the sensitive keys list (overrides defaults).
     * @param array $keys
     * @return $this
     */
    public function setSensitiveKeys(array $keys): self
    {
        $this->sensitiveKeys = $this->normalizeKeys($keys);

        return $this;
    }

    /**
     * Adds extra sensitive keys to the existing list.
     * @param array $keys
     * @return $this
     */
    public function addSensitiveKeys(array $keys): self
    {
        $this->sensitiveKeys = array_values(array_unique(array_merge(
            $this->sensitiveKeys,
            $this->normalizeKeys($keys)
        )));

        return $this;
    }

    /**
     * Sets the sensitive regex patterns list (overrides defaults).
     * @param array $patterns
     * @return $this
     */
    public function setSensitivePatterns(array $patterns): self
    {
        $this->sensitivePatterns = $patterns;

        return $this;
    }

    /**
     * Adds extra regex patterns to the existing list.
     * @param array $patterns
     * @return $this
     */
    public function addSensitivePatterns(array $patterns): self
    {
        $this->sensitivePatterns = array_values(array_unique(array_merge(
            $this->sensitivePatterns,
            $patterns
        )));

        return $this;
    }

    /**
     * Masks sensitive values in an array (recursively).
     * @param array $data
     * @return array
     */
    public function maskSensitiveInArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->maskSensitiveInArray($value);

                continue;
            }

            $keyLower = is_string($key) ? strtolower($key) : '';

            foreach ($this->sensitiveKeys as $sensitiveKey) {
                if ($keyLower !== '' && str_contains($keyLower, $sensitiveKey)) {
                    if (is_bool($value) || $value === 0 || $value === 1 || $value === '0' || $value === '1') {
                        $data[$key] = $value;
                    } else {
                        $data[$key] = is_string($value) ? $this->maskValue($value) : '***';
                    }
                    continue 2;
                }
            }

            if (is_string($value)) {
                $data[$key] = $this->maskSensitiveInString($value);
            }
        }

        return $data;
    }

    /**
     * Masks sensitive data inside a string using regex patterns.
     * @param string $value
     * @return string
     */
    public function maskSensitiveInString(string $value): string
    {
        foreach ($this->sensitivePatterns as $pattern) {
            $value = preg_replace_callback(
                $pattern,
                function (array $matches): string {
                    $key = $matches[1] ?? '';
                    $raw = $matches[2] ?? '';

                    return $key . '= ' . $this->maskValue($raw);
                },
                $value
            ) ?? $value;
        }

        return $value;
    }

    /**
     * Masks a value: first 4 and last 4, or only last 4 if too short.
     * @param string $value
     * @return string
     */
    private function maskValue(string $value): string
    {
        $value = trim($value);
        $length = mb_strlen($value);

        if ($length <= 4) {
            return '***';
        }

        if ($length <= 11) {
            return str_repeat('*', max(0, $length - 4)) . mb_substr($value, -4);
        }

        $start = mb_substr($value, 0, 4);
        $end = mb_substr($value, -4);
        $middle = str_repeat('*', $length - 8);

        return $start . $middle . $end;
    }

    /**
     * Normalizes keys to lowercase and removes duplicates.
     * @param array $keys
     * @return array
     */
    private function normalizeKeys(array $keys): array
    {
        $normalized = [];

        foreach ($keys as $key) {
            if (is_string($key) && $key !== '') {
                $normalized[] = strtolower($key);
            }
        }

        return array_values(array_unique($normalized));
    }

}