<?php

/*******************************************************************************
*                                 Opencart SEO Pack                            *
*                             � Copyright Ovidiu Fechete                       *
*                              email: ovife21@gmail.com                        *
*                Below source-code or any part of the source-code              *
*                          cannot be resold or distributed.                    *
*******************************************************************************/

require_once('../../../config.php');
// Startup
require_once(DIR_SYSTEM . 'startup.php');

// Config
$config = new Config();
$config->load('default');

// Database
$db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);


echo '<html><head><meta charset="UTF-8" /></head>
<body>
<FORM><INPUT TYPE="BUTTON" VALUE="Go Back" 
ONCLICK="history.go(-1)"></FORM>';
$query = $db->query("SELECT * FROM INFORMATION_SCHEMA.STATISTICS 
									 WHERE `TABLE_SCHEMA` = DATABASE() AND
									`TABLE_NAME` = '" . DB_PREFIX . "product_description' AND `INDEX_NAME` = 'ft_namerel'");

			$exists = 0;
			foreach ($query->rows as $index) {
				$exists++;
				}

			if (!$exists) {$db->query("CREATE FULLTEXT INDEX ft_namerel ON " . DB_PREFIX . "product_description (name, description); ");}

			$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` like 'seopack%'");

			foreach ($query->rows as $result) {
						if (!$result['serialized']) {
							$data[$result['key']] = $result['value'];
						} else {
							if ($result['value'][0] == '{') {$data[$result['key']] = json_decode($result['value'], true);} else {$data[$result['key']] = unserialize($result['value']);}
						}
					}
					
			if (isset($data)) {$parameters = $data['seopack_parameters'];}
				else {$parameters['related'] = 5;}

if ((!isset($_GET['gkey'])) || ($_GET['gkey'] != $parameters['gkey'])) 	{

		header('Location: ' . HTTP_SERVER);
	}
	else {
									
			
			$nomax = $parameters['related'];

			$query = $db->query("select pd.name as pname, min(pc.category_id) as category_id, pd.description as pdescription, pd.product_id as product_id, p.price from " . DB_PREFIX . "product_description pd
					inner join " . DB_PREFIX . "product_to_category pc on pd.product_id = pc.product_id
					inner join " . DB_PREFIX . "product p on pd.product_id = p.product_id
					group by pd.name, pd.description, pd.product_id, p.price");

			$bef = array("%", "_","\"","'",'"');
			$aft = array("", " ", " ", " ", "");	
				
					
			foreach ($query->rows as $product) {
				echo 'Generating related products for <b>'.$product['pname'].' </b>: ';
				
				$query_rp_number = $db->query("select count(*) as rp_number from " . DB_PREFIX . "product_related where product_id = " . $product['product_id']);
				foreach ($query_rp_number->rows as $rp)
						{
							$rp_number = $rp['rp_number'];
						}
				
				if ($rp_number < $nomax)
					{						
							$query_rp = $db->query("select distinct p.product_id, pd.name, 
							max(2 / (case p.price >= ". $product['price'] ." when 0 then (". $product['price'] ." / p.price) else (p.price / ". $product['price'] .") end) * 
							(case category_id when ". $product['category_id'] ." then 2 else 1 end)
							+ (1 + rel.rlv) )
							as relevance, rel.rlv from " . DB_PREFIX . "product p 
							inner join " . DB_PREFIX . "product_description pd on p.product_id = pd.product_id
							inner join " . DB_PREFIX . "product_to_category pc on p.product_id = pc.product_id
							inner join (SELECT product_id, MATCH(name, description) AGAINST ('". strip_tags(trim($db->escape(htmlspecialchars_decode(str_replace($bef, $aft,$product['pname']))))) . ' ' . strip_tags(trim($db->escape(htmlspecialchars_decode(str_replace($bef, $aft,$product['pdescription']))))) ."') as rlv FROM " . DB_PREFIX . "product_description) as rel on rel.product_id = p.product_id  
							group by p.product_id
							having p.product_id <> ". $product['product_id'] ." and relevance >= 5 and p.product_id not in (select related_id from " . DB_PREFIX . "product_related where product_id = ". $product['product_id'] .")
							order by relevance desc
							limit 0,". ($nomax - $rp_number));
							
							foreach ($query_rp->rows as $rproduct)
								{
									echo $rproduct['name']. ' (' . $rproduct['relevance']. ') ,';
									$db->query("insert into " . DB_PREFIX . "product_related (product_id, related_id) values (". $product['product_id'] ." , ". $rproduct['product_id'] .")");
								}
							
					}
					
				echo '<br><br>';
				
				}
	}
?>

</body>
</html>


