<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of rateIt, a plugin for Dotclear 2.
#
# Copyright (c) 2009 JC Denis and contributors
# jcdenis@gdwd.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
#
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')) return;

require dirname(__FILE__).'/_widgets.php';

$core->url->register('rateit','rateit','^rateit/(.+)$',
	array('rateItPublic','file_get_contents'));

if (!$core->blog->settings->rateit_active) {

	$core->tpl->addBlock('rateIt',
		array('rateItPublic','disableBlock'));
	$core->tpl->addBlock('rateItIf',
		array('rateItPublic','disableBlock'));
	$core->tpl->addValue('rateItLinker',
		array('rateItPublic','disableValue'));
	$core->tpl->addValue('rateItTitle',
		array('rateItPublic','disableValue'));
	$core->tpl->addValue('rateItTotal',
		array('rateItPublic','disableValue'));
	$core->tpl->addValue('rateItMax',
		array('rateItPublic','disableValue'));
	$core->tpl->addValue('rateItMin',
		array('rateItPublic','disableValue'));
	$core->tpl->addValue('rateItNote',
		array('rateItPublic','disableValue'));
	$core->tpl->addValue('rateItFullnote',
		array('rateItPublic','disableValue'));
	$core->tpl->addValue('rateItQuotient',
		array('rateItPublic','disableValue'));

} else {
	$core->addBehavior('publicHeadContent',
		array('rateItPublic','publicHeadContent'));
	$core->addBehavior('publicEntryAfterContent',
		array('rateItPublic','publicEntryAfterContent'));

	$core->tpl->addBlock('rateIt',
		array('rateItPublic','rateIt'));
	$core->tpl->addBlock('rateItIf',
		array('rateItPublic','rateItIf'));
	$core->tpl->addValue('rateItLinker',
		array('rateItPublic','rateItLinker'));
	$core->tpl->addValue('rateItTitle',
		array('rateItPublic','rateItTitle'));
	$core->tpl->addValue('rateItTotal',
		array('rateItPublic','rateItTotal'));
	$core->tpl->addValue('rateItMax',
		array('rateItPublic','rateItMax'));
	$core->tpl->addValue('rateItMin',
		array('rateItPublic','rateItMin'));
	$core->tpl->addValue('rateItQuotient',
		array('rateItPublic','rateItQuotient'));
	$core->tpl->addValue('rateItNote',
		array('rateItPublic','rateItNote'));
	$core->tpl->addValue('rateItFullnote',
		array('rateItPublic','rateItFullnote'));

	$core->url->register('rateitnow','rateitnow','^rateitnow/(.+)$',
		array('rateItPublic','rateitnow'));

	$core->url->register('rateitservice','rateitservice','^rateitservice/$',
		array('rateItRest','service'));
}

class rateItPublic extends dcUrlHandlers
{
	public static function disableUrl($a)
	{
		self::p404(); exit;
	}
	public static function disableBlock($a,$b)
	{
		return '';
	}
	public static function disableValue($a)
	{
		return '';
	}
	private static function dirname(&$f)
	{
		$d = array_pop(explode(PATH_SEPARATOR, DC_PLUGINS_ROOT.'/rateIt/default-templates'));
		$f = $d.'/'.$f;
		return dirname($f);
	}

	public static function rateitnow($args)
	{
		global $core;

		if (!$core->blog->settings->rateit_active) {
			self::p404();
			exit;
		}

		if (!preg_match('#([^/]+)/([^/]+)/([^/]+)$#',$args,$m)) {
			self::p404();
			exit;
		}

		$voted = false;
		$type = $m[1];
		$id = $m[2];
		$note = $m[3];

		$ss = new rateIt($core);
		$voted = $ss->voted($type,$id);
		if (!$voted) {
			$ss->set($type,$id,$note);
			$voted = true;
		}

		if ($type='post') {
			$post = $core->blog->getPosts(array('post_id'=>$id,'no_content'=>1));
			if ($post->post_id) {
				http::redirect($core->blog->url.'post/'.$post->post_url.($voted ? '#rateit' : ''));
			}
		}
	}

	public static function file_get_contents($args)
	{
		global $core;

		if (!$core->blog->settings->rateit_active) {
			self::p404();
			exit;
		}

		if (!preg_match('#^(.*?)$#',$args,$m)) {
			self::p404();
			exit;
		}

		$f = $m[1];
		if (strstr($f,"..") !== false) {
			self::p404();
			exit;
		}

		$path = self::dirname($f);
		if (!is_dir($path)) {
			self::p404();
			exit;
		}

		$allowed_types = array('png','jpg','jpeg','gif','css','js','swf');
		if (!file_exists($f) || !in_array(files::getExtension($f),$allowed_types)) {
			self::p404();
			exit;
		}

		//http::cache(array_merge(array($f),get_included_files()));
		$type = files::getMimeType($f);
		header('Content-Type: '.$type);
		header('Content-Length: '.filesize($f));
		if ($type != "text/css" || $core->blog->settings->url_scan == 'path_info') {
			readfile($f);
		} else {
			echo preg_replace('#url\((?!(http:)|/)#','url('.$core->blog->url.'rateit/',file_get_contents($f));
		}
		exit;
	}

	public static function publicHeadContent(&$core)
	{
		echo "\n".
		'<script type="text/javascript" src="'.$core->blog->url.'rateit/js/jquery.rating.pack.js"></script>'."\n".
		"<style type=\"text/css\">\n@import url(".$core->blog->url."rateit/js/jquery.rating.css);\n</style>\n".
		'<script type="text/javascript" src="'.$core->blog->url.'rateit/js/rateit.js"></script>'."\n".
		"<style type=\"text/css\">\n@import url(".$core->blog->url."rateit/rateit.css);\n</style>\n".
		'<script type="text/javascript">'."\n".
		"//<![CDATA[\n".
		"rateIt.prototype.service_url = '".html::escapeJS($core->blog->url.'rateitservice/')."';\n".
		"rateIt.prototype.blocs = ['rateit','rateitwidget'];\n".
		"\n//]]>\n".
		"</script>\n";
	}

	public static function publicEntryAfterContent($core,$_ctx)
	{
		if (!$core->blog->settings->rateit_poststpl) return;

		$f = 'tpl/rateit.html';
		$d = self::dirname($f);
		$core->tpl->setPath($core->tpl->getPath(),$d);

		if ('' != ($fc = $core->tpl->getData('rateit.html'))) {
			echo $fc;
		}
	}

	public static function rateIt($attr,$content)
	{
		$res = 
		'<?php'."\n".
		'$rateit_type = !$_ctx->exists("posts") ? "cat" : "post";'."\n".
		'$rateit_id = !$_ctx->exists("posts") ? $_ctx->categories->cat_id : $_ctx->posts->post_id;'."\n".
		'$rateIt = new rateIt($core);'."\n".
		'$rateit_voted= $rateIt->voted($rateit_type,$rateit_id);'."\n".
		'$_ctx->rateIt = $rateIt->get($rateit_type,$rateit_id);'."\n".
		'?>'."\n".$content."\n".
		'<?php'."\n".
		'unset($rateit_type,$rateit_id,$rateit_voted);'."\n".
		'$_ctx->rateIt = null;'."\n".
		'?>';
	
		return $res;
	}

	public static function rateItIf($attr,$content)
	{
		$res =
		'<?php $star_if = 0;'."\n";
		if (isset($attr['has_vote'])) {
			$res .= $attr['has_vote'] == 1 ?
				 'if ($_ctx->rateIt->total > 0) { $star_if = 1; }' :
				 'if ($_ctx->rateIt->total == 0) { $star_if = 1; }';
		}

		$res .=
		'if ($star_if == 1) { ?>'.$content.'<?php } ?>'."\n";

		return $res;
	}

	public static function rateItTitle($attr)
	{
		global $core,$_ctx;
		$f = $core->tpl->getFilters($attr);

		if (!$_ctx->exists("posts"))
			return '<?php echo '.sprintf($f,"__('Vote for this category')").'; ?>';
		else
			return '<?php echo '.sprintf($f,"__('Vote for this enrty')").'; ?>';
	}

	public static function rateItLinker($attr)
	{
		global $core;
		$f = $core->tpl->getFilters($attr);
		return 
		'<?php '."\n".
		'echo \'<form class="rateit-linker" id="raiteit-linker-\'.$rateit_type.\'-\'.$rateit_id.\'" action="'.$core->blog->url.'rateitnow/\'.$rateit_type.\'/\'.$rateit_id.\'/" method="post"><p>\';'."\n".
		'for($i=0;$i<$_ctx->rateIt->quotient;$i++){'."\n".
		'	$dis = $rateit_voted ?'."\n".
		'		\' disabled="disabled"\' : \'\';'."\n".
		'	$chk = $_ctx->rateIt->note > $i && $_ctx->rateIt->note <= $i+1 ? '.
		'		\' checked="checked"\' : \'\';'."\n".
		'	echo \'<input name="rateit-\'.$rateit_type.\'-\'.$rateit_id.\'" class="rateit-\'.$rateit_type.\'-\'.$rateit_id.\'" type="radio" value="\'.($i+1).\'"\'.$chk.$dis.\'/>'."\n".
		'\'; } ?>'."\n".
		'<input type="submit" name="submit" value="'.__('Vote').'"/>'."\n".
		'</p></form>';
	}

	public static function rateItFullnote($attr)
	{		global $core;
		$f = $core->tpl->getFilters($attr);
		return '<?php echo \'<span id="rateit-fullnote-\'.$rateit_type.\'-\'.$rateit_id.\'"  class="rateit-fullnote">\'.'.sprintf($f,'$_ctx->rateIt->note."/".$_ctx->rateIt->quotient').'.\'</span>\'; ?>';
	}

	public static function rateItQuotient($attr)
	{
		return self::rateItValue($attr,'quotient');
	}

	public static function rateItTotal($attr)
	{
		return self::rateItValue($attr,'total');
	}

	public static function rateItMax($attr)
	{
		return self::rateItValue($attr,'max');
	}

	public static function rateItMin($attr)
	{
		return self::rateItValue($attr,'min');
	}

	public static function rateItNote($attr)
	{
		return self::rateItValue($attr,'note');
	}

	private static function rateItValue($a,$r)
	{
		global $core;
		$f = $core->tpl->getFilters($a);
		return '<?php echo \'<span id="rateit-'.$r.'-\'.$rateit_type.\'-\'.$rateit_id.\'"  class="rateit-'.$r.'">\'.'.sprintf($f,'$_ctx->rateIt->'.$r).'.\'</span>\'; ?>';
	}
}

?>