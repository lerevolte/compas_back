<?
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use TCG\Voyager\Facades\Voyager;
use Auth;

class Permission extends \TCG\Voyager\Models\Permission
{
    protected $guarded = [];

    public function roles()
    {
        return $this->belongsToMany(Voyager::modelClass('Role'));
    }

    public function childs()
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }

    public static function newEntityRow(int $entityId, int $roleId, ?bool $isAdminRole = null): array
    {
        if ($isAdminRole === null) {
            $isAdminRole = (bool) \DB::table('roles')->where('id', $roleId)->value('is_admin');
        }
        $value = $isAdminRole ? 'A' : 'N';

        return [
            'entity_id' => $entityId,
            'role_id' => $roleId,
            'read_p' => $value,
            'create_p' => $value,
            'update_p' => $value,
            'delete_p' => $value,
            'export_p' => $value,
            'import_p' => $value,
        ];
    }

}