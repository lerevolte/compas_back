<?
namespace App\Http\Middleware;

class TenantKeeper
{
    public function handle(\Illuminate\Http\Request $request, \Closure $next)
    {
        
        //date_default_timezone_set('Europe/Moscow');
        // $tenant = tenant('id');

        // config([
        //     'littlegatekeeper.username' => $tenant->getLittleGateKeeperUsername(),
        //     'littlegatekeeper.password' => $tenant->getLittleGateKeeperPassword(),
        //     'littlegatekeeper.session_key' => $tenant->getLittleGateKeeperSessionKey(),
        // ]);
        $user = \Auth::user();
        if($user) {
            if($user->token_expiration_date <= date('Y-m-d H:i:s')) {
                $user->api_token = null;
                $user->save();
            }
        }

        if(strstr($request->path(), 'api/objects') || strstr($request->path(), 'api/gibdd')) {
            //info('balance '.$request->path());
            $balance = \App\Models\Balance::first();
            $info = $balance->getInfo(new \Illuminate\Http\Request);
            if($info['current_tariff'] != 1 && !$info['balance'] && $request->path() != 'api/objects/users/compose' && $request->path() != 'api/objects/documents/compose') {
                info('balance '.$request->path());
                return response()->json([
                    'message' => 'balance is zero'
                ], 403);
            }
            //info($info);
        }
        
        return $next($request);
    }
}
