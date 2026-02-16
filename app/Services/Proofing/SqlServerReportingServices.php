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

    public static function makeSsrsUrl($opts = [])
    {
        // Default options
        $defaultOptions = [
            'ssrsServer' => null,
            'ssrsFolder' => null,
            'ssrsReport' => null,
            'format' => null,
            'params' => null,
        ];

        // Merge provided options with defaults
        $opts = array_merge($defaultOptions, $opts);

        // Build the base URL
        $url = '';
        $url .= $opts['ssrsServer'];
        $url .= "?";
        
        // Include folder and report in the URL
        $url .= urlencode(self::makeStartsWithAndEndsWith($opts['ssrsFolder'], "/", "/"));
        $url .= urlencode(trim($opts['ssrsReport'], "/"));

        // Add format to the URL if present
        if ($opts['format']) {
            $url .= "&rs:Format=" . urlencode($opts['format']);
        }

        // Append parameters to the URL if present
        if ($opts['params']) {
            foreach ($opts['params'] as $paramK => $paramV) {
                if($paramK === 'ts_job_id')
                {
                    $paramK = 'schoolid';
                }elseif($paramK === 'ts_folder_id')
                {
                    $paramK = 'folderid';
                }
                $url .= "&" . urlencode($paramK) . "=" . urlencode($paramV);
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
            foreach ($reportRow as $cellKey => $cell) {
                $reportRow[$cellKey] = self::valueToString($cell, ","); // Use self:: to call static methods
            }
            $currentRow = self::qualifyWords($reportRow, "\"", [",", "\r", "\n", "\t"]);
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
