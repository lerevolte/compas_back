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

}