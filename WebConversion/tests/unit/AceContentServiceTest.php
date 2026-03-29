<?php

declare(strict_types=1);

use App\Services\AceContentService;

require_once __DIR__ . '/../bootstrap.php';

$service = new AceContentService();
$pack = $service->generatePack('Это длинный транскрипт вебинара о продажах и конверсии. В конце дали запись и оффер.');
assertTrue(isset($pack['summary']), 'ACE pack must contain summary');
assertTrue(isset($pack['email_followup']), 'ACE pack must contain email_followup');
assertTrue(count($pack) >= 4, 'ACE pack should contain multiple assets');

$benchmark = $service->qualityBenchmark([
    ['content_type' => 'summary', 'content_text' => $pack['summary']],
]);
assertTrue(isset($benchmark['average_score']), 'Benchmark should include average score');
