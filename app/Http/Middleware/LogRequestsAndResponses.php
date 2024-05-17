<?php

namespace App\Http\Middleware;

use App\Utils\Session;
use Closure;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class LogRequestsAndResponses
{
    /**
     * The list of URI paths that should not be logged.
     *
     * @var array
     */
    protected array $except = [
        'health-check',
        'api/v1/app-version',
        // Add more paths as needed
    ];

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the current path is in the $except array and skip logging if it is.
        foreach ($this->except as $path) {
            if ($request->is($path)) {
                return $next($request);
            }
        }

        // Start time
        $startTime = Session::getStartTime();

        // Check for existing request ID
        Session::setRequestId($request->header('X-Request-ID') ?? Session::getRequestId());

        // Serialize request body to string, excluding sensitive data
        $requestBody = json_encode($request->except(['password', 'password_confirmation']));
        $requestHeaders = json_encode(Arr::except($request->headers->all(), ['cookie', 'authorization']));

        // Handle the request and get the response
        $response = $next($request);

        // End time
        $endTime = new DateTime();

        // Calculate execution time in seconds with milliseconds
        $executionTime = $endTime->getTimestamp() - $startTime->getTimestamp();
        $executionTime += ($endTime->format('u') - $startTime->format('u')) / 1e6;

        // Serialize response body to string, consider large bodies
        $responseBody = $response->getContent();

        // Determine status from the response
        $status = json_decode($responseBody, true)['status'] ?? 'unknown';

        $context = [
            'api_request_response' => [
                'http' => [
                    'request' => [
                        'id' => Session::getRequestId(),
                        'method' => $request->method(),
                        'uri' => $request->getUri(),
                        'body' => ['content' => $requestBody],
                        'headers' => str_replace(["\n", "\r"], "", $requestHeaders),
                        'date' => $startTime->format('Y-m-d\TH:i:s.v'),
                    ],
                    'response' => [
                        'status' => $status,
                        'status_code' => $response->status(),
                        'body' => ['content' => Str::limit($responseBody, 5000)], // Limiting response body length to 5000 characters
                        'date' => $endTime->format('Y-m-d\TH:i:s.v'),
                    ],

                ],
                'execution_time' => $executionTime,
                'client_ip' => $this->getClientIp($request),
            ],
        ];

        // Log request and response details together
        Log::info('API Request-Response Log', $context);

        return $response;
    }

    /**
     * Get client ip from request
     *
     * @param Request $request
     * @return string
     */
    protected function getClientIp(Request $request): string
    {
        $ip = $request->ip();

        $forwardedHeader = $request->header('X-Forwarded-For');
        if ($forwardedHeader) {
            $forwardedIps = explode(',', $forwardedHeader);
            $ip = trim($forwardedIps[0]);
        }

        return $ip;
    }
}
