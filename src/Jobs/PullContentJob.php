<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Connectors\ConnectorFactory;
use App\Core\ContentRepository;
use App\Core\Logger;

final class PullContentJob
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly Logger $logger,
    ) {
    }

    public function handle(array $payload): void
    {
        $provider = (string) ($payload['provider'] ?? '');
        $sourceId = (int) ($payload['source_id'] ?? 0);

        if ($provider === '' || $sourceId <= 0) {
            throw new \RuntimeException('provider and source_id are required');
        }

        $connector = ConnectorFactory::make($provider);
        $items = $connector->fetch($payload);

        foreach ($items as $item) {
            $this->contentRepository->save($sourceId, $provider, $item);
        }

        $this->logger->info('Pull completed', [
            'provider' => $provider,
            'source_id' => $sourceId,
            'mode' => $payload['mode'] ?? '',
            'items' => count($items),
        ]);
    }
}
