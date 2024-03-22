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

namespace Meta\Sales\Model\Api;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Meta\BusinessExtension\Model\Api\CustomApiKey\Authenticator;

class DiscountDeleteApi implements \Meta\Sales\Api\DiscountDeleteApiInterface
{
    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var Authenticator
     */
    private Authenticator $authenticator;

    /**
     * DiscountDeleteApi constructor.
     *
     * @param ResourceConnection $resourceConnection
     * @param Authenticator $authenticator
     */
    public function __construct(ResourceConnection $resourceConnection, Authenticator $authenticator)
    {
        $this->resourceConnection = $resourceConnection;
        $this->authenticator = $authenticator;
    }

    /**
     * Delete expired/used coupon codes
     *
     * @param string $externalBusinessId
     * @param string[] $couponCodes
     * @return string[]
     * @throws LocalizedException
     */
    public function deleteDiscount(string $externalBusinessId, array $couponCodes): array
    {
        $this->authenticator->authenticateRequest();

        try {
            $connection = $this->resourceConnection->getConnection();
            $couponTable = $this->resourceConnection->getTableName('salesrule_coupon');

            $deletedCouponCodes = [];

            if (!empty($couponCodes)) {
                // Code is indexed. OTHER FIELDS ARE NOT. Use of resourceconnection should
                // be restricted to ONLY indexed columns
                $predicate = $connection->quoteInto('code IN (?)', $couponCodes);
                $deletedCouponCodes = $connection->fetchCol(
                    $connection->select()->from($couponTable, 'code')->where($predicate)
                );
                $connection->delete($couponTable, $predicate);
            }

            return $deletedCouponCodes;
        } catch (\Exception $e) {
            throw new LocalizedException(__('Failed to delete expired/used coupon codes: %1', $e->getMessage()));
        }
    }
}
