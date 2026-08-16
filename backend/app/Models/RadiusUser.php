<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RadiusUser extends Model
{
    protected $table='radius_users';
    protected $fillable=['service_account_id','username','auth_type','password','status'];
    protected function casts(): array { return ['password'=>'encrypted']; }
    public function serviceAccount(): BelongsTo { return $this->belongsTo(ServiceAccount::class); }
}
