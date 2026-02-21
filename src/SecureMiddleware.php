<?php

namespace NimblePHP\Secure;

use NimblePHP\Framework\Abstracts\AbstractModel;
use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Interfaces\ModelInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Middleware\Interfaces\LogMiddlewareInterface;
use NimblePHP\Framework\Middleware\Interfaces\ModelMiddlewareInterface;
use NimblePHP\Secure\Services\ArrayService;
use NimblePHP\Secure\Services\MysqlService;

class SecureMiddleware implements ModelMiddlewareInterface, LogMiddlewareInterface
{

    /**
     * @param ModelInterface $model
     * @return void
     */
    public function afterConstructModel(ModelInterface $model): void
    {
    }

    /**
     * @param array $data
     * @return void
     * @throws HiddenException
     */
    public function processingModelData(array &$data): void
    {
        $processingData = $data['data'];
        /** @var AbstractModel $model */
        $model = $data['model'];
        /** @var MysqlService $secureService */
        $secureService = Kernel::$serviceContainer->get('secure.mysql');
        $secureService->processingQueryData(tableName: $model->useTable, content: $processingData);
        $data['data'] = $processingData;
    }

    /**
     * @param array $data
     * @return void
     */
    public function processingModelQuery(array &$data): void
    {
    }

    /**
     * @param string $message
     * @return void
     */
    public function beforeLog(string &$message): void
    {
        /** @var ArrayService $arrayService */
        $arrayService = Kernel::$serviceContainer->get('secure.array');

        $message = $arrayService->maskSensitiveInString($message);
    }

    /**
     * @param array $logContent
     * @return void
     */
    public function afterLog(array &$logContent): void
    {
        /** @var ArrayService $arrayService */
        $arrayService = Kernel::$serviceContainer->get('secure.array');

        $logContent['message'] = $arrayService->maskSensitiveInString($logContent['message'] ?? '');
        $logContent['content'] = $arrayService->maskSensitiveInArray($logContent['content'] ?? []);
        $logContent['get'] = $arrayService->maskSensitiveInArray($logContent['get'] ?? []);
    }

}