<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;

class LanguageController extends BaseController
{
    public function setLanguage(Request $request)
    {
        $language = $request->language ?? 'en';
        $availableLanguages = ['en', 'vi'];
        if (!in_array($language, $availableLanguages)) {
            return $this->responsesService->error(400, __('message.language_not_support'), null);
        }
        return $this->responsesService->success(200,  __('message.language_set_successful'), null)->cookie('language', $language, 60 * 24 * 30);
    }
}
