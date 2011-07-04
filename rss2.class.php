<?php
/**
 * XMLWriter class for rss 2.0
 *
 * @author     Frank Bueltge <frank@bueltge.de>
 * @copyright  2011 -- Frank Bueltge
 * @license    Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Germany
 *             (http://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.en)
 * @link       http://bueltge.de
 * @version    1.0
 * @date       04.07.2011 17:17:29
*/

@date_default_timezone_set("GMT"); 

class Rss extends XMLWriter {
	
	function __construct( $file = 'php://output', $title, $description, $link, $date ) {
		
		$this -> openURI( $file ); 
		$this -> startDocument( '1.0', 'UTF-8' ); 
		$this -> setIndent( TRUE ); 
		
		$this -> startElement( 'rss' ); 
		$this -> writeAttribute('version', '2.0'); 
		$this -> writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom'); 
		
		$url = filter_var( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED );
		$this -> startElement( 'atom:link' );
		$this -> writeAttribute( 'href', $url );
		$this -> writeAttribute( 'rel', 'self' );
		$this -> writeAttribute( 'type', 'application/rss+xml' );
		$this -> endElement();
		
		$this -> startElement( 'channel' ); 
		$this -> writeElement( 'title', $title );
		$this -> writeElement( 'description', $description ); 
		$this -> writeElement( 'link', $link ); 
		$this -> writeElement( 'pubDate', date( 'D, d M Y H:i:s e', strtotime($date) ) ); 
		
	}
	
	function add_item($array) {
		
		if ( ! is_array($array) )
			return;
			
			$this -> startElement( 'item' );
			$this -> writeElement( 'title', $array['title'] );
			if ( $array['link'] )
				$this -> writeElement( 'link', $array['link'] ); 
			$this -> writeElement( 'description', $array['description'] );
			if ( $array['guid'] )
				$this -> writeElement( 'guid', $array['guid'] ); 
			
			if ( isset($array['date']) )
				$this -> writeElement('pubDate', date( "D, d M Y H:i:s e", strtotime($array['date']) ) ); 
			
			if ( isset($array['category']) && isset( $array['category']['title']) ) {
				$this -> startElement('category');
				$this -> writeAttribute('domain', $array['category']['domain']); 
				$this -> text($array['category']['title']); 
				$this -> endElement();
			}
			
			$this -> endElement();
		
	}
	
	function end_rss() {
		// End channel 
		$this -> endElement(); 
		// End rss 
		$this -> endElement(); 
		// End doc
		$this -> endDocument(); 
		// Flush current buffer
		$this -> flush(); 
	}
	
} // end class