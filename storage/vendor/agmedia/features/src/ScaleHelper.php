<?php


namespace Agmedia\Features;


use Agmedia\Helpers\Log;

class ScaleHelper
{
    
    /**
     * @param string|null $key
     *
     * @return array|null
     */
    public static function get(string $key = null): array
    {
        $scale = (include 'scale.php');
        // If scale is array and not empty,
        // key is not null, and scale has the key of $key.
        if (is_array($scale) && ! empty($scale) && $key && array_key_exists($key, $scale)) {
            return $scale[$key];
        }
        
        return $scale;
    }


    /**
     * @param string $scale
     *
     * @return int
     */
    public static function resolveOptionId(string $scale): int
    {
        switch ($scale) {
            case 'A':
                return 1;
            case 'B':
                return 2;
            case 'C':
                return 3;
            default:
                return 0;
        }
    }
    
}