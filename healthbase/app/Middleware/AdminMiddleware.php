<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class AdminMiddleware
{
    public function handle(Request $request): void
    {
        $authorized = isset($_SESSION['admin_user_id']);
        if (!$authorized) {
            Response::redirect('/admin/login.php');
        }
    }
}
