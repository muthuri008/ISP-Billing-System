<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ServiceAccount extends Model
{
    protected $fillable=['customer_id','subscription_id','router_id','username','password_hash','access_type','status','mac_address','ip_address','radius_profile','last_provisioned_at','provisioning_metadata'];
    protected function casts(): array { return ['password_hash'=>'encrypted','last_provisioned_at'=>'datetime','provisioning_metadata'=>'array']; }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function subscription(): BelongsTo { return $this->belongsTo(Subscription::class); }
    public function router(): BelongsTo { return $this->belongsTo(Router::class); }
    public function sessions(): HasMany { return $this->hasMany(NetworkSession::class); }
}
