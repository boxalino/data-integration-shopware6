<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\ModeDisabledException;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @package Boxalino\DataIntegration\Service\Document\Order
 */
abstract class ModeIntegrator extends IntegrationSchemaPropertyHandler
    implements ModeIntegratorInterface
{
    use DocDeltaIntegrationTrait;
    use DocInstantIntegrationTrait;

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQuery(?string $propertyName = null) : QueryBuilder
    {
        /** for delta requests */
        if($this->filterByCriteria())
        {
            return $this->getQueryDelta($propertyName);
        }

        /** for instant updates */
        if($this->filterByIds())
        {
            if($this->hasModeEnabled())
            {
                return $this->getQueryInstant($propertyName);
            }

            throw new ModeDisabledException("Boxalino DI: instant mode not active. Skipping sync.");
        }

        return $this->_getQuery($propertyName);
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function addDeltaCondition(QueryBuilder $query) : QueryBuilder
    {
        return $this->addOrDeltaConditionByEntityNameDate(
            $query,
            ProductDefinition::ENTITY_NAME,
            "product",
            $this->getDeltaDateConditional(),
            $this->_getDeltaSyncCheckDate()
        );
    }

    /**
     * As a daily basis, the products can be exported for the past hour only
     * OR since last update
     *
     * @return string
     */
    public function getDeltaDateConditional() : string
    {
        $dateCriteria = $this->_getDeltaSyncCheckDate();
        return "STR_TO_DATE(product.updated_at, '%Y-%m-%d %H:%i') > '$dateCriteria' OR STR_TO_DATE(product.created_at, '%Y-%m-%d %H:%i') > '$dateCriteria'";
    }

    /**
     * @return string
     */
    protected function _getDeltaSyncCheckDate() : string
    {
        return $this->getSyncCheck() ?? date("Y-m-d H:i", strtotime("-1 hour"));
    }

    /**
     * @param QueryBuilder $query
     * @return QueryBuilder
     */
    protected function addInstantCondition(QueryBuilder $query) : QueryBuilder
    {
        $query->andWhere("product.id IN (:ids)")
            ->setParameter("ids", Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY);

        return $query;
    }

    /**
     * @return QueryBuilder
     */
    protected function _getProductQuery(?array $fields = []) : QueryBuilder
    {
        if(empty($fields))
        {
            $fields = ["product.id", "product.version_id", "product.updated_at", "product.created_at"];
        }
        $query = $this->connection->createQueryBuilder();
        $query->select($fields)
            ->from("product")
            ->leftJoin('product', 'product_visibility', 'pv', 'product.id = pv.product_id AND pv.sales_channel_id = :channelId')
            ->andWhere('product.version_id = :live')
            ->andWhere("JSON_SEARCH(product.category_tree, 'one', :channelRootCategoryId) IS NOT NULL OR pv.product_id IS NOT NULL")
            ->orderBy("product.product_number", "DESC")
            ->addOrderBy("product.created_at", "DESC")
            ->setFirstResult($this->getFirstResultByBatch())
            ->setMaxResults($this->getSystemConfiguration()->getBatchSize());

        /** for delta requests */
        if($this->filterByCriteria())
        {
            return $this->addDeltaCondition($query);
        }

        /** for instant syncs */
        if($this->hasModeEnabled() & $this->filterByIds())
        {
            return $this->addInstantCondition($query);
        }

        return $query;
    }

    /**
     * @return ConfigurationDataObject
     */
    public function getDiConfiguration() : ConfigurationDataObject
    {
        return $this->getSystemConfiguration();
    }

    /**
     * @return QueryBuilder
     * @throws \Doctrine\DBAL\DBALException
     */
    abstract function _getQuery(?string $propertyName = null) : QueryBuilder;

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    abstract function getQueryDelta(?string $propertyName = null) : QueryBuilder;

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    abstract function getQueryInstant(?string $propertyName = null) : QueryBuilder;


}
