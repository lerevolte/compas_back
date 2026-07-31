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
use App\Services\PhoneVerificationService;
use App\Services\TenantService;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Mail\AccountRegistered;

class RegistrationController extends Controller
{
    //private CrudService $crudService;
    private TenantService $tenantService;
    private PhoneVerificationService $phoneVerification;

    public function __construct(TenantService $tenantService, PhoneVerificationService $phoneVerification)
    {
       // $this->crudService = $crudService;
        $this->tenantService = $tenantService;
        $this->phoneVerification = $phoneVerification;
    }

    public function __invoke(RegistrationRequest $request)
    {
        $data = $request->toArray();

        if ($this->phoneVerification->enabled()) {
            if (empty($data['phone'])) {
                $this->fail(['phone' => ['Укажите номер телефона']]);
            }
            if (!$this->phoneVerification->check($data['phone'], $data['verification_token'] ?? null)) {
                $this->fail(['phone' => ['Подтвердите номер телефона']]);
            }
        }

        $this->checkDailyLimit($data);

        info('beforecreate');
        $res = $this->tenantService->create($data);
        $this->phoneVerification->markUsed($data['verification_token'] ?? null);
        info('aftercreate');
        $res['user_id'] = 1;
        $res['account_id'] = tenant('id');

        return response()->json($res)->withCookie(cookie('user_id', 1, 60))->withCookie(cookie('account_id', tenant('id'), 60));
    }

    private function checkDailyLimit(array $data): void
    {
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $phone = PhoneVerificationService::normalizePhone($data['phone'] ?? null);

        $recent = \DB::table('accounts')
            ->where('created_at', '>=', now()->subDay())
            ->get(['phone', 'email']);

        foreach ($recent as $account) {
            if ($email !== '' && mb_strtolower(trim((string) $account->email)) === $email) {
                $this->fail(['email' => ['На эту почту уже зарегистрирован портал сегодня. Попробуйте завтра']]);
            }
            if ($phone && PhoneVerificationService::normalizePhone($account->phone) === $phone) {
                $this->fail(['phone' => ['На этот номер уже зарегистрирован портал сегодня. Попробуйте завтра']]);
            }
        }
    }

    private function fail(array $errors): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Ошибки валидации',
            'data' => $errors,
        ]));
    }
}
