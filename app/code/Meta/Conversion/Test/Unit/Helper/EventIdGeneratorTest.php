<?php
/**
 * Copyright (c) Meta Platforms, Inc. and affiliates.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Meta\Conversion\Test\Unit\Helper;

use Meta\Conversion\Helper\EventIdGenerator;
use PHPUnit\Framework\TestCase;

class EventIdGeneratorTest extends TestCase
{
    /**
     * Used to reset or change values after running a test
     *
     * @return void
     */
    public function tearDown(): void
    {
    }

    /**
     * Used to set the values before running a test
     *
     * @return void
     */
    public function setUp(): void
    {
    }

    /**
     * Test generation of unique event ids
     *
     * @return void
     */
    public function testGeneratesUniqueValues()
    {
        $eventIds = [];
        for ($i = 0; $i < 100; $i++) {
            $eventIds[] = EventIdGenerator::guidv4();
        }
        $eventIds = array_unique($eventIds);
        $this->assertEquals(100, count($eventIds));
    }
}
