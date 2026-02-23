<?php

namespace NimblePHP\Secure;

use krzysztofzylka\DatabaseManager\Exception\ConnectException;
use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use NimblePHP\Framework\Exception\DatabaseException;
use NimblePHP\Framework\Exception\NimbleException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;
use NimblePHP\Framework\Module\Interfaces\ModuleUpdateInterface;
use NimblePHP\Migrations\Exceptions\MigrationException;
use NimblePHP\Migrations\Migrations;
use NimblePHP\Secure\Services\ArrayService;
use NimblePHP\Secure\Services\MysqlService;
use NimblePHP\Secure\Services\RateLimiterService;
use Throwable;

class Module implements ModuleInterface, ModuleUpdateInterface
{

    public function getName(): string
    {
        return 'Secure for NimblePHP';
    }

    public function register(): void
    {
        Kernel::$serviceContainer->set('secure.mysql', new MysqlService());
        Kernel::$serviceContainer->set('secure.array', new ArrayService());
        Kernel::$serviceContainer->set('secure.rateLimiter', new RateLimiterService());
        Kernel::$middlewareManager->add(new SecureMiddleware(), 5000);
    }

    /**
     * @return void
     * @throws DatabaseException
     * @throws NimbleException
     * @throws MigrationException
     * @throws Throwable
     * @throws ConnectException
     * @throws DatabaseManagerException
     */
    public function onUpdate(): void
    {
        $migration = new Migrations(Kernel::$projectPath, __DIR__ . '/Migrations', 'module_secure');
        $migration->runMigrations();
    }

}