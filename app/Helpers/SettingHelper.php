<?php

namespace App\Helpers;

use App\Models\Language;
use App\Models\Menu;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class SettingHelper
{
    public static function setSettingCache() :void
    {
        $website_settings = $list_menus = $active_languages = [];

        if (! app()->runningInConsole()) {
            $website_settings = self::setSettings();
            $list_menus       = self::setMenus();
            $active_languages = self::setLanguages();
        }

        self::setRoutePrefix($website_settings['route_prefix'] ?? '');
        self::shareValues($website_settings, $list_menus, $active_languages);
    }

    public static function setSettings() :array
    {
        return Cache::remember('website_settings', 60 * 60 * 24, function () {
                    return Setting::active()->autoload()->pluck('value', 'key')->toArray();
                });
    }

    public static function setMenus() :Collection|array
    {
        return Cache::remember('list_menus', 60 * 60 * 24, function () {
                    return Schema::hasTable('menus')
                            ? Menu::with('visibleSubs')->parent()->getVisible()->get()
                            : [];
                });
    }

    public static function setLanguages() :array
    {
        return Cache::remember('active_languages', 60 * 60 * 24, function () {
                    $data = [];
                    foreach (Language::active()->orderBy('short_name', 'ASC')->get() as $lang) {
                        $data[$lang->short_name] = [
                            'short_name' => $lang->short_name,
                            'icon' => $lang->icon,
                            'name' => $lang->name,
                            'native' => $lang->native,
                        ];
                    }
                    return $data;
                });
    }

    public static function setRoutePrefix(?string $prefix) :void
    {
        $prefix = $prefix ?? '';
        $prefix = str_replace(' ', '_', strtolower($prefix));
        define('URL_PREFIX', $prefix);
        $prefix_route = $prefix ? "$prefix." : '';
        define('ROUTE_PREFIX', $prefix_route);
    }

    public static function shareValues(array $website_settings, $list_menus, array $active_languages) :void
    {
        View::share([
            'website_settings'  => $website_settings,
            'list_menus'        => $list_menus,
            'active_languages'  => $active_languages,
            'successAudio'      => $website_settings['success_audio'] ?? '',
            'warrningAudio'     => $website_settings['warrning_audio'] ?? '',
            'notificationAudio' => $website_settings['notification_audio'] ?? '',
            'settingLogo'       => $website_settings['logo'] ?? ''
        ]);
    }
}
