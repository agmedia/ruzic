<?php 

/*******************************************************************************
*                                 Opencart SEO Pack                            *
*                             � Copyright Ovidiu Fechete                       *
*                              email: ovife21@gmail.com                        *
*                Below source-code or any part of the source-code              *
*                          cannot be resold or distributed.                    *
*******************************************************************************/


class ControllerExtensionExtensionSeoEdit extends Controller { 
	
	public function index() {
	
		if($_POST['id'])
		{
		$id = $this->db->escape($_POST['id']);
		$keyword = $this->db->escape($_POST['keyword']);
		$meta_keyword = $this->db->escape($_POST['meta_keyword']);
		$meta_description = $this->db->escape($_POST['meta_description']);
		$tags = $this->db->escape($_POST['tags']);
		$language_id = $this->db->escape($_POST['lang']);

		if (strpos('x'.$id, 'Product') != false)
			{
			$id = str_replace('Product','',$id);
			$id = (int)$id;
			
			$query = $this->db->query("delete from " . DB_PREFIX . "seo_url where store_id = 0 and query = 'product_id=$id';");
			if ($keyword<>'') {$query = $this->db->query("insert into " . DB_PREFIX . "seo_url(query, keyword) values('product_id=$id','$keyword');");}
			
			$query = $this->db->query("update " . DB_PREFIX . "product_description set meta_keyword = '$meta_keyword' where product_id = $id and language_id = $language_id;");
			$query = $this->db->query("update " . DB_PREFIX . "product_description set meta_description = '$meta_description' where product_id = $id and language_id = $language_id;");
			$query = $this->db->query("update " . DB_PREFIX . "product_description set tag = '$tags' where product_id = $id and language_id = $language_id;");
			
			
			
			
			}

		if (strpos('x'.$id, 'Category') != false)
			{
			$id = str_replace('Category','',$id);
			$id = (int)$id;
			
			$query = $this->db->query("delete from " . DB_PREFIX . "seo_url where store_id = 0 and query = 'category_id=$id';");
			if ($keyword<>'') {$query = $this->db->query("insert into " . DB_PREFIX . "seo_url(query, keyword) values('category_id=$id','$keyword');");}
			
			$query = $this->db->query("update " . DB_PREFIX . "category_description set meta_keyword = '$meta_keyword' where category_id = $id and language_id = $language_id;");
			$query = $this->db->query("update " . DB_PREFIX . "category_description set meta_description = '$meta_description' where category_id = $id and language_id = $language_id;");
				
			}
			
		if (strpos('x'.$id, 'Information') != false)
			{
			$id = str_replace('Information','',$id);
			$id = (int)$id;
			
			$query = $this->db->query("delete from " . DB_PREFIX . "seo_url where store_id = 0 and query = 'information_id=$id';");
			if ($keyword<>'') {$query = $this->db->query("insert into " . DB_PREFIX . "seo_url(query, keyword) values('information_id=$id','$keyword');");}
			
			$query = $this->db->query("update " . DB_PREFIX . "information_description set meta_keyword = '$meta_keyword' where information_id = $id and language_id = $language_id;");
			$query = $this->db->query("update " . DB_PREFIX . "information_description set meta_description = '$meta_description' where information_id = $id and language_id = $language_id;");
			
					
			}
			
		if (strpos('x'.$id, 'Manufacturer') != false)
			{
			$id = str_replace('Manufacturer','',$id);
			$id = (int)$id;
			
			$query = $this->db->query("delete from " . DB_PREFIX . "seo_url where store_id = 0 and query = 'manufacturer_id=$id';");
			if ($keyword<>'') {$query = $this->db->query("insert into " . DB_PREFIX . "seo_url(query, keyword) values('manufacturer_id=$id','$keyword');");}
					
			}
	

	
		}	
		 
	}
	

	
}
?>