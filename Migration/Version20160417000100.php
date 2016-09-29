<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160417000100 extends AbstractMigration
{
    public function up(Schema $schema)
    {
        $this->dropTables($schema);
        $this->createTables($schema);
    }

    public function down(Schema $schema)
    {
        $this->dropTables($schema);
    }

    private function dropTables(Schema $schema) {
        $tableNames = $schema->getTableNames();
        $dropTableNames = array(
            $schema->getName() . '.plg_pay_jp_customer',
            $schema->getName() . '.plg_pay_jp_token',
            $schema->getName() . '.plg_pay_jp_config',
            $schema->getName() . '.plg_pay_jp_log',
            $schema->getName() . '.plg_pay_jp_order',
        );

        foreach ($dropTableNames as $drop) {
            if (array_search($drop, $tableNames)) {
                $schema->dropTable($drop);
            }
        }
    }

    private function createTables(Schema $schema)
    {
        $table = $schema->createTable("plg_pay_jp_customer");
        $table->addColumn('id', 'integer', array('notnull' => true));
        $table->addColumn('pay_jp_customer_id', 'string', array('notnull' => true));
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(array('id'));

        $table = $schema->createTable("plg_pay_jp_token");
        $table->addColumn('id', 'string', array('length' => 16, 'notnull' => true));
        $table->addColumn('pay_jp_token', 'string', array('length' => 40, 'notnull' => true));
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(array('id'));

        $table = $schema->createTable("plg_pay_jp_config");
        $table->addColumn('id', 'integer', array('notnull' => true));
        $table->addColumn('api_key_secret', 'string', array('length' => 40, 'notnull' => true));
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(array('id'));

        $table = $schema->createTable("plg_pay_jp_log");
        $table->addColumn('id', 'integer', array('notnull' => true, 'autoincrement' => true));
        $table->addColumn('message', 'string', array('length' => 1024));
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(array('id'));

        $table = $schema->createTable("plg_pay_jp_order");
        $table->addColumn('id', 'integer', array('notnull' => true, 'autoincrement' => true));
        $table->addColumn('order_id', 'integer', array('notnull' => false));
        $table->addColumn('pay_jp_customer_id', 'string', array('notnull' => false));
        $table->addColumn('pay_jp_token', 'string', array('notnull' => false));
        $table->addColumn('pay_jp_charge_id', 'string', array('notnull' => false));
        $table->addColumn('created_at', 'datetime');
        $table->setPrimaryKey(array('id'));
        $table->addUniqueIndex(array('order_id'));
    }
}