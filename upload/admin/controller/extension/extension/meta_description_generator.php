<?php

/*******************************************************************************
*                                 Opencart SEO Pack                            *
*                             � Copyright Ovidiu Fechete                       *
*                              email: ovife21@gmail.com                        *
*                Below source-code or any part of the source-code              *
*                          cannot be resold or distributed.                    *
*******************************************************************************/

function first_sentence($content) {

    $content = strip_tags(html_entity_decode($content));
    $pos = strpos($content, '.');
       
    if($pos === false) {
        return $content;
    }
    else {
        return substr($content, 0, $pos+1);
    }
   
}

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
$query = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` like 'seopack%'");

		foreach ($query->rows as $result) {
					if (!$result['serialized']) {
						$data[$result['key']] = $result['value'];
					} else {
						if ($result['value'][0] == '{') {$data[$result['key']] = json_decode($result['value'], true);} else {$data[$result['key']] = unserialize($result['value']);}
					}
				}
				
		if (isset($data)) {$parameters = $data['seopack_parameters'];}
			else {$parameters['metas'] = '%p - %f';}
		
if ((!isset($_GET['gkey'])) || ($_GET['gkey'] != $parameters['gkey'])) 	{

		header('Location: ' . HTTP_SERVER);
	}
	else {

			
		$db->query("update " . DB_PREFIX . "category_description set meta_description = concat(name, case description when '' then '' else ' - ' end, substring_index(description, '.', 1)) where meta_description = '';");
		$db->query("update " . DB_PREFIX . "information_description set meta_description = concat(title, case description when '' then '' else ' - ' end, substring_index(description, '.', 1)) where meta_description = '';");

		$query = $db->query("select pd.name as pname, p.price as price, cd.name as cname, pd.description as pdescription, pd.language_id as language_id, pd.product_id as product_id, p.model as model, p.sku as sku, p.upc as upc, m.name as brand from " . DB_PREFIX . "product_description pd
				left join " . DB_PREFIX . "product_to_category pc on pd.product_id = pc.product_id
				inner join " . DB_PREFIX . "product p on pd.product_id = p.product_id
				left join " . DB_PREFIX . "category_description cd on cd.category_id = pc.category_id and cd.language_id = pd.language_id
				left join " . DB_PREFIX . "manufacturer m on m.manufacturer_id = p.manufacturer_id;");

		foreach ($query->rows as $product) {
			echo 'Generating meta description for <b>'.$product['pname'].' (from '.$product['cname'].')</b>: ';
			
			$bef = array("%", "_","\"","'","\\", "\r", "\n");
			$aft = array("", " ", " ", " ", "", "", "");
			
			$ncategory = trim($db->escape(html_entity_decode(str_replace($bef, $aft,$product['cname']), ENT_COMPAT, "UTF-8")));
			$nproduct = trim($db->escape(html_entity_decode(str_replace($bef, $aft,$product['pname']), ENT_COMPAT, "UTF-8")));
			$model = trim($db->escape(html_entity_decode(str_replace($bef, $aft,$product['model']), ENT_COMPAT, "UTF-8")));
			$sku = trim($db->escape(html_entity_decode(str_replace($bef, $aft,$product['sku']), ENT_COMPAT, "UTF-8")));
			$upc = trim($db->escape(html_entity_decode(str_replace($bef, $aft,$product['upc']), ENT_COMPAT, "UTF-8")));
			$sentence = trim($db->escape(html_entity_decode(str_replace($bef, $aft,first_sentence($product['pdescription'])), ENT_COMPAT, "UTF-8")));
			$brand = trim($db->escape(html_entity_decode(str_replace($bef, $aft,$product['brand']), ENT_COMPAT, "UTF-8")));
			$price = trim($db->escape(html_entity_decode(str_replace($bef, $aft, number_format($product['price'], 2)), ENT_COMPAT, "UTF-8")));
			
			$bef = array("%c", "%p", "%m", "%s", "%u", "%f", "%b", "%$");
			$aft = array($ncategory, $nproduct, $model, $sku, $upc, $sentence, $brand, $price);
			
			$meta_description = str_replace($bef, $aft,  $parameters['metas']);
			
			$exists = $db->query("select count(*) as times from " . DB_PREFIX . "product_description where product_id = ".$product['product_id']." and language_id = ".$product['language_id']." and meta_description not like '%".htmlspecialchars($meta_description)."%';");
			
					foreach ($exists->rows as $exist)
						{
						$count = $exist['times'];
						}
			
			if ($count) {$db->query("update " . DB_PREFIX . "product_description set meta_description = concat(meta_description, '". htmlspecialchars($meta_description) ."') where product_id = ".$product['product_id']." and language_id = ".$product['language_id'].";");}			
			
			echo " - ".( (!$count) ?"No new ":"<span style=\"color:red;\">$meta_description</span> ")."meta description was added;<br>";
			}
	}
	
?>

</body>
</html>


