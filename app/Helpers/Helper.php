<?php // Code within app\Helpers\Helper.php

namespace App\Helpers;

class Helper
{
    public static function wrapSalutationFirstNameLastName($salutation, $first_name, $last_name, $uniqueClassPrefix)
    {
        $uSalutation = sprintf('<span id="%s-salutation" class="%s-salutation">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $salutation);
        $uFirst = sprintf('<span id="%s-first-name" class="%s-first-name">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $first_name);
        $uLast = sprintf('<span id="%s-last-name" class="%s-last-name">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $last_name);

        $returnText = $uSalutation . " " . $uFirst . " " . $uLast;

        return $returnText;
    }

    public static function wrapSalutationPrefixFirstNameLastNameSuffix($salutation, $prefix, $first_name, $last_name, $suffix, $uniqueClassPrefix)
    {
        $uSalutation = sprintf('<span id="%s-salutation" class="%s-salutation">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $salutation);
        $uPrefix = sprintf('<span id="%s-prefix" class="%s-prefix">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $prefix);
        $uFirst = sprintf('<span id="%s-first-name" class="%s-first-name">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $first_name);
        $uLast = sprintf('<span id="%s-last-name" class="%s-last-name">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $last_name);
        $uSuffix = sprintf('<span id="%s-suffix" class="%s-suffix">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $suffix);

        $returnText = $uSalutation . " " . $uPrefix . " " . $uFirst . " " . $uLast . " " . $uSuffix;

        return $returnText;
    }

    public static function wrapSalutationPrefixFirstNameLastNameSuffixAsText($salutation, $prefix, $first_name, $last_name, $suffix)
    {
        $uSalutation = trim($salutation) ? sprintf("%s", $salutation) : '';
        $uPrefix     = trim($prefix) ? sprintf("%s", $prefix) : '';
        $uFirst      = trim($first_name) ? sprintf("%s", $first_name) : '';
        $uLast       = trim($last_name) ? sprintf("%s", $last_name) : '';
        $uSuffix     = trim($suffix) ? sprintf("%s", $suffix) : '';
    
        // Combine only non-empty parts with a space
        $returnText = trim(implode(' ', array_filter([$uSalutation, $uPrefix, $uFirst, $uLast, $uSuffix])));
    
        return $returnText;
    }
    

    public static function wrapFirstNameLastName($first_name, $last_name, $uniqueClassPrefix)
    {
        $uFirst = sprintf('<span class="%s-first-name">%s</span>', $uniqueClassPrefix, $first_name);
        $uLast = sprintf('<span class="%s-last-name">%s</span>', $uniqueClassPrefix, $last_name);

        $returnText = $uFirst . " " . $uLast;

        return $returnText;
    }

    public static function wrapSalutation($salutation, $uniqueClassPrefix)
    {
        $uSalutation = sprintf('<span id="%s-salutation" class="%s-salutation">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $salutation);

        return $uSalutation;
    }

    public static function wrapTitle($title, $uniqueClassPrefix)
    {
        $uTitle = sprintf('<span id="%s-title" class="%s-title">%s</span>', $uniqueClassPrefix, $uniqueClassPrefix, $title);

        return $uTitle;
    }

    public static function compileFullName($salutation = "", $first_name = "", $last_name = "")
    {
        $returnText = $salutation . " " . $first_name . " " . $last_name;
        $returnText = trim(str_replace("  ", " ", $returnText));

        return $returnText;
    }

    public static function pushTabSession($data)
    {
        return ['tabSession' => $data];
    }

}