<?php

namespace tests;

use PHPUnit\Framework\TestCase;
use tests\tests\MysqlService;

class MysqlServiceTest extends TestCase
{

    public function testVarcharIsTrimmedToSize(): void
    {
        $service = new MysqlService();
        $content = ['name' => '123456789'];

        $service->processingQueryData('test', $content);

        $this->assertSame('12345', $content['name']);
    }

    public function testEnumInvalidValueBecomesNull(): void
    {
        $service = new MysqlService();
        $content = ['role' => 'hacker'];

        $service->processingQueryData('test', $content);

        $this->assertNull($content['role']);
    }

    public function testIntInvalidValueBecomesNull(): void
    {
        $service = new MysqlService();
        $content = ['age' => 'abc'];

        $service->processingQueryData('test', $content);

        $this->assertNull($content['age']);
    }

    public function testDecimalIsFormattedWithScale(): void
    {
        $service = new MysqlService();
        $content = ['price' => '12,345'];

        $service->processingQueryData('test', $content);

        $this->assertSame('12.35', $content['price']);
    }

    public function testUnknownColumnIsRemoved(): void
    {
        $service = new MysqlService();
        $content = [
            'name' => 'testing',
            'unknown' => 'xxx'
        ];

        $service->processingQueryData('test', $content);

        $this->assertArrayNotHasKey('unknown', $content);
    }

    public function testNullValueIsNotTouched(): void
    {
        $service = new MysqlService();
        $content = ['name' => null];

        $service->processingQueryData('test', $content);

        $this->assertNull($content['name']);
    }

}