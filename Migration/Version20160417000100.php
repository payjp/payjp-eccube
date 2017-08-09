<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20160417000100 extends AbstractMigration
{
    protected $entities = array(
        'Plugin\PayJp\Entity\PayJpConfig',
        'Plugin\PayJp\Entity\PayJpCustomer',
        'Plugin\PayJp\Entity\PayJpLog',
        'Plugin\PayJp\Entity\PayJpOrder',
        'Plugin\PayJp\Entity\PayJpToken',
    );

    public function up(Schema $schema)
    {
        $this->dropTables($schema);
        if ($this->connection->getDatabasePlatform()->getName() == "postgresql") {
            $this->dropSequences($schema);
        }
        $this->createTables($schema);
    }

    public function down(Schema $schema)
    {
        $this->dropTables($schema);
        if ($this->connection->getDatabasePlatform()->getName() == "postgresql") {
            $this->dropSequences($schema);
        }
    }

    private function dropTables(Schema $schema) {
        $tableNames = $schema->getTableNames();
        $dropTableNames = array(
            'plg_pay_jp_customer',
            'plg_pay_jp_token',
            'plg_pay_jp_config',
            'plg_pay_jp_log',
            'plg_pay_jp_order',
        );

        foreach ($dropTableNames as $drop) {
            if ($schema->hasTable($drop)) {
                $schema->dropTable($drop);
            }
        }
    }

    private function dropSequences(Schema $schema) {
        $targetSequences = array(
            'plg_pay_jp_customer_id_seq',
            'plg_pay_jp_token_id_seq',
            'plg_pay_jp_config_id_seq',
            'plg_pay_jp_log_id_seq',
            'plg_pay_jp_order_id_seq',
        );
        foreach ($targetSequences as $seq) {
            if ($schema->hasSequence($seq)) {
                $schema->dropSequence($seq);
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
