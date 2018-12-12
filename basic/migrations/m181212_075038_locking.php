<?php

use yii\db\Migration;
use yii\db\Schema;

class m181212_075038_locking extends Migration
{
    public function safeUp()
    {
        // 创建表
        $this->createTable('clocking', [
            'id' => Schema::TYPE_PK,
            'title' => Schema::TYPE_STRING . ' NOT NULL',
            'ver' => Schema::TYPE_BIGINT . ' DEFAULT 0',
        ]);
        // 添加一条数据
        $this->insert('clocking', [
            'title' => 'test',
        ]);
    }

    public function safeDown()
    {
        echo "m181212_075038_locking cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m181212_075038_locking cannot be reverted.\n";

        return false;
    }
    */
}
