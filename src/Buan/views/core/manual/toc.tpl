<?php
/* $Id: toc.tpl 155 2007-09-17 10:48:30Z james $ */
use Buan\UrlCommand;
?>

<?php
function writeToc($toc, $indexPrefix=NULL, $labelPrefix=NULL) {
	$html = '<dl>';
	$index = 1;
	foreach($toc as $label=>$chapter) {
		$chapterNumber = $indexPrefix===NULL ? $index : "$indexPrefix.$index";
		$hash = substr($label, 0, 1)=='#' ? $label : '';
		$label = $labelPrefix===NULL ? $label : (substr($label, 0, 1)=='#' ? "$labelPrefix": "$labelPrefix.$label");

		$html .= '<dt>'.$chapterNumber.' &nbsp; <a href="'.UrlCommand::createUrl('core', 'manual', $label).$hash.'">'.htmlentities($chapter['title'], ENT_QUOTES, 'UTF-8').'</a>';
		if(isset($chapter['chapters'])) {
			$html .= '<dd>'.writeToc($chapter['chapters'], $chapterNumber, $label).'</dd>';
		}
		$html .= '</dt>';
		$index++;
	}
	$html .= '</dl>';
	return $html;
}
echo writeToc($toc);
?>