<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order;

use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerInterface;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;
use Boxalino\DataIntegrationDoc\Doc\Schema\Order\Voucher as OrderVoucherSchema;

/**
 * Class Item
 * Access the order item
 *
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
abstract class Item extends ModeIntegrator
{

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select($this->getFields())
            ->from("order_line_item", "oli")
            ->leftJoin(
                "oli", '( ' . $this->getOrderJoinQuery()->__toString() . ') ', 'o',
                "oli.order_id = o.id AND oli.order_version_id = o.version_id AND o.sales_channel_id=:channelId"
            )
            ->andWhere("o.id IS NOT NULL")
            ->andWhere("oli.type='{$this->getType()}'")
            ->setParameter('channelId', Uuid::fromHexToBytes($this->getSystemConfiguration()->getSalesChannelId()), ParameterType::BINARY)
            ->setParameter('live', Uuid::fromHexToBytes(Defaults::LIVE_VERSION), ParameterType::BINARY);

        return $query;
    }

    /**
     * Item type (promotion, product, etc)
     *
     * @return string
     */
    abstract public function getType() : string;


    /**
     * Fields specific to each type data structure (as documented in Boxalino Data Structure)
     *
     * @return string[]
     */
    abstract public function getFields() : array;

    /**
     * @return QueryBuilder
     */
    protected function getOrderJoinQuery() : QueryBuilder
    {
        /** for delta requests */
        if($this->filterByCriteria())
        {
            return $this->getQueryDelta();
        }

        /** for instant updates */
        if($this->filterByIds())
        {
            return $this->getQueryInstant();
        }

        return $this->_getQuery();
    }

    /**
     * @return QueryBuilder
     */
    public function _getQuery() : QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $query->select(["o.id", "o.version_id", "o.sales_channel_id"])
            ->from("`order`", "o")
            ->andWhere("o.sales_channel_id=:channelId")
            ->andWhere("o.version_id = :live")
            ->addOrderBy("o.order_date_time", 'DESC')
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        return $query;
    }


}
