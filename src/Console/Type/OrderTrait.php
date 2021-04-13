<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Console\Type;

use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;

/**
 * Class OrderTrait
 *
 * @package Boxalino\DataIntegration\Console\Type
 */
trait OrderTrait
{

    public function canRun(ConfigurationDataObject $configurationDataObject): bool
    {
        return $configurationDataObject->getAllowOrderSync();
    }

    public function getIntegrationHandler(): IntegrationHandlerInterface
    {
        return $this->integrationHandler;
    }

}
