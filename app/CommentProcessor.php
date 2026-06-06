<?php

declare(strict_types=1);

namespace Commentor;

use PDO;

final class CommentProcessor
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly SettingRepository $settings,
        private readonly GeminiClient $gemini,
        private readonly PlatformClient $platformClient,
        private readonly Logger $logger,
    ) {
    }

    public function processPending(int $limit = 10): int
    {
        $comments = $this->claimBatch($limit);

        $processed = 0;
        foreach ($comments as $comment) {
            $this->processOne($comment);
            $processed++;
        }

        return $processed;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function claimBatch(int $limit): array
    {
        $candidateStmt = $this->pdo->prepare("SELECT id FROM comments
            WHERE status IN ('pending', 'retry')
              AND (next_attempt_at IS NULL OR next_attempt_at <= CURRENT_TIMESTAMP)
            ORDER BY received_at ASC
            LIMIT :limit");
        $candidateStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $candidateStmt->execute();
        $ids = array_map(static fn(array $row): int => (int) $row['id'], $candidateStmt->fetchAll());

        if ($ids === []) {
            return [];
        }

        $in = implode(',', array_fill(0, count($ids), '?'));
        $lockStmt = $this->pdo->prepare("UPDATE comments
            SET status='in_progress', locked_at=CURRENT_TIMESTAMP
            WHERE id IN ($in) AND status IN ('pending', 'retry')");
        foreach ($ids as $i => $id) {
            $lockStmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $lockStmt->execute();

        $fetchStmt = $this->pdo->prepare("SELECT * FROM comments WHERE id IN ($in) AND status='in_progress'");
        foreach ($ids as $i => $id) {
            $fetchStmt->bindValue($i + 1, $id, PDO::PARAM_INT);
        }
        $fetchStmt->execute();
        return $fetchStmt->fetchAll();
    }

    private function processOne(array $comment): void
    {
        $deadlineSeconds = (int) Env::get('RESPONSE_DEADLINE_SECONDS', '180');
        $maxAttempts = (int) Env::get('MAX_RETRY_ATTEMPTS', '5');
        $baseRetry = (int) Env::get('RETRY_BASE_SECONDS', '30');

        $receivedTs = strtotime((string) ($comment['received_at'] ?? 'now'));
        if (time() - $receivedTs > $deadlineSeconds) {
            $this->updateStatus((int) $comment['id'], 'expired', 'Превышен дедлайн ответа ' . $deadlineSeconds . ' секунд');
            return;
        }

        try {
            $account = $this->loadAccount((int) $comment['account_id']);
            $prompt = $this->buildPrompt($comment);
            $generated = $this->gemini->generateReply($prompt);

            if (!str_starts_with(trim($generated), '@')) {
                $generated = '@' . $comment['commenter_handle'] . ' ' . ltrim($generated);
            }

            $postResult = $this->platformClient->postReply($comment['platform'], $account, $comment, $generated);

            $update = $this->pdo->prepare('UPDATE comments
                SET generated_reply = :reply,
                    posted_reply_id = :reply_id,
                    status = :status,
                    replied_at = CURRENT_TIMESTAMP,
                    error_message = NULL,
                    locked_at = NULL
                WHERE id = :id');
            $update->execute([
                ':reply' => $generated,
                ':reply_id' => $postResult['posted_reply_id'] ?? null,
                ':status' => $postResult['status'] ?? 'posted',
                ':id' => $comment['id'],
            ]);
        } catch (\Throwable $e) {
            $attempts = ((int) $comment['attempts']) + 1;
            $nextDelay = $baseRetry * (2 ** max(0, $attempts - 1));
            $nextAttemptAt = date('Y-m-d H:i:s', time() + $nextDelay);

            if ($attempts >= $maxAttempts) {
                $stmt = $this->pdo->prepare('UPDATE comments
                    SET status=:status,
                        attempts=:attempts,
                        error_message=:error,
                        next_attempt_at=NULL,
                        locked_at=NULL
                    WHERE id=:id');
                $stmt->execute([
                    ':status' => 'dead',
                    ':attempts' => $attempts,
                    ':error' => $e->getMessage(),
                    ':id' => $comment['id'],
                ]);
                $this->logger->error('Comment moved to dead-letter queue', ['comment_id' => $comment['id'], 'error' => $e->getMessage()]);
                return;
            }

            $stmt = $this->pdo->prepare('UPDATE comments
                SET status=:status,
                    attempts=:attempts,
                    error_message=:error,
                    next_attempt_at=:next_attempt_at,
                    locked_at=NULL
                WHERE id=:id');
            $stmt->execute([
                ':status' => 'retry',
                ':attempts' => $attempts,
                ':error' => $e->getMessage(),
                ':next_attempt_at' => $nextAttemptAt,
                ':id' => $comment['id'],
            ]);

            $this->logger->warning('Comment retry scheduled', [
                'comment_id' => $comment['id'],
                'attempts' => $attempts,
                'next_attempt_at' => $nextAttemptAt,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function updateStatus(int $commentId, string $status, string $error): void
    {
        $update = $this->pdo->prepare('UPDATE comments SET status = :status, error_message = :error, locked_at = NULL WHERE id = :id');
        $update->execute([
            ':status' => $status,
            ':error' => $error,
            ':id' => $commentId,
        ]);
    }

    private function loadAccount(int $accountId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM accounts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $accountId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new \RuntimeException('Аккаунт не найден для comment.account_id=' . $accountId);
        }

        return $row;
    }

    private function buildPrompt(array $comment): string
    {
        $systemPrompt = $this->settings->get('system_prompt', 'Ты мягкий эксперт по здоровью.');
        $ctaLink = $this->settings->get('cta_link', Env::get('DEFAULT_CTA_LINK', 'https://028.uno/diag'));

        return <<<PROMPT
{$systemPrompt}

Требования к ответу:
1) Начни строго с @{$comment['commenter_handle']}.
2) Тон: мягкий экспертный, с высокой эмпатией.
3) Учитывай контекст публикации.
4) Дай только общие подходы, без персонализированных назначений.
5) Объясни, что персонализированный маршрут определяется на детальной консультации-диагностике.
6) В конце сделай мягкий призыв записаться и добавь ссылку: {$ctaLink}

Контекст публикации:
{$comment['content_context']}

Комментарий пользователя:
{$comment['comment_text']}
PROMPT;
    }
}
