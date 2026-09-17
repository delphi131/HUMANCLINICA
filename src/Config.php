<?php

declare(strict_types=1);

final class Config
{
    private static ?array $app = null;
    private static ?array $schema = null;

    public static function app(): array
    {
        if (self::$app === null) {
            $path = __DIR__ . '/../config/config.php';
            if (!is_file($path)) {
                throw new RuntimeException(
                    'config/config.php non trovato. Copia config/config.sample.php in config/config.php e compilalo.'
                );
            }
            self::$app = require $path;
        }
        return self::$app;
    }

    public static function schema(): array
    {
        if (self::$schema === null) {
            self::$schema = require __DIR__ . '/../config/schema.php';
        }
        return self::$schema;
    }

    public static function get(string $dotted, $default = null)
    {
        $parts = explode('.', $dotted);
        $value = self::app();
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
