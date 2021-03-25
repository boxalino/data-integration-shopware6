<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document;

use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;

/**
 * Interface IntegrationDocHandlerInterface
 * Required inheritance in order to set ids&configuration context
 * Can be joined with the IntegrationDocHandlerTrait
 *
 * @package Boxalino\DataIntegration\Service\Document
 */
interface IntegrationDocHandlerInterface
{

    /**
     * @return arrau
     */
    public function getIds() : array;

    /**
     * @param array $ids
     * @return IntegrationDocHandlerInterface
     */
    public function setIds(array $ids) : IntegrationDocHandlerInterface;

    /**
     * @return ConfigurationDataObject
     */
    public function getConfiguration() : ConfigurationDataObject;

    /**
     * @param ConfigurationDataObject $configuration
     * @return IntegrationDocHandlerInterface
     */
    public function setConfiguration(ConfigurationDataObject $configuration) : IntegrationDocHandlerInterface;

}
