<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Order;

use Boxalino\DataIntegration\Service\Integration\Mode\Full;
use Boxalino\DataIntegration\Service\Integration\Type\OrderTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderIntegrationHandlerInterface;

/**
 * Class FullIntegrationHandler
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
class FullIntegrationHandler extends Full implements OrderIntegrationHandlerInterface
{
    use OrderTrait;

}
