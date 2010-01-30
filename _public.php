<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of rateIt, a plugin for Dotclear 2.
# 
# Copyright (c) 2009-2010 JC Denis and contributors
# jcdenis@gdwd.com
# 
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

if (!defined('DC_RC_PATH')){return;}

require dirname(__FILE__).'/_widgets.php';

$core->addBehavior('publicHeadContent',array('urlRateIt','publicHeadContent'));
$core->addBehavior('publicEntryAfterContent',array('urlRateIt','publicEntryAfterContent'));
$core->addBehavior('publicCommentAfterContent',array('urlRateIt','publicCommentAfterContent'));
$core->addBehavior('publicEntryAfterContent',array('urlRateIt','publicGalleryAfterContent'));

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
			return;
		}

		if (!preg_match('#([^/]+)/([^/]+)$#',$args,$m)) {
			self::p404();
			return;
		}

		if (!isset($_POST['rateit-'.$m[1].'-'.$m[2]])) {
			self::p404();
			return;
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
				return;
			}
		}

		if ($type='comment') {
			$comment = $core->blog->getComments($id);
			if ($comment->comment_id) {
				http::redirect($core->blog->url.$core->url->getBase('post').'/'.$post->post_url.($voted ? '#rateit' : ''));
				return;
			}
		}

		if ($type='category') {
			$cat = $core->blog->getCategory($id);
			if ($cat->cat_id) {
				http::redirect($core->blog->url.$core->url->getBase('category').'/'.$cat->cat_url.($voted ? '#rateit' : ''));
				return;
			}
		}

		if ($type='tag') {
			$objMeta = new dcMeta($core);
			$metas = $objMeta->getMeta('tag',null,$id);
			if ($metas->meta_id) {
				http::redirect($core->blog->url.$core->url->getBase('tag').'/'.$metas->meta_id.($voted ? '#rateit' : ''));
				return;
			}
		}

		if ($type='gal') {
			$gal = $core->blog->getPost(array('post_id'=>$id,'no_content'=>true));
			if ($gal->cat_id) {
				http::redirect($core->blog->url.$core->url->getBase('galleries').'/'.$gal->post_url.($voted ? '#rateit' : ''));
				return;
			}
		}

		if ($type='galitem') {
			$gal = $core->blog->getPost(array('post_id'=>$id,'no_content'=>true));
			if ($gal->cat_id) {
				http::redirect($core->blog->url.$core->url->getBase('gal').'/'.$gal->post_url.($voted ? '#rateit' : ''));
				return;
			}
		}
		# --BEHAVIOR-- templateRateItRedirect
		$core->callBehavior('templateRateItRedirect',$voted,$type,$id,$note);


		http::redirect($core->blog->url);
		return;
	}

	public static function files($args)
	{
		global $core;

		if (!$core->blog->settings->rateit_active) {
			self::p404();
			return;
		}

		if (!preg_match('#^(.*?)$#',$args,$m)) {
			self::p404();
			return;
		}

		if (!($f = self::searchRateItTplFiles($m[1]))) {
			self::p404();
			return;
		}

		$allowed_types = array('png','jpg','jpeg','gif','css','js','swf');
		if (!file_exists($f) || !in_array(files::getExtension($f),$allowed_types)) {
			self::p404();
			return;
		}

		//http::cache(array_merge(array($f),get_included_files()));
		$type = files::getMimeType($f);
		header('Content-Type: '.$type.'; charset=UTF-8');
		//header('Content-Length: '.filesize($f));
		if ($type != "text/css" || $core->blog->settings->url_scan == 'path_info') {
			readfile($f);
		} else {
			echo preg_replace('#url\((?!(http:)|/)#','url('.$core->blog->url.$core->url->getBase('rateItmodule').'/',file_get_contents($f));
		}
		return;
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
		"div.rating-cancel a,div.star-rating a{display:block;width:".$s['w']."px;height:100%;background-position:0 0px;border:0} \n".
		"div.star-rating-on a{background-position:0 -".$s['h']."px!important} \n".
		"div.star-rating-hover a{background-position:0 -".($s['h']*2)."px} \n".
		"div.star-rating-readonly a{cursor:default !important} \n".
		"div.star-rating{background:transparent!important;overflow:hidden!important} \n".
		"</style> \n";

		if (!$core->blog->settings->rateit_dispubcss) {

			echo
			"<style type=\"text/css\"> \n @import url(".
				$core->blog->url.$core->url->getBase('rateItmodule')."/rateit.css); \n".
			"</style> \n";
		}

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

			$GLOBALS['rateit_params']['type'] = 'post';
			$GLOBALS['rateit_params']['id'] = $_ctx->posts->post_id;

			echo $core->tpl->getData('rateit.html');

		} else return;
	}

	public static function publicCommentAfterContent($core,$_ctx)
	{
		if (!$core->blog->settings->rateit_active 
		 || !$core->blog->settings->rateit_comment_active 
		 || !$core->blog->settings->rateit_commentstpl 
		 || !$_ctx->exists('comments')) return;

		$GLOBALS['rateit_params']['type'] = 'comment';
		$GLOBALS['rateit_params']['id'] = $_ctx->comments->comment_id;

		echo $core->tpl->getData('rateit.html');
	}

	public static function publicGalleryAfterContent($core,$_ctx)
	{
		if (!$core->plugins->moduleExists('gallery')
		 || !$_ctx->exists('posts')) return;

		if ($_ctx->posts->post_type == 'gal' 
		 && $core->blog->settings->rateit_gal_active 
		 && $core->blog->settings->rateit_galtpl
		 || $_ctx->posts->post_type == 'galitem'  
		 && $core->blog->settings->rateit_galitem_active
		 && $core->blog->settings->rateit_galitemtpl) {

			$GLOBALS['rateit_params']['type'] = $_ctx->posts->post_type;
			$GLOBALS['rateit_params']['id'] = $_ctx->posts->post_id;

			echo $core->tpl->getData('rateit.html');
		}
		return;
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

			$return = 
			"if (\$_ctx->exists('posts')".
			" && \$_ctx->posts->post_type == 'post'".
			" && \$core->blog->settings->rateit_post_active) { \n".
			" \$rateit_params['type'] = 'post'; \n".
			" \$rateit_params['id'] = \$_ctx->posts->post_id; \n".
			"} \n";
		}
		elseif ($type == 'comment') {

			$return = 
			"if (\$_ctx->exists('comments')".
			" && \$core->blog->settings->rateit_comment_active) { \n".
			" \$rateit_params['type'] = 'comment'; \n".
			" \$rateit_params['id'] = \$_ctx->comments->comment_id; \n".
			"} \n";
		}
		elseif ($type == 'category') {

			$return = 
			"if (\$_ctx->exists('categories')".
			" && \$core->blog->settings->rateit_category_active) { \n".
			" \$rateit_params['type'] = 'category'; \n".
			" \$rateit_params['id'] = \$_ctx->categories->cat_id; \n".
			"} \n";
		}
		elseif ($type == 'tag' || $type == 'meta') {

			$return = 
			"if (\$_ctx->exists('meta')".
			" && \$_ctx->meta->meta_type = 'tag'".
			" && \$core->blog->settings->rateit_tag_active) { \n".
			" \$rateit_params['type'] = 'tag'; \n".
			" \$rateit_params['id'] = \$_ctx->meta->meta_id; \n".
			"} \n";
		}
		elseif ($type == 'gal' || $type == 'galitem') {

			$return = 
			"if (\$_ctx->exists('posts') ".
			" && \$_ctx->posts->post_type == '".$type."' ".
			" && \$core->blog->settings->rateit_".$type."_active ".
			" && \$core->blog->settings->rateit_".$type."tpl) { \n".
			" \$rateit_params['type'] = '".$type."'; \n".
			" \$rateit_params['id'] = \$_ctx->posts->post_id; \n".
			"} \n";
		}

		# --BEHAVIOR-- templateRateIt
		$return .= $core->callBehavior('templateRateIt',$type);


		return
		"<?php \n".
		$return.
		"if (!empty(\$rateit_params['type'])) { \n".
		" \$rateIt = new rateIt(\$core); \n".
		" \$rateit_voted = \$rateIt->voted(\$rateit_params['type'],\$rateit_params['id']); \n".
		" \$_ctx->rateIt = \$rateIt->get(\$rateit_params['type'],\$rateit_params['id']); \n".
		" ?> \n".
		$content."\n".
		" <?php \n".
		" unset(\$rateit_voted); \n".
		" \$_ctx->rateIt = null; \n".
		"} \n".
		"unset(\$rateit_params); \n".
		"?> \n";
	}

	public static function rateItIf($attr,$content)
	{
		$operator = isset($attr['operator']) ? self::getOperator($attr['operator']) : '&&';

		if (isset($attr['has_vote'])) {

			$sign = (boolean) $attr['has_vote'] ? '' : '!';
			$if[] = $sign.'(0 < $_ctx->rateIt->total)';
		}

		if (isset($attr['user_voted'])) {

			$sign = (boolean) $attr['user_voted'] ? '' : '!';
			$if[] = $sign.'$rateit_voted';
		}

		if (!empty($attr['type'])) {

			if (substr($attr['type'],0,1) == '!')
				$if[] = '\''.substr($attr['type'],1).'\' != $_ctx->rateIt->type';
			else
				$if[] = '\''.$attr['type'].'\' == $_ctx->rateIt->type';
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
		"if (\$_ctx->rateIt->type == 'post') \$title = __('Rate this entry'); \n".
		"elseif (\$_ctx->rateIt->type == 'comment') \$title = __('Rate this comment'); \n".
		"elseif (\$_ctx->rateIt->type == 'category') \$title = __('Rate this category'); \n".
		"elseif (\$_ctx->rateIt->type == 'tag') \$title = __('Rate this tag'); \n".
		"elseif (\$_ctx->rateIt->type == 'gal') \$title = __('Rate this gallery'); \n".
		"elseif (\$_ctx->rateIt->type == 'galitem') \$title = __('Rate this image'); \n".
		"else \n".
		" \$title = \$core->callBehavior('templateRateItTitle',\$_ctx->rateIt->type,\$title); \n\n".
		"echo ".sprintf($f,'$title')."; \n".
		"?> \n";

	}

	public static function rateItLinker($attr)
	{
		global $core;
		$f = $core->tpl->getFilters($attr);
		return 
		"<form class=\"rateit-linker\" id=\"rateit-linker-<?php echo \$_ctx->rateIt->type.'-'.\$_ctx->rateIt->id; ?>\" action=\"".
		"<?php echo \$core->blog->url.\$core->url->getBase('rateItpostform').'/'.\$_ctx->rateIt->type.'/'.\$_ctx->rateIt->id; ?>\" method=\"post\"><p>\n".
		"<?php for(\$i=0;\$i<\$_ctx->rateIt->quotient;\$i++){ \n".
		" \$dis = \$rateit_voted ? ' disabled=\"disabled\"' : ''; \n".
		" \$chk = \$_ctx->rateIt->note > \$i && \$_ctx->rateIt->note <= \$i+1 ? ' checked=\"checked\"' : ''; \n".
		" echo '<input name=\"rateit-'.\$_ctx->rateIt->type.'-'.\$_ctx->rateIt->id.'\" class=\"rateit-'.\$_ctx->rateIt->type.'-'.\$_ctx->rateIt->id.'\" type=\"radio\" value=\"'.(\$i+1).'\"'.\$chk.\$dis.' />'; \n".
		"} \n".
		"if (!\$rateit_voted) { \n".
		" echo '<input class=\"rateit-submit\" name=\"rateit_submit\" type=\"submit\" value=\"".__('Vote')."\"/>'; \n".
		"} ?>\n".
		"</p></form>";
	}

	public static function rateItFullnote($attr)
	{		global $core;
		$f = $core->tpl->getFilters($attr);
		return '<?php echo \'<span id="rateit-fullnote-\'.$_ctx->rateIt->type.\'-\'.$_ctx->rateIt->id.\'"  class="rateit-fullnote">\'.'.sprintf($f,'$_ctx->rateIt->note."/".$_ctx->rateIt->quotient').'.\'</span>\'; ?>';
	}

	public static function rateItQuotient($attr)
	{
		return self::rateItValue($attr,'quotient');
	}

	public static function rateItTotal($attr)
	{
		$r = '';
		if (isset($attr['totaltext']) && $attr['totaltext'] == 1) {
			$r = 
			"<?php \n".
			"if (\$_ctx->rateIt->total == 0) { \n".
			"  \$total = sprintf(__('no rate'),\$_ctx->rateIt->total); \n".
			"} elseif (\$_ctx->rateIt->total == 1) {\n".
			"  \$total = sprintf(__('one rate'),\$_ctx->rateIt->total); \n".
			"} else { \n".
			"  \$total = sprintf(__('%d rates'),\$_ctx->rateIt->total); \n".
			"} \n".
			"\$_ctx->rateIt->total = \$total; ?>\n";
		}
		return $r.self::rateItValue($attr,'total');
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

		if (isset($a['nospan']) && $a['nospan'] == 1)
			return "<?php echo ".sprintf($f,'$_ctx->rateIt->'.$r)."; ?>\n";
		else 
			return '<?php echo \'<span id="rateit-'.$r.'-\'.$_ctx->rateIt->type.\'-\'.$_ctx->rateIt->id.\'"  class="rateit-'.$r.'">\'.'.sprintf($f,'$_ctx->rateIt->'.$r).".'</span>'; ?>\n";
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

class rateItPublicWidget
{
	public static function vote($w)
	{
		global $core, $_ctx; 

		if (!$core->blog->settings->rateit_active) return;

		if ($w->enable_post && 'post.html' == $_ctx->current_tpl 
		&& $core->blog->settings->rateit_post_active 
		&& (!$core->blog->settings->rateit_categorylimitposts
		 || $core->blog->settings->rateit_categorylimitposts == $_ctx->posts->cat_id)) {
			$w->type = 'post';
			$w->id = $_ctx->posts->post_id;
			$w->title = $w->title_post;
		}

		if ($w->enable_cat && 'category.html' == $_ctx->current_tpl
		 && $core->blog->settings->rateit_category_active) {
			$w->type = 'category';
			$w->id = $_ctx->categories->cat_id;
			$w->title = $w->title_cat;
		}

		if ($w->enable_tag && 'tag.html' == $_ctx->current_tpl
		 && $core->blog->settings->rateit_tag_active) {
			$w->type = 'tag';
			$w->id = $_ctx->meta->meta_id;
			$w->title = $w->title_tag;
		}

		if ($w->enable_gal && strstr($_ctx->current_tpl,'gallery.html') 
		 && $core->blog->settings->rateit_gal_active) {
			$w->type = 'gal';
			$w->id = $_ctx->posts->post_id;
			$w->title = $w->title_gal;
		}

		if ($w->enable_galitem && strstr($_ctx->current_tpl,'image.html')
		 && $core->blog->settings->rateit_galitem_active) {
			$w->type = 'galitem';
			$w->id = $_ctx->posts->post_id;
			$w->title = $w->title_galitem;
		}


		# --BEHAVIOR-- parseWidgetRateItVote
		$core->callBehavior('parseWidgetRateItVote',$w);


		$type = $w->type;
		$id = $w->id;
		$title = $w->title;

		if (empty($type)) return;

		$rateIt = new rateIt($core);
		$rs = $rateIt->get($type,$id);
		$voted = $rateIt->voted($type,$id);

		$res = '<div class="rateit">';

		if (!empty($title))
			$res .= '<h2>'.html::escapeHTML($title).'</h2>';

		if ($w->show_fullnote == 'percent')
			$res .= '<p><span id="rateit-fullnote-'.$type.'-'.$id.'" class="rateit-fullnote">'.round($rs->note / $rs->quotient * 100,$rs->digit).'%</span></p>';
		elseif ($w->show_fullnote == 'full')
			$res .= '<p><span id="rateit-fullnote-'.$type.'-'.$id.'" class="rateit-fullnote">'.$rs->note.'/'.$rs->quotient.'</span></p>';

		$res .= 
		'<form class="rateit-linker" id="rateit-linker-'.$type.'-'.$id.'" action="'.$core->blog->url.$core->url->getBase('rateItpostform').'/'.$type.'/'.$id.'" method="post"><p>'.
		'<input type="hidden" name="rateit-style" value="'.$core->blog->settings->rateit_style.'" />';

		$dis = $voted ? ' disabled="disabled"' : '';
		for($i=0;$i<$rs->quotient;$i++) {
			$chk = $rs->note > $i && $rs->note <= $i+1 ? ' checked="checked"' : '';

			$res .= '<input name="rateit-'.$type.'-'.$id.'" class="rateit-'.$type.'-'.$id.'" type="radio" value="'.($i+1).'"'.$chk.$dis.'/>';
		}

		if (!$voted)
			$res .= '<input type="submit" name="submit" value="'.__('Vote').'"/>';

		$res .= '</p></form>';

		if ($w->show_note || $w->show_vote || $w->show_higher || $w->show_lower) {
			$res .=	'<ul>';
			if ($w->show_note)
				$res .= '<li>'.__('Note:').'<span id="rateit-note-'.$type.'-'.$id.'" class="rateit-note">'.$rs->note.'</span></li>';
			if ($w->show_vote)
				$res .= '<li>'.__('Votes:').'<span id="rateit-total-'.$type.'-'.$id.'" class="rateit-total">'.$rs->total.'</span></li>';
			if ($w->show_higher)
				$res .= '<li>'.__('Higher:').'<span id="rateit-max-'.$type.'-'.$id.'" class="rateit-max">'.$rs->max.'</span></li>';
			if ($w->show_lower)
				$res .= '<li>'.__('Lower:').'<span id="rateit-min-'.$type.'-'.$id.'" class="rateit-min">'.$rs->min.'</span></li>';
			$res .= '</ul>';
		}
		return $res.'<p>&nbsp;</p></div>';
	}

	public static function rank($w)
	{
		global $core; 

		if (!$core->blog->settings->rateit_active 
		 || $w->homeonly && $core->url->type != 'default') return;

		$p = array('from'=>'','sql'=>'','columns'=>array());
		$p['order'] = ($w->sortby && in_array($w->sortby,array('rateit_avg','rateit_total','rateit_time'))) ? 
			$w->sortby.' ' : 'rateit_total ';

		$p['order'] .= $w->sort == 'desc' ? 'DESC' : 'ASC';

		if ($w->sortby != 'rateit_total')
			$p['order'] .= ',rateit_total DESC ';

		$p['limit'] = abs((integer) $w->limit);

		$p['rateit_type'] = $w->type;

		if ($w->type == 'post') {
			if (!$core->blog->settings->rateit_post_active) return;

			$p['columns'][] = $core->con->concat("'".$core->blog->url.$core->getPostPublicUrl('post','')."'",'P.post_url').' AS url';
			$p['columns'][] = 'P.post_url AS url';
			$p['columns'][] = 'P.post_title AS title';
			$p['columns'][] = 'P.post_id AS id';
			$p['groups'][] = 'P.post_url';
			$p['groups'][] = 'P.post_title';
			$p['groups'][] = 'P.post_id';
			$p['from'] .= ' INNER JOIN '.$core->prefix.'post P ON CAST(P.post_id as char)=RI.rateit_id ';
			$p['sql'] .= " AND P.post_type='post' AND P.post_status = 1 AND P.post_password IS NULL ";
		}

		if ($w->type == 'comment') {
			if (!$core->blog->settings->rateit_comment_active) return;

			$p['columns'][] = $core->con->concat("'".$core->blog->url.$core->getPostPublicUrl('post','')."'",'P.post_url').' AS url';
			$p['columns'][] = 'P.post_title AS title';
			$p['groups'][] = 'P.post_url';
			$p['groups'][] = 'P.post_title';
			$p['from'] .= ' INNER JOIN '.$core->prefix.'comment C ON CAST(C.comment_id as char)=RI.rateit_id ';
			$p['from'] .= ' INNER JOIN '.$core->prefix.'post P ON C.comment_id = P.post_id ';
		}

		if ($w->type == 'category') {
			if (!$core->blog->settings->rateit_category_active) return;

			$p['columns'][] = $core->con->concat("'".$core->blog->url.$core->url->getBase('category')."/'",'C.cat_url').' AS url';
			$p['columns'][] = 'C.cat_title AS title';
			$p['groups'][] = 'C.cat_url';
			$p['groups'][] = 'C.cat_title';
			$p['from'] .= ' INNER JOIN '.$core->prefix.'category C ON CAST(C.cat_id as char)=RI.rateit_id ';
		}

		if ($w->type == 'tag') {
			if (!$core->blog->settings->rateit_tag_active) return;

			$p['columns'][] = $core->con->concat("'".$core->blog->url.$core->url->getBase('tag')."/'",'M.meta_id').' AS url';
			$p['columns'][] = 'M.meta_id AS title';
			$p['groups'][] = 'M.meta_id';
			$p['from'] .= ' INNER JOIN '.$core->prefix.'meta M ON M.meta_id=RI.rateit_id ';
			$p['sql'] .= "AND M.meta_type='tag' ";
		}

		if ($w->type == 'gal') {
			if (!$core->blog->settings->rateit_gal_active) return;

			$p['columns'][] = $core->con->concat("'".$core->blog->url.$core->url->getBase('gal')."/'",'P.post_url').' AS url';
			$p['columns'][] = 'P.post_title AS title';
			$p['columns'][] = 'P.post_id AS id';
			$p['groups'][] = 'P.post_url';
			$p['groups'][] = 'P.post_title';
			$p['groups'][] = 'P.post_id';
			$p['from'] .= ' INNER JOIN '.$core->prefix.'post P ON CAST(P.post_id as char)=RI.rateit_id ';
			$p['sql'] .= "AND post_type='gal' ";
		}

		if ($w->type == 'galitem') {
			if (!$core->blog->settings->rateit_galitem_active) return;

			$p['columns'][] = $core->con->concat("'".$core->blog->url.$core->url->getBase('galitem')."/'",'P.post_url').' AS url';
			$p['columns'][] = 'P.post_title AS title';
			$p['columns'][] = 'P.post_id AS id';
			$p['groups'][] = 'P.post_url';
			$p['groups'][] = 'P.post_title';
			$p['groups'][] = 'P.post_id';
			$p['from'] .= ' INNER JOIN '.$core->prefix.'post P ON CAST(P.post_id as char)=RI.rateit_id ';
			$p['sql'] .= "AND post_type='galitem' ";
		}

		$w->sql = $p;


		# --BEHAVIOR-- parseWidgetRateItRank
		$core->callBehavior('parseWidgetRateItRank',$w);


		if ($w->type == '') return;

		$sql = (array) $w->sql;
		foreach($sql as $k => $v){
			$p[$k] = $v;
		}

		$rateIt = new rateIt($core);
		$rs = $rateIt->getRates($p);

		if ($rs->isEmpty()) return;

		$q = $core->blog->settings->rateit_quotient;
		$d = $core->blog->settings->rateit_digit;

		$res =
		'<div class="rateitpostsrank rateittype'.$w->type.'">'.
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';
		$i=0;
		while ($rs->fetch()) {

			$title = html::escapeHTML($rs->title);

			$cut_len = abs((integer) $w->titlelen);
			if (strlen($title) > $cut_len)
				$title = text::cutString($title,$cut_len).'...';

			if ($rs->rateit_total == 0)
				$totaltext = __('no rate');
			elseif ($rs->rateit_total == 1)
				$totaltext = __('one rate');
			else
				$totaltext = sprintf(__('%d rates'),$rs->rateit_total);

			# Fixed issue with plugin planet
			if ($w->type == 'post') {
				$post = $core->blog->getPosts(array('post_id'=>$rs->id));
				$url = $post->getURL();
			} else {
				$url = $rs->url;
			}

				
			$i++;
			$res .= '<li>'.str_replace(
				array(
					'%rank%',
					'%title%',
					'%note%',
					'%quotient%',
					'%percent%',
					'%count%',
					'%totaltext%',
					'%entryfirstimage%'
				),
				array(
					'<span class="rateit-rank">'.$i.'</span>',
					'<a href="'.$url.'">'.$title.'</a>',
					round($rs->rateit_avg * $q,$d),
					$q,
					floor($rs->rateit_avg * 100),
					$rs->rateit_total,
					$totaltext,
					self::entryFirstImage($core,$w->type,$rs->id)
				),
				$w->text
			).'</li>';
		}
		$res .= '</ul></div>';

		return $res;
	}

	private static function entryFirstImage($core,$type,$id)
	{
		if (!in_array($type,array('post','gal','galitem'))) return '';

		$rs = $core->blog->getPosts(array('post_id'=>$id,'post_type'=>$type));

		if ($rs->isEmpty()) return '';

		$size = $core->blog->settings->rateit_firstimage_size;
		if (!preg_match('/^sq|t|s|m|o$/',$size))
		{
			$size = 's';
		}

		$p_url = $core->blog->settings->public_url;
		$p_site = preg_replace('#^(.+?//.+?)/(.*)$#','$1',$core->blog->url);
		$p_root = $core->blog->public_path;

		$pattern = '(?:'.preg_quote($p_site,'/').')?'.preg_quote($p_url,'/');
		$pattern = sprintf('/<img.+?src="%s(.*?\.(?:jpg|gif|png))"[^>]+/msu',$pattern);

		$src = '';
		$alt = '';

		$subject = $rs->post_excerpt_xhtml.$rs->post_content_xhtml.$rs->cat_desc;
		if (preg_match_all($pattern,$subject,$m) > 0)
		{
			foreach ($m[1] as $i => $img)
			{
				if (($src = self::ContentFirstImageLookup($p_root,$img,$size)) !== false)
				{
					$src = $p_url.(dirname($img) != '/' ? dirname($img) : '').'/'.$src;
					if (preg_match('/alt="([^"]+)"/',$m[0][$i],$malt))
					{
						$alt = $malt[1];
					}
					break;
				}
			}
		}
		if (!$src) return '';

		return 
		'<div class="img-box">'.				
		'<div class="img-thumbnail">'.
		'<a title="'.html::escapeHTML($rs->post_title).'" href="'.$rs->getURL().'">'.
		'<img alt="'.$alt.'" src="'.$src.'" />'.
		'</a></div>'.
		"</div>\n";
	}
	
	private static function ContentFirstImageLookup($root,$img,$size)
	{
		# Get base name and extension
		$info = path::info($img);
		$base = $info['base'];
		
		if (preg_match('/^\.(.+)_(sq|t|s|m)$/',$base,$m)) {
			$base = $m[1];
		}
		
		$res = false;
		if ($size != 'o' && file_exists($root.'/'.$info['dirname'].'/.'.$base.'_'.$size.'.jpg'))
		{
			$res = '.'.$base.'_'.$size.'.jpg';
		}
		else
		{
			$f = $root.'/'.$info['dirname'].'/'.$base;
			if (file_exists($f.'.'.$info['extension'])) {
				$res = $base.'.'.$info['extension'];
			} elseif (file_exists($f.'.jpg')) {
				$res = $base.'.jpg';
			} elseif (file_exists($f.'.png')) {
				$res = $base.'.png';
			} elseif (file_exists($f.'.gif')) {
				$res = $base.'.gif';
			}
		}
		
		if ($res) {
			return $res;
		}
		return false;
	}
}
?>