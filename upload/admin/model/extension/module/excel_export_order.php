<?php
class ModelExtensionModuleExcelExportOrder extends Model {


    public function ExcelFolder(){
        return DIR_SYSTEM.'../excel/';
    }
    public function ExcelFile(){
        return DIR_SYSTEM.'../excel/excel_export_order.xls';
    }

    public function getLanguages(){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language");
        return $query->rows;
    }

    public function getOrderStatus() {
        $query        = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER by name ASC");
        $order_status = $query->rows;
        if($order_status){
            return $order_status;
        }else{
            return array();
        }
    }




    public function getStores() {
        $return_stores = array();
        $query         = $this->db->query("SELECT value FROM " . DB_PREFIX . "setting WHERE `key` = 'config_name'");
        $default_store = $query->row;
        $return_stores[0]['store_id'] = 0;
        $return_stores[0]['name']     = $default_store['value'];
        $query  = $this->db->query("SELECT store_id, name FROM " . DB_PREFIX . "store ORDER by name ASC");
        $stores = $query->rows;
        if($stores){
            $i = 1;
            foreach($stores as $store){
                $return_stores[$i] = $store;
                $i++;
            }
        }
        return $return_stores;
    }


    public function getOrderProduct($order_id){

        $product_cell = '';
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '".(int)$order_id."'");
        if($query->rows){
            foreach($query->rows as $product){
                $product_cell .= '['. $product['model'].'] '.$product['name'].' ('.$product['quantity'].'x)
';
                $query_option = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '".(int)$order_id."' AND order_product_id = '".(int)$product['order_product_id']."'");
                if($query_option->rows){
                    foreach($query_option->rows as $product_option){
                        $product_cell .= ' - '.$product_option['name'].': '.$product_option['value'].'
';
                    }
                }
                $product_cell .= "\n";
            }
            return $product_cell;
        }else{
            return '';
        }
    }

    public function getDefaultCurrencySR(){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency WHERE value = 1");
        return $query->row['symbol_right'];
    }

    public function getShopName(){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE `key` = 'config_title'");
        if($query->row){return $query->row['value'];}
        else{return '';}
    }


    public function getLanguageFolder($lang_id){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "language WHERE language_id = '".(int)$lang_id."'");
        return $query->row['directory'];
    }

    public function getCurrencySL($currency_id){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency WHERE currency_id = '".(int)$currency_id."'");
        return $query->row['symbol_left'];
    }

    public function getCurrencySR($currency_id){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "currency WHERE currency_id = '".(int)$currency_id."'");
        return $query->row['symbol_right'];
    }

    public function getOrderStatusName($order_status_id){
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '".(int)$order_status_id."'");
        if($query->row){return $query->row['name'];}
        else{return "";}
    }

    public function getLanguageName($language_id){
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "language` WHERE language_id = '".$language_id."'");
        if($query->row){return $query->row['name'];}
        else{return "";}

    }

    public function getProductSku($order_id){

        $return_sku = array();

        $query = $this->db->query("SELECT product_id FROM `" . DB_PREFIX . "order_product` WHERE order_id = '".(int)$order_id."'");
        foreach($query->rows as $op){
            $product = $this->db->query("SELECT sku FROM `" . DB_PREFIX . "product` WHERE product_id = '".(int)$op['product_id']."'");
            $return_sku[] = $product->row['sku'];
        }

        return implode(", ",$return_sku);

    }

    public function getCustomerGroupName($customer_group_id){
        if($customer_group_id){
            $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "customer_group WHERE customer_group_id = '".(int)$customer_group_id."'");
            if(isset($query->row['name'])){echo $query->row['name'];}
        }else{
            return "";
        }
    }




    public function exportExcel($data){
        $get_data = array();

        require(str_replace('/catalog/','/admin/',DIR_LANGUAGE.'en-gb/extension/module/excel_export_order.php'));

        if(isset($data['excel_export_order_items']['order_id'])){$get_data[] = 'order_id';}
        if(isset($data['excel_export_order_items']['invoice_no'])){$get_data[] = 'invoice_no';}

        if(isset($data['filter']['separated_products']) AND $data['filter']['separated_products'] == 1){
            $separated_products = true;
            if(isset($data['excel_export_order_items']['product_id'])){$get_data[] = 'product_id';}
            if(isset($data['excel_export_order_items']['product_name'])){$get_data[] = 'product_name';}
            if(isset($data['excel_export_order_items']['product_model'])){$get_data[] = 'product_model';}
            if(isset($data['excel_export_order_items']['product_sku'])){$get_data[] = 'product_sku';}
            if(isset($data['excel_export_order_items']['product_option'])){$get_data[] = 'product_option';}
            if(isset($data['excel_export_order_items']['product_price'])){$get_data[] = 'product_price';}
            if(isset($data['excel_export_order_items']['product_quantity'])){$get_data[] = 'product_quantity';}
            if(isset($data['excel_export_order_items']['product_total_price'])){$get_data[] = 'product_total_price';}
        }else{
            $separated_products = false;
            $get_data[] = 'product';
        }

        if(isset($data['excel_export_order_items']['total'])){$get_data[] = 'total';}
        if(isset($data['excel_export_order_items']['invoice_prefix'])){$get_data[] = 'invoice_prefix';}
        if(isset($data['excel_export_order_items']['store_name'])){$get_data[] = 'store_name';}
        if(isset($data['excel_export_order_items']['comment'])){$get_data[] = 'comment';}
        if(isset($data['excel_export_order_items']['reward'])){$get_data[] = 'reward';}
        if(isset($data['excel_export_order_items']['order_status_id'])){$get_data[] = 'order_status_id';}
        if(isset($data['excel_export_order_items']['date_added'])){$get_data[] = 'date_added';}
        if(isset($data['excel_export_order_items']['date_modified'])){$get_data[] = 'date_modified';}
        if(isset($data['excel_export_order_items']['ip'])){$get_data[] = 'ip';}
        if(isset($data['excel_export_order_items']['shipping_firstname'])){$get_data[] = 'shipping_firstname';}
        if(isset($data['excel_export_order_items']['shipping_lastname'])){$get_data[] = 'shipping_lastname';}
        if(isset($data['excel_export_order_items']['shipping_company'])){$get_data[] = 'shipping_company';}
        if(isset($data['excel_export_order_items']['shipping_address_1'])){$get_data[] = 'shipping_address_1';}
        if(isset($data['excel_export_order_items']['shipping_address_2'])){$get_data[] = 'shipping_address_2';}
        if(isset($data['excel_export_order_items']['shipping_city'])){$get_data[] = 'shipping_city';}
        if(isset($data['excel_export_order_items']['shipping_postcode'])){$get_data[] = 'shipping_postcode';}
        if(isset($data['excel_export_order_items']['shipping_country'])){$get_data[] = 'shipping_country';}
        if(isset($data['excel_export_order_items']['shipping_zone'])){$get_data[] = 'shipping_zone';}
        if(isset($data['excel_export_order_items']['shipping_address_format'])){$get_data[] = 'shipping_address_format';}
        if(isset($data['excel_export_order_items']['shipping_method'])){$get_data[] = 'shipping_method';}
        if(isset($data['excel_export_order_items']['payment_firstname'])){$get_data[] = 'payment_firstname';}
        if(isset($data['excel_export_order_items']['payment_lastname'])){$get_data[] = 'payment_lastname';}
        if(isset($data['excel_export_order_items']['payment_company'])){$get_data[] = 'payment_company';}
        if(isset($data['excel_export_order_items']['payment_address_1'])){$get_data[] = 'payment_address_1';}
        if(isset($data['excel_export_order_items']['payment_address_2'])){$get_data[] = 'payment_address_2';}
        if(isset($data['excel_export_order_items']['payment_city'])){$get_data[] = 'payment_city';}
        if(isset($data['excel_export_order_items']['payment_postcode'])){$get_data[] = 'payment_postcode';}
        if(isset($data['excel_export_order_items']['payment_country'])){$get_data[] = 'payment_country';}
        if(isset($data['excel_export_order_items']['payment_zone'])){$get_data[] = 'payment_zone';}
        if(isset($data['excel_export_order_items']['payment_address_format'])){$get_data[] = 'payment_address_format';}
        if(isset($data['excel_export_order_items']['payment_method'])){$get_data[] = 'payment_method';}
        if(isset($data['excel_export_order_items']['affiliate_id'])){$get_data[] = 'affiliate_id';}
        if(isset($data['excel_export_order_items']['commission'])){$get_data[] = 'commission';}
        if(isset($data['excel_export_order_items']['language_id'])){$get_data[] = 'language_id';}
        if(isset($data['excel_export_order_items']['currency_value'])){$get_data[] = 'currency_value';}
        if(isset($data['excel_export_order_items']['customer_id'])){$get_data[] = 'customer_id';}
        if(isset($data['excel_export_order_items']['customer_group_id'])){$get_data[] = 'customer_group_id';}
        if(isset($data['excel_export_order_items']['firstname'])){$get_data[] = 'firstname';}
        if(isset($data['excel_export_order_items']['lastname'])){$get_data[] = 'lastname';}
        if(isset($data['excel_export_order_items']['email'])){$get_data[] = 'email';}
        if(isset($data['excel_export_order_items']['telephone'])){$get_data[] = 'telephone';}
        if(isset($data['excel_export_order_items']['fax'])){$get_data[] = 'fax';}

        $price_code = false;
        if(isset($data['filter']['price_code']) && (int)$data['filter']['price_code'] == 1){
            $price_code = true;
        }




        $export = array();
        $i = 0;

        $sql = "SELECT * FROM `" . DB_PREFIX . "order` WHERE 1 = 1";

        if(isset($data['filter']['order_date_start']) && isset($data['filter']['order_date_start']) && $data['filter']['order_date_start'] != '' && $data['filter']['order_date_stop'] != ''){
            $date_start = $data['filter']['order_date_start']." 00:00:00";
            $date_stop  = $data['filter']['order_date_stop']." 23:59:59";
            $sql .= " AND date_added >= '".$this->db->escape($date_start)."' AND date_added <= '".$this->db->escape($date_stop)."'";
        }

        if(isset($data['filter']['order_store']) && $data['filter']['order_store'] != '-'){
            $sql .= " AND store_id = '".(int)$data['filter']['order_store']."'";
        }

        if(isset($data['filter']['order_status']) && $data['filter']['order_status'] != '-'){
            $sql .= " AND order_status_id = '".(int)$data['filter']['order_status']."'";
        }else{
            $sql .= " AND order_status_id > '0'";
        }


        $all_orders = $this->db->query($sql);
        foreach($all_orders->rows as $order){

            foreach($get_data as $dat){
                if($dat != 'order_status_id' AND $dat != 'language_id' AND
                    $dat != 'customer_group_id' AND $dat != 'sku' AND $dat != 'product' AND $dat != 'firstname' AND $dat != 'payment_address_1' AND $dat != 'shipping_method' AND $dat != 'order_status_id'){
                    if($dat == 'total'){
                        if($price_code){
                            $export[$i][$dat] = $this->getCurrencySL($order['currency_id']).$order[$dat].$this->getCurrencySR($order['currency_id']);
                        }else{
                            $export[$i][$dat] = $order[$dat];
                        }
                    }else{

                        if(isset($order[$dat])){$export[$i][$dat] = $order[$dat];}
                        else{$export[$i][$dat] = "";}
                    }
                }else{
                    if($dat == 'order_status_id'){$export[$i][$dat] = $this->getOrderStatusName($order['order_status_id']);}
                    if($dat == 'language_id'){$export[$i][$dat] = $this->getLanguageName($order['language_id']);}
                    if($dat == 'customer_group_id'){$export[$i][$dat] = $this->getCustomerGroupName($order['customer_group_id']);}
                    if($dat == 'sku'){$export[$i][$dat] = $this->getProductSku($order['order_id']);}

                    if($dat == 'firstname'){$export[$i][$dat] = $order['firstname'].' '.$order['lastname'];}


                    if($order['shipping_code'] =='collector.collector'){

                        $s =  $order['shipping_method'];
                        $s = strstr($s, '<i>', true);

                        if($dat == 'shipping_method'){$export[$i][$dat] = $s;}


                        $t = str_replace('Naša vlastita besplatna dostava - Zagreb i okolica', '', $order['shipping_method']);

                        $t = str_replace('<i>', '', $t);

                        $t = str_replace('</i>', '', $t);

                        if($dat == 'order_status_id'){$export[$i][$dat] = $t;}

                    }
                    else{

                        if($dat == 'shipping_method'){$export[$i][$dat] = $order['shipping_method'];}

                        if($dat == 'order_status_id'){$export[$i][$dat] = '';}
                    }










                    if($dat == 'payment_address_1'){$export[$i][$dat] = $order['payment_address_1'].', '.$order['shipping_postcode'].', '.$order['shipping_city'];}

                    if($dat == 'product'){
                        $product_data = "";

                        $products_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '".(int)$order['order_id']."'");
                        if($products_query->rows){
                            foreach($products_query->rows as $product){


                                if(isset($data['excel_export_order_items']['product_id'])){$product_data .= $_['export_product_id'].": ".$product['product_id'].";\n";}
                                if(isset($data['excel_export_order_items']['product_name'])){$product_data .= $_['export_product_name'].": ".$product['name'].";\n";}
                                if(isset($data['excel_export_order_items']['product_model'])){$product_data .= $_['export_product_model'].": ".$product['model'].";\n";}

                                $product_sku = '';
                                $product_detail = $this->db->query("SELECT sku FROM " . DB_PREFIX . "product WHERE product_id = '".(int)$product['product_id']."'");
                                if($product_detail->row){
                                    $product_sku = $product_detail->row['sku'];
                                }
                                if(isset($data['excel_export_order_items']['product_sku'])){$product_data .= $_['export_product_sku'].": ".$product_sku.";\n";}


                                if(isset($data['excel_export_order_items']['product_option'])){

                                    $product_option_value = "";

                                    $product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '".(int)$order['order_id']."' AND order_product_id = '".(int)$product['order_product_id']."'");
                                    if($product_option_query->rows){
                                        foreach($product_option_query->rows as $product_option){
                                            $product_option_value .= "   ".$product_option['name'].": ".$product_option['value'].";\n";
                                        }
                                    }

                                    $product_data .= $_['export_product_option'].": \n";
                                    if($product_option_value != ""){
                                        $product_data .= $product_option_value.";\n";
                                    }
                                }

                                if(isset($data['excel_export_order_items']['product_price'])){
                                    if($price_code){
                                        $product_data .= $_['export_product_price'].": ".$this->getCurrencySL($order['currency_id']).$product['price'].$this->getCurrencySR($order['currency_id']).";\n";
                                    }else{
                                        $product_data .= $_['export_product_price'].": ".$product['price'].";\n";
                                    }
                                }

                                if(isset($data['excel_export_order_items']['product_quantity'])){$product_data .= $_['export_product_quantity'].": ".$product['quantity'].";\n";}

                                if(isset($data['excel_export_order_items']['product_total_price'])){
                                    if($dat == 'product_price'){
                                        if($price_code){
                                            $product_data .= $_['export_product_total_price'].": ".$this->getCurrencySL($order['currency_id']).$product['total'].$this->getCurrencySR($order['currency_id']).";\n";
                                        }else{
                                            $product_data .= $_['export_product_total_price'].": ".$product['total'].";\n";
                                        }
                                    }
                                }
                                $product_data = $product_data."\n";
                            }
                        }
                        $export[$i][$dat] = $product_data;
                    }
                }
            }








            if($separated_products){

                $products_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '".(int)$order['order_id']."'");
                if($products_query->rows){
                    foreach($products_query->rows as $product){

                        foreach($get_data as $dat){

                            if(isset($export[$i][$dat])){
                                $export[$i][$dat] = $export[$i][$dat];
                            }

                            if($dat == 'product_id'){$export[$i][$dat] = $product['product_id'];}
                            if($dat == 'product_name'){$export[$i][$dat] = $product['name'];}
                            if($dat == 'product_model'){$export[$i][$dat] = $product['model'];}
                            if($dat == 'product_sku'){
                                $product_sku = '';
                                $product_detail = $this->db->query("SELECT sku FROM " . DB_PREFIX . "product WHERE product_id = '".(int)$product['product_id']."'");
                                if($product_detail->row){
                                    $product_sku = $product_detail->row['sku'];
                                }
                                $export[$i][$dat] = $product_sku;
                            }


                            if($dat == 'product_option'){
                                $product_option_value = "";
                                $product_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '".(int)$order['order_id']."' AND order_product_id = '".(int)$product['order_product_id']."'");
                                if($product_option_query->rows){
                                    foreach($product_option_query->rows as $product_option){
                                        $product_option_value .= $product_option['name'].": ".$product_option['value']."\n";
                                    }
                                }
                                $export[$i][$dat] = $product_option_value;
                            }



                            if($dat == 'product_quantity'){$export[$i][$dat] = $product['quantity'];}

                            if($dat == 'product_price'){
                                if($price_code){
                                    $export[$i][$dat] = $this->getCurrencySL($order['currency_id']).$product['price'].$this->getCurrencySR($order['currency_id']);
                                }else{
                                    $export[$i][$dat] = $product['price'];
                                }
                            }


                            if($dat == 'product_total_price'){
                                if($price_code){
                                    $export[$i][$dat] = $this->getCurrencySL($order['currency_id']).$product['total'].$this->getCurrencySR($order['currency_id']);
                                }else{
                                    $export[$i][$dat] = $product['total'];
                                }
                            }
                        }
                        $i++;
                    }
                }



            }else{
                $i++;
            }


        }

        $return['cols'] = $get_data;
        $return['rows'] = $export;

        if(count($return['cols'])){return $this->createExcel($return);}
        else{return false;}


    }

    public function createExcel($data){

        error_reporting(E_ALL);
        date_default_timezone_set('Europe/London');
        require_once DIR_SYSTEM.'phpexcel/Classes/PHPExcel.php';

        $objPHPExcel = new PHPExcel();
        $objPHPExcel->getProperties()->setCreator("DEAWid, DEAWid@seznam.cz")
            ->setLastModifiedBy("DEAWid")
            ->setTitle("EXCEL BACKUP ORDERS ".$this->getShopName()." FROM ".date("d-m-Y",time()))
            ->setSubject("EXCEL BACKUP ORDERS ".$this->getShopName())
            ->setDescription("EXCEL BACKUP ORDERS ".$this->getShopName()." FROM ".date("d-m-Y",time()))
            ->setKeywords("EXCEL BACKUP ORDERS ".$this->getShopName()." FROM ".date("d-m-Y",time()))
            ->setCategory("EXCEL BACKUP ORDERS ".$this->getShopName()." FROM ".date("d-m-Y",time()));


        require(str_replace('/catalog/','/admin/',DIR_LANGUAGE.'en-gb/extension/module/excel_export_order.php'));

        $i = 0;
        foreach($data['cols'] as $col){
            $i++;
            $lang_title = $_['export_'.$col];


            $width = "15";


            if($col == "order_id"){$width = "8";}
            if($col == "invoice_no"){$width = "10";}
            if($col == "product_id"){$width = "10";}
            if($col == "product_name"){$width = "30";}
            if($col == "product_model"){$width = "14";}
            if($col == "product_quantity"){$width = "16";}
            if($col == "product_price"){$width = "13";}
            if($col == "product_total_price"){$width = "17";}
            if($col == "product"){$width = "100";}
            if($col == "invoice_prefix"){$width = "13";}
            if($col == "store_name"){$width = "15";}
            if($col == "comment"){$width = "14";}
            if($col == "total"){$width = "12";}
            if($col == "reward"){$width = "12";}
            if($col == "order_status_id"){$width = "25";}
            if($col == "date_added"){$width = "18";}
            if($col == "date_modified"){$width = "18";}
            if($col == "ip"){$width = "15";}
            if($col == "shipping_firstname"){$width = "19";}
            if($col == "shipping_lastname"){$width = "19";}
            if($col == "shipping_company"){$width = "19";}
            if($col == "shipping_address_1"){$width = "19";}
            if($col == "shipping_address_2"){$width = "19";}
            if($col == "shipping_city"){$width = "19";}
            if($col == "shipping_postcode"){$width = "19";}
            if($col == "shipping_country"){$width = "19";}
            if($col == "shipping_zone"){$width = "19";}
            if($col == "shipping_address_format"){$width = "19";}
            if($col == "shipping_method"){$width = "60";}
            if($col == "shipping_method_time"){$width = "60";}
            if($col == "payment_firstname"){$width = "19";}
            if($col == "payment_lastname"){$width = "19";}
            if($col == "payment_company"){$width = "19";}
            if($col == "payment_address_1"){$width = "30";}
            if($col == "payment_address_2"){$width = "19";}
            if($col == "payment_city"){$width = "19";}
            if($col == "payment_postcode"){$width = "19";}
            if($col == "payment_country"){$width = "19";}
            if($col == "payment_zone"){$width = "19";}
            if($col == "payment_address_format"){$width = "22";}
            if($col == "payment_method"){$width = "18";}
            if($col == "affiliate_id"){$width = "12";}
            if($col == "commission"){$width = "12";}
            if($col == "language_id"){$width = "12";}
            if($col == "currency_value"){$width = "14";}
            if($col == "customer_id"){$width = "11";}
            if($col == "customer_group_id"){$width = "16";}
            if($col == "firstname"){$width = "19";}
            if($col == "lastname"){$width = "19";}
            if($col == "email"){$width = "19";}
            if($col == "telephone"){$width = "19";}
            if($col == "fax"){$width = "19";}
            if($col == "sku"){$width = "19";}

            //set width
            $objPHPExcel->getActiveSheet()->getColumnDimension($this->IncToAbc($i))->setWidth($width);
            $objPHPExcel->getActiveSheet()->setCellValue($this->IncToAbc($i).'1', $lang_title);
        }

        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$this->IncToAbc($i).'1')->getFont()->setBold(true);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$this->IncToAbc($i).'1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        $objPHPExcel->getActiveSheet()->getStyle('A1:'.$this->IncToAbc($i).'1')->getFill()->getStartColor()->setARGB('FFA0A0A0');

        $row_num = 2;
        $i = 0;
        foreach($data['rows'] as $row){
            $col_num = 1;
            foreach($data['cols'] as $col){
                $coordinate = $this->IncToAbc($col_num).$row_num;
                if(isset($row[$col])){
                    $cell_value = htmlspecialchars_decode($row[$col]);
                }else{
                    $cell_value = "";
                }
                $objPHPExcel->getActiveSheet()->setCellValue($coordinate, $cell_value);

                if($objPHPExcel->getActiveSheet()->getCell('A'.$row_num)->getValue() != ""){
                    $styleArray = array(
                        'borders' => array(
                            'top' => array(
                                'style' => PHPExcel_Style_Border::BORDER_THIN,
                                'color' => array('argb' => 'FFA0A0A0'),
                            )
                        )
                    );
                    $objPHPExcel->getActiveSheet()->getStyle($coordinate)->applyFromArray($styleArray);
                }

                $col_num++;
            }
            $i++;
            $row_num++;
        }

        $objPHPExcel->setActiveSheetIndex(0);

        require_once DIR_SYSTEM.'phpexcel/Classes/PHPExcel/IOFactory.php';
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');



        if(!file_exists($this->ExcelFolder())){
            mkdir($this->ExcelFolder());
        }
        $excel_filename = $this->ExcelFile();

        if(file_exists($excel_filename)){
            unlink($excel_filename);
        }


        $objWriter->save($excel_filename);

        return $excel_filename;
    }

    public function IncToAbc($num){
        if($num == 1){return 'A';}
        if($num == 2){return 'B';}
        if($num == 3){return 'C';}
        if($num == 4){return 'D';}
        if($num == 5){return 'E';}
        if($num == 6){return 'F';}
        if($num == 7){return 'G';}
        if($num == 8){return 'H';}
        if($num == 9){return 'I';}
        if($num == 10){return 'J';}
        if($num == 11){return 'K';}
        if($num == 12){return 'L';}
        if($num == 13){return 'M';}
        if($num == 14){return 'N';}
        if($num == 15){return 'O';}
        if($num == 16){return 'P';}
        if($num == 17){return 'Q';}
        if($num == 18){return 'R';}
        if($num == 19){return 'S';}
        if($num == 20){return 'T';}
        if($num == 21){return 'U';}
        if($num == 22){return 'V';}
        if($num == 23){return 'W';}
        if($num == 24){return 'X';}
        if($num == 25){return 'Y';}
        if($num == 26){return 'Z';}
        if($num == 27){return 'AA';}
        if($num == 28){return 'AB';}
        if($num == 29){return 'AC';}
        if($num == 30){return 'AD';}
        if($num == 31){return 'AE';}
        if($num == 32){return 'AF';}
        if($num == 33){return 'AG';}
        if($num == 34){return 'AH';}
        if($num == 35){return 'AI';}
        if($num == 36){return 'AJ';}
        if($num == 37){return 'AK';}
        if($num == 38){return 'AL';}
        if($num == 39){return 'AM';}
        if($num == 40){return 'AN';}
        if($num == 41){return 'AO';}
        if($num == 42){return 'AP';}
        if($num == 43){return 'AQ';}
        if($num == 44){return 'AR';}
        if($num == 45){return 'AS';}
        if($num == 46){return 'AT';}
        if($num == 47){return 'AU';}
        if($num == 48){return 'AV';}
        if($num == 49){return 'AW';}
        if($num == 50){return 'AX';}
        if($num == 51){return 'AY';}
        if($num == 52){return 'AZ';}
        if($num == 53){return 'BA';}
    }
}


?>