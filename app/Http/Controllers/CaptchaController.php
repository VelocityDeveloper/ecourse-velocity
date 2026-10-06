<?php

namespace App\Http\Controllers;

use App\Support\Captcha;
use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    /**
     * A new picture of digits for the login or registration form.
     */
    public function __invoke(string $form): Response
    {
        return response(Captcha::issue($form), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
