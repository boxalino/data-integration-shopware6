<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Integrate\Type;

use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;

/**
 * Class ProductTrait
 * @package Boxalino\DataIntegration\Service
 */
trait ProductTrait
{

    public function canRun(ConfigurationDataObject $configurationDataObject): bool
    {
        return $configurationDataObject->getAllowProductSync() ?? false;
    }

    public function getIntegrationHandler(): IntegrationHandlerInterface
    {
        return $this->integrationHandler;
    }

}
