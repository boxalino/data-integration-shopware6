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
     * @return ConfigurationDataObject
     */
    public function getSystemConfiguration() : ConfigurationDataObject;

    /**
     * @param ConfigurationDataObject $configuration
     * @return IntegrationDocHandlerInterface
     */
    public function setSystemConfiguration(ConfigurationDataObject $configuration) : IntegrationDocHandlerInterface;

    /**
     * @param string $handlerIntegrateTime
     */
    public function setHandlerIntegrateTime(string $handlerIntegrateTime) : void;

    /**
     * @return string
     */
    public function getHandlerIntegrateTime(): string;


}
