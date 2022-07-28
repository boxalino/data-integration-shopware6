<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product;

use Boxalino\DataIntegration\Service\Document\IntegrationDocHandlerInterface;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaPropertyHandlerInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocDeltaIntegrationInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\Mode\DocInstantIntegrationInterface;

/**
 * Interface used to declare the behavior for certain integration modes
 * A default is provided with the plugin and it is easy customizable by the integrator in the integration layer
 *
 * @package Boxalino\DataIntegration\Service\Document\Product
 */
interface ModeIntegratorInterface
    extends DocInstantIntegrationInterface, DocDeltaIntegrationInterface
    , DocSchemaPropertyHandlerInterface, IntegrationDocHandlerInterface
{


}
