<?php

use Illuminate\Support\Arr;

it('keeps every admin translation path available in each supported language', function ($locale) {
    $directory = base_path('packages/Webkul/Admin/src/Resources/lang');
    $expected = array_keys(Arr::dot(require $directory.'/en/app.php'));
    $actual = array_keys(Arr::dot(require $directory.'/'.$locale.'/app.php'));
    sort($expected);
    sort($actual);

    expect($actual)->toBe($expected);
})->with(['ar', 'es', 'fa', 'ja', 'ko', 'pt_BR', 'tr', 'vi', 'zh_CN']);
