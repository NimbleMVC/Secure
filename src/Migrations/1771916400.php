<?php

use krzysztofzylka\DatabaseManager\CreateIndex;
use krzysztofzylka\DatabaseManager\CreateTable;
use krzysztofzylka\DatabaseManager\Table;
use NimblePHP\Migrations\AbstractMigration;

return new class extends AbstractMigration
{

    public function run(): void
    {
        $this->createRateLimitTable();
        $this->createRateLimitIndexes();
    }

    private function createRateLimitTable(): void
    {
        if ((new \krzysztofzylka\DatabaseManager\Table('module_secure_rate_limit'))->exists()) {
            return;
        }

        (new CreateTable('module_secure_rate_limit'))
            ->addIdColumn()
            ->addSimpleVarcharColumn('key_hash', 64, false)
            ->addSimpleIntColumn('attempts', false, true)
            ->addSimpleIntColumn('expires_at', false, true)
            ->addDateModifyColumn()
            ->addDateCreatedColumn()
            ->execute();
    }

    private function createRateLimitIndexes(): void
    {
        try {
            $table = new Table('module_secure_rate_limit');
            $table->getPdoInstance()->exec('CREATE UNIQUE INDEX module_secure_rate_limit_key_hash_unq ON module_secure_rate_limit (key_hash)');
        } catch (\Throwable) {
        }

        try {
            (new CreateIndex('module_secure_rate_limit'))
                ->setName('module_secure_rate_limit_expires_at_idx')
                ->addColumn('expires_at')
                ->execute();
        } catch (\Throwable) {
        }
    }

};
