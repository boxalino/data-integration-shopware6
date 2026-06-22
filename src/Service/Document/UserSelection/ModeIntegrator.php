<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\UserSelection;

use Boxalino\DataIntegration\Service\Document\IntegrationSchemaPropertyHandler;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationTrait;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * @package Boxalino\DataIntegration\Service\Document\UserSelection
 */
abstract class ModeIntegrator extends IntegrationSchemaPropertyHandler
    implements ModeIntegratorInterface
{
    use DocDeltaIntegrationTrait;

    public function getQuery(?string $propertyName = null): QueryBuilder
    {
        if ($this->filterByCriteria()) {
            return $this->getQueryDelta();
        }

        return $this->_getQuery();
    }

    public function getQueryDelta(): QueryBuilder
    {
        $query = $this->_getQuery();
        $query->andWhere($this->getDeltaDateConditional());

        return $query;
    }

    public function getDeltaDateConditional(): string
    {
        $dateCriteria = $this->_getDeltaSyncCheckDate();
        return "w.created_at >= '$dateCriteria' OR w.updated_at >= '$dateCriteria'";
    }

    protected function _getDeltaSyncCheckDate(): string
    {
        return $this->getSyncCheck() ?? date("Y-m-d H:i", strtotime("-1 week"));
    }

    public function getDiConfiguration(): ConfigurationDataObject
    {
        return $this->getSystemConfiguration();
    }

    abstract function _getQuery(): QueryBuilder;

}
