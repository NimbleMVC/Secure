<?php

namespace NimblePHP\Secure\Services;

use krzysztofzylka\DatabaseManager\Exception\DatabaseManagerException;
use krzysztofzylka\DatabaseManager\Table;
use NimblePHP\Framework\Cache;
use NimblePHP\Framework\Exception\HiddenException;
use NimblePHP\Framework\Kernel;
use NimblePHP\Framework\Log;
use Throwable;

class MysqlService
{

    /**
     * @param string $tableName
     * @param array  $content
     * @param array  $disableProcessingColumn
     * @return void
     * @throws HiddenException
     */
    public function processingQueryData(string $tableName, array &$content, array $disableProcessingColumn = []): void
    {
        try {
            $tableColumn = $this->getTableColumns($tableName);
            $allowedColumns = array_keys($tableColumn);
            $content = array_intersect_key($content, array_flip($allowedColumns));

            foreach ($tableColumn as $columnName => $columnType) {
                if (!array_key_exists($columnName, $content) || in_array($columnName, $disableProcessingColumn, true)) {
                    continue;
                }

                preg_match('/^(\w+)(?:\((.+)\))?/', $columnType, $matches);
                $type = strtolower($matches[1] ?? '');
                $size = $matches[2] ?? null;

                if ($content[$columnName] === null) {
                    continue;
                }

                if (in_array($type, ['varchar', 'text', 'longtext'], true)) {
                    $value = trim((string)$content[$columnName]);

                    if ($size !== null && ctype_digit($size)) {
                        $maxLength = (int)$size;
                        if (mb_strlen($value) > $maxLength) {
                            $value = mb_substr($value, 0, $maxLength);
                        }
                    }

                    $content[$columnName] = $value;
                } elseif ($type === 'enum') {
                    preg_match_all("/'([^']*)'/", (string)$size, $m);
                    $allowedValues = $m[1] ?? [];

                    if (!in_array($content[$columnName], $allowedValues, true)) {
                        $content[$columnName] = null;
                    }
                } elseif ($type === 'tinyint' && (int)$size === 1) {
                    $content[$columnName] = (int)(bool)$content[$columnName];
                }  elseif (in_array($type, ['int', 'bigint', 'tinyint', 'smallint', 'mediumint'], true)) {
                    if (!is_numeric($content[$columnName])) {
                        $content[$columnName] = null;
                    } else {
                        $content[$columnName] = (int)$content[$columnName];
                    }
                } elseif ($type === 'decimal') {
                    $value = str_replace(',', '.', (string)$content[$columnName]);

                    if (!is_numeric($value)) {
                        $content[$columnName] = null;
                    } else {
                        if ($size && str_contains($size, ',')) {
                            [, $scale] = array_map('intval', explode(',', $size, 2));
                            $value = number_format((float)$value, $scale, '.', '');
                        }

                        $content[$columnName] = $value;
                    }
                } elseif (in_array($type, ['float', 'double'], true)) {
                    $value = str_replace(',', '.', (string)$content[$columnName]);

                    if (!is_numeric($value)) {
                        $content[$columnName] = null;
                    } else {
                        $content[$columnName] = (float)$value;
                    }
                }
            }

        } catch (Throwable $e) {
            Log::log('Failed processing mysql data', 'FATAL_ERR', ['exception' => $e->getMessage(), 'backtrace' => $e->getTraceAsString()]);

            throw new HiddenException('Failed processing mysql data');
        }
    }

    /**
     * @param string $tableName
     * @return array
     * @throws DatabaseManagerException
     */
    protected function getTableColumns(string $tableName): array
    {
        /** @var Cache $cache */
        $cache = Kernel::$serviceContainer->get('kernel.cache');
        $cacheKey = 'secure_mysql_table_columns_' . sha1($tableName);

        if ($cache->has($cacheKey)) {
            return $cache->get($cacheKey);
        }

        $table = new Table($tableName);
        $columnList = $table->columnList();

        $list = array_combine(
            array_column($columnList, 'Field'),
            array_column($columnList, 'Type')
        );

        $cache->set($cacheKey, $list);

        return $list;
    }

}