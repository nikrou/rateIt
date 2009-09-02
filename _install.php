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

if (!defined('DC_CONTEXT_ADMIN')){return;}
//*
if ($core->plugins->moduleExists('rateItComment') 
 || $core->plugins->moduleExists('rateItCategory')
 || $core->plugins->moduleExists('rateItTag')
 || $core->plugins->moduleExists('rateItGallery')) {
	throw new Exception('You must uninstall rateItComment, rateItCategory, rateItTag, rateItGallery before installing rateIt 0.9.5 and higher');
	return false;
}
//*/
$new_version = $core->plugins->moduleInfo('rateIt','version');
$old_version = $core->getVersion('rateIt');

if (version_compare($old_version,$new_version,'>=')) return;

try {
	# Database
	$s = new dbStruct($core->con,$core->prefix);
	$s->rateit
		->blog_id ('varchar',32,false)
		->rateit_id ('varchar',255,false)
		->rateit_type('varchar',64,false)
		->rateit_note ('float',0,false)
		->rateit_quotient ('float',0,false)
		->rateit_ip ('varchar',64,false)
		->rateit_time ('timestamp',0,false,'now()')
		->primary('pk_rateit','blog_id','rateit_type','rateit_id','rateit_ip')
		->index('idx_rateit_blog_id','btree','blog_id')
		->index('idx_rateit_rateit_type','btree','rateit_type')
		->index('idx_rateit_rateit_id','btree','rateit_id')
		->index('idx_rateit_rateit_ip','btree','rateit_ip');

	$si = new dbStruct($core->con,$core->prefix);
	$changes = $si->synchronize($s);

	$core->blog->settings->setNameSpace('rateit');

	# Settings main
	$core->blog->settings->put('rateit_active',false,'boolean','rateit plugin enabled',false,true);
	$core->blog->settings->put('rateit_importexport_active',true,'boolean','rateit import/export enabled',false,true);
	$core->blog->settings->put('rateit_quotient',5,'integer','rateit maximum note',false,true);
	$core->blog->settings->put('rateit_digit',1,'integer','rateit note digits number',false,true);
	$core->blog->settings->put('rateit_msgthanks','Thank you for having voted','string','rateit message when voted',false,true);
	$core->blog->settings->put('rateit_userident',0,'integer','rateit use cookie and/or ip',false,true);
	$core->blog->settings->put('rateit_dispubjs',false,'boolean','disable rateit public javascript',false,true);
	$core->blog->settings->put('rateit_dispubcss',false,'boolean','disable rateit public css',false,true);
	# Settings for posts
	$core->blog->settings->put('rateit_post_active',true,'boolean','Enabled post rating',false,true);
	$core->blog->settings->put('rateit_poststpl',false,'boolean','rateit template on post on post page',false,true);
	$core->blog->settings->put('rateit_homepoststpl',false,'boolean','rateit template on post on home page',false,true);
	$core->blog->settings->put('rateit_tagpoststpl',false,'boolean','rateit template on post on tag page',false,true);
	$core->blog->settings->put('rateit_categorypoststpl',false,'boolean','rateit template on post on category page',false,true);
	$core->blog->settings->put('rateit_categorylimitposts',false,'integer','rateit limit post vote to one category',false,true);
	# Settings for comments
	$core->blog->settings->put('rateit_comment_active',false,'boolean','Enable comments rating',false,true);
	$core->blog->settings->put('rateit_commentstpl',true,'boolean','Use comments behavior',false,true);
	# Settings for categories
	$core->blog->settings->put('rateit_category_active',false,'boolean','rateit category addon enabled',false,true);
	# Settings for tags
	$core->blog->settings->put('rateit_tag_active',false,'boolean','rateit tag addon enabled',false,true);
	# Settings for galleries
	$core->blog->settings->put('rateit_gal_active',false,'boolean','rateit addon gallery enabled',false,true);
	$core->blog->settings->put('rateit_galitem_active',false,'boolean','rateit addon gallery item enabled',false,true);
	$core->blog->settings->put('rateit_galtpl',true,'boolean','rateit template galleries page',false,true);
	$core->blog->settings->put('rateit_galitemtpl',true,'boolean','rateit template gallery items page',false,true);

	$core->blog->settings->setNameSpace('system');

	# Version
	$core->setVersion('rateIt',$core->plugins->moduleInfo('rateIt','version'));

	return true;
}
catch (Exception $e) {
	$core->error->add($e->getMessage());
}
return false;
?>