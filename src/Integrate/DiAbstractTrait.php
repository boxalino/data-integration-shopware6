<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Integrate;

use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;

/**
 * Trait DiAbstractTrait
 * Abstract behavior required for making the DI triggers reusable (scheduled tasks, console commands, subscribers)
 *
 * @package Boxalino\DataIntegration\Console
 */
trait DiAbstractTrait
{

    /**
     * Logic for accessing the configurations
     * @return array
     */
    abstract function getConfigurations() : array;

    /**
     * @param ConfigurationDataObject $configurationDataObject
     * @return bool
     */
    abstract function canRun(ConfigurationDataObject $configurationDataObject) : bool;

    /**
     * @return IntegrationHandlerInterface
     */
    abstract function getIntegrationHandler() : IntegrationHandlerInterface;


}
