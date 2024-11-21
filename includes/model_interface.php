<?php

declare(strict_types=1);

namespace WP_Custom_API\Includes;

interface Model_Interface
{
    public static function table_name(): string;
    public static function table_schema(): array;
    public static function run_migration(): bool;
}
