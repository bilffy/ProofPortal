<?php

namespace App\Helpers;

use App\Models\FilenameFormat;
use DB;

class FilenameFormatHelper
{
    public static function applyFormat(string $format, array $options): string
    {
        $rawValues = self::extractValuesFromBraces($format);
        $extractedFormat = self::replaceValuesWithAsterisks($format);

        $resultValues = array_map(function ($rawValue) use ($options) {
            return self::getValue($rawValue, $options);
        }, $rawValues);

        $resultFormat = self::replaceAsterisksWithValues($extractedFormat, $resultValues);
        $resultFormat = preg_replace('/\s+/', ' ', trim($resultFormat));
        $resultFormat = preg_replace('/[\/\\\\\?\*\:]/', '-', $resultFormat);
        return $resultFormat;
    }

    private static function extractValuesFromBraces(string $input): array
    {
        // Use a regular expression to match values inside curly braces
        preg_match_all('/\{(.*?)\}/', $input, $matches);
    
        // Return the matched values as an array
        return $matches[1] ?? [];
    }

    private static function replaceValuesWithAsterisks(string $input): string
    {
        return preg_replace('/\{.*?\}/', '*', $input);
    }

    private static function replaceAsterisksWithValues(string $input, array $values): string
    {
        $index = 0;
        // Use preg_replace_callback to replace each asterisk with a value from the array
        return preg_replace_callback('/\*/', function () use (&$index, $values) {
            // Check if there are still values left in the array
            return $values[$index++] ?? '*'; // Replace with the value or keep the asterisk if no values are left
        }, $input);
    }

    private static function getValue(string $variable, array $options): string
    {
        $values = explode('.', $variable);

        $row = DB::table($values[0])->find($options[$values[0]] ?? 0);

        if ($row) {
            $value = $row->{$values[1]};
            return null === $value ? '' : $value;
        }
        
        return '';
    }

    public static function getFormatOptions($category): array
    {
        $formats = FilenameFormat::whereJsonContains('visibility', $category)
            ->get(['name', 'format_key'])
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'format_key' => $item->format_key,
                ];
            });

        $options = [];
        foreach ($formats as $format) {
            $options[] = [
                'name' => $format['name'],
                'format_key' => $format['format_key'],
            ];
        }
        return $options;
    }
}
