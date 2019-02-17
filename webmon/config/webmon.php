<?php

return [

    'scanners' => [
        'git' => [
            'class' => \App\Jobs\ScanPublicGitFolder::class,
            'min_scan_interval'=> 10080 // 1 week
        ],
        'unresolvable' => [
            'class' => \App\Jobs\CheckDomainResolves::class,
            'min_scan_interval' => 5000 // ~3 days
        ]
    ],

    // Do not run any scanners on domains that have been scanned within this time (in minutes)
    'min_scan_interval' => env('WEBMON_MIN_INTERVAL', 4320), // default 3 days

    // Do not re-notify about scanner findings for the domain, if we have already notified about it within this time (in minutes)
    'min_renotify_interval'=> env('WEBMON_RENOTIFY_INTERVAL', 43800) // 1 month

];
