<?php

namespace App\Utils;

use Monolog\Formatter\NormalizerFormatter;

class ElasticSearchFormatter extends NormalizerFormatter
{
    public function format(array $record): string
    {
        $message = $record['message'];
        $date = $record['datetime'];
        $severity = $record['level_name'];
        $exception = $this->getExceptionDataFromContext($record);
        $logReqResp = $this->getRequestAndResponseFromContext($record);
        $application = $this->getApplication();

        $data = array_filter([
            "application" => $application,
            "context" => json_encode($record["context"] ?? []),
            "date" => $date->format('Y-m-d\TH:i:s.v'),
            "exception" => $exception,
            "message" => $message,
            "severity" => $severity,
        ]);
        return trim(json_encode($data + $logReqResp)) . "\n";
    }

    /**
     * @param array $record
     * @return array
     */
    protected function getRequestAndResponseFromContext(array &$record): array
    {
        $requestId = [
            'http' => [
                'request' => ['id' => Session::getRequestId()],
            ]
        ];
        $logReqResp = $record['context']['api_request_response'] ?? $requestId;

        if (array_key_exists('api_request_response', $record['context'])) {
            $record['context'] = [];
        }

        return $logReqResp;
    }

    /**
     * @return array
     */
    protected function getApplication(): array
    {
        return [
            "name" => env("APP_NAME"),
            "environment" => env("APP_ENV"),
            "version" => json_decode(file_get_contents(base_path("composer.json")), true)["version"],
        ];
    }

    /**
     * @param array $record
     * @return array|null
     */
    protected function getExceptionDataFromContext(array &$record): ?array
    {
        $exception = $record['context']['exception'] ?? null;
        if (!array_key_exists('exception', $record['context'])) {
            return null;
        }

        $exception = [
            'name' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stacktrace' => $exception->getTraceAsString(),
        ];

        $record['context'] = [];

        return $exception;
    }
}
