<?php
/**
 * @package		OpenCart
 * @author		Daniel Kerr
 * @copyright	Copyright (c) 2005 - 2017, OpenCart, Ltd. (https://www.opencart.com/)
 * @license		https://opensource.org/licenses/GPL-3.0
 * @link		https://www.opencart.com
*/

/**
* Document class
*/
class Document {
	private $title;

				private $socialseo;
				
	private $description;
	private $keywords;
private $opengraph = array();
				private $twittercard = array();
				private $structureddata = array();

	private $links = array();
	private $styles = array();
	private $scripts = array();

	/** + robot, noindex */
	private $robots;

	public function setRobots($value) {
		$this->robots = $value;
	}

	public function getRobots() {
		return $this->robots;
	}
			

	/**
     * 
     *
     * @param	string	$title
     */
	public function setTitle($title) {
		$this->title = $title;
	}

	/**
     * 
	 * 
	 * @return	string
     */
	public function getTitle() {
		return $this->title;
	}

	/**
     * 
     *
     * @param	string	$description
     */
	public function setDescription($description) {
		$this->description = $description;
	}

	/**
     * 
     *
     * @param	string	$description
	 * 
	 * @return	string
     */

				public function setSocialSeo($socialseo) {
					$this->socialseo = $socialseo;
				}

				public function getSocialSeo() {
					return $this->socialseo;
				}
				
	public function getDescription() {
		return $this->description;
	}

	/**
     * 
     *
     * @param	string	$keywords
     */
	public function setKeywords($keywords) {
		$this->keywords = $keywords;
	}

	/**
     *
	 * 
	 * @return	string
     */
	public function getKeywords() {
		return $this->keywords;
	}
	
	/**
     * 
     *
     * @param	string	$href
	 * @param	string	$rel
     */

				public function removeLink($rel) {
					foreach ($this->links as $val=>$link) {
						if ($link['rel'] == $rel) {unset($this->links[$val]);}
					}
				}
			
public function setOpengraph($meta, $content) {
                    $this->opengraph[] = array(
                        'meta'   => $meta,
                        'content' => $content
                     );
                }
                
                public function getOpengraph() {
                    return $this->opengraph;
                }

				public function setTwittercard($name, $content) {
                    $this->twittercard[] = array(
                        'name'   => $name,
                        'content' => $content
                     );
                }
                
                public function getTwittercard() {
                    return $this->twittercard;
                }
                
                public function setStructureddata($content) {
                    $this->structureddata[] = array(
                        'content' => $content
                     );
                }
                
                public function getStructureddata() {
                    return $this->structureddata;
                }
	public function addLink($href, $rel) {
		$this->links[$href] = array(
			'href' => $href,
			'rel'  => $rel
		);
	}

	/**
     * 
	 * 
	 * @return	array
     */
	public function getLinks() {
		return $this->links;
	}

	/**
     * 
     *
     * @param	string	$href
	 * @param	string	$rel
	 * @param	string	$media
     */
	public function addStyle($href, $rel = 'stylesheet', $media = 'screen', $position = 'header') {
		$this->styles[$position][$href] = array(
			'href'  => $href,
			'rel'   => $rel,
			'media' => $media
		);
	}

	/**
     * 
	 * 
	 * @return	array
     */
	public function getStyles($position = 'header') {
		if (isset($this->styles[$position])) {
			return $this->styles[$position];
		} else {
			return array();
		}
	}

	/**
     * 
     *
     * @param	string	$href
	 * @param	string	$position
     */
	public function addScript($href, $position = 'header') {
		$this->scripts[$position][$href] = $href;
	}

	/**
     * 
     *
     * @param	string	$position
	 * 
	 * @return	array
     */
	public function getScripts($position = 'header') {
		if (isset($this->scripts[$position])) {
			return $this->scripts[$position];
		} else {
			return array();
		}
	}
}