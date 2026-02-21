<?php

namespace NimblePHP\Secure;

use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Module\Interfaces\ModuleInterface;
use NimblePHP\Secure\Services\ArrayService;
use NimblePHP\Secure\Services\MysqlService;

class Module implements ModuleInterface
{

    public function getName(): string
    {
        return 'Secure for NimblePHP';
    }

    public function register(): void
    {
        Kernel::$serviceContainer->set('secure.mysql', new MysqlService());
        Kernel::$serviceContainer->set('secure.array', new ArrayService());
        Kernel::$middlewareManager->add(new SecureMiddleware(), 5000);
    }

}