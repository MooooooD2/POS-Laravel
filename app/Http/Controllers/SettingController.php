<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }

    /**
     * Get all settings - الحصول على كل الإعدادات
     */
    public function all()
    {
        return response()->json([
            'settings' => Setting::getAllGrouped(),
        ]);
    }

    /**
     * Update settings - تحديث الإعدادات
     */
    public function update(Request $request)
    {
        $data = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string|exists:settings,key',
            'settings.*.value' => 'nullable',
        ]);

        foreach ($data['settings'] as $item) {
            Setting::set($item['key'], $item['value'] ?? '');
        }

        // Clear all setting caches
        Cache::flush();

        return response()->json(['success' => true, 'message' => __('pos.settings_saved')]);
    }

    /**
     * Get a single group - الحصول على مجموعة واحدة
     */
    public function group(string $group)
    {
        return response()->json(['settings' => Setting::getGroup($group)]);
    }
}