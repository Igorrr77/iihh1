<!doctype html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commentor Dashboard</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <header class="topbar">
        <h1>Commentor — админ-панель</h1>
        <a href="/index.php?action=logout">Выйти</a>
    </header>

    <?php if (isset($_GET['saved'])): ?><div class="alert">Настройки сохранены</div><?php endif; ?>
    <?php if (isset($_GET['account_added'])): ?><div class="alert">Аккаунт добавлен</div><?php endif; ?>


    <section class="card">
        <h2>🚀 Инструкция ДЛЯ ЧАЙНИКОВ: подключение Meta и TikTok за 5 минут</h2>
        <p><strong>Важно:</strong> делайте шаги строго по порядку. Если где-то застряли — переходите к шагу ниже только после того, как текущий шаг выполнен.</p>

        <h3>Часть A — Meta (Instagram + Facebook Page)</h3>
        <ol>
            <li><strong>Откройте Meta for Developers:</strong> <a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener">https://developers.facebook.com/apps/</a> и нажмите <em>Create App</em>.</li>
            <li><strong>Создайте приложение</strong> (любой подходящий тип для бизнес-интеграции), укажите email и сохраните.</li>
            <li><strong>Добавьте продукты:</strong> Webhooks + Instagram Graph API + Pages API (внутри Dashboard кнопка <em>Add products</em>).</li>
            <li><strong>Настройте Webhook callback URL:</strong> укажите <code>https://ВАШ-ДОМЕН/webhook.php</code>.</li>
            <li><strong>Verify Token:</strong> скопируйте значение <code>WEBHOOK_VERIFY_TOKEN</code> из файла <code>.env</code> и вставьте в Meta.</li>
            <li><strong>Подпись Webhook:</strong> в <code>.env</code> заполните <code>META_APP_SECRET</code> (из настроек приложения Meta). Без этого подпись Meta не пройдет проверку.</li>
            <li><strong>Подпишитесь на события комментариев</strong> для Instagram/Facebook (comments related fields в разделе subscriptions).</li>
            <li><strong>Получите access token и внешний ID аккаунта</strong>:
                <ul>
                    <li>Graph API Explorer: <a href="https://developers.facebook.com/tools/explorer/" target="_blank" rel="noopener">https://developers.facebook.com/tools/explorer/</a></li>
                    <li>Get Started по Graph API: <a href="https://developers.facebook.com/docs/graph-api/get-started" target="_blank" rel="noopener">https://developers.facebook.com/docs/graph-api/get-started</a></li>
                </ul>
            </li>
            <li><strong>Добавьте аккаунт в этой админке:</strong>
                <ul>
                    <li><code>platform</code>: instagram или facebook_page</li>
                    <li><code>account_id</code>: это ID из webhook entry.id (Page ID / IG User ID)</li>
                    <li><code>access_token</code>: токен с правами публикации reply</li>
                </ul>
            </li>
            <li><strong>Проверьте работу:</strong> оставьте комментарий под постом и убедитесь, что запись появилась в таблице «Последние комментарии и ответы».</li>
        </ol>

        <p><strong>Полезные ссылки Meta:</strong><br>
            • Webhooks getting started: <a href="https://developers.facebook.com/docs/graph-api/webhooks/getting-started" target="_blank" rel="noopener">https://developers.facebook.com/docs/graph-api/webhooks/getting-started</a><br>
            • Instagram API with Instagram Login: <a href="https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login" target="_blank" rel="noopener">https://developers.facebook.com/docs/instagram-platform/instagram-api-with-instagram-login</a>
        </p>

        <h3>Часть B — TikTok</h3>
        <ol>
            <li><strong>Создайте TikTok Developer аккаунт:</strong> <a href="https://developers.tiktok.com/signup" target="_blank" rel="noopener">https://developers.tiktok.com/signup</a></li>
            <li><strong>Откройте документацию и создайте App:</strong> <a href="https://developers.tiktok.com/doc/overview" target="_blank" rel="noopener">https://developers.tiktok.com/doc/overview</a></li>
            <li><strong>Подключите нужный продукт</strong> (обычно Login Kit + Content Posting API, в зависимости от вашего сценария).</li>
            <li><strong>Получите токен доступа</strong> и endpoint для reply (если выдан для вашего приложения).</li>
            <li><strong>Добавьте TikTok аккаунт в админке:</strong>
                <ul>
                    <li><code>platform</code>: tiktok</li>
                    <li><code>access_token</code>: ваш TikTok token</li>
                    <li><code>Заметка / JSON meta</code>: <code>{"reply_api_url":"https://..."}</code></li>
                </ul>
            </li>
            <li><strong>Проверьте тестовым комментарием</strong> и посмотрите статус в таблице комментариев.</li>
        </ol>

        <p><strong>Полезные ссылки TikTok:</strong><br>
            • Developer docs overview: <a href="https://developers.tiktok.com/doc/overview" target="_blank" rel="noopener">https://developers.tiktok.com/doc/overview</a><br>
            • Content Posting API: <a href="https://developers.tiktok.com/products/content-posting-api/" target="_blank" rel="noopener">https://developers.tiktok.com/products/content-posting-api/</a><br>
            • Getting started FAQ: <a href="https://developers.tiktok.com/doc/getting-started-faq" target="_blank" rel="noopener">https://developers.tiktok.com/doc/getting-started-faq</a><br>
            • Support: <a href="https://developers.tiktok.com/support/" target="_blank" rel="noopener">https://developers.tiktok.com/support/</a>
        </p>

        <h3>Мини-чеклист «если не работает»</h3>
        <ol>
            <li>Проверьте, что cron запущен каждую минуту (<code>/public/cron.php?secret=...</code>).</li>
            <li>Проверьте, что в <code>.env</code> корректны <code>WEBHOOK_VERIFY_TOKEN</code>, <code>WEBHOOK_SHARED_SECRET</code>, <code>META_APP_SECRET</code>.</li>
            <li>Проверьте, что <code>account_id</code> в админке совпадает с <code>entry.id</code> из входящего webhook.</li>
            <li>Проверьте права токена (без нужных scopes ответ публиковаться не будет).</li>
        </ol>
    </section>


    <section class="card">
        <h2>🩺 Диагностика подключения аккаунта (пошагово)</h2>
        <p>Выберите аккаунт и нажмите кнопку. Система покажет, что работает, что сломано и что именно исправить.</p>
        <form method="post" class="stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="run_diagnostic">
            <label>Аккаунт для проверки
                <select name="diagnostic_account_id" required>
                    <option value="">Выберите аккаунт</option>
                    <?php foreach ($accounts as $acc): ?>
                        <option value="<?= (int) $acc['id'] ?>">#<?= (int) $acc['id'] ?> — <?= e($acc['platform']) ?> — <?= e($acc['account_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button type="submit">Запустить диагностику подключения</button>
        </form>

        <?php if (!empty($diagnosticReport) && is_array($diagnosticReport)): ?>
            <div class="alert"><?= e((string) ($diagnosticReport['summary'] ?? 'Диагностика завершена')) ?></div>
            <table>
                <thead><tr><th>Статус</th><th>Шаг</th><th>Результат / Что исправить</th></tr></thead>
                <tbody>
                <?php foreach (($diagnosticReport['steps'] ?? []) as $step): ?>
                    <?php $status = (string) ($step['status'] ?? 'warn'); ?>
                    <tr>
                        <td>
                            <?php if ($status === 'ok'): ?>✅ OK<?php elseif ($status === 'fail'): ?>❌ FAIL<?php else: ?>⚠️ WARN<?php endif; ?>
                        </td>
                        <td><?= e((string) ($step['title'] ?? 'Шаг')) ?></td>
                        <td><?= e((string) ($step['message'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!empty($diagnosticReport['checked_at'])): ?>
                <p><small>Проверено: <?= e((string) $diagnosticReport['checked_at']) ?></small></p>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <section class="card">
        <h2>Промт и поведение AI</h2>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="save_settings">
            <label>System prompt
                <textarea name="system_prompt" rows="8" required><?= e($settings['system_prompt'] ?? '') ?></textarea>
            </label>
            <div class="grid2">
                <label>CTA ссылка
                    <input type="url" name="cta_link" value="<?= e($settings['cta_link'] ?? 'https://028.uno/diag') ?>" required>
                </label>
                <label>Язык ответа
                    <input type="text" name="response_language" value="<?= e($settings['response_language'] ?? 'ru') ?>">
                </label>
            </div>
            <button type="submit">Сохранить</button>
        </form>
    </section>

    <section class="card">
        <h2>Подключенные аккаунты</h2>
        <form method="post" class="stack">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="add_account">
            <div class="grid2">
                <label>Платформа
                    <select name="platform" required>
                        <option value="instagram">Instagram</option>
                        <option value="facebook">Facebook</option>
                        <option value="facebook_page">Facebook Page</option>
                        <option value="tiktok">TikTok</option>
                    </select>
                </label>
                <label>Имя аккаунта<input name="account_name" required></label>
                <label>ID аккаунта / Page ID / IG User ID<input name="account_id" placeholder="178414..." required></label>
                <label>Access token<input name="access_token" placeholder="EAAB..." required></label>
                <label>Refresh token (опц.)<input name="refresh_token" placeholder="refresh..."></label>
                <label>Token expires at (unix, опц.)<input name="token_expires_at" type="number" min="0" placeholder="1735689600"></label>
            </div>
            <label>Заметка / JSON meta (TikTok reply_api_url или oauth_token_url/client_id/client_secret)<input name="note"></label>
            <button type="submit">Добавить аккаунт</button>
        </form>

        <table>
            <thead><tr><th>ID</th><th>Платформа</th><th>Аккаунт</th><th>External ID</th><th>Token exp</th><th>Создан</th></tr></thead>
            <tbody>
            <?php foreach ($accounts as $acc): ?>
                <tr>
                    <td><?= (int) $acc['id'] ?></td>
                    <td><?= e($acc['platform']) ?></td>
                    <td><?= e($acc['account_name']) ?></td>
                    <td><?= e((string) ($acc['account_id'] ?? '')) ?></td>
                    <td><?= e((string) ($acc['token_expires_at'] ?? '')) ?></td>
                    <td><?= e($acc['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Последние комментарии и ответы</h2>
        <table>
            <thead><tr><th>ID</th><th>Платформа</th><th>Аккаунт</th><th>Комментарий</th><th>Ответ</th><th>Статус</th><th>Попытки</th></tr></thead>
            <tbody>
            <?php foreach ($comments as $comment): ?>
                <tr>
                    <td><?= (int) $comment['id'] ?></td>
                    <td><?= e($comment['platform']) ?></td>
                    <td><?= e((string) ($comment['account_name'] ?? '—')) ?></td>
                    <td><strong>@<?= e($comment['commenter_handle']) ?></strong><br><?= e($comment['comment_text']) ?></td>
                    <td><?= e((string) ($comment['generated_reply'] ?? '')) ?></td>
                    <td><?= e($comment['status']) ?><br><small><?= e((string) ($comment['error_message'] ?? '')) ?></small></td>
                    <td><?= (int) ($comment['attempts'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card">
        <h2>Аудит-лог (последние 20)</h2>
        <table>
            <thead><tr><th>Когда</th><th>Уровень</th><th>Сообщение</th><th>Payload</th></tr></thead>
            <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= e($log['created_at']) ?></td>
                    <td><?= e($log['level']) ?></td>
                    <td><?= e($log['message']) ?></td>
                    <td><?= e((string) ($log['payload_json'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>
</body>
</html>
