<?php

if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        $timestamp = strtotime($datetime);
        $difference = time() - $timestamp;
        
        $periods = [
            'year' => 31536000,
            'month' => 2592000,
            'week' => 604800,
            'day' => 86400,
            'hour' => 3600,
            'minute' => 60,
            'second' => 1
        ];
        
        foreach ($periods as $key => $value) {
            $result = floor($difference / $value);
            
            if ($result >= 1) {
                return $result . ' ' . $key . ($result > 1 ? 's' : '') . ' ago';
            }
        }
        
        return 'Just now';
    }
}
?>