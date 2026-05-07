<?php
namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    private const ALLOWED_KEYS = [
        'store_name', 'store_address', 'store_phone', 'store_email', 'store_logo',
        'tax_enabled', 'tax_rate', 'tax_inclusive', 'tax_name_ar', 'tax_name_en',
        'invoice_footer', 'invoice_header', 'default_payment', 'auto_print',
        'pos_sound', 'currency_ar', 'currency_en', 'currency_position',
        'low_stock_alert', 'receipt_copies', 'invoice_notes',
    ];

    public function index()
    {
        return view('settings.index');
    }

    public function all()
    {
        return response()->json(['settings' => Setting::getAllGrouped()]);
    }

    public function update(Request $request)
    {
        $this->authorize('update_settings');

        $data = $request->validate([
            'settings'         => 'required|array',
            'settings.*.key'   => ['required', 'string', 'exists:settings,key'],
            'settings.*.value' => 'nullable',
        ]);

        foreach ($data['settings'] as $item) {
            if (!\in_array($item['key'], self::ALLOWED_KEYS, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'مفتاح الإعداد غير مسموح به: ' . $item['key'],
                ], 422);
            }

            $value = $item['value'] ?? '';
            if (\is_string($value)) {
                $value = strip_tags($value);
            }

            Setting::set($item['key'], $value);
            Cache::forget('setting_' . $item['key']);
        }

        return response()->json(['success' => true, 'message' => __('pos.settings_saved')]);
    }

    public function group(string $group)
    {
        $allowed = ['general', 'tax', 'pos', 'invoice', 'stock', 'accounting'];
        if (!\in_array($group, $allowed, true)) {
            return response()->json([], 400);
        }
        return response()->json(['settings' => Setting::getGroup($group)]);
    }
}
