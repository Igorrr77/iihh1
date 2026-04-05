<?php

declare(strict_types=1);
require __DIR__ . '/bootstrap_cron.php';

$jobs = $db->query("SELECT aj.*, v.* FROM ai_jobs aj INNER JOIN videos v ON v.id = aj.video_id WHERE aj.status='queued' ORDER BY aj.id ASC LIMIT 10")->fetchAll();
$taxonomy = $db->query('SELECT * FROM categories WHERE is_active=1')->fetchAll();
$classifier = new App\Services\AIClassifierService(
    new App\AI\GeminiClient((string)getenv('GEMINI_API_KEY'), (string)(getenv('GEMINI_MODEL_ID') ?: config('app')['gemini_model_id']), $logger),
    $logger
);
$rules = new App\Services\RulePreclassifier();

foreach ($jobs as $job) {
    $db->prepare("UPDATE ai_jobs SET status='running', attempts=attempts+1, started_at=:now, updated_at=:now WHERE id=:id")->execute(['id'=>$job['id'],'now'=>gmdate('Y-m-d H:i:s')]);
    if ((int)$job['manual_lock'] === 1) {
        $db->prepare("UPDATE ai_jobs SET status='done', finished_at=:now, updated_at=:now WHERE id=:id")->execute(['id'=>$job['id'],'now'=>gmdate('Y-m-d H:i:s')]);
        continue;
    }

    $result = $classifier->classify($job, $taxonomy, $rules->guess((string)$job['title'], (string)$job['description']));
    $now = gmdate('Y-m-d H:i:s');
    if (($result['status'] ?? '') === 'auto_approve' && !empty($result['data'])) {
        $slug = $result['data']['primary_category_slug'];
        $catStmt = $db->prepare('SELECT id FROM categories WHERE slug = :slug LIMIT 1');
        $catStmt->execute(['slug' => $slug]);
        $catId = $catStmt->fetchColumn();
        $db->prepare('UPDATE videos SET ai_summary=:summary, ai_confidence=:conf, ai_primary_category_id=:cat, final_primary_category_id=COALESCE(manual_primary_category_id,:cat), is_start_here=:start, is_faq=:faq, is_story=:story, is_public=1, updated_at=:now WHERE id=:id')->execute([
            'summary' => mb_substr((string)$result['data']['short_summary'], 0, 350),
            'conf' => (float)$result['confidence'],
            'cat' => $catId,
            'start' => !empty($result['data']['is_start_here']) ? 1 : 0,
            'faq' => !empty($result['data']['is_faq']) ? 1 : 0,
            'story' => !empty($result['data']['is_story']) ? 1 : 0,
            'now' => $now,
            'id' => $job['video_id'],
        ]);
        $db->prepare('UPDATE ai_jobs SET status="done", response_payload=:payload, finished_at=:now, updated_at=:now WHERE id=:id')->execute(['payload'=>json_encode($result, JSON_UNESCAPED_UNICODE), 'now'=>$now, 'id'=>$job['id']]);
    } else {
        $db->prepare('INSERT INTO manual_reviews (video_id, review_status, note, created_at, updated_at) VALUES (:video_id, "pending", :note, :now, :now)')->execute(['video_id'=>$job['video_id'],'note'=>'AI confidence or validator mismatch','now'=>$now]);
        $db->prepare('UPDATE ai_jobs SET status="failed", error_message="manual_review", finished_at=:now, updated_at=:now WHERE id=:id')->execute(['now'=>$now,'id'=>$job['id']]);
    }
}

echo json_encode(['ok'=>true,'processed'=>count($jobs)], JSON_UNESCAPED_UNICODE);
