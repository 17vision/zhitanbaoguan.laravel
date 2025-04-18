<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use  Illuminate\Support\Str;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;
use Illuminate\Support\Facades\Cache;
class CaptchaController extends Controller
{
    public function getCaptcha()
    {
        $key = Str::random(9);

        $cacheKey =  'captcha_'.$key;

        $expiredAt = now()->addMinutes(5);

        $phraseBuilder = new PhraseBuilder(4);

        $builder = new CaptchaBuilder(null, $phraseBuilder);

        $builder->setBackgroundColor(255,255,255);

        $captcha = $builder->build(98, 38);

        Cache::put($cacheKey, ['code' => strtolower($captcha->getPhrase())], $expiredAt);

        $result = [
            'captcha_key' => $key,
            'expired_at' => $expiredAt->toDateTimeString(),
            'captcha' => $captcha->inline()
        ];

        return response()->json($result);
    }
}
