<?
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExternalLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ExternalLinkController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'model_slug' => 'required|string',
            'model_id' => 'required|integer',
            'expires_at' => 'nullable|date',
            'max_visits' => 'nullable|integer|min:1',
        ]);

        $link = ExternalLink::create([
            'token' => ExternalLink::generateToken(),
            'model_slug' => $request->model_slug,
            'model_id' => $request->model_id,
            'expires_at' => $request->expires_at,
            'max_visits' => $request->max_visits,
        ]);

        return response()->json([
            'url' => route('external.show', $link->token),
            'token' => $link->token,
            'expires_at' => $link->expires_at,
            'max_visits' => $link->max_visits,
        ]);
    }

    public function show($token, ObjectController $objectController, Request $request)
    {
        $link = ExternalLink::where('token', $token)->firstOrFail();

        if (!$link->isValid()) {
            abort(404, 'Link is expired or no longer available');
        }

        $link->incrementVisits();

        return $objectController->compose_show($link->model_slug, $link->model_id, new Request());
    }

    public function revoke($token)
    {
        $link = ExternalLink::where('token', $token)->firstOrFail();
        $link->update(['is_active' => false]);

        return response()->json(['message' => 'Link has been revoked']);
    }

    public function list(Request $request)
    {
        $query = ExternalLink::query();

        if ($request->model_slug) {
            $query->where('model_slug', $request->model_slug);
        }

        if ($request->model_id) {
            $query->where('model_id', $request->model_id);
        }

        return response()->json($query->paginate());
    }
}