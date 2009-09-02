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

if ($core->blog->settings->rateit_importexport_active) {

	$core->addBehavior('exportFull',array('rateitBackup','exportFull'));
	$core->addBehavior('exportSingle',array('rateitBackup','exportSingle'));
	$core->addBehavior('importInit',array('rateitBackup','importInit'));
	$core->addBehavior('importSingle',array('rateitBackup','importSingle'));
	$core->addBehavior('importFull',array('rateitBackup','importFull'));
}

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
			'SELECT blog_id, rateit_id, rateit_type, rateit_note, rateit_quotient, rateit_ip, rateit_time '.
			'FROM '.$core->prefix.'rateit '.
			"WHERE blog_id = '".$blog_id."'"
		);
	}

	public static function exportFull($core,$exp)
	{
		$exp->exportTable('rateit');
	}

	public static function importInit($bk,$core)
	{
		$bk->cur_rateit = $core->con->openCursor($core->prefix.'rateit');
	}

	public static function importSingle($line,$bk,$core)
	{
		if ($line->__name != 'rateit') return;

		if ($line->rateit_type == 'post' && isset($bk->old_ids['post'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['post'][(integer) $line->rateit_id];
		}

		elseif ($line->rateit_type == 'comment') {
			# Can't retreive old/new comment_id
			return;
		}

		elseif ($line->rateit_type == 'category' && isset($bk->old_ids['category'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['category'][(integer) $line->rateit_id];
		}

		elseif ($line->rateit_type == 'gal' && isset($bk->old_ids['post'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['post'][(integer) $line->rateit_id];
		}

		elseif ($line->rateit_type == 'galitem' && isset($bk->old_ids['post'][(integer) $line->rateit_id])) {
			$line->rateit_id = $bk->old_ids['post'][(integer) $line->rateit_id];
		}

		elseif ($line->rateit_type == 'tag') {
			$line->rateit_id = (string) $line->rateit_id;
		}

		else return;


		$bk->cur_rateit->clean();
		$bk->cur_rateit->blog_id   = (string) $core->blog_id;
		$bk->cur_rateit->rateit_id   = (string) $line->rateit_id;
		$bk->cur_rateit->rateit_type   = (string) $line->rateit_type;
		$bk->cur_rateit->rateit_note   = (integer) $line->rateit_note;
		$bk->cur_rateit->rateit_quotient   = (integer) $line->rateit_quotient;
		$bk->cur_rateit->rateit_ip   = (string) $line->rateit_ip;
		$bk->cur_rateit->rateit_time   = (string) $line->rateit_time;
		$bk->cur_rateit->insert();
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