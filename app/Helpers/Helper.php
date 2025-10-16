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

}