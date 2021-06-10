<?php declare(strict_types=1);
namespace Boxalino\DataIntegration\Core\DataIntegration;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\WriteProtected;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Version\Aggregate\VersionCommit\VersionCommitDefinition;
use Shopware\Core\Framework\Struct\Collection;

class DiFlaggedIdDefinition extends EntityDefinition
{

    public const ENTITY_NAME = 'boxalino_di_flagged_id';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function isVersionAware(): bool
    {
        return false;
    }

    public function getEntityClass(): string
    {
        return DiFlaggedIdEntity::class;
    }

    public function getDefaults(): array
    {
        $date = (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT);
        return ['createdAt' => $date, 'updatedAt' => $date];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey()),
            (new IntField('row_id', 'rowId'))->addFlags(new WriteProtected()),
            (new StringField('entity_name', 'entityName'))->addFlags(new Required()),
            (new StringField('entity_id', 'entityId'))->addFlags(new Required())
        ]);
    }
}
