<?php
/**
 * Simple GooglePlus-Feed
 *
 * @author     Frank Bueltge <frank@bueltge.de>
 * @copyright  2011 -- Frank Bueltge
 * @license    Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Germany
 *             (http://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.en)
 * @link       http://bueltge.de
 * @version    1.0
 * @date       04.07.2011 17:17:29
*/

require_once( 'googleplus.class.php' );
$arrTests = array( 'https://plus.google.com/111291152590065605567/posts' );

@date_default_timezone_set( 'GMT' );

foreach ( $arrTests as $strTest ) {
	$oGooglePlus = new GooglePlus( $strTest );
	
	if ( $oGooglePlus -> isReady ) {
		
		$writer = new XMLWriter();
		$writer -> openURI( 'php://output' );
		$writer -> startDocument( '1.0', 'UTF-8' );
		$writer -> setIndent( TRUE );
		
		// declarer for rss element
		$writer -> startElement( 'rss' );
		$writer -> writeAttribute( 'version', '2.0' );
		$writer -> writeAttribute( 'xmlns:atom', 'http://www.w3.org/2005/Atom' );
		
			// start element channel
			$writer -> startElement( 'channel' );
				//$writer -> writeElement( 'ttl', '0' );
				$writer -> writeElement( 'title', 'Google+ profile of ' . $oGooglePlus -> getName() );
				$writer -> writeElement( 'description', $oGooglePlus -> getIntroduction() . ', ' . $oGooglePlus -> getOccupation() );
				$writer -> writeElement( 'link', 'https://plus.google.com/' . $oGooglePlus -> strProfileId . '/posts' );
				$writer -> writeElement( 'pubDate', date( 'D, d M Y H:i:s e' ) );
				/*
				$writer -> startElement( 'image' );
				$writer -> writeElement( 'title', 'Latest Products' );
				$writer -> writeElement( 'link', 'http://www.domain.com/link.htm' );
				$writer -> writeElement( 'url', 'http://www.iab.net/media/image/120x60.gif' );
				$writer -> writeElement( 'width', '120' );
				$writer -> writeElement( 'height', '60' );
				*/
			$writer -> endElement();
			// end element channel
			
				$arrPosts = $oGooglePlus -> getPosts();
				$arrLinks = $oGooglePlus -> getLinks();
				$i = 0;
				foreach ($arrPosts as $strPost) {
					$writer -> startElement( 'item' );
					$writer -> writeElement( 'title', $strPost );
					// Found a link?
					if ( $arrLinks[$i] )
						$writer -> writeElement( 'link', 'https://plus.google.com/' . $oGooglePlus -> strProfileId . '/posts/' . $arrLinks[$i] );
					//$writer -> writeElement( 'description', '' );
					if ( $arrLinks[$i] )
						$writer -> writeElement( 'guid', 'https://plus.google.com/' . $oGooglePlus -> strProfileId . '/posts/' . $arrLinks[$i] );
					//$writer -> writeElement( 'pubDate', '' );
					
					$writer -> endElement();
					$i++;
				}
				$i++;
			
			// end element channel
			$writer -> endElement();
			
		// End rss element
		$writer -> endElement();
		$writer -> endDocument();
		
		$writer -> flush();
	}
}