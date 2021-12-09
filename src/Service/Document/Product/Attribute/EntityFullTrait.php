<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Product\Attribute;

use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Service\Integration\Doc\DocProductHandlerInterface;

/**
 * @package Boxalino\DataIntegration\Service\Document\Order\Mode
 */
trait EntityFullTrait
{

    /**
     * Generic properties to be updated on the product entity for full or delta data syncs
     * For instant updates - a lesser structure is recommended, to ensure the speed of the process
     *
     * @return string[]
     */
    public function getFullFields() : array
    {
        return [
            /** process-required properties (for mapping) */
            "IF(product.parent_id IS NULL, '" . DocProductHandlerInterface::DOC_PRODUCT_LEVEL_GROUP . "', '" .  DocProductHandlerInterface::DOC_PRODUCT_LEVEL_SKU . "') AS " . DocSchemaInterface::DI_DOC_TYPE_FIELD,
            "LOWER(HEX(product.parent_id)) AS " . DocSchemaInterface::DI_PARENT_ID_FIELD,
            "LOWER(HEX(product.id)) AS " . DocSchemaInterface::DI_ID_FIELD,
            "IF(product.child_count = 0, 1, 0) AS " . DocSchemaInterface::DI_AS_VARIANT,

            /** entity-specific properties */
            "LOWER(HEX(product.id)) AS id",
            "product.product_number AS product_number",
            "product.created_at AS created_at",
            "product.updated_at AS updated_at",
            "IF(product.active IS NULL, parent.active, product.active) AS active",
            "IF(product.ean IS NULL, parent.ean, product.ean) AS ean",
            "IF(product.is_closeout IS NULL, IF(parent.is_closeout = '1', 0, 1), IF(product.is_closeout = '1', 0, 1)) AS " . DocSchemaInterface::FIELD_SHOW_OUT_OF_STOCK,
            "IF(DATEDIFF(NOW(), product.release_date) < {$this->getSystemConfiguration()->getMarkAsNew()}, 1, 0) AS " . DocSchemaInterface::FIELD_NEW,

            /** numeric properties */
            'IF(product.parent_id IS NULL, product.rating_average, parent.rating_average) AS rating_average',
            'product.restock_time AS restock_time',
            'product.sales AS sales',
            'product.child_count AS child_count',
            'IF(product.max_purchase IS NULL, parent.max_purchase, product.max_purchase) AS max_purchase',
            'IF(product.min_purchase IS NULL, parent.min_purchase, product.min_purchase) AS min_purchase',
            'tax.tax_rate AS tax_rate',
            'IF(product.parent_id IS NULL, product.purchase_unit, parent.purchase_unit) AS purchase_unit',
            'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(product.price, \'$.*.net\'),\'$[0]\'), 2), ",", "") AS purchase_price_net',
            'REPLACE(FORMAT(JSON_EXTRACT(JSON_EXTRACT(product.price, \'$.*.gross\'),\'$[0]\'), 2), ",", "") AS purchase_price_gross',
            "IF(product.parent_id IS NOT NULL, parent.child_count, IF(product.child_count = '0', 1, product.child_count)) AS variant_count",

            /** string properties */
            'product.auto_increment AS auto_increment',
            'IF(product.shipping_free IS NULL, parent.shipping_free, product.shipping_free) AS shipping_free',
            'IF(product.is_closeout IS NULL, parent.is_closeout, product.is_closeout) AS is_closeout',
            'IF(product.available IS NULL, parent.available, product.available) AS available',
            'IF(product.mark_as_topseller IS NULL, parent.mark_as_topseller, product.mark_as_topseller) AS mark_as_topseller',
            'IF(product.main_variant_id IS NULL, LOWER(HEX(parent.main_variant_id)), LOWER(HEX(product.main_variant_id))) AS main_variant_id',
            'IF(product.manufacturer_number IS NULL, LOWER(HEX(parent.manufacturer_number)), LOWER(HEX(product.manufacturer_number))) AS manufacturer_number',
            'IF(product.delivery_time_id IS NULL, LOWER(HEX(parent.delivery_time_id)), LOWER(HEX(product.delivery_time_id))) AS delivery_time_id',
            'LOWER(HEX(product.parent_id)) AS parent_id',
            'product.category_tree AS category_tree',
            'product.option_ids AS option_ids',
            'product.property_ids AS property_ids',
            'product.display_group AS display_group',

            /** datetime attributes */
            'IF(product.release_date IS NULL, parent.release_date, product.release_date) AS release_date'
        ];
    }

}
