<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\User;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @package Boxalino\DataIntegration\Service\Document\User
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
     * If the logic for delta needs to be updated - rewrite this function
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryDelta() : QueryBuilder
    {
        return $this->addOrDeltaConditionByEntityNameDate(
            $this->_getQuery(),
            CustomerDefinition::ENTITY_NAME,
            "customer",
            $this->getDeltaDateConditional(),
            $this->_getDeltaSyncCheckDate()
        );
    }

    /**
     * As a daily basis, the customers can be exported for the past week only
     * OR since last update
     *
     * @return string
     */
    public function getDeltaDateConditional() : string
    {
        $dateCriteria = $this->_getDeltaSyncCheckDate();
        return "customer.created_at >= '$dateCriteria' OR customer.updated_at >= '$dateCriteria'";
    }

    /**
     * @return string
     */
    protected function _getDeltaSyncCheckDate() : string
    {
        return $this->getSyncCheck() ?? date("Y-m-d H:i", strtotime("-1 week"));
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryInstant() : QueryBuilder
    {
        $query = $this->_getQuery();
        $query->andWhere("customer.id IN (:ids)")
            ->setParameter("ids", Uuid::fromHexToBytesList($this->getIds()), Connection::PARAM_STR_ARRAY);

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
    abstract function _getQuery() : QueryBuilder;


}
