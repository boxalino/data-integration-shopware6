<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document;

use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Doc\DocSchemaInterface;

/**
 * Trait IntegrationIntegrationDocHandlerTrait
 *
 * @package Boxalino\DataIntegration\Service\Document
 */
trait IntegrationDocHandlerTrait
{
    /**
     * @var array
     */
    protected $ids = [];

    /**
     * @var ConfigurationDataObject
     */
    protected $configuration;

    /**
     * @return array
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    /**
     * @param array $ids
     * @return IntegrationDocHandlerInterface
     */
    public function setIds(array $ids): IntegrationDocHandlerInterface
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @return ConfigurationDataObject
     */
    public function getConfiguration(): ConfigurationDataObject
    {
        return $this->configuration;
    }

    /**
     * @param ConfigurationDataObject $configuration
     * @return IntegrationDocHandlerInterface
     */
    public function setConfiguration(ConfigurationDataObject $configuration): IntegrationDocHandlerInterface
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * setIds and setConfiguration to all of the Attribute elements
     * for data access purposes
     */
    public function addPropertiesOnHandlers()
    {
        foreach($this->getHandlers() as $handler)
        {
            if($handler instanceof IntegrationDocHandlerInterface)
            {
                $handler->setConfiguration($this->getConfiguration())->setIds($this->getIds());
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getDiIdField() : string
    {
        return DocSchemaInterface::DI_ID_FIELD;
    }

}
