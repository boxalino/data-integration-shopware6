<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\UserSelection;

use Boxalino\DataIntegration\Service\Integration\Mode\Delta;
use Boxalino\DataIntegration\Service\Integration\Type\UserSelectionTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\UserSelectionDeltaIntegrationHandlerInterface;

/**
 * Class DeltaIntegrationHandler
 *
 * @package Boxalino\DataIntegration\Service\Integration\UserSelection
 */
class DeltaIntegrationHandler extends Delta
    implements UserSelectionDeltaIntegrationHandlerInterface
{
    use UserSelectionTrait;

}
