<?php
/**
 * GooglePlus Feed RSS 2.0
 *
 * @author     Frank Bueltge <frank@bueltge.de>
 * @copyright  2011 -- Frank Bueltge
 * @license    Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Germany
 *             (http://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.en)
 * @link       http://bueltge.de
 * @version    1.1
 * @date       10.07.2011 20:11:01
*/

require_once 'rss2.class.php';
require_once 'googleplus.class.php';

$oGooglePlus = new GooglePlus('https://plus.google.com/111291152590065605567/posts');
if ($oGooglePlus->isReady) {

	$channel                = array();
	$channel['title']       = 'Google+ profile of ' . $oGooglePlus->get('name');
	$channel['description'] = strip_tags($oGooglePlus->get('introduction'));
	$channel['link']        = $oGooglePlus->get('url');

	$w = new Rss(
		'php://output',
		$channel['title'],
		$channel['description'],
		$channel['link'],
		date('Y-m-d')
	);
	if ($oGooglePlus->get('plainposts') != 'n/A') {
		foreach ($oGooglePlus->get('plainposts') as $arrLink) {
			$item = array();
			$item['title']       = GooglePlus::getShortText($arrLink[0], 120, true);
			if ($arrLink[1]) {
				$item['link']    = $arrLink[1];
				$item['guid']    = $arrLink[1];
			}
			$item['description'] = $arrLink[0];
			$w->add_item($item);
		}
	}

	$w->end_rss();
}
