<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Integrate\Type;

use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;

/**
 * Class UserTrait
 * @package Boxalino\DataIntegration\Service
 */
trait UserTrait
{

    public function canRun(ConfigurationDataObject $configurationDataObject): bool
    {
        return $configurationDataObject->getAllowUserSync();
    }

    public function getIntegrationHandler(): IntegrationHandlerInterface
    {
        return $this->integrationHandler;
    }

}
