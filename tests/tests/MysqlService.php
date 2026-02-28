<?php

namespace tests\tests;

class MysqlService extends \NimblePHP\Secure\Services\MysqlService
{
    protected function getTableColumns(string $tableName): array
    {
        return [
            'name' => 'varchar(5)',
            'age' => 'int(11)',
            'role' => "enum('admin','user')",
            'price' => 'decimal(5,2)',
            'test' => 'varchar(5)'
        ];
    }
}