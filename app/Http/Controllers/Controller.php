<?php

namespace App\Http\Controllers;

/**
 * Conventional Laravel base controller.
 *
 * MUST exist even if empty — Laravel's routing internals check
 * `App\Http\Controllers\Controller` by name when resolving controller
 * middleware. Without this file, any controller class fails to autoload
 * with:
 *   Class "App\Http\Controllers\Controller" not found
 * and the request returns HTTP 500/503.
 */
abstract class Controller
{
    //
}
