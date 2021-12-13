<?php

namespace Phoenix\TestingMigrations;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Database\Element\Column;
use Phoenix\Migration\AbstractMigration;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidFactoryInterface;

class AddPrimaryColumns extends AbstractMigration
{
    /** @var UuidFactoryInterface */
    private $uuidFactory;

    public function __construct(UuidFactoryInterface $uuidFactory, AdapterInterface $adapter)
    {
        parent::__construct($adapter);
        $this->uuidFactory = $uuidFactory;
    }

    public function up(): void
    {
        $this->table('table_3')
            ->addPrimaryColumns([new Column('id', 'integer', ['autoincrement' => true])])
            ->save();

        $this->table('table_4')
            ->addPrimaryColumns([new Column('identifier', 'uuid')], function (array $row) {
                $row['identifier'] = (string) $this->uuidFactory->uuid4();
                return $row;
            })
            ->save();

        $this->table('table_4')
            ->dropColumn('identifier')
            ->save();

        $this->table('table_4')
            ->addPrimaryColumns([new Column('identifier', 'uuid')], function (array $row) {
                $row['identifier'] = (string) $this->uuidFactory->uuid4();
                return $row;
            }, 100)
            ->save();
    }

    public function down(): void
    {
        $this->table('table_4')
            ->dropColumn('identifier')
            ->save();

        $this->table('table_3')
            ->dropColumn('id')
            ->save();
    }
}
