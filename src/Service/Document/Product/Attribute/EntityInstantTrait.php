<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocProductHandlerInterface;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;

/**
 * During an instant data sync only the most relevant product information is integrated
 * This is done in order to ensure a fast processing (thereso the name - instant)
 * Based on your project specifications, check and ensure that only the critical content required for real-time sync is exported
 *
 * @package Boxalino\DataIntegration\Service\Document\Order\Mode
 */
trait EntityInstantTrait
{

    /**
     * Generic properties to be updated on the product entity
     *
     * @return string[]
     */
    public function getInstantFields() : array
    {
        return [
            /** process-required properties (for mapping) */
            "IF(product.parent_id IS NULL, '" . DocProductHandlerInterface::DOC_PRODUCT_LEVEL_GROUP . "', '" .  DocProductHandlerInterface::DOC_PRODUCT_LEVEL_SKU . "') AS " . DocSchemaInterface::DI_DOC_TYPE_FIELD,
            "LOWER(HEX(product.parent_id)) AS " . DocSchemaInterface::DI_PARENT_ID_FIELD,
            "LOWER(HEX(product.id)) AS " . DocSchemaInterface::DI_ID_FIELD,

            /** entity-specific properties */
            "LOWER(HEX(product.id)) AS id",
            "product.product_number AS product_number",
            "product.created_at AS created_at",
            "product.updated_at AS updated_at",
            "IF(product.active IS NULL, parent.active, product.active) AS active",
            "IF(product.ean IS NULL, parent.ean, product.ean) AS ean",
            "IF(product.is_closeout IS NULL, IF(parent.is_closeout = '1', 0, 1), IF(product.is_closeout = '1', 0, 1)) AS is_closeout",
            'IF(product.parent_id IS NULL, product.rating_average, parent.rating_average) AS rating_average',
            'IF(product.mark_as_topseller IS NULL, parent.mark_as_topseller, product.mark_as_topseller) AS mark_as_topseller'
        ];
    }

}
