<?php

namespace App\Helpers\Constants;

use ArrayObject;

enum FilenameFormat: int
{
    case FIRST_NAME_LAST_NAME = 1;
    case FIRST_NAME_ONLY = 2;
    case LAST_NAME_ONLY = 3;
    case IMAGE_CODE = 4;
    case GROUP_NAME = 5;
    
    public function text(): string
    {
        return match($this)
        {
            FilenameFormat::FIRST_NAME_ONLY => "First Name Only",
            FilenameFormat::LAST_NAME_ONLY => "Last Name Only",
            FilenameFormat::FIRST_NAME_LAST_NAME => "First Name & Last Name",
            FilenameFormat::IMAGE_CODE => "Image Code",
            FilenameFormat::GROUP_NAME => "Class or Group Name",
        };
    }

    public static function getSubjectsFormat(): array
    {
        return
        [
            FilenameFormat::FIRST_NAME_ONLY,
            FilenameFormat::LAST_NAME_ONLY,
            FilenameFormat::FIRST_NAME_LAST_NAME,
            FilenameFormat::IMAGE_CODE,
        ];
    }

    public static function getFoldersFormat(): array
    {
        return
        [
            FilenameFormat::IMAGE_CODE,
            FilenameFormat::GROUP_NAME,
        ];
    }
}
