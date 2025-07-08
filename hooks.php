<?php

namespace WP_Custom_API;

/** 
 * Prevent direct access from sources other than the Wordpress environment
 */

if (!defined('ABSPATH')) exit;

/** 
 * Used for adding additional functionality to this plugin.
 * Two hooks are provided: before_init and after_init for adding functionality before and after the plugin initializes.
 */

final class Hooks
{

    /**
     * Code that run before the plugin initializes
     * 
     * This is where you can add any additional code you want to run before the plugin initializes
     */

    public static function before_init(): void
    {
        // Add additional functionality you want to load before the plugin initializes
    }

    /**
     * Code that runs after the plugin initializes
     * 
     * This is where you can add any additional code you want to run after the plugin initializes
     */

    public static function after_init(): void
    {
        // Add additional functionality you want to load after the plugin initializes
    }
}