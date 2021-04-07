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
    protected $systemConfiguration;

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
    public function getSystemConfiguration(): ConfigurationDataObject
    {
        return $this->systemConfiguration;
    }

    /**
     * @param ConfigurationDataObject $configuration
     * @return IntegrationDocHandlerInterface
     */
    public function setSystemConfiguration(ConfigurationDataObject $configuration): IntegrationDocHandlerInterface
    {
        $this->systemConfiguration = $configuration;
        return $this;
    }

    /**
     * setIds and setSystemConfiguration to all of the Attribute elements
     * for data access purposes
     */
    public function addSystemConfigurationOnHandlers()
    {
        foreach($this->getHandlers() as $handler)
        {
            if($handler instanceof IntegrationDocHandlerInterface)
            {
                $handler->setSystemConfiguration($this->getSystemConfiguration())->setIds($this->getIds());
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
