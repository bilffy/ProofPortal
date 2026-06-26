<?php

namespace App\Services\Proofing;
use Illuminate\Database\Query\Builder as Query;
use Illuminate\Support\Str;

class SqlServerReportingServices
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Map portal parameter keys to SSRS report parameter names.
     */
    public static function mapSsrsParamKey(string $paramKey): string
    {
        $configured = config('settings.ssrs_param_map', []);

        if (isset($configured[$paramKey])) {
            return $configured[$paramKey];
        }

        return match ($paramKey) {
            'ts_job_id' => 'schoolid',
            'ts_folder_id' => 'folderid',
            default => $paramKey,
        };
    }

    /**
     * Map UI/download format keys to SSRS rs:Format values.
     */
    public static function mapDownloadFormat(string $format): string
    {
        $normalized = strtolower($format);

        return match ($normalized) {
            'csv' => 'CSV',
            'pdf' => 'PDF',
            'xlsx', 'excelopenxml' => 'EXCELOPENXML',
            'xls', 'excel' => 'EXCEL',
            default => strtoupper($format),
        };
    }

    /**
     * File extension for a download format.
     */
    public static function downloadExtension(string $format): string
    {
        $normalized = strtolower($format);

        return match ($normalized) {
            'csv' => 'csv',
            'pdf' => 'pdf',
            'xlsx', 'excelopenxml' => 'xlsx',
            'xls', 'excel' => 'xls',
            default => strtolower($format),
        };
    }

    /**
     * Normalize portal param keys to SSRS URL parameter names.
     */
    public static function normalizeSsrsParams(array $params): array
    {
        $normalized = [];

        foreach ($params as $key => $value) {
            $normalized[self::mapSsrsParamKey($key)] = $value;
        }

        return $normalized;
    }

    /**
     * Apply report-specific extra SSRS parameters and discover any remaining required params.
     */
    public static function completeSsrsParams(string $reportName, array $params): array
    {
        $params = self::normalizeSsrsParams($params);
        $params = self::applyConfiguredExtraParams($reportName, $params);

        $maxIterations = (int) config('settings.ssrs_param_discovery_attempts', 8);

        for ($i = 0; $i < $maxIterations; $i++) {
            $missing = self::getMissingReportParameter($reportName, $params);

            if ($missing === null) {
                break;
            }

            $value = self::resolveMissingParameterValue($missing, $params);

            if ($value === null || array_key_exists($missing, $params)) {
                break;
            }

            $params[$missing] = $value;
        }

        return $params;
    }

    protected static function applyConfiguredExtraParams(string $reportName, array $params): array
    {
        $extras = config("settings.ssrs_report_extra_params.{$reportName}", []);

        foreach ($extras as $paramName => $template) {
            if (array_key_exists($paramName, $params)) {
                continue;
            }

            $value = self::interpolateParamTemplate($template, $params);
            if ($value !== null && $value !== '') {
                $params[$paramName] = $value;
            }
        }

        return $params;
    }

    protected static function interpolateParamTemplate(string $template, array $params): ?string
    {
        if (preg_match('/^\{(.+)\}$/', $template, $matches)) {
            return isset($params[$matches[1]]) ? (string) $params[$matches[1]] : null;
        }

        return $template;
    }

    protected static function resolveMissingParameterValue(string $paramName, array $params): mixed
    {
        if (isset($params[$paramName])) {
            return $params[$paramName];
        }

        return match ($paramName) {
            'email' => $params['email'] ?? null,
            'schoolid' => $params['schoolid'] ?? null,
            'folderid' => $params['folderid'] ?? null,
            default => null,
        };
    }

    protected static function getMissingReportParameter(string $reportName, array $params): ?string
    {
        $response = self::ssrsHttpGet($reportName, $params, [
            'rs:Command' => 'GetReportParameters',
            'rs:Format' => 'XML',
        ]);

        if ($response->successful()) {
            return null;
        }

        return self::parseMissingParameterName($response->body());
    }

    protected static function parseMissingParameterName(string $body): ?string
    {
        if (preg_match("/report parameter &#39;([^&#]+)&#39;/i", $body, $matches)) {
            return $matches[1];
        }

        if (preg_match("/report parameter '([^']+)'/i", html_entity_decode($body), $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected static function parseUnknownParameterName(string $body): ?string
    {
        if (preg_match("/parameter '([^']+)' that is not defined/i", html_entity_decode($body), $matches)) {
            return $matches[1];
        }

        if (preg_match("/parameter &#39;([^&#]+)&#39; that is not defined/i", $body, $matches)) {
            return $matches[1];
        }

        return null;
    }

    protected static function ssrsHttpGet(string $reportName, array $params, array $queryOptions = [], ?string $format = null)
    {
        $url = self::makeSsrsUrl([
            'ssrsServer' => config('settings.ssrs_server'),
            'ssrsFolder' => config('settings.ssrs_folder'),
            'ssrsReport' => $reportName,
            'format' => $format,
            'params' => $params,
            'queryOptions' => $queryOptions,
        ]);

        return \Illuminate\Support\Facades\Http::withBasicAuth(
            config('settings.ssrs_username'),
            config('settings.ssrs_password')
        )->get($url);
    }

    /**
     * Fetch a rendered report from SQL Server Reporting Services.
     *
     * @return array{success: bool, body: ?string, contentType: ?string, extension: ?string, error: ?string, url: ?string, params: array<string, mixed>}
     */
    public static function downloadFromReportServer(string $reportQueryName, string $format, array $params): array
    {
        $ssrsFormat = self::mapDownloadFormat($format);
        $extension = self::downloadExtension($format);
        // Params are finalized when the run page is built; only normalize key names here.
        $params = self::normalizeSsrsParams($params);

        $maxAttempts = 3;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $result = self::attemptSsrsDownload($reportQueryName, $ssrsFormat, $extension, $params);

            if ($result['success'] || $attempt === $maxAttempts - 1) {
                return $result;
            }

            $unknownParam = self::parseUnknownParameterName($result['error'] ?? '');

            if ($unknownParam === null || ! array_key_exists($unknownParam, $params)) {
                return $result;
            }

            unset($params[$unknownParam]);
        }

        return $result;
    }

    /**
     * @return array{success: bool, body: ?string, contentType: ?string, extension: ?string, error: ?string, url: ?string, params: array<string, mixed>}
     */
    protected static function attemptSsrsDownload(string $reportQueryName, string $ssrsFormat, string $extension, array $params): array
    {
        $ssrsUrl = self::makeSsrsUrl([
            'ssrsServer' => config('settings.ssrs_server'),
            'ssrsFolder' => config('settings.ssrs_folder'),
            'ssrsReport' => $reportQueryName,
            'format' => $ssrsFormat,
            'params' => $params,
        ]);

        $response = \Illuminate\Support\Facades\Http::withBasicAuth(
            config('settings.ssrs_username'),
            config('settings.ssrs_password')
        )->get($ssrsUrl);

        if (!$response->successful()) {
            return [
                'success' => false,
                'body' => null,
                'contentType' => null,
                'extension' => $extension,
                'error' => self::extractSsrsErrorMessage($response->body()) ?: 'Failed to download report from Report Server.',
                'url' => $ssrsUrl,
                'params' => $params,
            ];
        }

        $body = $response->body();
        $contentType = $response->header('Content-Type') ?: self::defaultContentType($extension);

        if ($ssrsFormat === 'CSV' && self::csvHasOnlyHeader($body)) {
            return [
                'success' => false,
                'body' => null,
                'contentType' => $contentType,
                'extension' => $extension,
                'error' => 'Report Server returned no data for your account. Please verify your email is registered in Report Server for this school.',
                'url' => $ssrsUrl,
                'params' => $params,
            ];
        }

        return [
            'success' => true,
            'body' => $body,
            'contentType' => $contentType,
            'extension' => $extension,
            'error' => null,
            'url' => $ssrsUrl,
            'params' => $params,
        ];
    }

    public static function csvHasOnlyHeader(string $body): bool
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($body));

        return count($lines) <= 1;
    }

    protected static function defaultContentType(string $extension): string
    {
        return match ($extension) {
            'csv' => 'text/csv',
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
            default => 'application/octet-stream',
        };
    }

    protected static function extractSsrsErrorMessage(string $body): ?string
    {
        if (preg_match('/<li>(.*?)<\/li>/s', $body, $matches)) {
            return trim(strip_tags(html_entity_decode($matches[1])));
        }

        return null;
    }

    /**
     * Build SSRS URL parameters and display metadata for a report run.
     *
     * @param  string  $userEmail
     * @param  array<int, mixed>  $passedParamValues
     * @param  array<int, array<string, mixed>>  $reportParams
     * @return array{ssrsParams: array<string, mixed>, sqlParams: array<int, array<string, mixed>>, downloadName: string}
     */
    public static function buildReportParams(string $userEmail, array $passedParamValues, array $reportParams, string $reportDisplayName): array
    {
        $sqlParams = [
            [
                'sqlFriendly' => 'email',
                'urlKey' => 'email',
                'urlValue' => $userEmail,
            ],
        ];

        $downloadName = now()->format('Ymd-His') . ' - ' . $reportDisplayName;
        $passedParamValues = array_values($passedParamValues);

        foreach ($reportParams as $k => $reportParam) {
            if (!isset($passedParamValues[$k])) {
                continue;
            }

            $paramValue = $passedParamValues[$k];
            $record = collect($reportParam['query'])->firstWhere($reportParam['keyField'], $paramValue);
            $friendlyValue = $record
                ? data_get($record, $reportParam['valueField'], $paramValue)
                : $paramValue;

            $urlKey = self::mapSsrsParamKey($reportParam['keyField']);

            $sqlParams[] = [
                'sqlFriendly' => strtolower(str_replace('.', '_', str_replace('s.', '.', $reportParam['keyField']))),
                'urlKey' => $urlKey,
                'urlValue' => $paramValue,
                'friendlyName' => $reportParam['name'],
                'friendlyValue' => $friendlyValue,
            ];

            $downloadName .= ' - ' . $friendlyValue;
        }

        $ssrsParams = [];
        foreach ($sqlParams as $sqlParam) {
            $ssrsParams[$sqlParam['urlKey']] = $sqlParam['urlValue'];
        }

        return [
            'ssrsParams' => $ssrsParams,
            'sqlParams' => $sqlParams,
            'downloadName' => $downloadName,
        ];
    }

    /**
     * Build final SSRS params for a report, including report-server-specific extras.
     */
    public static function buildSsrsDownloadParams(string $reportQueryName, string $userEmail, array $passedParamValues, array $reportParams, string $reportDisplayName): array
    {
        $payload = self::buildReportParams($userEmail, $passedParamValues, $reportParams, $reportDisplayName);
        $payload['ssrsParams'] = self::completeSsrsParams($reportQueryName, $payload['ssrsParams']);

        return $payload;
    }

    public static function makeSsrsUrl($opts = [])
    {
        // Default options
        $defaultOptions = [
            'ssrsServer' => null,
            'ssrsFolder' => null,
            'ssrsReport' => null,
            'format' => null,
            'params' => null,
            'queryOptions' => [],
        ];

        // Merge provided options with defaults
        $opts = array_merge($defaultOptions, $opts);

        // Build the base URL
        $url = '';
        $url .= $opts['ssrsServer'];
        $url .= "?";
        
        // Include folder and report in the URL
        $url .= urlencode(self::makeStartsWithAndEndsWith($opts['ssrsFolder'], "/", "/"));
        $url .= rawurlencode(trim($opts['ssrsReport'], "/"));

        // Add format to the URL if present
        if ($opts['format']) {
            $url .= "&rs:Format=" . urlencode($opts['format']);
        }

        foreach ($opts['queryOptions'] as $queryKey => $queryValue) {
            $url .= "&" . urlencode($queryKey) . "=" . urlencode((string) $queryValue);
        }

        // Append parameters to the URL if present
        if ($opts['params']) {
            foreach ($opts['params'] as $paramK => $paramV) {
                if (array_key_exists($paramK, $opts['queryOptions'])) {
                    continue;
                }

                $paramK = self::mapSsrsParamKey($paramK);
                $url .= "&" . urlencode($paramK) . "=" . urlencode((string) $paramV);
            }
        }

        return $url;
    }

    // Helper method to ensure a string starts and ends with specific characters
    public static function makeStartsWithAndEndsWith($string = "", $startsWith = "", $endsWith = "")
    {
        if (!self::endsWith($string, $endsWith)) {
            $string .= $endsWith;
        }

        if (!self::startsWith($string, $startsWith)) {
            $string = $startsWith . $string;
        }

        return $string;
    }

    // Helper method to check if a string starts with a given substring
    public static function startsWith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    // Helper method to check if a string ends with a given substring
    public static function endsWith($haystack, $needle)
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }


    public static function queryToCsvReport($query = null)
    {
        // Disable hydration in Laravel (Laravel queries don't hydrate by default in the same way as CakePHP)
        $reportRows = $query->toArray();
        $inProgress = '';
    
        // Get header row
        if (empty($reportRows)) {
            return '';
        }
        $headerRow = array_keys((array)$reportRows[0]);
        $headerRow = self::humanizeWords($headerRow); // Use self:: to call static methods
        $headerRow = self::qualifyWords($headerRow, "\"", [",", "\r", "\n", "\t"]);
        $inProgress .= implode(",", $headerRow) . PHP_EOL;
    
        foreach ($reportRows as $reportRow) {
            $rowArray = (array)$reportRow;
            foreach ($rowArray as $cellKey => $cell) {
                $rowArray[$cellKey] = self::valueToString($cell, ",");
            }
            $currentRow = self::qualifyWords($rowArray, "\"", [",", "\r", "\n", "\t"]);
            $inProgress .= implode(",", $currentRow) . PHP_EOL;
        }
    
        return $inProgress;
    }
    
    protected static function humanizeWords(array $words) // Change to static
    {
        return array_map(function ($word) {
            return Str::title(str_replace('_', ' ', $word)); // Humanizes words like 'first_name' -> 'First Name'
        }, $words);
    }
    
    protected static function qualifyWords(array $words, $qualifier = "\"", array $specialChars = []) // Change to static
    {
        return array_map(function ($word) use ($qualifier, $specialChars) {
            if (strpbrk($word, implode('', $specialChars))) {
                return $qualifier . $word . $qualifier;
            }
            return $word;
        }, $words);
    }
    
    protected static function valueToString($value, $delimiter) // Change to static
    {
        if (is_null($value)) {
            return '';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_scalar($value)) {
            return (string)$value;
        } else {
            // Convert non-scalar values (like arrays) to a JSON-encoded string
            return json_encode($value);
        }
    }
    

}
