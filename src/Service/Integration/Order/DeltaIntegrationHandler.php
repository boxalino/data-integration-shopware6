<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Order;

use Boxalino\DataIntegration\Service\Integration\Mode\Delta;
use Boxalino\DataIntegration\Service\Integration\Type\OrderTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderDeltaIntegrationHandlerInterface;

/**
 * Class DeltaIntegrationHandler
 * Handles the product integration scenarios:
 * - delta
 *
 * Integrated as a service
 *
 * @package Boxalino\DataIntegrationDoc\Service\Integration\Order
 */
class DeltaIntegrationHandler extends Delta implements OrderDeltaIntegrationHandlerInterface
{
    use OrderTrait;

}
