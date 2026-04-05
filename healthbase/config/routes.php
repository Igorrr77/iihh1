<?php

declare(strict_types=1);

use App\Controllers\AdminController;
use App\Controllers\HomeController;
use App\Controllers\VideoController;
use App\Controllers\SearchController;

return function ($router): void {
    $router->get('/', [HomeController::class, 'index'], 'home');
    $router->get('/start', [HomeController::class, 'start'], 'start');
    $router->get('/topics', [HomeController::class, 'topics'], 'topics');
    $router->get('/videos', [VideoController::class, 'index'], 'videos.index');
    $router->get('/video/{slug}', [VideoController::class, 'show'], 'videos.show');
    $router->get('/search', [SearchController::class, 'index'], 'search');
    $router->post('/search/ai', [SearchController::class, 'ai'], 'search.ai');

    $router->get('/admin', [AdminController::class, 'dashboard'], 'admin.dashboard', ['admin']);
    $router->get('/admin/videos', [AdminController::class, 'videos'], 'admin.videos', ['admin']);
    $router->get('/admin/review', [AdminController::class, 'reviews'], 'admin.reviews', ['admin']);
    $router->post('/admin/video/{id}/lock', [AdminController::class, 'lockVideo'], 'admin.video.lock', ['admin']);
    $router->post('/admin/video/{id}/reclassify', [AdminController::class, 'reclassifyVideo'], 'admin.video.reclassify', ['admin']);
    $router->get('/healthz', [AdminController::class, 'healthz'], 'admin.healthz', ['admin']);

    $router->get('/{category}', [HomeController::class, 'category'], 'category.show');
    $router->get('/{category}/{subcategory}', [HomeController::class, 'subcategory'], 'subcategory.show');
};
