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

class rateItBackup
{
	public static function exportSingle($core,$exp,$blog_id)
	{
		$exp->export('rateit',
			'SELECT RI.blog_id, rateit_id, rateit_type, rateit_note, rateit_quotient, rateit_ip, rateit_time '.
			'FROM '.$core->prefix.'rateit RI, '.$core->prefix.'post P '.
			"WHERE P.post_id = rateit_id AND rateit_type='post' ".
			"AND P.blog_id = '".$blog_id."'"
		);

		$exp->export('rateitcomment',
			'SELECT RI.blog_id, rateit_id, rateit_type, rateit_note, rateit_quotient, rateit_ip, rateit_time '.
			'FROM '.$core->prefix.'rateit RI, '.$core->prefix.'comment C, '.$core->prefix.'post P '.
			"WHERE P.post_id=C.comment_id AND C.comment_id=rateit_id AND rateit_type='comment' ".
			"AND P.blog_id = '".$blog_id."'"
		);

		$exp->export('rateitcategory',
			'SELECT RI.blog_id, rateit_id, rateit_type, rateit_note, rateit_quotient, rateit_ip, rateit_time '.
			'FROM '.$core->prefix.'rateit RI, '.$core->prefix.'category C '.
			"WHERE C.cat_id = rateit_id AND rateit_type='category' ".
			"AND C.blog_id = '".$blog_id."'"
		);
	}

	public static function exportFull($core,$exp)
	{
		$exp->exportTable('rateit');
	}

	public static function importInit($bk,$core)
	{
		$bk->cur_rateit = $core->con->openCursor($core->prefix.'rateit');
		$bk->cur_rateitcomment = $core->con->openCursor($core->prefix.'rateit');
		$bk->cur_rateitcategory = $core->con->openCursor($core->prefix.'rateit');
	}

	public static function importSingle($line,$bk,$core)
	{
		if ($line->__name == 'rateit' && $line->rateit_type == 'post' && isset($bk->old_ids['post'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['post'][(integer) $line->rateit_id];

			$bk->cur_rateit->clean();
			$bk->cur_rateit->blog_id   = (string) $line->blog_id;
			$bk->cur_rateit->rateit_id   = (string) $line->rateit_id;
			$bk->cur_rateit->rateit_type   = (string) $line->rateit_type;
			$bk->cur_rateit->rateit_note   = (integer) $line->rateit_note;
			$bk->cur_rateit->rateit_quotient   = (integer) $line->rateit_quotient;
			$bk->cur_rateit->rateit_ip   = (string) $line->rateit_ip;
			$bk->cur_rateit->rateit_time   = (string) $line->rateit_time;
			$bk->cur_rateit->insert();
		}

		if ($line->__name == 'rateitcomment' && $line->rateit_type == 'comment' && isset($bk->old_ids['comment'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['comment'][(integer) $line->rateit_id];

			$bk->cur_rateitcomment->clean();
			$bk->cur_rateitcomment->blog_id   = (string) $line->blog_id;
			$bk->cur_rateitcomment->rateit_id   = (string) $line->rateit_id;
			$bk->cur_rateitcomment->rateit_type   = (string) $line->rateit_type;
			$bk->cur_rateitcomment->rateit_note   = (integer) $line->rateit_note;
			$bk->cur_rateitcomment->rateit_quotient   = (integer) $line->rateit_quotient;
			$bk->cur_rateitcomment->rateit_ip   = (string) $line->rateit_ip;
			$bk->cur_rateitcomment->rateit_time   = (string) $line->rateit_time;
			$bk->cur_rateitcomment->insert();
		}

		if ($line->__name == 'rateitcategory' && $line->rateit_type == 'category' && isset($bk->old_ids['category'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['category'][(integer) $line->rateit_id];

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

	public static function importFull($line,$bk,$core)
	{
		if ($line->__name == 'rateit') {
			$bk->cur_rateit->clean();
			$bk->cur_rateit->blog_id   = (string) $line->blog_id;
			$bk->cur_rateit->rateit_id   = (string) $line->rateit_id;
			$bk->cur_rateit->rateit_type   = (string) $line->rateit_type;
			$bk->cur_rateit->rateit_note   = (integer) $line->rateit_note;
			$bk->cur_rateit->rateit_quotient   = (integer) $line->rateit_quotient;
			$bk->cur_rateit->rateit_ip   = (string) $line->rateit_ip;
			$bk->cur_rateit->rateit_time   = (string) $line->rateit_time;
			$bk->cur_rateit->insert();
		}
	}
}
?>