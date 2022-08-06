<?php


namespace Agmedia\Features;


use Agmedia\Helpers\Database;
use Agmedia\Helpers\Log;

class CartHelper
{
    
    
    public static function checkRegion($customer, $session)
    {
        $db = new Database();
        $res = '';
        
        $carts = $db->query("SELECT * FROM " . DB_PREFIX . "cart WHERE api_id = '" . (isset($session->data['api_id']) ? (int)$session->data['api_id'] : 0) . "' AND customer_id = '" . (int)$customer->getId() . "' AND session_id = '" . $db->escape($session->getId()) . "'");
        
        if ($carts->num_rows) {
            foreach ($carts->rows as $cart) {
                if ($cart['region']) {
                    $res = $cart['region'];
                }
            }
        }
        
        return $res;
    }
    
    
}