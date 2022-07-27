<?php


namespace Agmedia\Features;


use Agmedia\Helpers\Log;

class AttributeHelper
{

    /**
     * @param array $attributes
     *
     * @return string
     */
    public static function resolveScale(array $attributes, string $tag = 'Skala'): string
    {
        foreach ($attributes as $attribute) {
            if ($attribute->naziv == $tag) {
                return $attribute->vrijednost;
            }
        }

        return '';
    }


    /**
     * @param array  $attributes
     * @param string $tag
     *
     * @return int
     */
    public static function resolveMin(array $attributes, string $tag = 'Min_kol_web'): int
    {
        foreach ($attributes as $attribute) {
            if ($attribute->naziv == $tag) {
                return intval($attribute->vrijednost);
            }
        }

        return 0;
    }


    /**
     * @param array  $attributes
     * @param string $tag
     *
     * @return int
     */
    public static function resolveZdravo(array $attributes, string $tag = 'Web_zdravo'): string
    {
        foreach ($attributes as $attribute) {
            if ($attribute->naziv == $tag) {
                return (string)$attribute->vrijednost;
            }
        }

        return '';
    }
    
}