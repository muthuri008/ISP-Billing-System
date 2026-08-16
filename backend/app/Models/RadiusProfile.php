<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RadiusProfile extends Model
{
    protected $table='radius_profiles';
    protected $fillable=['package_id','name','download_speed','upload_speed','session_timeout','data_limit_bytes','attributes'];
    protected function casts(): array { return ['session_timeout'=>'integer','data_limit_bytes'=>'integer','attributes'=>'array']; }
    public function package(): BelongsTo { return $this->belongsTo(Package::class); }
}
