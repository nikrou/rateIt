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

if (!defined('DC_RC_PATH')){return;}

require dirname(__FILE__).'/_widgets.php';

$core->addBehavior('publicHeadContent',array('urlRateIt','publicHeadContent'));
$core->addBehavior('publicEntryAfterContent',array('urlRateIt','publicEntryAfterContent'));
$core->addBehavior('publicCommentAfterContent',array('urlRateIt','publicCommentAfterContent'));

if (!$core->blog->settings->rateit_active) {

	$core->tpl->addBlock('rateIt',array('tplRateIt','disable'));
	$core->tpl->addBlock('rateItIf',array('tplRateIt','disable'));
	$core->tpl->addValue('rateItLinker',array('tplRateIt','disable'));
	$core->tpl->addValue('rateItTitle',array('tplRateIt','disable'));
	$core->tpl->addValue('rateItTotal',array('tplRateIt','disable'));
	$core->tpl->addValue('rateItMax',array('tplRateIt','disable'));
	$core->tpl->addValue('rateItMin',array('tplRateIt','disable'));
	$core->tpl->addValue('rateItNote',array('tplRateIt','disable'));
	$core->tpl->addValue('rateItFullnote',array('tplRateIt','disable'));
	$core->tpl->addValue('rateItQuotient',array('tplRateIt','disable'));

} else {
	$core->tpl->setPath($core->tpl->getPath(),dirname(__FILE__).'/default-templates/tpl/');

	$core->tpl->addBlock('rateIt',array('tplRateIt','rateIt'));
	$core->tpl->addBlock('rateItIf',array('tplRateIt','rateItIf'));
	$core->tpl->addValue('rateItLinker',array('tplRateIt','rateItLinker'));
	$core->tpl->addValue('rateItTitle',array('tplRateIt','rateItTitle'));
	$core->tpl->addValue('rateItTotal',array('tplRateIt','rateItTotal'));
	$core->tpl->addValue('rateItMax',array('tplRateIt','rateItMax'));
	$core->tpl->addValue('rateItMin',array('tplRateIt','rateItMin'));
	$core->tpl->addValue('rateItQuotient',array('tplRateIt','rateItQuotient'));
	$core->tpl->addValue('rateItNote',array('tplRateIt','rateItNote'));
	$core->tpl->addValue('rateItFullnote',array('tplRateIt','rateItFullnote'));
}

class urlRateIt extends dcUrlHandlers
{
	private static function searchRateItTplFiles($file)
	{
		if (strstr($file,"..") !== false)
			return false;

		$paths = $GLOBALS['core']->tpl->getPath();

		foreach($paths as $path)
		{
			if (preg_match('/tpl(\/|)$/',$path) )
				$path = path::real($path.'/..');

			if (file_exists($path.'/'.$file))
				return $path.'/'.$file;
		}
		return false;
	}

	public static function service($args)
	{
		global $core;
		$core->rest->addFunction('rateItVote',array('rateItRest','vote'));
		$core->rest->serve();
		exit;
	}

	public static function postform($args)
	{
		global $core;

		if (!$core->blog->settings->rateit_active) {
			self::p404();
			exit;
		}

		if (!preg_match('#([^/]+)/([^/]+)$#',$args,$m)) {
			self::p404();
			exit;
		}

		if (!isset($_POST['rateit-'.$m[1].'-'.$m[2]])) {
			self::p404();
			exit;
		}

		$voted = false;
		$type = $m[1];
		$id = $m[2];
		$note = $_POST['rateit-'.$m[1].'-'.$m[2]];

		$ss = new rateIt($core);
		$voted = $ss->voted($type,$id);
		if (!$voted) {
			$ss->set($type,$id,$note);
			$voted = true;
		}

		if ($type='post') {
			$post = $core->blog->getPosts(array('post_id'=>$id,'no_content'=>1));
			if ($post->post_id) {
				http::redirect($core->blog->url.$core->url->getBase('post').'/'.$post->post_url.($voted ? '#rateit' : ''));
				exit;
			}
		}

		if ($type='comment') {
			$comment = $core->blog->getComments($id);
			if ($comment->comment_id) {
				http::redirect($core->blog->url.$core->url->getBase('post').'/'.$post->post_url.($voted ? '#rateit' : ''));
			}
		}

		if ($type='category') {
			$cat = $core->blog->getCategory($id);
			if ($cat->cat_id) {
				http::redirect($core->blog->url.$core->url->getBase('category').'/'.$cat->cat_url.($voted ? '#rateit' : ''));
			}
		}


		# --BEHAVIOR-- templateRateItRedirect
		$core->callBehavior('templateRateItRedirect',$voted,$type,$id,$note);


		http::redirect($core->blog->url);
		exit;
	}

	public static function files($args)
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

		if (!($f = self::searchRateItTplFiles($m[1]))) {
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
			echo preg_replace('#url\((?!(http:)|/)#','url('.$core->blog->url.$core->url->getBase('rateItmodule').'/',file_get_contents($f));
		}
		exit;
	}

	public static function publicHeadContent($core)
	{
		if (!$core->blog->settings->rateit_active) return;

		$s = libImagePath::getSize($core,'rateIt');

		echo 
		"\n<!-- CSS for rateit --> \n".
		"<style type=\"text/css\"> \n".
		"div.rating-cancel,div.star-rating{float:left;width:".($s['w']+1)."px;height:".$s['h']."px;text-indent:-999em;cursor:pointer;display:block;background:transparent;overflow:hidden} \n".
		"div.rating-cancel,div.rating-cancel a{background:url(".
			$core->blog->url.$core->url->getBase('rateItmodule')."/img/delete.png) no-repeat 0 -16px} \n".
		"div.star-rating,div.star-rating a{background:url(".
			libImagePath::getUrl($core,'rateIt').") no-repeat 0 0px} \n".
		"div.rating-cancel a,div.star-rating a{display:block;width:".$s."px;height:100%;background-position:0 0px;border:0} \n".
		"div.star-rating-on a{background-position:0 -".$s['h']."px!important} \n".
		"div.star-rating-hover a{background-position:0 -".($s['h']*2)."px} \n".
		"div.star-rating-readonly a{cursor:default !important} \n".
		"div.star-rating{background:transparent!important;overflow:hidden!important} \n".
		"</style> \n".
		"<style type=\"text/css\"> \n @import url(".
			$core->blog->url.$core->url->getBase('rateItmodule')."/rateit.css); \n</style> \n";

		if ($core->blog->settings->rateit_dispubjs) return;

		echo 
		"\n<!-- JS for rateit --> \n".
		"<script type=\"text/javascript\" src=\"".
			$core->blog->url.$core->url->getBase('rateItmodule').'/js/jquery.rating.pack.js">'.
		"</script> \n".
		"<script type=\"text/javascript\" src=\"".
			$core->blog->url.$core->url->getBase('rateItmodule')."/js/jquery.rateit.js\"></script> \n".
		"<script type=\"text/javascript\"> \n".
		"//<![CDATA[\n".
		" \$(function(){if(!document.getElementById){return;} \n".
		"  \$.fn.rateit.defaults.service_url = '".html::escapeJS($core->blog->url.$core->url->getBase('rateItservice').'/')."'; \n".
		"  \$.fn.rateit.defaults.service_func = '".html::escapeJS('rateItVote')."'; \n".
		"  \$.fn.rateit.defaults.image_size = '".$s['h']."'; \n".
		"  \$.fn.rateit.defaults.blog_uid = '".html::escapeJS($core->blog->uid)."'; \n".
		"  \$.fn.rateit.defaults.enable_cookie = '".($core->blog->settings->rateit_userident > 0 ? '1' : '0')."'; \n".
		"  \$.fn.rateit.defaults.msg_thanks = '".html::escapeJS($core->blog->settings->rateit_msgthanks)."'; \n".
		"  \$('.rateit').rateit(); \n".
		" })\n".
		"//]]>\n".
		"</script>\n";
	}

	public static function publicEntryAfterContent($core,$_ctx)
	{
		if ($core->blog->settings->rateit_active 
		 && $core->blog->settings->rateit_post_active
		 && $_ctx->exists('posts') 
		 && $_ctx->posts->post_type == 'post'
		 && (
			 $core->blog->settings->rateit_poststpl && 'post.html' == $_ctx->current_tpl 
		  || $core->blog->settings->rateit_homepoststpl && 'home.html' == $_ctx->current_tpl
		  || $core->blog->settings->rateit_tagpoststpl && 'tag.html' == $_ctx->current_tpl 
		  || $core->blog->settings->rateit_categorypoststpl && 'category.html' == $_ctx->current_tpl
		 )
		 && (
			!$core->blog->settings->rateit_categorylimitposts
		  || $core->blog->settings->rateit_categorylimitposts == $_ctx->posts->cat_id
		 )
		) {

			$_ctx->rateit_params = new ArrayObject();
			$_ctx->rateit_params->type = 'post';
			$_ctx->rateit_params->id = $_ctx->posts->post_id;

			echo $core->tpl->getData('rateit.html');

		} else return;
	}

	public static function publicCommentAfterContent($core,$_ctx)
	{
		if (!$core->blog->settings->rateit_active 
		 || !$core->blog->settings->rateit_comment_active 
		 || !$core->blog->settings->rateit_commentstpl 
		 || !$_ctx->exists('comments')) return;

		$_ctx->rateit_params = new ArrayObject();
		$_ctx->rateit_params->type = 'comment';
		$_ctx->rateit_params->id = $_ctx->comments->comment_id;

		echo $core->tpl->getData('rateit.html');
	}
}

class tplRateIt
{
	public static function disable($a,$b=null)
	{
		return '';
	}

	public static function rateIt($attr,$content)
	{
		global $core;

		$type = isset($attr['type']) ? $attr['type'] : '';
		$return = '';

		if ($type == 'post') {

			$return .= 
			"if (\$_ctx->exists('posts') && \$_ctx->posts->post_type == 'post' ".
			" && \$core->blog->settings->rateit_post_active) { \n".
			" \$_ctx->rateit_params->type = 'post'; \n".
			" \$_ctx->rateit_params->id = \$_ctx->posts->post_id; \n".
			"} \n";
		}
		elseif ($type == 'comment') {

			$return .= 
			"if (\$_ctx->exists('comments') ".
			" && \$core->blog->settings->rateit_comment_active) { \n".
			" \$_ctx->rateit_params->type = 'comment'; \n".
			" \$_ctx->rateit_params->id = \$_ctx->comments->comment_id; \n".
			"} \n";
		}
		elseif ($type == 'category') {

			$return .= 
			"if (\$_ctx->exists('categories') ".
			" && \$core->blog->settings->rateit_category_active) { \n".
			" \$_ctx->rateit_params->type = 'category'; \n".
			" \$_ctx->rateit_params->id = \$_ctx->categories->cat_id; \n".
			"} \n";
		}


		# --BEHAVIOR-- templateRateIt
		$return .= $core->callBehavior('templateRateIt',$type);


		return
		"<?php \n".
		"if (!\$_ctx->rateit_params->type) { \n".
		" \$_ctx->rateit_params = new ArrayObject(); \n".
		" \$rateit_params->type = ''; \n".
		" \$rateit_params->id = 0; \n".
		"} \n".
		$return.
		"if (\$_ctx->rateit_params->type != '') { \n".
		" \$rateIt = new rateIt(\$core); \n".
		" \$rateit_voted= \$rateIt->voted(\$_ctx->rateit_params->type,\$_ctx->rateit_params->id); \n".
		" \$_ctx->rateIt = \$rateIt->get(\$_ctx->rateit_params->type,\$_ctx->rateit_params->id); \n".
		" ?> \n".
		$content."\n".
		" <?php \n".
		" unset(\$rateit_voted); \n".
		" \$_ctx->rateIt = null; \n".
		"} \n".
		"\$_ctx->rateit_params = null; \n".
		"?> \n";
	}

	public static function rateItIf($attr,$content)
	{
		$operator = isset($attr['operator']) ? self::getOperator($attr['operator']) : '&&';

		if (isset($attr['has_vote'])) {

			$sign = (boolean) $attr['has_vote'] ? '' : '!';
			$if[] = $sign.'(0 < $_ctx->rateIt->total)';
		}

		if (empty($if))
			return $content;

		return 
		"<?php if(".implode(' '.$operator.' ',$if).") : ?>\n".
		$content.
		"<?php endif; ?>\n";
	}

	public static function rateItTitle($attr)
	{
		global $core;
		$f = $core->tpl->getFilters($attr);

		return 
		"<?php \n".
		"\$title = ''; \n".
		"if (\$_ctx->rateit_params->type == 'post') \n".
		" \$title = __('Rate this entry'); \n".
		"elseif (\$_ctx->rateit_params->type == 'comment') \n".
		" \$title = __('Rate this comment'); \n".
		"elseif (\$_ctx->rateit_params->type == 'category') \n".
		" \$title = __('Rate this category'); \n".
		"else \n".
		" \$title = \$core->callBehavior('templateRateItTitle',\$_ctx->rateit_params->type,\$title); \n\n".
		"echo ".sprintf($f,'$title')."; \n".
		"?> \n";

	}

	public static function rateItLinker($attr)
	{
		global $core;
		$f = $core->tpl->getFilters($attr);
		return 
		'<?php '."\n".
		'echo \'<form class="rateit-linker" id="rateit-linker-\'.$_ctx->rateit_params->type.\'-\'.$_ctx->rateit_params->id.\'" action="'.
			$core->blog->url.$core->url->getBase('rateItpostform').'/\'.$_ctx->rateit_params->type.\'/\'.$_ctx->rateit_params->id.\'" method="post"><p>\';'."\n".
		'for($i=0;$i<$_ctx->rateIt->quotient;$i++){'."\n".
		'	$dis = $rateit_voted ?'."\n".
		'		\' disabled="disabled"\' : \'\';'."\n".
		'	$chk = $_ctx->rateIt->note > $i && $_ctx->rateIt->note <= $i+1 ? '.
		'		\' checked="checked"\' : \'\';'."\n".
		'	echo \'<input name="rateit-\'.$_ctx->rateit_params->type.\'-\'.$_ctx->rateit_params->id.\'" class="rateit-\'.$_ctx->rateit_params->type.\'-\'.$_ctx->rateit_params->id.\'" type="radio" value="\'.($i+1).\'"\'.$chk.$dis.\'/>'."\n".
		'\'; } ?>'."\n".
		'<input type="submit" name="submit" value="'.__('Vote').'"/>'."\n".
		'</p></form>';
	}

	public static function rateItFullnote($attr)
	{		global $core;
		$f = $core->tpl->getFilters($attr);
		return '<?php echo \'<span id="rateit-fullnote-\'.$_ctx->rateit_params->type.\'-\'.$_ctx->rateit_params->id.\'"  class="rateit-fullnote">\'.'.sprintf($f,'$_ctx->rateIt->note."/".$_ctx->rateIt->quotient').'.\'</span>\'; ?>';
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
		return '<?php echo \'<span id="rateit-'.$r.'-\'.$_ctx->rateit_params->type.\'-\'.$_ctx->rateit_params->id.\'"  class="rateit-'.$r.'">\'.'.sprintf($f,'$_ctx->rateIt->'.$r).'.\'</span>\'; ?>';
	}

	protected static function getOperator($op)
	{
		switch (strtolower($op))
		{
			case 'or':
			case '||':
				return '||';
			case 'and':
			case '&&':
			default:
				return '&&';
		}
	}
}
?>