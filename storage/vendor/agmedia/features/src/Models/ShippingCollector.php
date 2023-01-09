<?php


namespace Agmedia\Features\Models;


use Agmedia\Helpers\Log;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ShippingCollector extends Model
{
    
    /**
     * @var string
     */
    protected $table = 'shipping_collector';
    
    /**
     * @var string
     */
    protected $primaryKey = 'shipping_collector_id';
    
    /**
     * @var array
     */
    protected $guarded = [
        'shipping_collector_id'
    ];
    
    public $timestamps = false;


    /**
     * @param string $destination
     * @param int    $days
     * @param bool   $raw
     *
     * @return array
     */
    public static function getList(string $destination = 'zapad', int $days = 15, bool $raw = false): array
    {
        $response = [];
        $list     = self::getCleanList($destination, $days);
        
        foreach ($list as $item) {
            if ($item->collected < $item->collect_max) {
                $date = Carbon::make($item->collect_date);

                if ( ! self::isToday($date)) {
                    if ($raw) {
                        $response[] = $item;
                    } else {
                        $response[] = self::response($date, $item);
                    }
                }
            }
        }
        
        return $response;
    }


    /**
     * @param $shipping_collector_id
     *
     * @return string
     */
    public static function getTitle($shipping_collector_id): string
    {
        $item = self::where('shipping_collector_id', $shipping_collector_id)->first();

        if ($item) {
            $date = Carbon::make($item->collect_date);
            $text = self::response($date, $item);

            return $text['label'];
        }

        return 'Kolektor nije pronađen.';
    }
    
    
    /**
     * @param int $id
     *
     * @return string
     */
    public static function setLabelByID(int $id): string
    {
        foreach (agconf('shipping_collector_regions') as $item) {
            if ($item['id'] == $id) {
                return $item['label'];
            }
        }
        
        return 'istok';
    }
    
    
    /**
     * @param string $code
     *
     * @return string
     */
    public static function setLabelByCode(string $code): string
    {
        foreach (agconf('shipping_collector_regions') as $item) {
            if ($item['code'] == $code) {
                return $item['label'];
            }
        }
    
        return 'istok';
    }

    /*******************************************************************************
    *                                Copyright : AGmedia                           *
    *                              email: filip@agmedia.hr                         *
    *******************************************************************************/

    /**
     * @param string $destination
     * @param int    $days
     *
     * @return Collection
     */
    private static function getCleanList(string $destination = 'zapad', int $days = 15): Collection
    {
        return self::where('status', 1)
                   ->where('collect_destination', $destination)
                   ->where('collect_date', '>', Carbon::now())
                   ->where('collect_date', '<', Carbon::now()->addDays($days))
                   ->orderBy('collect_date')->orderBy('shipping_collector_id', 'desc')->get();
    }
    
    
    /**
     * @param Carbon $date
     * @param        $item
     *
     * @return array
     */
    private static function response(Carbon $date, $item): array
    {
        return [
            'label' => self::localeDay($date->localeDayOfWeek) . ', ' . $date->format('d.m.Y.') . ' - ' . $item->collect_time,
            'value' => $item->shipping_collector_id
        ];
    }
    
    
    /**
     * @param Carbon $date
     *
     * @return bool
     */
    private static function isToday(Carbon $date): bool
    {
        if ($date->format('d') == Carbon::now()->format('d')) {
            return true;
        }
        
        return false;
    }
    
    
    /**
     * @param string $day
     *
     * @return string
     */
    private static function localeDay(string $day): string
    {
        switch ($day) {
            case 'Monday':
                return 'Ponedeljak';
            case 'Tuesday':
                return 'Utorak';
            case 'Wednesday':
                return 'Srijeda';
            case 'Thursday':
                return 'Četvrtak';
            case 'Friday':
                return 'Petak';
            case 'Saturday':
                return 'Subota';
            case 'Sunday':
                return 'Nedelja';
            default:
                return '';
        }
    }
}