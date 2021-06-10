<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Core\DataIntegration;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

/**
 * Class DiFlaggedIdEntity
 * @package Boxalino\DataIntegration\Core\DataIntegration
 */
class DiFlaggedIdEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var int
     */
    protected $rowId;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $entityId;

    /**
     * @var string
     */
    protected $id;

    public function getUniqueIdentifier(): string
    {
        return $this->getEntityId();
    }

    public function getRowId(): int
    {
        return $this->rowId;
    }

    public function setRowId(int $rowId): void
    {
        $this->rowId = $rowId;
    }

    public function getEntityName(): string
    {
        return $this->entityName;
    }

    public function setEntityName(string $entityName): void
    {
        $this->entityName = $entityName;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function setEntityId(string $entityId): void
    {
        $this->entityId = $entityId;
    }


}
