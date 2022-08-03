<?php


namespace Agmedia\Features;


use Agmedia\Helpers\Log;

class Helper
{
    
    /**
     * @param int $target
     * @param     $session
     *
     * @return string
     */
    public static function resolveSession(int $target, $session): string
    {
        $set = false;
    
        foreach (agconf('delivery_region') as $item) {
            if ($target == $item['id']) {
                $set = true;
            }
        }
        
        if ($set) {
            foreach (agconf('delivery_region') as $item) {
                if ($item['id'] == $target) {
                    return $item['label'];
                }
            }
        }
        
        if ($session) {
            return $session;
        }

        return 'croatia';
    }
    
    
    /**
     * @param array $zones
     * @param       $session
     *
     * @return array
     */
    public static function resolveZoneList(array $zones, $session): array
    {
        if ( ! $session) {
            return $zones;
        }
        
        $response = [];
    
        foreach ($zones as $key => $zone) {
            foreach (agconf('shipping_collector_regions') as $item) {
                if ($zone['code'] == $item['code']) {
                    $response[$key] = $zone;
                }
            }
        }
        
        if ($session == 'croatia') {
            $response = collect($zones)->diffKeys($response)->toArray();
        }
    
        $response = array_values($response);
    
        return $response;
    }
    
}