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

$core->addBehavior('initWidgets',array('rateItWidget','initVote'));
$core->addBehavior('initWidgets',array('rateItWidget','initRank'));

class rateItWidget
{
	public static function initVote(&$w)
	{
		global $core;

		$w->create('rateit',__('Rating'),
			array('rateItWidget','parseVote'));
		$w->rateit->setting('enable_post',__('Enable vote for entries'),
			1,'check');
		$w->rateit->setting('title_post',__('Title for entries:'),
			__('Rate this entry'),'text');

		# --BEHAVIOR-- initWidgetRateItVote
		$core->callBehavior('initWidgetRateItVote',$w);

		$w->rateit->setting('show_fullnote',__('Show full note'),'full','combo',
			array(__('Hidden')=>'hide',__('Full note (5/20)')=>'full',__('Percent (25%)')=>'percent'));
		$w->rateit->setting('show_note',__('Show note'),
			1,'check');
		$w->rateit->setting('show_vote',__('Show the count of vote'),
			1,'check');
		$w->rateit->setting('show_higher',__('Show the highest rate'),
			1,'check');
		$w->rateit->setting('show_lower',__('Show the lowest rate'),
			1,'check');
		$w->rateit->setting('type','type','','hidden');
		$w->rateit->setting('id','id','0','hidden');
		$w->rateit->setting('title','title','rateIt','hidden');
	}

	public static function parseVote(&$w)
	{
		global $core, $_ctx; 

		if (!$core->blog->settings->rateit_active) return;

		if ($w->enable_post && 'post.html' == $_ctx->current_tpl) {
			$w->type = 'post';
			$w->id = $_ctx->posts->post_id;
			$w->title = $w->title_post;
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

		$res = '<div class="rateitwidget">';

		if (!empty($title))
			$res .= '<h2>'.html::escapeHTML($title).'</h2>';

		if ($w->show_fullnote == 'percent')
			$res .= '<p><span id="rateit-fullnote-'.$type.'-'.$id.'" class="rateit-fullnote">'.round($rs->note / $rs->quotient * 100,$rs->digit).'%</span></p>';
		elseif ($w->show_fullnote == 'full')
			$res .= '<p><span id="rateit-fullnote-'.$type.'-'.$id.'" class="rateit-fullnote">'.$rs->note.'/'.$rs->quotient.'</span></p>';

		$res .= '<form class="rateit-linker" id="raiteitwidget-linker-'.$type.'-'.$id.'" action="'.$core->blog->url.'rateitnow/'.$type.'/'.$id.'/" method="post"><p>';

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
				$res .= '<li>'.__('Note:').'<span id="rateitwidget-note-'.$type.'-'.$id.'" class="rateit-note">'.$rs->note.'</span></li>';
			if ($w->show_vote)
				$res .= '<li>'.__('Votes:').'<span id="rateitwidget-total-'.$type.'-'.$id.'" class="rateit-total">'.$rs->total.'</span></li>';
			if ($w->show_higher)
				$res .= '<li>'.__('Higher:').'<span id="rateitwidget-max-'.$type.'-'.$id.'" class="rateit-max">'.$rs->max.'</span></li>';
			if ($w->show_lower)
				$res .= '<li>'.__('Lower:').'<span id="rateitwidget-min-'.$type.'-'.$id.'" class="rateit-min">'.$rs->min.'</span></li>';
			$res .= '</ul>';
		}
		return $res.'<p>&nbsp;</p></div>';
	}

	public static function initRank(&$w)
	{
		global $core;
		$w->create('rateitrank',__('Top rating'),
			array('rateItWidget','parseRank'));
		$w->rateitrank->setting('title',__('Title:'),
			__('Top rated entries'),'text');

		$types = new ArrayObject();

		# --BEHAVIOR-- initWidgetRateItRank
		$core->callBehavior('initWidgetRateItRank',$types);

		$types[] = array(__('entries')=>'post');
		$types = (array) $types;
		$combo = array();
		foreach($types as $k => $v){
			$combo = array_merge($v,$combo);
		}

		$w->rateitrank->setting('type',__('Type:'),'post','combo',$combo);
		
		$w->rateitrank->setting('limit',__('Length:'),3,'combo',array(
			1=>1,2=>2,3=>3,4=>4,5=>5,10=>10,15=>15,20=>20));
		$w->rateitrank->setting('sortby',__('Order by:'),'rateit_note','combo',array(
			__('Note') => 'rateit_note',
			__('Votes') => 'rateit_total'));
		$w->rateitrank->setting('sort',__('Sort:'),'desc','combo',array(
			__('Ascending') => 'asc',
			__('Descending') => 'desc'));
		$w->rateitrank->setting('text',__('Text'),'%rank% %title% (%note%/%quotient%)','text');
		$w->rateitrank->setting('homeonly',__('Home page only'),1,'check');
		$w->rateitrank->setting('sql','sql','','hidden');
	}

	public static function parseRank(&$w)
	{
		global $core; 

		if (!$core->blog->settings->rateit_active) return;

		if ($w->homeonly && $core->url->type != 'default') return;

		$p = array('from'=>'','sql'=>'','columns'=>array());
		$p['order'] = ($w->sortby && in_array($w->sortby,array('rateit_note','rateit_total'))) ? 
			$w->sortby.' ' : 'rateit_total ';

		$p['order'] .= $w->sort == 'desc' ? 'desc' : 'asc';

		$p['limit'] = abs((integer) $w->limit);

		$p['rateit_type'] = $w->type;

		if ($w->type == 'post') {
			$p['columns'][] = "CONCAT('".$core->blog->url.$core->getPostPublicUrl('post','')."',post_url) AS url";
			$p['columns'][] = 'post_title AS title';
			$p['from'] .= ' LEFT JOIN '.$core->prefix.'post P ON P.post_id = RI.rateit_id ';
			$p['sql'] .= ' AND post_status = 1 AND post_password IS NULL ';
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
		'<div class="rateitpostsrank">'.
		($w->title ? '<h2>'.html::escapeHTML($w->title).'</h2>' : '').
		'<ul>';
		$i=0;
		while ($rs->fetch()) {
			$i++;
			$res .= '<li>'.str_replace(array('%rank%','%title%','%note%','%quotient%','%percent%','%count%'),array(
				'<span class="rateit-rank">'.$i.'</span>',
				'<a href="'.$rs->url.'">'.
					html::escapeHTML($rs->title).'</a>',
				round($rs->rateit_avg * $q,$d),
				$q,
				floor($rs->rateit_avg * 100),
				$rs->rateit_total
			),
			$w->text).'</li>';
		}
		$res .= '</ul></div>';

		return $res;
	}
}
?>