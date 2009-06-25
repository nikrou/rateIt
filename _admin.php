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

if (!defined('DC_CONTEXT_ADMIN')) return;

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

$core->addBehavior('pluginsBeforeDelete', array('rateItInstall', 'pluginsBeforeDelete'));

$core->addBehavior('adminBeforePostDelete',array('rateItAdmin','adminBeforePostDelete'));
$core->addBehavior('adminPostsActionsCombo',array('rateItAdmin','adminPostsActionsCombo'));
$core->addBehavior('adminPostsActions',array('rateItAdmin','adminPostsActions'));
$core->addBehavior('adminPostsActionsContent',array('rateItAdmin','adminPostsActionsContent'));

$core->addBehavior('adminRateItTabs',array('rateItAdmin','adminTabsCat'));

class rateItAdmin
{
	public static function adminBeforePostDelete(&$post_id)
	{
		$post_id = (integer) $post_id;
		$rateIt = new rateIt($GLOBALS['core']);
		$rateIt->del('post',$post_id);
	}

	public static function adminPostsActionsCombo(&$args)
	{
		global $core;
		if ($core->blog->settings->rateit_active 
		 && $core->auth->check('delete,contentadmin',$core->blog->id)) {
			$args[0][__('delete rating')] = 'rateit_empty';
		}
	}

	public static function adminPostsActions(&$core,$posts,$action,$redir)
	{
		if ($action == 'rateit_do_empty') {
			try {
				$rateIt = new rateIt($core);
				while ($posts->fetch()) {
					$rateIt->del('post',$posts->post_id);
				}
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

	public static function adminTabsCat(&$core)
	{
		try {
			$rateIt = new rateIt($core);
			$categories = $core->blog->getCategories(array('post_type'=>'post'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}

		if (isset($_POST['action']) && $_POST['action'] == 'rateit_cat_empty' && isset($_POST['entries'])) {
			foreach($_POST['entries'] as $cat_id) {
				$rateIt->del('cat',$cat_id);
			}
		}

		$table = '';
		while ($categories->fetch()) {
			$rs = $rateIt->get('cat',$categories->cat_id);
			if (!$rs->total) continue;
			$table .= 
			'<tr class="line">'.
			'<td class="nowrap">'.form::checkbox(array('entries[]'),$categories->cat_id,'','','',false).'</td>'.
			'<td class="maximal"><a href="plugin.php?p=rateIt&amp;t=post&amp;cat_id='.$categories->cat_id.'">
				'.html::escapeHTML($categories->cat_title).'</a></td>'.
			'<td class="nowrap">'.$rs->note.'</td>'.
			'<td class="nowrap"><a title="'.__('Show rating details').'" href="plugin.php?p=rateIt&amp;t=details&amp;type=cat&amp;id='.$categories->cat_id.'">'.$rs->total.'</a></td>'.
			'<td class="nowrap">'.$rs->max.'</td>'.
			'<td class="nowrap">'.$rs->min.'</td>'.
			'<td class="nowrap">'.$categories->cat_id.'</td>'.
			'<td class="nowrap">'.$categories->level.'</td>'.
			'<td class="nowrap">'.$categories->nb_post.'</td>'.
			'</tr>';
		}

		echo 
		'<div class="multi-part" id="cat" title="'.__('Categories').'">'.
		'<p>'.__('This is a list of all the categories having rating').'</p>'.
		'<form action="plugin.php" method="post" id="form-cats">';

		if ($table=='') {
			echo '<p class="message">'.__('There is no category rating at this time').'</p>';
		} else {
			echo 
			'<table class="clear"><tr>'.
			'<th colspan="2">'.__('Title').'</th>'.
			'<th>'.__('Note').'</th>'.
			'<th>'.__('Votes').'</th>'.
			'<th>'.__('Higher').'</th>'.
			'<th>'.__('Lower').'</th>'.
			'<th>'.__('Id').'</th>'.
			'<th>'.__('Level').'</th>'.
			'<th>'.__('Entries').'</th>'.
			'</tr>'.
			$table.
			'</table>';
		}

		if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
			echo 
			'<div class="two-cols">'.
			'<p class="col checkboxes-helpers"></p>'.
			'<p class="col right">'.__('Selected categories action:').' '.
			form::combo(array('action'),array(__('delete rating') => 'rateit_cat_empty')).
			'<input type="submit" name="save[cat]" value="'.__('ok').'" />'.
			form::hidden(array('p'),'rateIt').
			form::hidden(array('t'),'cat').
			$core->formNonce().
			'</p>'.
			'</div>';
		}
		echo 
		'</form>'.
		'</div>';
	}
}
?>