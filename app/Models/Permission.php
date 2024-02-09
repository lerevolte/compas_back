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

    public static function generateFor($table_name)
    {
        self::firstOrCreate(['key' => 'browse_'.$table_name, 'entity' => $table_name]);
        self::firstOrCreate(['key' => 'read_'.$table_name, 'entity' => $table_name]);
        self::firstOrCreate(['key' => 'edit_'.$table_name, 'entity' => $table_name]);
        self::firstOrCreate(['key' => 'add_'.$table_name, 'entity' => $table_name]);
        self::firstOrCreate(['key' => 'delete_'.$table_name, 'entity' => $table_name]);
    }

    public static function removeFrom($table_name)
    {
        self::where(['entity' => $table_name])->delete();
    }

    public static function getPermName(string $value = '')
    {
        switch ($value) {
            case 'A':
                $perm_name = 'Свои';
                break;
            case 'O':
                $perm_name = 'Eсть доступ';
                break;
            
            default:
                $perm_name = 'Нет доступа';
                break;
        }
        
        return $perm_name;
    }
}