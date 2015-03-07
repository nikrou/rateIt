<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of rateIt, a plugin for Dotclear 2.
#
# Copyright(c) 2014 Nicolas Roudaire <nikrou77@gmail.com> http://www.nikrou.net
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

		$rateit_types = $core->rateIt->getModules();

		if (empty($rateit_types)) return;

		$w->create('rateit',__('Rating'),array('rateItPublicWidget','vote'));

		# --BEHAVIOR-- adminRateItWidgetVote
		$core->callbehavior('adminRateItWidgetVote',$w);

		$w->rateit->setting('show_fullnote',__('Show notes next to images'),
			1,'check');
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

		$wildcards = '%rank%, %title%, %note%, %quotient%, %percent%, %count%, %totaltext%, %entryfirstimage%'; //todo; , %negative%, %positive%
		$types = new ArrayObject();

		$rateit_types = $core->rateIt->getModules();

		if (empty($rateit_types)) return;

		$w->create('rateitrank',__('Top rating'),array('rateItPublicWidget','rank'));
		$w->rateitrank->setting('title',__('Title:'),__('Top rated entries'),'text');

		# --BEHAVIOR-- adminRateItWidgetRank
		$core->callbehavior('adminRateItWidgetRank',$types);

		$types = $types->getArrayCopy();

		$combo_types = array();
		foreach($types as $k => $v)
		{
			$combo_types = array_merge($v,$combo_types);
		}

		$combo_categories = array('-'=>'');
		try
		{
			$categories = $core->blog->getCategories(array('post_type'=>'post'));
		}
		catch (Exception $e)
		{
			$core->error->add($e->getMessage());
		}
		while ($categories->fetch())
		{
			$combo_categories[str_repeat('&nbsp;&nbsp;',$categories->level-1).'&bull; '.
				html::escapeHTML($categories->cat_title)] = $categories->cat_id;
		}

		$w->rateitrank->setting('type',__('Type:'),'post','combo',$combo_types);
		$w->rateitrank->setting('catlimit',__('Category limit: (if possible)'),'','combo',$combo_categories);
		$w->rateitrank->setting('limit',__('Length:'),3,'combo',array(
			1=>1,2=>2,3=>3,4=>4,5=>5,10=>10,15=>15,20=>20)
		);
		$w->rateitrank->setting('sortby',__('Order by:'),'rateit_avg','combo',array(
			__('Note') => 'rateit_avg',
			__('Votes') => 'rateit_total',
			__('Date') => 'rateit_time',
			__('Positive votes (for simple/twin mode)') => 'POSITIVE')
		);
		$w->rateitrank->setting('sort',__('Sort:'),'desc','combo',array(
			__('Ascending') => 'asc',
			__('Descending') => 'desc')
		);
		$w->rateitrank->setting('text',sprintf(__('Text: Use wildcards %s'),$wildcards),'%rank% %title% (%note%/%quotient%)','text');
		$w->rateitrank->setting('titlelen',__('Title length: (if truncate)'),100);
		$w->rateitrank->setting('homeonly',__('Home page only'),1,'check');
		$w->rateitrank->setting('sql','sql','','hidden');
	}
}
