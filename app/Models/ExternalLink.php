<?
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalLink extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'model_slug',
        'model_id',
        'expires_at',
        'max_visits',
        'visits',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public static function generateToken()
    {
        do {
            $token = \Str::random(64);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public function isValid()
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_visits && $this->visits >= $this->max_visits) {
            return false;
        }

        return true;
    }

    public function incrementVisits()
    {
        $this->increment('visits');
    }
}