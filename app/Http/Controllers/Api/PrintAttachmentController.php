<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PrintAttachmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrintAttachmentController extends Controller
{
    public function index($slug, $id)
    {
        return response()->json(['data' => PrintAttachmentService::forObject((string) $slug, (int) $id)]);
    }

    public function store($slug, $id, Request $request)
    {
        if (!Schema::hasTable($slug) || !DB::table($slug)->where('id', (int) $id)->exists()) {
            return response()->json(['message' => 'Объект не найден'], 404);
        }
        $request->validate([
            'file' => 'required|file|max:20480|mimes:' . implode(',', PrintAttachmentService::EXTENSIONS),
            'copies' => 'nullable|integer|min:1|max:' . PrintAttachmentService::MAX_COPIES,
        ], [
            'file.mimes' => 'Можно загрузить PDF, JPG или PNG',
            'file.max' => 'Файл больше 20 МБ',
        ]);

        return response()->json(['data' => PrintAttachmentService::store((string) $slug, (int) $id, $request->file('file'), (int) $request->input('copies', 1))]);
    }

    public function update($slug, $id, $attachment, Request $request)
    {
        $row = PrintAttachmentService::find((string) $slug, (int) $id, (int) $attachment);
        if (!$row) {
            return response()->json(['message' => 'Документ не найден'], 404);
        }
        $request->validate(['copies' => 'required|integer|min:1|max:' . PrintAttachmentService::MAX_COPIES]);

        return response()->json(['data' => PrintAttachmentService::updateCopies($row, (int) $request->input('copies'))]);
    }

    public function destroy($slug, $id, $attachment)
    {
        $row = PrintAttachmentService::find((string) $slug, (int) $id, (int) $attachment);
        if (!$row) {
            return response()->json(['message' => 'Документ не найден'], 404);
        }
        PrintAttachmentService::delete($row);

        return response()->json(['success' => true]);
    }
}
