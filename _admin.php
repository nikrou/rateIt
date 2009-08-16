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

if ($core->blog->settings->rateit_active === null) {
	try {
		rateItInstall::setSettings($core);
	}
	catch (Exception $e) {
		$core->error->add($e->getMessage());
	}
}

$_menu['Plugins']->addItem(
	__('Rate it'),
	'plugin.php?p=rateIt','index.php?pf=rateIt/icon.png',
	preg_match('/plugin.php\?p=rateIt(&.*)?$/',$_SERVER['REQUEST_URI']),
	$core->auth->check('usage,contentadmin',$core->blog->id));

require dirname(__FILE__).'/_widgets.php';

$core->addBehavior('pluginsBeforeDelete',array('rateItInstall', 'pluginsBeforeDelete'));

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
		if ($action == 'rateit_do_empty') {
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
?>