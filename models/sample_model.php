<?php

namespace WP_Custom_API\Models;

use WP_Custom_API\Core\Model;

class Sample_Model implements Model
{
    public static function table_name():string {
        return "sample";
    }
    public static function table_schema(): array {
        return 
            [
                'first_name' => 'varchar(100) NOT NULL',
                'last_name' => 'varchar(100) NOT NULL',
                'phone' => 'varchar(20) NOT NULL',
                'email' => 'varchar(100) NOT NULL',
                'how_did_you_hear_about_us' => 'varchar(50) NOT NULL',
                'tell_us_how_you_heard_about_us' => 'varchar(255) NOT NULL',
                'message' => 'varchar(255) NOT NULL',
                'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP',
            ];
    }

    public static function run_migration(): bool {
        return true;
    }
}
