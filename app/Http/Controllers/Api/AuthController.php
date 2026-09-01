<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\PasswordResetRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\CrudService;

class AuthController extends Controller
{
    // public function login(Request $request)
    // {
    //     if($request->email) {
    //         $user = User::whereRaw('LOWER(email) like ?', '%'.mb_strtolower($request->email).'%')->first();
    //         if(!$user) {
    //             $node = '$.value';
    //             $user = User::whereRaw("JSON_EXTRACT(LOWER(email), '{$node}') = '". mb_strtolower($request->email)."'")->first();

    //         }
    //            //$user = User::whereRaw('LOWER(email->"$.value")) like ?', '%'.mb_strtolower($request->email).'%')->first();
    //             //$user = User::whereJsonContains('email->value', $request->email)->first();
   
    //         if($user && $user->password && $user->password == Hash::check($request->password, $user->password)) {
    //             $menu = $user->getSidebar();
    //             $token = $user->api_token;
    //             if(!$token) {
    //                 $token = $user->generateToken();
    //             }

    //             info($menu);
    //             return response()->json(['code' => 200, 'token' => $token, 'url' => $menu[0]['link']]);
    //         }
    //     }

    //     return response()->json(['code' => 401, 'message' => 'Неверный логин или пароль']);
    // }

    public function login(LoginRequest $request)
    {
        $user = User::whereRaw('LOWER(email) like ?', '%'.mb_strtolower($request->email).'%')->first();
        // if(!$user) {
        //     $node = '$.value';
        //     $user = User::whereRaw("JSON_EXTRACT(LOWER(email), '{$node}') = '". mb_strtolower($request->email)."'")->first();

        // }
           //$user = User::whereRaw('LOWER(email->"$.value")) like ?', '%'.mb_strtolower($request->email).'%')->first();
            //$user = User::whereJsonContains('email->value', $request->email)->first();

        if($user && $user->password && $user->password == Hash::check($request->password, $user->password)) {
            $menu = $user->getSidebar();
            $token = $user->api_token;
            if(!$token) {
                $token = $user->generateToken();
            }
            if($user->id == 1 && tenant('id')) {
                $tenant = tenant('id');
                tenancy()->central(function () use ($tenant) {
                    $crudService = new CrudService;
                    $account = Account::where('tenant_id', $tenant)->first();
                    $data = [
                        'id' => $account->id,
                        'last_login' => date('Y-m-d H:i:s')
                    ];

                    $result = $crudService->batch('accounts', [$data]);
                });
            }
            $first_active_menu = null;
            foreach ($menu as $key => $value) {
                if(isset($value['enabled']) && $value['enabled']) {
                    $first_active_menu = $value;
                    break;
                }
            }
            $response = response()->json([
                'code' => 200,
                'token' => $token,
                'url' => isset($first_active_menu['is_group']) && $first_active_menu['is_group'] ?
                            (isset($first_active_menu['children'][0]['link']) ?
                                $first_active_menu['children'][0]['link']
                                : null
                            ) : (isset($first_active_menu) ? $first_active_menu['link'] : null)
                        ,
                'group' => isset($first_active_menu['is_group']) && $first_active_menu['is_group'] ? $first_active_menu['id'] : null
            ]);

            return $this->withAppCookies($response, $user->id, tenant('id'));
        }
        if($user && $user->password && $user->password != Hash::check($request->password, $user->password)) {
            throw new HttpResponseException(response()->json([
                'success'   => false,
                'message'   => 'Ошибки валидации',
                'data'      => [
                    'password' => 'Неверный пароль'
                ]
            ]));
        } else {
            throw new HttpResponseException(response()->json([
                'success'   => false,
                'message'   => 'Ошибки валидации',
                'data'      => [
                    'email' => 'Неверный email'
                ]
            ]));
        }
        
    }

    public const APP_COOKIE_MINUTES = 2628000;

    private function withAppCookies($response, $userId, $accountId)
    {
        $domain = $this->appCookieDomain();
        foreach (['user_id' => $userId, 'account_id' => $accountId] as $name => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $response = $response->withCookie(cookie($name, (string) $value, self::APP_COOKIE_MINUTES, '/', $domain, null, false, false, 'lax'));
        }

        return $response;
    }

    private function appCookieDomain(): ?string
    {
        $host = request()->getHost();
        foreach ((array) config('tenancy.central_domains', []) as $central) {
            $central = ltrim((string) $central, '.');
            if ($central === '' || $central === 'localhost' || filter_var($central, FILTER_VALIDATE_IP)) {
                continue;
            }
            if ($host === $central || str_ends_with($host, '.' . $central)) {
                $parts = explode('.', $central);
                $root = implode('.', array_slice($parts, -2));

                return '.' . $root;
            }
        }

        return null;
    }

    public function loginByUser($id)
    {
        if(Auth::user()->isAdmin()) {
            $user = User::where('id', $id)->firstOrFail();

            $menu = $user->getSidebar();
            $token = $user->api_token;
            if(!$token) {
                $token = $user->generateToken();
            }

            $first_active_menu = null;
            foreach ($menu as $key => $value) {
                if(isset($value['enabled']) && $value['enabled']) {
                    $first_active_menu = $value;
                    break;
                }
            }
            $response = response()->json([
                'code' => 200,
                'token' => $token,
                'url' => isset($first_active_menu['is_group']) && $first_active_menu['is_group'] ?
                            (isset($first_active_menu['children'][0]['link']) ?
                                $first_active_menu['children'][0]['link']
                                : null
                            ) : $first_active_menu['link']
                        ,
                'group' => isset($first_active_menu['is_group']) && $first_active_menu['is_group'] ? $first_active_menu['id'] : null
            ]);

            return $this->withAppCookies($response, $user->id, tenant('id'));
        }

        return response()->json(['code' => 403, 'message' => 'Доступ запрещен']);
    }

    public function loginByAccount($tenant)
    {
        if(Auth::user()->isAdmin()) {
            $tenant = \App\Models\Tenant::find($tenant);
            $res = $tenant->run(function ($tenant) {
                $user = \App\Models\User::where('id', 1)->firstOrFail();
                $menu = $user->getSidebar();
                $token = $user->api_token;
                if(!$token) {
                    $token = $user->generateToken();
                }
                $first_active_menu = null;
                foreach ($menu as $key => $value) {
                    if(isset($value['enabled']) && $value['enabled']) {
                        $first_active_menu = $value;
                        break;
                    }
                }
                return [
                    'code' => 200,
                    'token' => $token,
                    'user_id' => $user->id,
                    'url' => isset($first_active_menu['is_group']) && $first_active_menu['is_group'] ?
                                (isset($first_active_menu['children'][0]['link']) ?
                                    $first_active_menu['children'][0]['link']
                                    : null
                                ) : $first_active_menu['link']
                            ,
                   'group' => isset($first_active_menu['is_group']) && $first_active_menu['is_group'] ? $first_active_menu['id'] : null
                ];
            });
            $userId = $res['user_id'];
            unset($res['user_id']);

            return $this->withAppCookies(response()->json($res), $userId, $tenant->id);
        };

        return response()->json(['code' => 403, 'message' => 'Доступ запрещен']);
    }

    public function password_forgot(Request $request)
    {
        if($request->email) {
            $user = User::whereRaw('LOWER(email) like ?', '%'.mb_strtolower($request->email).'%')->first();
            if(!$user) {
                throw new HttpResponseException(response()->json([
                    'success'   => false,
                    'message'   => 'Ошибки валидации',
                    'data'      => [
                        'email' => 'Неверный email'
                    ]
                ]));
            }

            $status = Password::sendResetLink(array('email' => $request->email));

            return $status === Password::RESET_LINK_SENT
                ? response()->json(['status' => __($status)])
                : response()->json(['error' => __($status)]);
            
        }

        return response()->json(['code' => 401, 'message' => 'Неверный email']);
    }

    public function password_reset(PasswordResetRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
                    ? response()->json(['status' => __($status)])
                    : response()->json(['error' => __($status)]);
    }

    public function token_expired(Request $request)
    {
        $ob = \DB::table('password_resets')->where('email', $request->email)->first();

        return response()->json(['is_valid' => !\Carbon\Carbon::parse($ob->created_at)->addSeconds(3600)->isPast()]);
    }
}
