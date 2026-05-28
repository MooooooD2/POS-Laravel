<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves whether the current tenant's plan grants access to a named feature.
 *
 * Feature flags are stored as a JSON array on the plans table (new column `feature_flags`).
 * When that column is absent (old plans that only have a marketing-text `features` array)
 * we fall back to the tier-based defaults below so the system degrades gracefully.
 *
 * Usage:
 *   PlanFeatureService::has('hr_module')     // bool
 *   PlanFeatureService::check('crm')         // throws 403 if missing
 *
 * Blade:
 *   @planFeature('hr_module') … @endplanFeature
 */
class PlanFeatureService
{
    /**
     * Default feature flags per plan tier (used when the DB row has no feature_flags column).
     * Keys = plan IDs, values = list of enabled features.
     */
    private const PLAN_DEFAULTS = [
        'basic' => [
            'pos',
            'inventory',
            'returns',
            'expenses',
            'reports_basic',
            'customers',
        ],
        'pro' => [
            'pos',
            'inventory',
            'returns',
            'expenses',
            'reports_basic',
            'reports_advanced',
            'customers',
            'customer_groups',
            'promotions',
            'cashback',
            'accounting',
            'purchase_orders',
            'multi_warehouse',
            'whatsapp',
            'kitchen_display',
            'qr_ordering',
            'kiosk',
            'crm',
            'dynamic_pricing',
            'pricing_rules',
            'waste_tracking',
        ],
        'enterprise' => [
            'pos',
            'inventory',
            'returns',
            'expenses',
            'reports_basic',
            'reports_advanced',
            'reports_financial',
            'customers',
            'customer_groups',
            'promotions',
            'cashback',
            'accounting',
            'purchase_orders',
            'multi_warehouse',
            'multi_branch',
            'whatsapp',
            'kitchen_display',
            'qr_ordering',
            'kiosk',
            'crm',
            'dynamic_pricing',
            'pricing_rules',
            'waste_tracking',
            'hr_module',
            'payroll',
            'shift_management',
            'white_label',
            'currencies',
            'franchise',
            'ai_forecasting',
            'budget_vs_actual',
            'device_sessions',
            'backup_monitor',
        ],
    ];

    /**
     * Returns the set of features enabled for the current tenant's plan.
     */
    public static function features(): array
    {
        $tenant = tenancy()->tenant;

        // Master tenant always has all features
        $masterId = config('tenancy.master_tenant');
        if ($masterId && $tenant?->id === $masterId) {
            return array_merge(...array_values(self::PLAN_DEFAULTS));
        }

        $planId = $tenant?->plan ?? 'basic';

        return Cache::remember("plan_features:{$planId}", 3600, function () use ($planId) {
            // Try to load feature_flags from the DB (column may not exist yet)
            try {
                $plan = Plan::find($planId);
                if ($plan && ! empty($plan->feature_flags)) {
                    return (array) $plan->feature_flags;
                }
            } catch (\Throwable) {
                // Column doesn't exist yet — use defaults
            }

            return self::PLAN_DEFAULTS[$planId] ?? self::PLAN_DEFAULTS['basic'];
        });
    }

    /**
     * Check whether a single feature is enabled.
     */
    public static function has(string $feature): bool
    {
        return in_array($feature, self::features(), true);
    }

    /**
     * Abort with 403 + a helpful JSON/redirect response when the feature is not in the plan.
     */
    public static function check(string $feature): void
    {
        if (! static::has($feature)) {
            $message = __('pos.feature_not_in_plan', ['feature' => $feature]);
            if (request()->expectsJson() || request()->is('api/*')) {
                abort(response()->json(['success' => false, 'message' => $message, 'upgrade_required' => true], 403));
            }
            abort(403, $message);
        }
    }
}
