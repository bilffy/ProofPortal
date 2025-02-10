<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Request;
use App\Models\Log;

class ActivityLogHelper
{
    /**
     * create new activity log
     * @param string $action
     * @param array $details
     * @param int|null $userId
     * @return void
     */
    public static function log($action, $details, $userId = null)
    {
        $logParams = [];
        
        $logParams['user_id'] = is_null($userId) ? auth()->user()->id : $userId;
        $logParams['action'] = $action;
        $logParams['ip_address'] = Request::ip();
        $logParams['session_id'] = session()->getId();
        $logParams['details'] = json_encode($details);
        $logParams['created_at'] = now();

        Log::create($logParams);
    }

}
