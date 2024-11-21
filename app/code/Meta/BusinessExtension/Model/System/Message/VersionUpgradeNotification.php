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

namespace Meta\BusinessExtension\Model\System\Message;

use Magento\Framework\Escaper;
use Meta\BusinessExtension\Model\ResourceModel\MetaIssueNotification;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Notification\MessageInterface;
use Magento\Framework\UrlInterface;

class VersionUpgradeNotification implements MessageInterface
{
    /**
     * @var MetaIssueNotification
     */
    private $metaIssueNotification;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param MetaIssueNotification $metaIssueNotification
     * @param Escaper               $escaper
     * @param UrlInterface          $urlBuilder
     * @param RequestInterface      $request
     */
    public function __construct(
        MetaIssueNotification      $metaIssueNotification,
        Escaper                   $escaper,
        UrlInterface              $urlBuilder,
        RequestInterface          $request
    ) {
        $this->metaIssueNotification = $metaIssueNotification;
        $this->escaper = $escaper;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function getIdentity(): string
    {
        $notification = $this->metaIssueNotification->loadVersionNotification();
        return $notification['notification_id'] ?? '';
    }

    /**
     * @inheritDoc
     */
    public function isDisplayed(): bool
    {
        $notification = $this->metaIssueNotification->loadVersionNotification();
        return !empty($notification['message']);
    }

    /**
     * @inheritDoc
     */
    public function getText()
    {
        $notification = $this->metaIssueNotification->loadVersionNotification();
        $link_html_open = '<a href="https://fb.me/meta-extension">';
        $link_html_close = '</a>';
        if ($notification['message'] !== '') {
            return __(
                '%1 %2Open Adobe Commerce Marketplace%3',
                $this->escaper->escapeHtml($notification['message']),
                $link_html_open,
                $link_html_close
            );
        }
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getSeverity(): int
    {
        return  (int)$this->metaIssueNotification->loadVersionNotification()['severity'] ?? 0;
    }
}
