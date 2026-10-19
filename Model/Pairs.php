<?php
/**
 * Copyright © 2026 Roman Pasichnyk
 * SPDX-License-Identifier: OSL-3.0 OR AFL-3.0
 */

declare(strict_types=1);

namespace Zero\BoughtTogether\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface as EventManager;

/**
 * The zero_bought_together table: for a product, the products that orders
 * held with it, by how many orders. Rebuilt from sales_order_item by the
 * nightly cron or `bin/magento zero:bought-together:build`.
 */
class Pairs
{
    public const CACHE_TAG = 'zero_bought_together';

    /**
     * The cache tag of one product's page: only the pages whose pairs changed
     * are cleaned by a rebuild, not every product page at once.
     */
    public static function tagOf(int $productId): string
    {
        return self::CACHE_TAG . '_' . $productId;
    }

    /** Pairs kept per product: more than any "Products Shown" plus a margin for ones no longer sold. */
    private const KEEP = 12;

    private const TABLE = 'zero_bought_together';

    public function __construct(
        private readonly ResourceConnection $resource,
        private readonly EventManager $eventManager
    ) {
    }

    /**
     * @return int[] product ids bought with the product, most often first
     */
    public function with(int $productId, int $limit): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), ['with_product_id'])
            ->where('product_id = ?', $productId)
            ->order(['orders DESC', 'with_product_id'])
            ->limit($limit);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Rebuilds the table from the top-level lines of the orders of the last
     * $days days (canceled ones aside): every pair of products in one order,
     * kept from $minOrders orders on, the KEEP most frequent per product.
     * The pages of the products whose pairs changed are cleaned after (a
     * rebuild that changes little cleans little).
     *
     * @return int rows written
     */
    public function build(int $days, int $minOrders): int
    {
        $connection = $this->resource->getConnection();
        $items = $this->resource->getTableName('sales_order_item');
        $since = (new \DateTimeImmutable("-{$days} days", new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
        $select = $connection->select()
            ->from(['a' => $items], ['product_id' => 'a.product_id', 'with_product_id' => 'b.product_id', 'orders' => new \Zend_Db_Expr('COUNT(DISTINCT a.order_id)')])
            ->join(['b' => $items], 'b.order_id = a.order_id AND b.product_id <> a.product_id', [])
            ->join(['o' => $this->resource->getTableName('sales_order')], 'o.entity_id = a.order_id', [])
            ->where('a.parent_item_id IS NULL')
            ->where('b.parent_item_id IS NULL')
            ->where('a.product_id IS NOT NULL')
            ->where('b.product_id IS NOT NULL')
            ->where('o.created_at >= ?', $since)
            ->where('o.state <> ?', 'canceled')
            ->group(['a.product_id', 'b.product_id'])
            ->having('COUNT(DISTINCT a.order_id) >= ?', $minOrders)
            ->order(['a.product_id', 'orders DESC', 'b.product_id']);

        $table = $this->resource->getTableName(self::TABLE);
        $before = $this->fingerprints();
        $rows = [];
        $written = 0;
        $current = null;
        $kept = 0;
        $connection->beginTransaction();
        try {
            $connection->delete($table);
            $query = $connection->query($select);
            while ($row = $query->fetch()) {
                if ($row['product_id'] !== $current) {
                    $current = $row['product_id'];
                    $kept = 0;
                }
                if (++$kept > self::KEEP) {
                    continue;
                }
                $rows[] = ['product_id' => (int) $row['product_id'], 'with_product_id' => (int) $row['with_product_id'], 'orders' => (int) $row['orders']];
                if (count($rows) === 500) {
                    $written += $connection->insertMultiple($table, $rows);
                    $rows = [];
                }
            }
            if ($rows) {
                $written += $connection->insertMultiple($table, $rows);
            }
            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }
        $after = $this->fingerprints();
        $changed = [];
        foreach (array_keys($before + $after) as $productId) {
            if (($before[$productId] ?? '') !== ($after[$productId] ?? '')) {
                $changed[] = self::tagOf((int) $productId);
            }
        }
        if ($changed) {
            $this->eventManager->dispatch('clean_cache_by_tags', ['object' => new class ($changed) implements \Magento\Framework\DataObject\IdentityInterface {
                public function __construct(private readonly array $tags)
                {
                }

                public function getIdentities(): array
                {
                    return $this->tags;
                }
            }]);
        }

        return $written;
    }

    /**
     * What each product's pairs are, in order, as one string: two builds of the
     * table are compared by it.
     *
     * @return array<int, string> product id => "with ids:orders,…"
     */
    private function fingerprints(): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()
            ->from($this->resource->getTableName(self::TABLE), [
                'product_id',
                'pairs' => new \Zend_Db_Expr("GROUP_CONCAT(CONCAT(with_product_id, ':', orders) ORDER BY orders DESC, with_product_id SEPARATOR ',')"),
            ])
            ->group('product_id');

        return array_map('strval', $connection->fetchPairs($select));
    }
}
