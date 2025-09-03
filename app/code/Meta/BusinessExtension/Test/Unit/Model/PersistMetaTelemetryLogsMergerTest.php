<?php

declare(strict_types=1);

namespace Meta\BusinessExtension\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Meta\BusinessExtension\Model\PersistMetaTelemetryLogsMerger;

class PersistMetaTelemetryLogsMergerTest extends TestCase
{
    /**
     * Class setUp function
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->persistMetaTelemetryLogsHandlerMockObj = new PersistMetaTelemetryLogsMerger();
    }

    /**
     * Test merge function
     *
     * @return void
     */
    public function testMerge(): void
    {
        $messages = [
            'persist.meta.telemetry.logs' => [
                '{"log_type": "system", "message": "System started", "timestamp": 1678886400, "extra_data": {"version": "1.0.0"}}',
            ],
        ];

        $expectedMergedLogs = '[{"message":"System started","timestamp":1678886400,"extra_data":{"version":"1.0.0"}}]';
        $expectedResult = [
            'persist.meta.telemetry.logs' => [$expectedMergedLogs],
        ];

        $actualResult = $this->persistMetaTelemetryLogsHandlerMockObj->merge($messages);
        $this->assertEquals($expectedResult, $actualResult);
    }

    /**
     * Test merge function
     *
     * @return void
     */
    public function testMergeWithKeyIsNotPresent(): void
    {
        $messages = [
            '' => [
                '{"log_type": "system", "message": "System started", "timestamp": 1678886400, "extra_data": {"version": "1.0.0"}}',
            ],
        ];

        $actualResult = $this->persistMetaTelemetryLogsHandlerMockObj->merge($messages);
        $this->assertEquals($messages, $actualResult);
    }
}
