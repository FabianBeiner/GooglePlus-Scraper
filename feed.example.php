<?php
/**
 * GooglePlus Feed RSS 2.0
 *
 * @author     Frank Bueltge <frank@bueltge.de>
 * @copyright  2011 -- Frank Bueltge
 * @license    Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Germany
 *             (http://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.en)
 * @link       http://bueltge.de
 * @version    1.0
 * @date       04.07.2011 17:17:29
*/

require_once( 'rss2.class.php' );
require_once( 'googleplus.class.php' );
$arrTests = array( 'https://plus.google.com/111291152590065605567/posts' );

foreach ( $arrTests as $strTest ) {
	$oGooglePlus = new GooglePlus( $strTest );
	if ( $oGooglePlus -> isReady ) {

		$channel = array();
		$channel['title'] = 'Google+ profile of ' . $oGooglePlus -> getName();
		$channel['description'] = $oGooglePlus -> getIntroduction() . ', ' . $oGooglePlus -> getOccupation();
		$channel['link'] = 'https://plus.google.com/' . $oGooglePlus -> strProfileId . '/posts';

		$arrPosts = $oGooglePlus -> getPosts();
		$arrLinks = $oGooglePlus -> getLinks();
		$i = 0;
		$w = new Rss(
			'php://output',
			$channel['title'],
			$channel['description'],
			$channel['link'],
			date('Y-m-d')
		);
		foreach ($arrPosts as $strPost) {
			$item = array();
			$item['title']       = $oGooglePlus -> getShortText( $strPost, 120, TRUE );
			if ( $arrLinks[$i] )
				$item['link']    = 'https://plus.google.com/' . $oGooglePlus -> strProfileId . '/posts/' . $arrLinks[$i];
			$item['description'] = $strPost;
			if ( $arrLinks[$i] )
				$item['guid']    = 'https://plus.google.com/' . $oGooglePlus -> strProfileId . '/posts/' . $arrLinks[$i];
			//$item['date'] = date('Y-m-d');

			$w->add_item($item);
			$i++;
		}
		$i++;

		$w->end_rss();
	}
}
