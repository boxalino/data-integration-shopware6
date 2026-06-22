<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\UserSelection;

use Boxalino\DataIntegration\Service\Integration\Mode\Full;
use Boxalino\DataIntegration\Service\Integration\Type\UserSelectionTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\UserSelectionIntegrationHandlerInterface;

/**
 * Class FullIntegrationHandler
 *
 * @package Boxalino\DataIntegration\Service\Integration\UserSelection
 */
class FullIntegrationHandler extends Full
    implements UserSelectionIntegrationHandlerInterface
{
    use UserSelectionTrait;

}
