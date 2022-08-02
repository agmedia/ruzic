<?php
class ModelLocalisationZone extends Model {
	public function getZone($zone_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE zone_id = '" . (int)$zone_id . "' AND status = '1'");

		return $query->row;
	}

	public function getZonesByCountryId($country_id) {
		$zone_data = false;//$this->cache->get('zone.' . (int)$country_id);

		if (!$zone_data) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone WHERE country_id = '" . (int)$country_id . "' AND status = '1' ORDER BY name");

			$zone_data = $query->rows;

			$session = isset($this->session->data['delivery_region']) ? $this->session->data['delivery_region'] : null;
			
			//\Agmedia\Helpers\Log::info($zone_data);
			
			$zone_data = \Agmedia\Features\Helper::resolveZoneList($zone_data, $session);
			//$this->cache->set('zone.' . (int)$country_id, $zone_data);
            
            \Agmedia\Helpers\Log::info($zone_data);
		}

		return $zone_data;
	}
}