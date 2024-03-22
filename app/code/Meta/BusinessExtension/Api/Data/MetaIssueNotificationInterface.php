<?php

declare(strict_types=1);

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

namespace Meta\BusinessExtension\Api\Data;

use Meta\BusinessExtension\Model\MetaIssueNotification;

interface MetaIssueNotificationInterface
{
    /**
     * Severity Getter
     *
     * @return int
     */
    public function getSeverity(): int;

    /**
     * Severity Setter
     *
     * @param int $val
     * @return $this|MetaIssueNotification
     */
    public function setSeverity(int $val);

    /**
     * Severity Getter
     *
     * @return ?string
     */
    public function getMessage(): ?string;

    /**
     * Severity Setter
     *
     * @param string|null $val
     * @return $this|MetaIssueNotification
     */
    public function setMessage(?string $val);

    /**
     * Hash key Getter
     *
     * @return ?string
     */
    public function getNotificationId(): ?string;

    /**
     * Hash key Setter
     *
     * @param string $val
     * @return $this|MetaIssueNotification
     */
    public function setNotificationId(string $val);
}
