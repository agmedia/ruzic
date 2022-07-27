<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Response class
*/
class Response {
	private $headers = array();
	private $level = 0;
	private $output;

	/**
	 * Constructor
	 *
	 * @param	string	$header
	 *
 	*/
	public function addHeader($header) {
		$this->headers[] = $header;
	}
	
	/**
	 * 
	 *
	 * @param	string	$url
	 * @param	int		$status
	 *
 	*/
	public function redirect($url, $status = 302) {
		header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
		exit();
	}
	
	/**
	 * 
	 *
	 * @param	int		$level
 	*/
	public function setCompression($level) {
		$this->level = $level;
	}
	
	/**
	 * 
	 *
	 * @return	array
 	*/
	public function getOutput() {
		return $this->output;
	}
	
	/**
	 * 
	 *
	 * @param	string	$output
 	*/	
	public function setOutput($output) {
		$this->output = $output;
	}
	
	/**
	 * 
	 *
	 * @param	string	$data
	 * @param	int		$level
	 * 
	 * @return	string
 	*/
	private function compress($data, $level = 0) {
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
			$encoding = 'gzip';
		}

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
			$encoding = 'x-gzip';
		}

		if (!isset($encoding) || ($level < -1 || $level > 9)) {
			return $data;
		}

		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

		if (headers_sent()) {
			return $data;
		}

		if (connection_status()) {
			return $data;
		}

		$this->addHeader('Content-Encoding: ' . $encoding);

		return gzencode($data, (int)$level);
	}
	
	/**
	 * 
 	*/
	public function output() {

				// Replace Summernote paths if CKEditor Full is enabled
				global $cke_settings, $cke_status;

				if (!empty($cke_status) && !empty($this->output) && defined('DIR_CATALOG')) {

					$parts = explode('</head>', $this->output, 2);

					if (!empty($parts[0]) && strpos($parts[0], 'view/javascript/clicker_ckeditor/clicker/config.js') !== false) {
						$search = array(
							'view/javascript' . '/summernote/summernote.css' => 'view/javascript/clicker_ckeditor/clicker/summernote/summernote.css',
							'view/javascript' . '/summernote/summernote.js' => 'view/javascript/clicker_ckeditor/clicker/summernote/summernote.js',
							'view/javascript' . '/summernote/summernote-image-attributes.js' => 'view/javascript/clicker_ckeditor/clicker/summernote/summernote-image-attributes.js',
							'view/javascript' . '/summernote/opencart.js' => 'view/javascript/clicker_ckeditor/clicker/summernote/opencart.js',
							'view/javascript' . '/summernote/' => 'view/javascript/clicker_ckeditor/clicker/summernote/', // OC3.0.3.8
						);

						$this->output = str_replace(array_keys($search), array_values($search), $this->output);
					}
				}
			
		if ($this->output) {
			$output = $this->level ? $this->compress($this->output, $this->level) : $this->output;
			
			if (!headers_sent()) {
				foreach ($this->headers as $header) {
					header($header, true);
				}
			}
			
			echo $output;
		}
	}
}
