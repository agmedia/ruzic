<?php


namespace Agmedia\Features\Models;


use Agmedia\Helpers\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ZoneBlock extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'zone_block';
    
    /**
     * @var string
     */
    protected $primaryKey = 'zone_block_id';
    
    /**
     * @var array
     */
    protected $guarded = [
        'zone_block_id'
    ];
    
    
    /**
     * @param int $days
     *
     * @return array
     */
    public static function getList(int $zone): array
    {
        return self::where('status', 1)
            ->where('zone_id', $zone)
            ->get()->toArray();
        
    }

}