<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RadiusAccounting extends Model
{
    protected $table='radius_accounting';
    protected $fillable=['radius_user_id','router_id','acct_session_id','username','nas_ip_address','framed_ip_address','acct_start_time','acct_stop_time','acct_input_octets','acct_output_octets','acct_terminate_cause'];
    protected function casts(): array { return ['acct_start_time'=>'datetime','acct_stop_time'=>'datetime','acct_input_octets'=>'integer','acct_output_octets'=>'integer']; }
    public function radiusUser(): BelongsTo { return $this->belongsTo(RadiusUser::class); }
    public function router(): BelongsTo { return $this->belongsTo(Router::class); }
}
