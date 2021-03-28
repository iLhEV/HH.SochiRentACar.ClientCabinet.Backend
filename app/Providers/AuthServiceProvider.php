<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\models\Clients;
use App\Http\models\ClientTokens;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest('client-token', function (Request $request) {
            $token = $request->bearerToken();
            if(!$token) return null;
            if(!$clientToken = ClientTokens::where('token', $token)->first()) return null;
            return Clients::where('id', $clientToken->client_id)->first();
        });
    }
}
