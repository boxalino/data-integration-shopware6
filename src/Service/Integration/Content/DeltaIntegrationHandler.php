<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Content;

use Boxalino\DataIntegration\Service\Integration\Mode\Delta;
use Boxalino\DataIntegration\Service\Integration\Type\ContentTrait;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocContentHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\ContentDeltaIntegrationHandlerInterface;

/**
 * Class DeltaIntegrationHandler
 * Handles the product integration scenarios:
 * - delta
 *
 * Integrated as a service
 *
 * @package Boxalino\DataIntegrationDoc\Service\Integration\Order
 */
class DeltaIntegrationHandler extends Delta
    implements ContentDeltaIntegrationHandlerInterface
{
    use ContentTrait;


}
