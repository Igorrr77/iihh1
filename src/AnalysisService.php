<?php

declare(strict_types=1);

namespace App;

final class AnalysisService
{
    public function __construct(private Database $db, private GeminiClient $gemini)
    {
    }

    public function handleTranscriptChunk(int $tenantId, int $conversationId, string $chunk, string $languageHint = 'auto'): array
    {
        $productContext = (string) ($this->db->query(
            'SELECT product_text FROM tenant_product_context WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 1',
            ['tenant_id' => $tenantId]
        )->fetch()['product_text'] ?? '');

        $this->db->query(
            'INSERT INTO transcripts (tenant_id, conversation_id, role, chunk_text, created_at) VALUES (:tenant_id, :conversation_id, :role, :chunk_text, UTC_TIMESTAMP())',
            [
                'tenant_id' => $tenantId,
                'conversation_id' => $conversationId,
                'role' => 'client',
                'chunk_text' => $chunk,
            ]
        );

        $analysis = $this->gemini->analyzeSpeechChunk($chunk, $productContext, $languageHint);

        $this->db->query(
            'INSERT INTO analyses (tenant_id, conversation_id, sentiment, confidence_level, objections_json, pain_points_json, lead_score, churn_risk, personality_json, coaching_json, patterns_json, created_at)
             VALUES (:tenant_id, :conversation_id, :sentiment, :confidence_level, :objections_json, :pain_points_json, :lead_score, :churn_risk, :personality_json, :coaching_json, :patterns_json, UTC_TIMESTAMP())',
            [
                'tenant_id' => $tenantId,
                'conversation_id' => $conversationId,
                'sentiment' => (string) ($analysis['sentiment'] ?? 'neutral'),
                'confidence_level' => (int) ($analysis['confidence_level'] ?? 50),
                'objections_json' => json_encode($analysis['objections'] ?? []),
                'pain_points_json' => json_encode($analysis['pain_points'] ?? []),
                'lead_score' => (int) ($analysis['lead_score'] ?? 50),
                'churn_risk' => (int) ($analysis['churn_risk'] ?? 50),
                'personality_json' => json_encode($analysis['personality_profile'] ?? []),
                'coaching_json' => json_encode($analysis['live_coaching'] ?? []),
                'patterns_json' => json_encode($analysis['response_patterns'] ?? []),
            ]
        );

        return $analysis;
    }

    public function latestFeed(int $tenantId, int $limit = 30): array
    {
        return $this->db->query(
            'SELECT a.*, c.client_handle FROM analyses a JOIN conversations c ON c.id = a.conversation_id WHERE a.tenant_id = :tenant_id ORDER BY a.id DESC LIMIT :limit',
            ['tenant_id' => $tenantId, 'limit' => $limit]
        )->fetchAll();
    }
}
