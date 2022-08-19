<?php
class ControllerExtensionModuleExcelExportOrder extends Controller {
	public function index(){
    
/*================================================================================*/
/*=============================== EXCEL EXPORT ORDER =============================*/
/*==================== CREATED BY DEAWid : ALL RIGHTS RESERVED ===================*/
/*================================================================================*/
/*================================ www.deawid.com ================================*/
/*================================================================================*/


$export = array(

'filter'           => array(
  'order_store'        => '-',            //store_id .. for all leave "-"
  'order_status'       => '-',            //order_status_id .. for all leave "-"
  'order_date_start'   => date("Y-m-d"),  //date from - YYYY-mm-dd like "2017-12-24" .. for actual day leave date("Y-m-d")
  'order_date_stop'    => date("Y-m-d"),  //date to - YYYY-mm-dd like "2017-12-26" .. for actual day leave date("Y-m-d")
  'separated_products' => true,           //each product to new line .. true/false
  'price_code'         => true           //currency code .. true/false

),
'excel_export_order_items' => array(


//change this value to disable in excel:
'order_id'                    => true,
'invoice_no'                  => true,
'invoice_prefix'              => true,
'accept_language'             => true,
'total'                       => true,
'currency_id'                 => true,
'currency_code'               => true,
'currency_value'              => true,
'comment'                     => true,
'order_status_id'             => true,
'firstname'                   => true,
'lastname'                    => true,
'email'                       => true,
'telephone'                   => true,
'fax'                         => true,
'user_agent'                  => true,
'product_id'                  => true,
'product_name'                => true,
'product_model'               => true,
'product_quantity'            => true,
'product_price'               => true,
'product_total'               => true,
'product_tax'                 => true,
'product_reward'              => true,
'product_option'              => true,
'date_added'                  => true,
'date_modified'               => true,
'commission'                  => true,
'customer_group_id'           => true,
'affiliate_id'                => true,
'language_id'                 => true,
'customer_id'                 => true,
'payment_firstname'           => true,
'payment_lastname'            => true,
'payment_company'             => true,
'payment_address_1'           => true,
'payment_address_2'           => true,
'payment_city'                => true,
'payment_postcode'            => true,
'payment_country'             => true,
'payment_country_id'          => true,
'payment_zone'                => true,
'payment_zone_id'             => true,
'payment_method'              => true,
'payment_code'                => true,
'payment_address_format'      => true,
'store_id'                    => true,
'store_name'                  => true,
'shipping_firstname'          => true,
'shipping_lastname'           => true,
'shipping_company'            => true,
'shipping_address_1'          => true,
'shipping_address_2'          => true,
'shipping_city'               => true,
'shipping_postcode'           => true,
'shipping_country'            => true,
'shipping_country_id'         => true,
'shipping_zone'               => true,
'shipping_zone_id'            => true,
'shipping_method'             => true,
'shipping_code'               => true,
'shipping_address_format'     => true,
'store_url'                   => true,
'ip'                          => true
)
);


$export_data = array();
if(isset($export)){
  foreach($export as $key => $values){
    
    if($key == 'excel_export_order_items'){
      foreach($values as $item => $enabled){
        if($enabled == true){
          $export_data[$key][$item] = $item;
        }
      }
    }else{
      $export_data[$key] = $values;
    }
  }
}



/******************************************************************************/
// Registry
  $registry = new Registry();

// Config
  $config = new Config();
  $config->load('admin');

// Loader
  $loader = new Loader($registry);
  $registry->set('load', $loader);

// Language
  $language = new Language($config->get('language_directory'));
  $registry->set('language', $language);

// Language Autoload
  if ($config->has('language_autoload')) {
  	foreach ($config->get('language_autoload') as $value) {
  		$loader->language($value);
  	}
  }

// Database
  if ($config->get('db_autostart')) {
  	$registry->set('db', new DB($config->get('db_engine'), $config->get('db_hostname'), $config->get('db_username'), $config->get('db_password'), $config->get('db_database'), $config->get('db_port')));
  }
  
require_once('admin/model/extension/module/excel_export_order.php');

$exportToExcel = new ModelExtensionModuleExcelExportOrder($registry);
$exportToExcel->exportExcel($export_data);


header('Content-disposition: attachment; filename="EXCEL-EXPORT-ORDER.xls"');
header('Content-type: "text/xls"; charset="utf8"');
readfile($exportToExcel->ExcelFile());




  }
}