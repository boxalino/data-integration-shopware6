<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Order;

use Boxalino\DataIntegration\Service\Integration\Mode\Instant;
use Boxalino\DataIntegration\Service\Integration\Type\OrderTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\OrderInstantIntegrationHandlerInterface;

/**
 * Class InstantIntegrationHandler
 * Handles the product integration scenarios:
 * - instant
 *
 * Integrated as a service
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
class InstantIntegrationHandler extends Instant implements OrderInstantIntegrationHandlerInterface
{
    use OrderTrait;

}
