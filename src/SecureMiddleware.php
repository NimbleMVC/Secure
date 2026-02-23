<?php

namespace NimblePHP\Secure;

use NimblePHP\Framework\Abstracts\AbstractModel;
use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Interfaces\ModelInterface;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Middleware\Interfaces\ControllerMiddlewareInterface;
use NimblePHP\Framework\Middleware\Interfaces\LogMiddlewareInterface;
use NimblePHP\Framework\Middleware\Interfaces\ModelMiddlewareInterface;
use NimblePHP\Secure\Services\ArrayService;
use NimblePHP\Secure\Services\MysqlService;
use NimblePHP\Secure\Services\RateLimiterService;
use ReflectionMethod;

class SecureMiddleware implements ModelMiddlewareInterface, LogMiddlewareInterface, ControllerMiddlewareInterface
{

    /**
     * @param array $controllerContext
     * @return void
     * @throws HiddenException
     */
    public function beforeController(array &$controllerContext): void
    {
        /** @var RateLimiterService $rateLimiter */
        $rateLimiter = Kernel::$serviceContainer->get('secure.rateLimiter');

        if (!$rateLimiter->isEnabled()) {
            return;
        }

        $controllerName = (string)($controllerContext['controllerName'] ?? 'unknown');
        $methodName = (string)($controllerContext['methodName'] ?? 'unknown');
        $scope = strtolower($controllerName . '::' . $methodName);

        $result = $rateLimiter->hit(Kernel::$serviceContainer->get('kernel.request'), $scope);

        if (!$result['allowed']) {
            throw new HiddenException(sprintf(
                'Rate limit exceeded. Retry after %d seconds.',
                (int)$result['retryAfter']
            ));
        }
    }

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
     * @param string $controllerName
     * @param string $methodName
     * @param array $params
     * @return void
     */
    public function afterController(string $controllerName, string $methodName, array $params): void
    {
    }

    /**
     * @param ReflectionMethod $reflection
     * @param object $controller
     * @return void
     */
    public function afterAttributesController(ReflectionMethod $reflection, object $controller): void
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