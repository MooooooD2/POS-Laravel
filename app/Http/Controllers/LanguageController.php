<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class LanguageController extends Controller
{
    /**
     * Switch application language
     *
     * @param  string  $locale
     * @return \Illuminate\Http\RedirectResponse
     */
    public function switch($locale)
    {
        // Check if the locale is supported
        $supportedLocales = ['en', 'ar'];

        if (in_array($locale, $supportedLocales)) {
            App::setLocale($locale);
            Session::put('locale', $locale);

            // Set direction for RTL/LTR
            if ($locale === 'ar') {
                Session::put('direction', 'rtl');
            } else {
                Session::put('direction', 'ltr');
            }

            // Update user's language preference if logged in
            if (auth()->check()) {
                auth()->user()->update(['language' => $locale]);
            }
        }

        // Redirect back to the previous page
        return redirect()->back();
    }

    /**
     * Get translations for JavaScript
     *
     * @param  string  $locale
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTranslations($locale)
    {
        $translationFile = resource_path("lang/{$locale}/pos.php");

        if (file_exists($translationFile)) {
            $translations = include $translationFile;
            return response()->json($translations);
        }

        return response()->json([]);
    }
}