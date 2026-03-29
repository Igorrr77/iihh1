<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\TimelineRepository;
use App\Models\WebinarRepository;
use App\Services\RuntimeEngineService;

final class RuntimeController
{
    public function dueEvents(): void
    {
        $webinarId = (string) ($_GET['webinar_id'] ?? '');
        $elapsed = (int) ($_GET['elapsed'] ?? 0);

        $webinar = (new WebinarRepository())->findByExternalId($webinarId);
        if ($webinar === null) {
            Response::json(['error' => 'Вебинар не найден'], 404);
            return;
        }

        $events = (new TimelineRepository())->listEvents((int) $webinar['id']);
        $due = (new RuntimeEngineService())->dueEvents($events, $elapsed);

        Response::json(['elapsed' => $elapsed, 'events' => $due]);
    }
}
