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

$core->addBehavior('initWidgets',array('rateItAdminWidget','vote'));
$core->addBehavior('initWidgets',array('rateItAdminWidget','rank'));

class rateItAdminWidget
{
	public static function vote($w)
	{
		global $core;

		$w->create('rateit',__('Rating'),
			array('rateItPublicWidget','vote'));
		$w->rateit->setting('enable_post',__('Enable vote for entries'),
			1,'check');
		$w->rateit->setting('title_post',__('Title for entries:'),
			__('Rate this entry'),'text');

		$w->rateit->setting('enable_cat',__('Enable vote for categories'),
			0,'check');
		$w->rateit->setting('title_cat',__('Title for categories:'),
			__('Rate this category'),'text');

		if ($core->plugins->moduleExists('metadata')) {
			$w->rateit->setting('enable_tag',__('Enable vote for tags'),
				0,'check');
			$w->rateit->setting('title_tag',__('Title for tags:'),
				__('Rate this tag'),'text');
		}

		if ($core->plugins->moduleExists('gallery')) {
			$w->rateit->setting('enable_gal',__('Enable vote for gallery'),
				0,'check');
			$w->rateit->setting('title_gal',__('Title for gallery:'),
				__('Rate this gallery'),'text');
			$w->rateit->setting('enable_galitem',__('Enable vote for gallery item'),
				0,'check');
			$w->rateit->setting('title_galitem',__('Title for gallery item:'),
				__('Rate this gallery item'),'text');
		}

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

	public static function rank($w)
	{
		global $core;

		$w->create('rateitrank',__('Top rating'),
			array('rateItPublicWidget','rank'));
		$w->rateitrank->setting('title',__('Title:'),
			__('Top rated entries'),'text');

		$types = new ArrayObject();


		# --BEHAVIOR-- initWidgetRateItRank
		$core->callBehavior('initWidgetRateItRank',$types);


		$types[] = array(__('entries')=>'post');
		$types[] = array(__('Comments')=>'comment');
		$types[] = array(__('Categories')=>'category');

		if ($core->plugins->moduleExists('metadata')) {
			$types[] = array(__('Tag')=>'tag');
		}
		if ($core->plugins->moduleExists('gallery')) {
			$types[] = array(__('Gallery')=>'gal');
			$types[] = array(__('Gallery item')=>'galitem');
		}

		$types = (array) $types;
		$combo_types = array();
		foreach($types as $k => $v){
			$combo_types = array_merge($v,$combo_types);
		}

		$combo_categories = array('-'=>'');
		try {
			$categories = $core->blog->getCategories(array('post_type'=>'post'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
		while ($categories->fetch()) {
			$combo_categories[str_repeat('&nbsp;&nbsp;',$categories->level-1).'&bull; '.
				html::escapeHTML($categories->cat_title)] = $categories->cat_id;
		}

		$w->rateitrank->setting('type',__('Type:'),'post','combo',$combo_types);
		$w->rateitrank->setting('catlimit',__('Category limit (if possible):'),'','combo',$combo_categories);
		$w->rateitrank->setting('limit',__('Length:'),3,'combo',array(
			1=>1,2=>2,3=>3,4=>4,5=>5,10=>10,15=>15,20=>20));
		$w->rateitrank->setting('sortby',__('Order by:'),'rateit_avg','combo',array(
			__('Note') => 'rateit_avg',
			__('Votes') => 'rateit_total',
			__('Date') => 'rateit_time'));
		$w->rateitrank->setting('sort',__('Sort:'),'desc','combo',array(
			__('Ascending') => 'asc',
			__('Descending') => 'desc'));
		$w->rateitrank->setting('text',__('Text'),'%rank% %title% (%note%/%quotient%)','text');
		$w->rateitrank->setting('titlelen',__('Title length (if truncate)'),100);
		$w->rateitrank->setting('homeonly',__('Home page only'),1,'check');
		$w->rateitrank->setting('sql','sql','','hidden');
	}
}
?>