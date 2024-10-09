<?php

declare(strict_types=1);

namespace WP_Custom_API\Routes;

use WP_Custom_API\Core\Router;
use WP_Custom_API\Controllers\Sample as Controller;

// Get Sample Route

Router::get("/samples", [Controller::class, 'get_all']);
