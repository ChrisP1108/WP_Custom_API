<?php

namespace WP_Custom_API\Models;

class Sample_Model
{
    public const TABLE_NAME = "sample";
    public const TABLE_SCHEMA =
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
    public const RUN_MIGRATION = true;
}
