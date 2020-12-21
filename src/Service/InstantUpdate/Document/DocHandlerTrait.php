<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\InstantUpdate\Document;

use Boxalino\DataIntegration\Service\InstantUpdate\Util\InstantUpdateConfigurationElement;
use Boxalino\DataIntegrationDoc\Service\Doc\Schema\DocSchemaDefinitionInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\DocHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\DocProduct\AttributeHandlerInterface;

/**
 * Trait DocHandlerTrait
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
trait DocHandlerTrait
{
    /**
     * @var array
     */
    protected $ids;

    /**
     * @var InstantUpdateConfigurationElement
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
     * @return DocPropertiesHandlerInterface
     */
    public function setIds(array $ids): DocPropertiesHandlerInterface
    {
        $this->ids = $ids;
        return $this;
    }

    /**
     * @return InstantUpdateConfigurationElement
     */
    public function getConfiguration(): InstantUpdateConfigurationElement
    {
        return $this->configuration;
    }

    /**
     * @param InstantUpdateConfigurationElement $configuration
     * @return DocPropertiesHandlerInterface
     */
    public function setConfiguration(InstantUpdateConfigurationElement $configuration): DocPropertiesHandlerInterface
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
            if($handler instanceof DocPropertiesHandlerInterface)
            {
                $handler->setConfiguration($this->getConfiguration())->setIds($this->getIds());
            }
        }

        return $this;
    }

}
