<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Integrate\Type;

use Boxalino\DataIntegrationDoc\Service\Integration\IntegrationHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Util\ConfigurationDataObject;

/**
 * Class OrderTrait
 *
 * @package Boxalino\DataIntegration\Integrate\Type
 */
trait OrderTrait
{

    public function canRun(ConfigurationDataObject $configurationDataObject): bool
    {
        return $configurationDataObject->getAllowOrderSync();
    }


}
