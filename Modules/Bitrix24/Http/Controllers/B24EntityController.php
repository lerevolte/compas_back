<?php

namespace Modules\Bitrix24\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Bitrix24\Services\B24EntitySync;

class B24EntityController extends Controller
{
    /**
     * Вебхук Bitrix24 по событиям сделок/контактов/компаний/реквизитов (ONCRMDEAL*,
     * ONCRMCONTACT*, ONCRMCOMPANY*, ONCRMREQUISITE*, ONCRMBANKDETAIL*). Без auth — тенант по домену.
     * URL: /api/bitrix24/entity-hook
     * Ручной вызов: /api/bitrix24/entity-hook?type=deal&id=123
     */
    public function entityHook(Request $request)
    {
        Log::channel('bitrix24')->info('entity-hook: hit', [
            'host'  => $request->getHost(),
            'event' => $request->input('event'),
            'input' => $request->all(),
        ]);

        if (!B24EntitySync::ready() && !\Modules\Bitrix24\Services\B24ProductSync::ready()) {
            return response('entity sync is not configured', 200);
        }

        $event = strtoupper((string) $request->input('event', ''));
        $id = data_get($request->input('data'), 'FIELDS.ID') ?: $request->input('id');
        $type = $request->input('type');
        if (!$type) {
            if (str_contains($event, 'PRODUCT')) {
                $type = 'product';
            } elseif (str_contains($event, 'INVOICE')) {
                $type = 'invoice';
            } elseif (str_contains($event, 'BANKDETAIL')) {
                $type = 'bankdetail';
            } elseif (str_contains($event, 'REQUISITE')) {
                $type = 'requisite';
            } elseif (str_contains($event, 'DEAL')) {
                $type = 'deal';
            } elseif (str_contains($event, 'CONTACT')) {
                $type = 'contact';
            } elseif (str_contains($event, 'COMPANY')) {
                $type = 'company';
            }
        }
        if (!$type || !$id) {
            return response('no type or id', 200);
        }

        \Modules\Bitrix24\Jobs\ProcessEntityHook::dispatch(
            (string) tenant('id'),
            $type,
            (string) $id,
            str_ends_with($event, 'DELETE')
        );

        return response()->json(['status' => 'queued', 'type' => $type, 'id' => $id]);
    }

    /**
     * Список стадий сделок (value/label/color) для шкалы в карточке.
     */
    public function stages()
    {
        $svc = B24EntitySync::make();
        return response()->json($svc ? $svc->stageOptions() : []);
    }

    /**
     * Смена стадии сделки: улетает в Bitrix24, локально пишется история событий.
     */
    public function changeStage($id, Request $request)
    {
        $request->validate(['stage' => 'required|string']);

        $svc = B24EntitySync::make();
        if (!$svc) {
            return response()->json(['message' => 'Синхронизация Bitrix24 не настроена'], 422);
        }

        $deal = \App\Models\Deal::findOrFail($id);

        try {
            $stage = $svc->changeStage($deal, $request->input('stage'), Auth::user()?->id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            Log::channel('bitrix24')->error('change-stage: failed', [
                'deal_id' => $id, 'error' => $e->getMessage(),
            ]);
            return response()->json(['message' => 'Не удалось изменить стадию в Bitrix24'], 502);
        }

        return response()->json(['status' => 'ok', 'stage' => $stage]);
    }
}
