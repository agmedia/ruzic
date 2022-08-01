<?php
class ModelExtensionModuleShippingCollector extends Model {
    
    public function addShippingCollector($data) {
        $this->db->query("INSERT INTO " . DB_PREFIX . "shipping_collector SET
		                    collect_date = '" . $this->db->escape($data['collect_date']) . "',
		                    collect_time = '" . $this->db->escape($data['collect_time']) . "',
		                    collect_destination = '" . $this->db->escape($data['collect_destination']) . "',
		                    collect_max = '" . (int)$data['collect_max'] . "',
		                    collected = 0,
		                    price = 0,
		                    status = '" . (int)$data['status'] . "',
		                    date_added = NOW(),
		                    date_modified = NOW()");
        
        $shipping_collector_id = $this->db->getLastId();
        
        return $shipping_collector_id;
    }

	public function editShippingCollector($shipping_collector_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "shipping_collector SET
		                    collect_date = '" . $this->db->escape($data['collect_date']) . "',
		                    collect_time = '" . $this->db->escape($data['collect_time']) . "',
		                    collect_destination = '" . $this->db->escape($data['collect_destination']) . "',
		                    collect_max = '" . (int)$data['collect_max'] . "',
		                    collected = '" . (int)$data['collected'] . "',
		                    price = '" . (float)$data['price'] . "',
		                    status = '" . (int)$data['status'] . "',
		                    date_modified = NOW() WHERE shipping_collector_id = '" . (int)$shipping_collector_id . "'");
	}

	public function deleteShippingCollector($shipping_collector_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "shipping_collector WHERE shipping_collector_id = '" . (int)$shipping_collector_id . "'");
	}

	
	public function getShippingCollector($shipping_collector_id)
    {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "shipping_collector a WHERE a.shipping_collector_id = '" . (int)$shipping_collector_id . "'");

		return $query->row;
	}

	
	public function getShippingCollectors($data = array())
    {
		$sql = "SELECT * FROM " . DB_PREFIX . "shipping_collector sc";

		/*if (!empty($data['filter_name'])) {
			$sql .= " AND ad.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (!empty($data['filter_shipping_collector_group_id'])) {
			$sql .= " AND a.shipping_collector_group_id = '" . $this->db->escape($data['filter_shipping_collector_group_id']) . "'";
		}*/

		$sort_data = array(
			'sc.collect_date',
			'sc.collected'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY sc.collect_date";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}
	

	public function getTotalShippingCollectors()
    {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "shipping_collector");

		return $query->row['total'];
	}
    
    
    public function installShippingCollector()
    {
        return $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "shipping_collector` (
                  `shipping_collector_id` int(11) NOT NULL AUTO_INCREMENT,
                  `collect_date` datetime NOT NULL,
                  `collect_time` varchar(191) DEFAULT NULL,
                  `collect_destination` varchar(191) DEFAULT NULL,
                  `collect_max` int(11) DEFAULT NULL,
                  `collected` int(11) DEFAULT NULL,
                  `price` decimal(15,4) DEFAULT NULL,
                  `status` tinyint(1) NOT NULL,
                  `date_added` datetime NOT NULL,
                  `date_modified` datetime NOT NULL,
                  PRIMARY KEY (`shipping_collector_id`)
                ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci AUTO_INCREMENT=0;");
	}
    
    
    public function addDefaultShippingCollector($data) {
        return $this->db->query("INSERT INTO " . DB_PREFIX . "shipping_collector SET
		                    collect_date = '" . $this->db->escape($data['date']) . "',
		                    collect_time = '" . $this->db->escape($data['time']) . "',
		                    collect_destination = '" . $this->db->escape($data['destination']) . "',
		                    collect_max = '" . (int)$data['max'] . "',
		                    collected = 0,
		                    price = '" . (float)$data['price'] . "',
		                    status = 1,
		                    date_added = NOW(),
		                    date_modified = NOW()");
    }
}
