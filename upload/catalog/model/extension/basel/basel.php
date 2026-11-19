<?php
require_once DIR_SYSTEM . 'library/special_price_history.php';
class ModelExtensionBaselBasel extends Model {
	public function getSpecialEndDate($product_id) {
	  $query = $this->db->query("SELECT date_end FROM " . \SpecialPriceHistory::table($this->config) . " active_special WHERE product_id = '" . (int)$product_id . "' AND customer_group_id = '" . $this->config->get('config_customer_group_id') . "' AND ((date_end > NOW())) ORDER BY priority ASC, price ASC LIMIT 1");
        
		if ($query->num_rows) {
            return array(
                'date_end'   => $query->row['date_end'],
            );
        } else {
            return false;
        }
	}
	

	
}