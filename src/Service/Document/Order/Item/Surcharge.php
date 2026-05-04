<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Service\Document\Order\Item;

use Boxalino\DataIntegration\Service\Document\Order\Item;
use Boxalino\DataIntegrationDoc\Doc\DocSchemaInterface;
use Boxalino\DataIntegrationDoc\Doc\Schema\Order\Product as OrderProductSchema;

/**
 * Class Surcharge
 * Maps service-cost order line item types to the doc_order products array.
 *
 * Handles the following Shopware order_line_item types (only when total_price <> 0):
 *   - cart-surcharge      (e.g. Mindermengenzuschlag / small-order fee)
 *   - transport-insurance (percentage-based insurance fee)
 *   - payment-surcharge   (payment method fee)
 *   - splitorder          (split-order fee)
 *   - co2-compensation    (CO2 offset contribution)
 *   - shipping-method     (shipping cost as a line item)
 *
 * Each row is exported as a product entry with:
 *   - sku_id = identifier (the line item identifier)
 *   - numeric_attribute  doc_service_<type>_cost = total_price
 *   - string_attribute   payload = raw JSON payload
 *
 * @package Boxalino\DataIntegration\Service\Document\Order\Item
 */
class Surcharge extends Item
{
	
	/**
	 * @return array
	 */
	public function getValues(): array
	{
		$content = [];
		$iterator = $this->getQueryIterator($this->getStatementQuery());
		
		foreach ($iterator->getIterator() as $item)
		{
			if (!isset($content[$item[$this->getDiIdField()]]))
			{
				$content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_PRODUCTS] = [];
			}
			
			$schema = new OrderProductSchema($item);
			$schema->addStringAttribute(
				$this->getStringAttributeSchema([$item['payload']], 'payload')
			);
			
			$content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_PRODUCTS][] = $schema;
			try{
				if(!isset($content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_NUMERIC]))
				{
					$content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_NUMERIC] = [];
				}
				$content[$item[$this->getDiIdField()]][DocSchemaInterface::FIELD_NUMERIC][] =
					$this->getNumericAttributeSchema(
						[(float) $item['total_sales_price']],
						'doc_service_' . str_replace('-', '_', $item['type']) . '_cost'
					);
			} catch (\Throwable $exception) {}
		}
		
		return $content;
	}
	
	/**
	 * @return string
	 */
	public function getTypeFilter(): string
	{
		return "oli.type <> 'product' AND oli.total_price > 0";
	}
	
	/**
	 * SQL fields mapped to OrderProductSchema property names.
	 *
	 * - sku_id            : line item identifier (service items have no catalog product_id)
	 * - type              : raw Shopware type string (also used to build the cost attribute name)
	 * - label             : human-readable name of the service charge
	 * - quantity          : always 1 for surcharges
	 * - unit_sales_price  : the charge amount
	 * - total_sales_price : same as unit (used to derive the numeric attribute value in getValues)
	 * - unit_list_price   : mirrors unit_sales_price (no list price concept for service items)
	 * - total_list_price  : mirrors total_sales_price
	 * - payload           : raw JSON payload for string_attribute export
	 *
	 * @return string[]
	 */
	public function getFields(): array
	{
		return [
			"LOWER(HEX(oli.order_id)) AS " . $this->getDiIdField(),
			"oli.identifier AS sku_id",
			"oli.type AS type",
			"oli.label AS label",
			"oli.quantity AS quantity",
			"TRUNCATE(oli.unit_price, 2) AS unit_sales_price",
			"TRUNCATE(oli.total_price, 2) AS total_sales_price",
			"TRUNCATE(oli.unit_price, 2) AS unit_list_price",
			"TRUNCATE(oli.total_price, 2) AS total_list_price",
			"oli.payload AS payload",
		];
	}
	
	
}
