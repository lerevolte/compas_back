<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Validator;
use Storage;
use Auth;
use Mail;
use App\Helpers\ValueHelper;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegistrationRequest;
use App\Services\CrudService;
use App\Services\TenantService;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Mail\AccountRegistered;

class RegistrationController extends Controller
{
    //private CrudService $crudService;
    private TenantService $tenantService;

    public function __construct(TenantService $tenantService)
    {
       // $this->crudService = $crudService;
        $this->tenantService = $tenantService;
    }

    public function __invoke(RegistrationRequest $request)
    {
        $data = $request->toArray();
        $res = $this->tenantService->create($data);
        $res['user_id'] = 1;
        $res['account_id'] = tenant('id');

        return response()->json($res)->withCookie(cookie('user_id', 1, 60))->withCookie(cookie('account_id', tenant('id'), 60));
    }
}
