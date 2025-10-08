<?php

namespace App\Helpers;

class PhotographyHelper
{
    // TABS
    public const TAB_PORTRAITS = 'PORTRAITS';
    public const TAB_GROUPS = 'GROUPS';
    public const TAB_OTHERS = 'OTHERS';

    // EVENTS
    public const EV_CHANGE_TAB = 'EV_CHANGE_TAB';
    public const EV_SELECT_IMAGE = 'EV_SELECT_IMAGE';
    public const EV_CLEAR_SELECTED_IMAGES = 'EV_CLEAR_SELECTED_IMAGES';
    public const EV_UPDATE_FILTER = 'EV_UPDATE_FILTER';
    public const EV_UPDATE_SEARCH = 'EV_UPDATE_SEARCH';
    public const EV_UPDATE_FILTER_DATA = 'EV_UPDATE_FILTER_DATA';
    public const EV_UPDATE_FILENAME_FORMATS = 'EV_UPDATE_FILENAME_FORMATS';
    public const EV_TOGGLE_NO_IMAGES = 'EV_TOGGLE_NO_IMAGES';
    public const EV_IMAGE_UPLOADED = 'EV_IMAGE_UPLOADED';
    public const EV_IMAGE_DELETED = 'EV_IMAGE_DELETED';
}
