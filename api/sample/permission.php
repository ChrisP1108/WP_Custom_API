<?php

declare(strict_types=1);

namespace WP_Custom_API\Api\Sample;

use WP_REST_Request as Request;
use WP_Custom_API\Includes\Permission_Interface;
use WP_Custom_API\Api\Sample\Model;
use WP_Custom_API\Api\Sample\Utils;

/**
* Prevent direct access from sources other than the Wordpress environment
*/

if (!defined('ABSPATH')) exit;

/**
* Interface namespace - sample
*/

final class Permission extends Permission_Interface
{
    // Insert permission methods here.
}