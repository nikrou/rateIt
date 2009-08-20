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

if ($core->blog->settings->rateit_active === null) return;

$_menu['Plugins']->addItem(
	__('Rate it'),
	'plugin.php?p=rateIt','index.php?pf=rateIt/icon.png',
	preg_match('/plugin.php\?p=rateIt(&.*)?$/',$_SERVER['REQUEST_URI']),
	$core->auth->check('usage,contentadmin',$core->blog->id));

require dirname(__FILE__).'/_widgets.php';

$core->addBehavior('adminBeforePostDelete',array('rateItAdmin','adminBeforePostDelete'));
$core->addBehavior('adminPostsActionsCombo',array('rateItAdmin','adminPostsActionsCombo'));
$core->addBehavior('adminPostsActions',array('rateItAdmin','adminPostsActions'));
$core->addBehavior('adminPostsActionsContent',array('rateItAdmin','adminPostsActionsContent'));

$core->addBehavior('exportFull',array('rateitBackup','exportFull'));
$core->addBehavior('exportSingle',array('rateitBackup','exportSingle'));
$core->addBehavior('importInit',array('rateitBackup','importInit'));
$core->addBehavior('importSingle',array('rateitBackup','importSingle'));
$core->addBehavior('importFull',array('rateitBackup','importFull'));

class rateItAdmin
{
	public static function adminBeforePostDelete($post_id)
	{
		$post_id = (integer) $post_id;
		$rateIt = new rateIt($GLOBALS['core']);
		$rateIt->del('post',$post_id);
	}

	public static function adminPostsActionsCombo($args)
	{
		global $core;
		if ($core->blog->settings->rateit_active 
		 && $core->auth->check('delete,contentadmin',$core->blog->id)) {
			$args[0][__('delete rating')] = 'rateit_empty';
		}
	}

	public static function adminPostsActions($core,$posts,$action,$redir)
	{
		if ($action != 'rateit_do_empty') return;

		try {
			$rateIt = new rateIt($core);
			while ($posts->fetch()) {
				$rateIt->del('post',$posts->post_id);
			}
			$core->blog->triggerBlog();
			http::redirect($redir);
		}
		catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}

	public static function adminPostsActionsContent($core,$action,$hidden_fields)
	{
		if ($action != 'rateit_empty') return;

		echo 
		'<div id="rateit-edit">'.
		'<h3>'.__('delete rating').'</h3>'.
		'<form action="posts_actions.php" method="post"><div>'.
		'<p>'.__('Do you really want to delete all votes for these entries?').'</p>'.
		'<ul>';
		foreach($_POST['entries'] as $post) {
			$rs = $core->blog->getPosts(array('post_id'=>$post,'no_content'=>true));
			echo '<li><a href="post.php?id='.$rs->post_id.'">'.$rs->post_title.'</a></li>';
		}
		echo 
		'</ul>'.
		'<p>'.
		$hidden_fields.
		$core->formNonce().
		form::hidden(array('action'),'rateit_do_empty').
		'<input type="submit" value="'.__('Delete').'" /></p>'.
		'</div></form>'.
		'</div>';
	}
}

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

		$exp->export('rateittag',
			'SELECT RI.blog_id, rateit_id, rateit_type, rateit_note, rateit_quotient, rateit_ip, rateit_time '.
			'FROM '.$core->prefix.'rateit RI, '.$core->prefix.'meta M '.
			"WHERE M.meta_id = rateit_id AND rateit_type='tag' "
		);

		$exp->export('rateitgal',
			'SELECT RI.blog_id, rateit_id, rateit_type, rateit_note, rateit_quotient, rateit_ip, rateit_time '.
			'FROM '.$core->prefix.'rateit RI, '.$core->prefix.'post P '.
			"WHERE P.post_id = rateit_id AND rateit_type='gal' ".
			"AND P.blog_id = '".$blog_id."'"
		);

		$exp->export('rateitgalitem',
			'SELECT RI.blog_id, rateit_id, rateit_type, rateit_note, rateit_quotient, rateit_ip, rateit_time '.
			'FROM '.$core->prefix.'rateit RI, '.$core->prefix.'post P '.
			"WHERE P.post_id = rateit_id AND rateit_type='galitem' ".
			"AND P.blog_id = '".$blog_id."'"
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
		$bk->cur_rateittag = $core->con->openCursor($core->prefix.'rateit');
		$bk->cur_rateitgal = $core->con->openCursor($core->prefix.'rateit');
		$bk->cur_rateitgalitem = $core->con->openCursor($core->prefix.'rateit');
	}

	public static function importSingle($line,$bk,$core)
	{
		if ($line->__name == 'rateit' && $line->rateit_type == 'post' && isset($bk->old_ids['rateit'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['rateit'][(integer) $line->rateit_id];

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

		if ($line->__name == 'rateitcomment' && $line->rateit_type == 'comment' && isset($bk->old_ids['rateitcomment'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['rateitcomment'][(integer) $line->rateit_id];

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

		if ($line->__name == 'rateitcategory' && $line->rateit_type == 'category' && isset($bk->old_ids['rateitcategory'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['rateitcategory'][(integer) $line->rateit_id];

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

		if ($line->__name == 'rateittag' && $line->rateit_type == 'tag') {

			$bk->cur_rateittag->clean();
			$bk->cur_rateittag->blog_id   = (string) $line->blog_id;
			$bk->cur_rateittag->rateit_id   = (string) $line->rateit_id;
			$bk->cur_rateittag->rateit_type   = (string) $line->rateit_type;
			$bk->cur_rateittag->rateit_note   = (integer) $line->rateit_note;
			$bk->cur_rateittag->rateit_quotient   = (integer) $line->rateit_quotient;
			$bk->cur_rateittag->rateit_ip   = (string) $line->rateit_ip;
			$bk->cur_rateittag->rateit_time   = (string) $line->rateit_time;
			$bk->cur_rateittag->insert();
		}

		if ($line->__name == 'rateitgal' && $line->rateit_type == 'gal' && isset($bk->old_ids['rateitgal'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['rateitgal'][(integer) $line->rateit_id];

			$bk->cur_rateitgal->clean();
			$bk->cur_rateitgal->blog_id   = (string) $line->blog_id;
			$bk->cur_rateitgal->rateit_id   = (string) $line->rateit_id;
			$bk->cur_rateitgal->rateit_type   = (string) $line->rateit_type;
			$bk->cur_rateitgal->rateit_note   = (integer) $line->rateit_note;
			$bk->cur_rateitgal->rateit_quotient   = (integer) $line->rateit_quotient;
			$bk->cur_rateitgal->rateit_ip   = (string) $line->rateit_ip;
			$bk->cur_rateitgal->rateit_time   = (string) $line->rateit_time;
			$bk->cur_rateitgal->insert();
		}

		if ($line->__name == 'rateitgalitem' && $line->rateit_type == 'galitem' && isset($bk->old_ids['rateitgalitem'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['rateitgalitem'][(integer) $line->rateit_id];

			$bk->cur_rateitgalitem->clean();
			$bk->cur_rateitgalitem->blog_id   = (string) $line->blog_id;
			$bk->cur_rateitgalitem->rateit_id   = (string) $line->rateit_id;
			$bk->cur_rateitgalitem->rateit_type   = (string) $line->rateit_type;
			$bk->cur_rateitgalitem->rateit_note   = (integer) $line->rateit_note;
			$bk->cur_rateitgalitem->rateit_quotient   = (integer) $line->rateit_quotient;
			$bk->cur_rateitgalitem->rateit_ip   = (string) $line->rateit_ip;
			$bk->cur_rateitgalitem->rateit_time   = (string) $line->rateit_time;
			$bk->cur_rateitgalitem->insert();
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