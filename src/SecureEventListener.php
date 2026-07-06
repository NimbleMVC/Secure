<?php

namespace NimblePHP\Secure;

use NimblePHP\Framework\Abstracts\AbstractModel;
use NimblePHP\Framework\Event\Framework\AfterLogEvent;
use NimblePHP\Framework\Event\Framework\BeforeControllerEvent;
use NimblePHP\Framework\Event\Framework\BeforeLogEvent;
use NimblePHP\Framework\Event\Framework\ProcessingModelDataEvent;
use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Secure\Services\ArrayService;
use NimblePHP\Secure\Services\MysqlService;
use NimblePHP\Secure\Services\RateLimiterService;

/**
 * Framework event listeners for the Secure module.
 *
 * Replaces the former SecureMiddleware: every hook is now a listener bound to a
 * typed framework event and mutates the event payload in place.
 */
class SecureEventListener
{

    /**
     * Enforce the rate limiter before a controller action is dispatched.
     * @param BeforeControllerEvent $event
     * @return void
     * @throws HiddenException
     */
    public function onBeforeController(BeforeControllerEvent $event): void
    {
        /** @var RateLimiterService $rateLimiter */
        $rateLimiter = Kernel::$serviceContainer->get('secure.rateLimiter');

        if (!$rateLimiter->isEnabled()) {
            return;
        }

        $scope = strtolower($event->controllerName . '::' . $event->methodName);
        $result = $rateLimiter->hit(Kernel::$serviceContainer->get('kernel.request'), $scope);

        if (!$result['allowed']) {
            throw new HiddenException(sprintf(
                'Rate limit exceeded. Retry after %d seconds.',
                (int)$result['retryAfter']
            ));
        }
    }

    /**
     * Mask/secure model payloads before they reach the table layer.
     * @param ProcessingModelDataEvent $event
     * @return void
     */
    public function onProcessingModelData(ProcessingModelDataEvent $event): void
    {
        /** @var AbstractModel $model */
        $model = $event->model;
        /** @var MysqlService $secureService */
        $secureService = Kernel::$serviceContainer->get('secure.mysql');

        $processingData = $event->data;
        $secureService->processingQueryData(tableName: $model->useTable, content: $processingData);
        $event->data = $processingData;
    }

    /**
     * Mask sensitive data in a raw log message.
     * @param BeforeLogEvent $event
     * @return void
     */
    public function onBeforeLog(BeforeLogEvent $event): void
    {
        /** @var ArrayService $arrayService */
        $arrayService = Kernel::$serviceContainer->get('secure.array');

        $event->message = $arrayService->maskSensitiveInString($event->message);
    }

    /**
     * Mask sensitive data in the assembled log payload.
     * @param AfterLogEvent $event
     * @return void
     */
    public function onAfterLog(AfterLogEvent $event): void
    {
        /** @var ArrayService $arrayService */
        $arrayService = Kernel::$serviceContainer->get('secure.array');

        $payload = $event->payload;
        $payload['message'] = $arrayService->maskSensitiveInString($payload['message'] ?? '');
        $payload['content'] = $arrayService->maskSensitiveInArray($payload['content'] ?? []);
        $payload['get'] = $arrayService->maskSensitiveInArray($payload['get'] ?? []);
        $event->payload = $payload;
    }

}
