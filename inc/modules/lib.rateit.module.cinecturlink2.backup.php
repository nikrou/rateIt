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

class cinecturlink2RateItBackup
{
	# limit to category type
	public static function exportSingle(&$core,&$exp,$blog_id)
	{
		$exp->export('rateitcinecturlink2',
			'SELECT RI.blog_id, rateit_id, rateit_type, rateit_note, rateit_quotient, rateit_ip, rateit_time '.
			'FROM '.$core->prefix.'rateit RI, '.$core->prefix.'cinecturlink2 C '.
			"WHERE C.link_id = rateit_id AND rateit_type='cinecturlink2' ".
			"AND C.blog_id = '".$blog_id."'"
		);
	}
	
	public static function importInit(&$bk,&$core)
	{
		$bk->cur_rateitcinecturlink2 = $core->con->openCursor($core->prefix.'rateit');
	}
	
	# limit to category type
	public static function importSingle(&$line,&$bk,&$core)
	{
		if ($line->__name == 'rateitcinecturlink2' && $line->rateit_type == 'cinecturlink2' && isset($bk->old_ids['cinecturlink2'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['cinecturlink2'][(integer) $line->rateit_id];
			
			$bk->cur_rateitcategory->clean();
			$bk->cur_rateitcategory->blog_id   = (string) $line->blog_id;
			$bk->cur_rateitcategory->rateit_id   = (string) $line->rateit_id;
			$bk->cur_rateitcategory->rateit_type   = (string) $line->rateit_type;
			$bk->cur_rateitcategory->rateit_note   = (integer) $line->rateit_note;
			$bk->cur_rateitcategory->rateit_quotient   = (integer) $line->rateit_quotient;
			$bk->cur_rateitcategory->rateit_ip   = (string) $line->rateit_ip;
			$bk->cur_rateitcategory->rateit_time   = (string) $line->rateit_time;
			$bk->cur_rateitcategory->insert();
		}
	}
}
?>