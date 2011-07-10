<?php
/**
 * Simple GooglePlus-Feed
 *
 * @author     Frank Bueltge <frank@bueltge.de>
 * @copyright  2011 -- Frank Bueltge
 * @license    Creative Commons Attribution-NonCommercial-ShareAlike 3.0 Germany
 *             (http://creativecommons.org/licenses/by-nc-sa/3.0/de/deed.en)
 * @link       http://bueltge.de
 * @version    1.1
 * @date       10.07.2011 20:18:01
*/

require_once 'googleplus.class.php';

@date_default_timezone_set('GMT');

	$oGooglePlus = new GooglePlus('https://plus.google.com/111291152590065605567/posts');

	if ($oGooglePlus->isReady) {

		$writer = new XMLWriter();
		$writer->openURI('php://output');
		$writer->startDocument('1.0', 'UTF-8');
		$writer->setIndent(true);

		// declarer for rss element
		$writer->startElement('rss');
		$writer->writeAttribute('version', '2.0');
		$writer->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');

		// start element channel
		$writer->startElement('channel');
		$writer->writeElement('title', 'Google+ profile of ' . $oGooglePlus->get('name'));
		$writer->writeElement('description', strip_tags($oGooglePlus->get('introduction')));
		$writer->writeElement('link', $oGooglePlus->get('url'));
		$writer->writeElement('pubDate', date('D, d M Y H:i:s e') );
		$writer->endElement();
		// end element channel

		if ($oGooglePlus->get('plainposts') != 'n/A') {
			foreach ($oGooglePlus->get('plainposts') as $arrLink) {
				$writer->startElement('item');
				$writer->writeElement('title', GooglePlus::getShortText($arrLink[0], 120, true));
				$writer->writeElement('description', $arrLink[0]);
				if ($arrLink[1]) {
					$writer->writeElement('link', $arrLink[1]);
					$writer->writeElement('guid', $item['guid']    = $arrLink[1]);
				}
				$writer->endElement();
			}
		}

	// end element channel
	$writer->endElement();

	// End rss element
	$writer->endElement();
	$writer->endDocument();

	$writer->flush();
	}
