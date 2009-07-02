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

class rateItInstall
{
	public static function pluginsBeforeDelete($plugin)
	{
		if($plugin['id'] == 'rateIt') {
			http::redirect('plugin.php?p=rateIt&t=uninstall');
			exit;
		}
	}

	public static function setTable(&$core)
	{
		$s = new dbStruct($core->con,$core->prefix);
		$s->rateit
			->blog_id ('varchar',32,false)
			->rateit_id ('varchar',255,false)
			->rateit_type('varchar',64,false)
			->rateit_note ('integer',0,false)
			->rateit_quotient ('integer',0,false)
			->rateit_ip ('varchar',64,false)
			->rateit_time ('timestamp',0,false,'now()')
			->primary('pk_rateit','blog_id','rateit_type','rateit_id','rateit_ip')
			->index('idx_rateit_blog_id','btree','blog_id')
			->index('idx_rateit_rateit_type','btree','rateit_type')
			->index('idx_rateit_rateit_id','btree','rateit_id')
			->index('idx_rateit_rateit_ip','btree','rateit_ip');

		$si = new dbStruct($core->con,$core->prefix);
		$changes = $si->synchronize($s);
	}

	public static function delTable(&$core)
	{
		@$core->con->execute('TRUNCATE TABLE '.$core->con->escape($core->prefix.'rateit').'');
		@$core->con->execute('DROP TABLE '.$core->con->escape($core->prefix.'rateit').'');
	}

	public static function setSettings(&$core,$glob=false,$force=false)
	{
		$core->blog->settings->setNameSpace('rateit');
		$core->blog->settings->put('rateit_active',false,'boolean','rateit plugin enabled',$force,$glob);
		$core->blog->settings->put('rateit_poststpl',false,'boolean','rateit template on post',$force,$glob);
		$core->blog->settings->put('rateit_quotient',5,'integer','rateit maximum note',$force,$glob);
		$core->blog->settings->put('rateit_digit',1,'integer','rateit note digits number',$force,$glob);
		$core->blog->settings->put('rateit_msgthanks','Thank you for having voted','string','rateit message when voted',$force,$glob);
		$core->blog->settings->put('rateit_userident',0,'integer','rateit use cookie and/or ip',$force,$glob);
	}

	public static function delSettings(&$core)
	{
		$core->con->execute('DELETE FROM '.$core->prefix.'setting WHERE setting_ns = \'rateit\' ');
	}

	public static function setVersion(&$core)
	{
		$core->setVersion('rateIt',$core->plugins->moduleInfo('rateIt','version'));
	}

	public static function delVersion(&$core)
	{
		$core->delVersion('rateIt');
	}

	public static function delModule(&$core)
	{
		$core->plugins->deleteModule('rateIt');
	}
}
?>