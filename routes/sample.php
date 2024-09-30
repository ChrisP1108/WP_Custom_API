<?php

use WP_Custom_API\Core\Router;
use WP_Custom_API\Controllers\Sample_Controller;

// Get Sample Route

Router::get("/samples", [Sample_Controller::class, 'get_all']);
