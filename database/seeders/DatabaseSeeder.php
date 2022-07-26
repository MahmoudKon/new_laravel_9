<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use App\Traits\UploadFile;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use UploadFile;
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        session()->flush();
        cache()->flush();
        $truncate_tables = ['emails'];

        foreach ($truncate_tables as $table) truncateTables($table);

        $this->call(LanguageSeeder::class);
        $this->call(GovernorateSeeder::class);
        $this->call(CitySeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(RouteSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(SuperadminSeeder::class);
        $this->call(ContentTypeSeeder::class);
        $this->call(SettingSeeder::class);
        Announcement::factory(30)->create();

        $images = $this->GetApiImage('people');
        User::factory(30)->create()->each(function ($user) use($images) {
            try {
                $index = array_rand($images);
                $user->update(['image' => $this->uploadApiImage($images[$index]['src']['medium'], 'users')]);
                $user->roles()->attach(Role::whereNotIn('name', SUPERADMIN_ROLES)->inRandomOrder()->first()->id);
            } catch (Exception $e) {}
        });

        Artisan::call('passport:install');
    }
}
