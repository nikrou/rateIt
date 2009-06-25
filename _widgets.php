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

$core->addBehavior('initWidgets',
	array('rateItWidget','initWidget'));

class rateItWidget
{
	public static function initWidget(&$w)
	{
		global $core;

		$w->create('rateit',__('Vote'),
			array('rateItWidget','rateitVoteWidget'));
		$w->rateit->setting('enable_post',__('Enable vote for entries'),
			1,'check');
		$w->rateit->setting('title_post',__('Title for entries:'),
			__('Vote for this entry'),'text');
		$w->rateit->setting('enable_cat',__('Enable vote for categories'),
			0,'check');
		$w->rateit->setting('title_cat',__('Title for categories:'),
			__('Vote for this category'),'text');


		$w->create('rateitpostsrank',__('Top rated entries'),
			array('rateItWidget','rateitPostsRankWidget'));
		$w->rateitpostsrank->setting('title',__('Title:'),
			__('Top rated entries'),'text');
		$w->rateitpostsrank->setting('limit',__('Length:'),3,'combo',array(
			1=>1,2=>2,3=>3,4=>4,5=>5,10=>10,15=>15,20=>20));
		$w->rateitpostsrank->setting('sortby',__('Order by:'),'rateit_note','combo',array(
			__('Note') => 'rateit_note',
			__('Votes') => 'rateit_total'));
		$w->rateitpostsrank->setting('sort',__('Sort:'),'desc','combo',array(
			__('Ascending') => 'asc',
			__('Descending') => 'desc'));
		$w->rateitpostsrank->setting('homeonly',__('Home page only'),1,'check');
	}

	public static function rateitPostsRankWidget(&$w)
	{
		global $core, $_ctx; 

		if (!$core->blog->settings->rateit_active) return;

		if ($w->homeonly && $core->url->type != 'default') return;

		$p = array();
		$p['order'] = ($w->sortby && in_array($w->sortby,array('rateit_note','rateit_total'))) ? 
			$w->sortby.' ' : 'rateit_total ';

		$p['order'] .= $w->sort == 'desc' ? 'desc' : 'asc';

		$p['limit'] = abs((integer) $w->limit);

		$p['no_content'] = true;
		$p['rateit_type'] = 'post';
		$p['post_type'] = '';

		$rateIt = new rateIt($core);
		$rs = $rateIt->getPostsByRate($p);

		if ($rs->isEmpty()) return;

		$q = $core->blog->settings->rateit_quotient;
		$d = $core->blog->settings->rateit_digit;

		$res =
		'<div class="rateitpostsrank">'.
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';
		$i=0;
		while ($rs->fetch()) {
			$i++;
			$res .= 
			'<li><span class="rateit-rank">'.$i.'</span> '.
			'<a href="'.$rs->getURL().'" title="'.__('Votes:').' '.$rs->rateit_total.'">'.
			html::escapeHTML($rs->post_title).'</a> '.
			'('.round($rs->rateit_note * $q,$d).'/'.$q.')'.
			'</li>';
		}
		$res .= '</ul></div>';

		return $res;
		return;
	}

	public static function rateitVoteWidget(&$w)
	{
		global $core, $_ctx; 

		if (!$core->blog->settings->rateit_active) return;

		if ($w->enable_post && 'post.html' == $_ctx->current_tpl  
		 || $w->enable_cat && 'category.html' == $_ctx->current_tpl
		) {
			$type = '';
			$id = 0;
			$title = 'rateIt';
			$content = '';

			if ('post.html' == $_ctx->current_tpl) {
				$type = 'post';
				$id = $_ctx->posts->post_id;
				$title = $w->title_post;
			}
			if ('category.html' == $_ctx->current_tpl) {
				$type = 'cat';
				$id = $_ctx->categories->cat_id;
				$title = $w->title_cat;
			}

			$rateIt = new rateIt($core);
			$rs = $rateIt->get($type,$id);
			$voted = $rateIt->voted($type,$id);

			$dis = $voted ?
				' disabled="disabled"' : '';

			$content = '<form class="rateit-linker" id="raiteitwidget-linker-'.$type.'-'.$id.'" action="'.$core->blog->url.'rateitnow/'.$type.'/'.$id.'/" method="post"><p>';
			for($i=0;$i<$rs->quotient;$i++){
				$chk = $rs->note > $i && $rs->note <= $i+1 ? 
				' checked="checked"' : '';

				$content .= '<input name="rateit-'.$type.'-'.$id.'" class="rateit-'.$type.'-'.$id.'" type="radio" value="'.($i+1).'"'.$chk.$dis.'/>';
			}
			$content .= 
				($voted ? '' : '<input type="submit" name="submit" value="'.__('Vote').'"/>').
				'</p></form>'.
				'<ul>'.
				'<li>'.__('Note:').'<span id="rateitwidget-note-'.$type.'-'.$id.'" class="rateit-note">'.$rs->note.'</span></li>'.
				'<li>'.__('Votes:').'<span id="rateitwidget-total-'.$type.'-'.$id.'" class="rateit-total">'.$rs->total.'</span></li>'.
				'<li>'.__('Higher:').'<span id="rateitwidget-max-'.$type.'-'.$id.'" class="rateit-max">'.$rs->max.'</span></li>'.
				'<li>'.__('Lower:').'<span id="rateitwidget-min-'.$type.'-'.$id.'" class="rateit-min">'.$rs->min.'</span></li>'.
				'</ul>';

			return 
			'<div class="rateitwidget">'.
			(strlen($title) > 0 ? '<h2>'.html::escapeHTML($title).'</h2>' : '').
			$content.
			'</div>';
		}
		return;
	}
}
?>