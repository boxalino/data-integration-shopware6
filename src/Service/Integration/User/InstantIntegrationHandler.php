<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\User;

use Boxalino\DataIntegration\Service\Integration\Mode\Instant;
use Boxalino\DataIntegration\Service\Integration\Type\UserTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\UserInstantIntegrationHandlerInterface;

/**
 * Class InstantIntegrationHandler
 * Handles the product integration scenarios:
 * - instant
 *
 * Integrated as a service
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
class InstantIntegrationHandler extends Instant implements UserInstantIntegrationHandlerInterface
{
    use UserTrait;

}
