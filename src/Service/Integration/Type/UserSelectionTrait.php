<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Integration\Type;

use Boxalino\DataIntegrationDoc\Service\GcpRequestInterface;

/**
 * Class UserSelectionTrait
 *
 * @package Boxalino\DataIntegrationDoc\Service
 */
trait UserSelectionTrait
{

    public function getIntegrationType(): string
    {
        return GcpRequestInterface::GCP_TYPE_USER_SELECTION;
    }

    public function getEntityName(): string
    {
        return 'customer_wishlist';
    }

    public function clearDiFlaggedIds(): void
    {
        // Wishlist changes are not tracked via flagged IDs table
    }

}
