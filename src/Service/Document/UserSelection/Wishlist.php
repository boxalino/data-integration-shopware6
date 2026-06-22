<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\UserSelection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * Class Wishlist
 *
 * Exports customer wishlists as doc_user_selection records.
 * Queries the customer_wishlist and customer_wishlist_product tables.
 *
 * @package Boxalino\DataIntegration\Service\Document\UserSelection
 */
class Wishlist extends ModeIntegrator
{

    public function getValues(): array
    {
        $content = [];
        $wishlistIds = [];

        $iterator = $this->getQueryIterator($this->getStatementQuery());
        foreach ($iterator->getIterator() as $item) 
        {
            $diId = $item[$this->getDiIdField()];
            $content[$diId] = [
                'id'           => $item['id'],
                'persona_id'   => $item['persona_id'],
                'persona_type' => 'customer',
                'type'         => $item['type'],
                'creation'     => $item['creation'],
                'last_update'  => $item['last_update'],
                'products'     => [],
            ];
            $wishlistIds[] = Uuid::fromHexToBytes($diId);
        }

        if (empty($content)) 
        {
            return $content;
        }

        foreach ($this->getProducts($wishlistIds) as $row) 
        {
            $content[$row['customer_wishlist_id']]['products'][] = [
                'sku'  => $row['sku'],
                'type' => $row['type'],
            ];
        }

        return $content;
    }

    public function _getQuery(): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            'LOWER(HEX(w.id)) AS ' . $this->getDiIdField(),
            'LOWER(HEX(w.id)) AS id',
            'LOWER(HEX(w.customer_id)) AS persona_id',
            "'wishlist' AS type",
            'w.created_at AS creation',
            'IF(w.updated_at IS NULL, w.created_at, w.updated_at) AS last_update',
        ])
            ->from('customer_wishlist', 'w')
            ->andWhere('w.sales_channel_id = :channelId')
            ->addOrderBy('w.created_at', 'DESC')
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }

    protected function getProducts(array $wishlistBinaryIds): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select([
            'LOWER(HEX(wp.customer_wishlist_id)) AS customer_wishlist_id',
            'IFNULL(parent.product_number, p.product_number) AS sku',
            "'product' AS type",
        ])
            ->from('customer_wishlist_product', 'wp')
            ->leftJoin('wp', 'product', 'p', 'p.id = wp.product_id AND p.version_id = wp.product_version_id')
            ->leftJoin('p', 'product', 'parent', 'p.parent_id = parent.id AND p.parent_version_id = parent.version_id')
            ->andWhere('wp.customer_wishlist_id IN (:wishlistIds)')
            ->setParameter('wishlistIds', $wishlistBinaryIds, Connection::PARAM_STR_ARRAY);

        return $query->execute()->fetchAll();
    }

}
