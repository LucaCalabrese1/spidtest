<?php


namespace App\Helpers;
use App\LoginActivity as LoginActivityModel;
use Carbon\Carbon;


class LoginActivity
{

    public static function addToLog($email, $type, $ip, $event, $user_agent, $session_id)
    {
		$ua = strtolower($user_agent);
        $isMob = is_numeric(strpos($ua, "mobile"));

		if(!$isMob)
		{
			$device = 'DESKTOP';
		}
		else
		{
			$device = 'MOBILE';
		}

    	$log = [];
    	$log['user_name'] = $email;
		$log['login_type'] = $type;
		$log['ip_address'] = $ip;
		$log['desc_event'] = $event;
		$log['user_agent'] = $user_agent;
		$log['session_id'] = $session_id;
		$log['device'] = $device;

		if($event == 'LOGOUT')
		{
			$log['logout_time'] = Carbon::now();
			$log['login_time'] = '0000-00-00 00:00:00';
		}
		else
		{
			$log['login_time'] = Carbon::now();
			$log['logout_time'] = '0000-00-00 00:00:00';
		}

    	LoginActivityModel::create($log);
    }


}
