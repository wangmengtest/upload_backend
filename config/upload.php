<?php
return [
    'username' => [
        'vhall'      => env('UPLOAD_VHALL_PWD', 'password'),
    ],
    'bucket' => [
        'vhall'      => 'vhall',
    ],
    
    'storage' => env('STORAGE_PATH'),

    'errorCode' => [
        10001 => '文件不存在',
        10002 => '未设置参数',
    ]
];