<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class SocialLoginController extends Controller
{
    public function redirectToProvider(String $provider) {
        session(['provider' => $provider]);
        return Socialite::driver($provider)->redirect();
    }

    public function providerCallback(){
        try{
            $provider = $this->getProvider();
            $social_user = Socialite::driver($provider)->user();

            if ( $this->checkSocialAccountExists($provider, $social_user->getId()) )
                return redirect()->route(ROUTE_PREFIX.'/');

            $this->checkOrCreateUser($provider, $social_user);
            return redirect()->route(ROUTE_PREFIX.'/');

        }catch(\Exception $e){
            return redirect()->route('login');
        }
    }

    protected function getProvider()
    {
        $provider = session('provider');
        session()->forget('provider');
        $prev_url = request()->session()->previousUrl();
        return $provider ?? last( explode('/', $prev_url) );
    }

    protected function checkSocialAccountExists($provider, $provider_id)
    {
        // First Find Social Account
        $account = SocialAccount::where([
            'provider_name'=>$provider,
            'provider_id'=>$provider_id
        ])->first();

        // If Social Account Exist then Find User and Login
        if($account){
            auth()->login($account->user);
            return true;
        }

        return false;
    }

    protected function checkOrCreateUser($provider, $social_user)
    {
        // Find User
        $user = User::firstOrCreate(['email' => $social_user->email], [
            'name'              => $social_user->name,
            'email'             => $social_user->email,
            'password'          => $provider,
            'image'             => $social_user->avatar,
            'email_verified_at' => now(),
            'remember_token'    => now(),
        ]);

        // Create Social Accounts
        $user->socialAccounts()->create([
            'provider_id'   => $social_user->getId(),
            'provider_name' => $provider
        ]);

        // Login
        auth()->login($user);
    }
}