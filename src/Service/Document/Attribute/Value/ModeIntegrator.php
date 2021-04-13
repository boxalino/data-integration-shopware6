<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Attribute\Value;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\ErrorHandler\ModeDisabledException;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @package Boxalino\DataIntegration\Service\Attribute\Value
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
        /** for delta requests & full - same content is exported */
        $query = $this->_getQuery();

        /** for instant updates */
        if($this->filterByIds())
        {
            if($this->hasModeEnabled())
            {
                return $this->getQueryInstant($propertyName);
            }

            throw new ModeDisabledException("Boxalino DI: instant mode not active. Skipping sync.");
        }

        return $query;
    }

    /**
     * To be rewritten for any handler that is allowed to export content for the instant sync mode
     *
     * @param string | null $propertyName
     * @return QueryBuilder
     */
    public function getQueryInstant(?string $propertyName = null) : QueryBuilder
    {
        return $this->_getQuery($propertyName);
    }

    /**
     * @return QueryBuilder
     * @throws \Doctrine\DBAL\DBALException
     */
    abstract function _getQuery() : QueryBuilder;

    /**
     * @return ConfigurationDataObject
     */
    public function getDiConfiguration() : ConfigurationDataObject
    {
        return $this->getSystemConfiguration();
    }


}
