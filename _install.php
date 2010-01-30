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

if (!defined('DC_CONTEXT_ADMIN')){return;}

$new_version = $core->plugins->moduleInfo('rateIt','version');
$old_version = $core->getVersion('rateIt');
//*
if ($core->plugins->moduleExists('rateItComment') 
 || $core->plugins->moduleExists('rateItCategory')
 || $core->plugins->moduleExists('rateItTag')
 || $core->plugins->moduleExists('rateItGallery')) {
	throw new Exception('You must uninstall rateItComment, rateItCategory, rateItTag, rateItGallery before installing rateIt '.$new_version);
	return false;
}
//*/
if (version_compare($old_version,$new_version,'>=')) return;

try {
	# Check DC version (dev on)
	if (!version_compare(DC_VERSION,'2.1.6','>='))
	{
		throw new Exception('Plugin called rateIt requires Dotclear 2.1.6 or higher.');
	}
	# Database
	$s = new dbStruct($core->con,$core->prefix);
	$s->rateit
		->blog_id ('varchar',32,false)
		->rateit_id ('varchar',192,false)
		->rateit_type('varchar',16,false)
		->rateit_note ('float',0,false)
		->rateit_quotient ('float',0,false)
		->rateit_ip ('varchar',48,false)
		->rateit_time ('timestamp',0,false,'now()')
		->primary('pk_rateit','blog_id','rateit_type','rateit_id','rateit_ip') //mysql error 1071 limit key to 768.
		->index('idx_rateit_blog_id','btree','blog_id')
		->index('idx_rateit_rateit_type','btree','rateit_type')
		->index('idx_rateit_rateit_id','btree','rateit_id')
		->index('idx_rateit_rateit_ip','btree','rateit_ip');

	$si = new dbStruct($core->con,$core->prefix);
	$changes = $si->synchronize($s);
	$s = null;

	$s =& $core->blog->settings;
	$s->setNameSpace('rateit');

	# Settings main
	$s->put('rateit_active',false,'boolean','rateit plugin enabled',false,true);
	$s->put('rateit_importexport_active',true,'boolean','rateit import/export enabled',false,true);
	$s->put('rateit_quotient',5,'integer','rateit maximum note',false,true);
	$s->put('rateit_digit',1,'integer','rateit note digits number',false,true);
	$s->put('rateit_msgthanks','Thank you for having voted','string','rateit message when voted',false,true);
	$s->put('rateit_userident',0,'integer','rateit use cookie and/or ip',false,true);
	$s->put('rateit_dispubjs',false,'boolean','disable rateit public javascript',false,true);
	$s->put('rateit_dispubcss',false,'boolean','disable rateit public css',false,true);
	$s->put('rateit_firstimage_size','t','string','Size of entryfirstimage on widget',false,true);
	# Settings for posts
	$s->put('rateit_post_active',true,'boolean','Enabled post rating',false,true);
	$s->put('rateit_poststpl',false,'boolean','rateit template on post on post page',false,true);
	$s->put('rateit_homepoststpl',false,'boolean','rateit template on post on home page',false,true);
	$s->put('rateit_tagpoststpl',false,'boolean','rateit template on post on tag page',false,true);
	$s->put('rateit_categorypoststpl',false,'boolean','rateit template on post on category page',false,true);
	$s->put('rateit_categorylimitposts',false,'integer','rateit limit post vote to one category',false,true);
	# Settings for comments
	$s->put('rateit_comment_active',false,'boolean','Enable comments rating',false,true);
	$s->put('rateit_commentstpl',true,'boolean','Use comments behavior',false,true);
	# Settings for categories
	$s->put('rateit_category_active',false,'boolean','rateit category addon enabled',false,true);
	# Settings for tags
	$s->put('rateit_tag_active',false,'boolean','rateit tag addon enabled',false,true);
	# Settings for galleries
	$s->put('rateit_gal_active',false,'boolean','rateit addon gallery enabled',false,true);
	$s->put('rateit_galitem_active',false,'boolean','rateit addon gallery item enabled',false,true);
	$s->put('rateit_galtpl',true,'boolean','rateit template galleries page',false,true);
	$s->put('rateit_galitemtpl',true,'boolean','rateit template gallery items page',false,true);

	$s->setNameSpace('system');

	# Version
	$core->setVersion('rateIt',$core->plugins->moduleInfo('rateIt','version'));

	return true;
}
catch (Exception $e) {
	$core->error->add($e->getMessage());
}
return false;
?>