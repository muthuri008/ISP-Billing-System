<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class RadiusNas extends Model
{
    protected $table='radius_nas';
    protected $fillable=['router_id','nasname','shortname','type','secret','description'];
    protected function casts(): array { return ['secret'=>'encrypted']; }
    public function router(): BelongsTo { return $this->belongsTo(Router::class); }
}
