<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\Logger;

class AdminController extends BaseController
{
    public function dashboard(Request $request): void
    {
        $this->runPseudoCron();
        $stats = [
            'videos' => (int)$this->db()->query('SELECT COUNT(*) FROM videos')->fetchColumn(),
            'long_videos' => (int)$this->db()->query('SELECT COUNT(*) FROM videos WHERE is_long_video = 1')->fetchColumn(),
            'review' => (int)$this->db()->query("SELECT COUNT(*) FROM manual_reviews WHERE review_status='pending'")->fetchColumn(),
            'jobs' => (int)$this->db()->query("SELECT COUNT(*) FROM ai_jobs WHERE status='queued'")->fetchColumn(),
        ];

        Response::view('admin/dashboard', compact('stats'));
    }

    private function runPseudoCron(): void
    {
        $token = getenv('CRON_TOKEN');
        if (!$token) {
            return;
        }

        $base = rtrim((string)config('app')['url'], '/');
        $path = app_base_path();
        @file_get_contents($base . $path . '/cron/sync_youtube.php?token=' . urlencode($token));
        @file_get_contents($base . $path . '/cron/ai_reclassify.php?token=' . urlencode($token));
        $stmt = $this->db()->prepare("INSERT INTO settings (`key`,`value`,`type`,created_at,updated_at) VALUES ('last_pseudo_cron_at',:v,'datetime',:now,:now) ON DUPLICATE KEY UPDATE `value`=:v, updated_at=:now");
        $now = gmdate('Y-m-d H:i:s');
        $stmt->execute(['v' => $now, 'now' => $now]);
        (new Logger())->log('app', 'Sync and AI refresh triggered from admin dashboard entry');
    }

    public function videos(Request $request): void
    {
        $videos = $this->db()->query('SELECT * FROM videos ORDER BY published_at DESC LIMIT 100')->fetchAll();
        Response::view('admin/videos', compact('videos'));
    }

    public function reviews(Request $request): void
    {
        $reviews = $this->db()->query('SELECT mr.*, v.title, v.youtube_video_id FROM manual_reviews mr INNER JOIN videos v ON v.id = mr.video_id ORDER BY mr.created_at DESC LIMIT 100')->fetchAll();
        Response::view('admin/reviews', compact('reviews'));
    }

    public function lockVideo(Request $request, array $params): void
    {
        if (!verify_csrf($request->input('_token'))) {
            flash('error', 'Неверный CSRF токен.');
            Response::redirect('/admin/videos');
        }

        $stmt = $this->db()->prepare('UPDATE videos SET manual_lock = 1, updated_at = :now WHERE id = :id');
        $stmt->execute(['id' => (int)$params['id'], 'now' => gmdate('Y-m-d H:i:s')]);
        flash('success', 'Видео заблокировано от автоизменений.');
        Response::redirect('/admin/videos');
    }

    public function reclassifyVideo(Request $request, array $params): void
    {
        if (!verify_csrf($request->input('_token'))) {
            flash('error', 'Неверный CSRF токен.');
            Response::redirect('/admin/videos');
        }

        $sql = 'INSERT INTO ai_jobs (video_id, job_type, input_hash, request_payload, status, attempts, created_at, updated_at) VALUES (:video_id, "manual_reclassify", :hash, "{}", "queued", 0, :now, :now)';
        $this->db()->prepare($sql)->execute([
            'video_id' => (int)$params['id'],
            'hash' => sha1('manual-' . $params['id'] . '-' . microtime(true)),
            'now' => gmdate('Y-m-d H:i:s'),
        ]);

        flash('success', 'Переклассификация поставлена в очередь.');
        Response::redirect('/admin/videos');
    }

    public function healthz(Request $request): void
    {
        $data = [
            'time_utc' => gmdate('c'),
            'db' => (bool)$this->db()->query('SELECT 1')->fetchColumn(),
            'storage_writable' => is_writable(root_path('storage')),
            'queued_ai' => (int)$this->db()->query("SELECT COUNT(*) FROM ai_jobs WHERE status='queued'")->fetchColumn(),
            'pending_reviews' => (int)$this->db()->query("SELECT COUNT(*) FROM manual_reviews WHERE review_status='pending'")->fetchColumn(),
        ];

        Response::json(['ok' => true, 'health' => $data]);
    }
}
