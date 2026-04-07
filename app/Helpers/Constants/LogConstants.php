<?php

namespace App\Helpers\Constants;

class LogConstants
{
    public const LOGIN = 'user.login';
    public const CREATE_USER = 'user.create';
    public const EDIT_USER = 'user.edit';
    public const SEND_INVITE = 'user.send_invite';
    public const IMPERSONATE_USER = 'user.impersonate';
    public const EXIT_IMPERSONATE_USER = 'user.exit_impersonate';
    public const DOWNLOAD_PHOTOS = 'photos.download';
    public const UPLOAD_SCHOOL_LOGO = 'config.school_logo_upload';
    public const JOB_OPENED = 'job.opened';
    public const JOB_STATUS_CHANGED = 'job.status_changed';
    public const JOB_SINGLE_INVITE = 'job.single_invite';
    public const JOB_MULTIPLE_INVITE = 'job.multiple_invite';
    public const FOLDER_STATUS_CHANGED = 'folder.status_changed';
    public const UPDATE_SCHOOL_DOWNLOAD_PERMISSIONS = 'config.update_download_permissions';
    public const UPDATE_SCHOOL_FOLDER_CONFIG = 'config.update_job_folders';
    public const UPDATE_SCHOOL_DOWNLOAD_TIMELINE_CONFIG = 'config.update_download_timeline';
    public const UPLOAD_PHOTO = 'user.upload_photo';
    public const REMOVE_PHOTO = 'user.remove_photo';

    public const DISABLE_USER = 'user.disable';
}