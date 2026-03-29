<?php

declare(strict_types=1);

$tests = [
    __DIR__ . '/unit/AccessPolicyServiceTest.php',
    __DIR__ . '/unit/ScenarioServiceTest.php',
    __DIR__ . '/unit/AiScenarioGeneratorTest.php',
    __DIR__ . '/unit/PaymentServiceTest.php',
    __DIR__ . '/unit/JitSchedulerServiceTest.php',
    __DIR__ . '/unit/RuntimeEngineServiceTest.php',
    __DIR__ . '/unit/VideoProviderAdapterTest.php',
    __DIR__ . '/unit/ScenarioMacroCompilerTest.php',
    __DIR__ . '/unit/AiChatReplyServiceTest.php',
    __DIR__ . '/unit/ChatModerationServiceTest.php',
    __DIR__ . '/unit/SegmentServiceTest.php',
    __DIR__ . '/unit/EmailAutomationServiceTest.php',
    __DIR__ . '/unit/AceContentServiceTest.php',
    __DIR__ . '/unit/OfferServiceTest.php',
    __DIR__ . '/unit/EmbedSdkContractServiceTest.php',
    __DIR__ . '/unit/RoomReadinessServiceTest.php',
    __DIR__ . '/unit/ReleasePolicyServiceTest.php',
    __DIR__ . '/unit/GaStabilizationServiceTest.php',
];

$failed = [];
foreach ($tests as $testFile) {
    try {
        require $testFile;
        echo "PASS: " . basename($testFile) . PHP_EOL;
    } catch (Throwable $e) {
        $failed[] = ['file' => $testFile, 'error' => $e->getMessage()];
        echo "FAIL: " . basename($testFile) . ' -> ' . $e->getMessage() . PHP_EOL;
    }
}

if ($failed !== []) {
    exit(1);
}

echo "All tests passed: " . count($tests) . PHP_EOL;
