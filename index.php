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

/** Init some values **/

$msg = isset($_REQUEST['done']) ? __('Configuration saved') : '';
$img_green = '<img alt="" src="images/check-on.png" />';
$img_red = '<img alt="" src="images/check-off.png" />';

$tab = array('about' => __('About'));
if ($core->auth->check('usage,contentadmin',$core->blog->id))  {
	$tab['post'] = __('Entries');
	$tab['details'] = __('Details');
}
if ($core->auth->check('admin',$core->blog->id)) 
	$tab['admin'] = __('Administration');
if ($core->auth->isSuperAdmin()) 
	$tab['uninstall'] = __('Uninstall');

$show_filters = false;
$user_id = !empty($_GET['user_id']) ? $_GET['user_id'] : '';
$cat_id = !empty($_GET['cat_id']) ? $_GET['cat_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$selected = isset($_GET['selected']) ? $_GET['selected'] : '';
$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'post_dt';
$order = !empty($_GET['order']) ? $_GET['order'] : 'desc';
$period = !empty($_GET['period']) ? $_GET['period'] : '';

$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
$nb_per_page =  30;
if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
	if ($nb_per_page != $_GET['nb']) $show_filters = true;
	$nb_per_page = (integer) $_GET['nb'];
}

$understand = isset($_POST['s']['understand']) ? $_POST['s']['understand'] : 0;
$delete_table = isset($_POST['s']['delete_table']) ? $_POST['s']['delete_table'] : 0;
$delete_settings = isset($_POST['s']['delete_settings']) ? $_POST['s']['delete_settings'] : 0;

/** Combo array **/

$combo_action = array();
if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
	$combo_action[__('delete rating')] = 'rateit_empty';
}
if ($core->auth->check('publish,contentadmin',$core->blog->id)) {
	$combo_action[__('publish')] = 'publish';
	$combo_action[__('unpublish')] = 'unpublish';
	$combo_action[__('schedule')] = 'schedule';
	$combo_action[__('mark as pending')] = 'pending';
	$combo_action[__('mark as selected')] = 'selected';
	$combo_action[__('mark as unselected')] = 'unselected';
}
$combo_action[__('change category')] = 'category';
if ($core->auth->check('admin',$core->blog->id)) {
	$combo_action[__('change author')] = 'author';
}
if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
	$combo_action[__('delete')] = 'delete';
}

$categories_combo = array('-'=>'');
try {
	$categories = $core->blog->getCategories(array('post_type'=>'post'));
} catch (Exception $e) {
	$core->error->add($e->getMessage());
}
while ($categories->fetch()) {
	$categories_combo[str_repeat('&nbsp;&nbsp;',$categories->level-1).'&bull; '.
		html::escapeHTML($categories->cat_title)] = $categories->cat_id;
}

$status_combo = array('-' => '');
foreach ($core->blog->getAllPostStatus() as $k => $v) {
	$status_combo[$v] = (string) $k;
}

$selected_combo = array(
'-' => '',
__('selected') => '1',
__('not selected') => '0'
);

$sortby_combo = array(
__('Date') => 'post_dt',
__('Votes') => 'rateit_total',
__('Note') => 'rateit_note',
__('Higher') => 'rateit_max',
__('Lower') => 'rateit_min',
__('Title') => 'post_title',
__('Category') => 'cat_title',
__('Author') => 'user_id',
__('Status') => 'post_status',
__('Selected') => 'post_selected'
);

$order_combo = array(
__('Descending') => 'desc',
__('Ascending') => 'asc'
);

/** "Static" params **/

$params = array();
$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
$params['no_content'] = true;
$params['rateit_type'] = 'post';
$params['post_type'] = '';

/** Filters **/

if ($cat_id !== '' && in_array($cat_id,$categories_combo)) {
	$params['cat_id'] = $cat_id;
	$show_filters = true;
}

if ($status !== '' && in_array($status,$status_combo)) {
	$params['post_status'] = $status;
	$show_filters = true;
}

if ($selected !== '' && in_array($selected,$selected_combo)) {
	$params['post_selected'] = $selected;
	$show_filters = true;
}

if ($sortby !== '' && in_array($sortby,$sortby_combo)) {
	if ($order !== '' && in_array($order,$order_combo)) {
		$params['order'] = $sortby.' '.$order;
	}	
	if ($sortby != 'post_dt' || $order != 'desc') {
		$show_filters = true;
	}
}

/** Display **/

$request_tab = isset($_REQUEST['t']) ? $_REQUEST['t'] : '';
if (!$core->blog->settings->rateit_active && empty($request_tab)) $request_tab = 'admin';
if ($core->blog->settings->rateit_active && empty($request_tab)) $request_tab = 'post';
if (empty($request_tab)) $request_tab = 'about';

echo 
'<html>'.
'<head>'.
'<title>'.__('Rate it').'</title>'.
dcPage::jsLoad('js/_posts_list.js').
dcPage::jsPageTabs($request_tab).
'</head>'.
'<body>'.
'<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; '.__('Rate it').' &rsaquo; '.$tab[$request_tab].'</h2>'.
 (!empty($msg) ? '<p class="message">'.$msg.'</p>' : '');

/**************
** Entries
**************/

if (isset($tab['post'])) {

	try {
		$rateIt = new rateIt($core);
		$posts = $rateIt->getPostsByRate($params);
		$counter = $rateIt->getPostsByRate($params,true);
		$post_list = new rateItPostsList($core,$posts,$counter->f(0));
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}

	echo 
	'<div class="multi-part" id="post" title="'.$tab['post'].'">'.
	'<p>'.__('This is the list of all entries having rating').'</p>';
	if (!$show_filters) { 
		echo dcPage::jsLoad('js/filter-controls.js').'<p><a id="filter-control" class="form-control" href="#">'.__('Filters').'</a></p>';
	}
	echo 
	'<form action="'.$p_url.'" method="get" id="filters-form">'.
	'<fieldset><legend>'.__('Filters').'</legend>'.
	'<div class="three-cols">'.
	'<div class="col">'.
	'<label>'.__('Category:').form::combo('cat_id',$categories_combo,$cat_id).'</label> '.
	'<label>'.__('Status:').form::combo('status',$status_combo,$status).'</label> '.
	'<label>'.__('Selected:').form::combo('selected',$selected_combo,$selected).'</label> '.
	'</div>'.
	'<div class="col">'.
	'<label>'.__('Order by:').form::combo('sortby',$sortby_combo,$sortby).'</label> '.
	'<label>'.__('Sort:').form::combo('order',$order_combo,$order).'</label>'.
	'</div>'.
	'<div class="col">'.
	'<p><label class="classic">'.form::field('nb',3,3,$nb_per_page).' '.__('Entries per page').'</label> '.
	'<input type="submit" value="'.__('filter').'" />'.
	form::hidden(array('p'),'rateIt').
	form::hidden(array('t'),'post').
	$core->formNonce().
	'</p>'.
	'</div>'.
	'</div>'.
	'<br class="clear" />'.
	'</fieldset>'.
	'</form>';

	$post_list->display($page,$nb_per_page,
		'<form action="posts_actions.php" method="post" id="form-actions">'.
		'%s'.
		'<div class="two-cols">'.
		'<p class="col checkboxes-helpers"></p>'.
		'<p class="col right">'.__('Selected entries action:').' '.
		form::combo(array('action'),$combo_action).
		'<input type="submit" value="'.__('ok').'" />'.
		form::hidden(array('cat_id'),$cat_id).
		form::hidden(array('status'),$status).
		form::hidden(array('selected'),$selected).
		form::hidden(array('sortby'),$sortby).
		form::hidden(array('order'),$order).
		form::hidden(array('page'),$page).
		form::hidden(array('nb'),$nb_per_page).
		form::hidden(array('redir'),$p_url.'&amp;t=post').
		$core->formNonce().'</p>'.
		'</div>'.
		'</form>'
	);
	echo '</div>';
}

/**************
** Details
**************/

if (isset($tab['details']) && isset($_REQUEST['type']) && isset($_REQUEST['id'])) {

	$rateIt = new rateIt($core);

	if (isset($_POST['action']) && $_POST['action'] == 'rateit_del_entry' && !empty($_POST['entries'])) {
		foreach($_POST['entries'] AS $entry) {
			$val = explode('|',$entry);
			$rateIt->del($entry[0],$entry[1],$entry[2]);
		}
	}
	$rs = $rateIt->getDetails($_REQUEST['type'],$_REQUEST['id']);

	$lines = '';
	while ($rs->fetch()) {
		$lines .= 
		'<tr class="line">'.
		'<td class="nowrap">'.form::checkbox(array('entries[]'),$rs->rateit_type.'|'.$rs->rateit_id.'|'.$rs->rateit_ip,'','','',false).'</td>'.
		'<td class="nowrap">'.$rs->rateit_time.'</td>'.
		'<td class="nowrap">'.$rs->rateit_note.'</td>'.
		'<td class="nowrap">'.$rs->rateit_quotient.'</td>'.
		'<td class="nowrap maximal">'.$rs->rateit_ip.'</td>'.
		'<td class="nowrap">'.$rs->rateit_type.'</td>'.
		'<td class="nowrap">'.$rs->rateit_id.'</td>'.
		'</tr>';
	}

	echo 
	'<div class="multi-part" id="details" title="'.$tab['details'].'">'.
	'<p>'.sprintf(__('This is detailed list for rating of type "%s" and id "%s"'),$_REQUEST['type'],$_REQUEST['id']).'</p>'.
	'<form action="plugin.php" method="post" id="form-details">';

	if ($lines=='') {
		echo '<p class="message">'.__('There is no rating for this request at this time').'</p>';
	} else {
		echo 
		'<table class="clear"><tr>'.
		'<th colspan="2">'.__('Date').'</th>'.
		'<th>'.__('Note').'</th>'.
		'<th>'.__('Quotient').'</th>'.
		'<th>'.__('Ip').'</th>'.
		'<th>'.__('Type').'</th>'.
		'<th>'.__('Id').'</th>'.
		'</tr>'.
		$lines.
		'</table>';
	}
	if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
		echo 
		'<div class="two-cols">'.
		'<p class="col checkboxes-helpers"></p>'.
		'<p class="col right">'.__('Selected categories action:').' '.
		form::combo(array('action'),array(__('delete entry') => 'rateit_del_entry')).
		'<input type="submit" name="save[details]" value="'.__('ok').'" />'.
		form::hidden(array('p'),'rateIt').
		form::hidden(array('t'),'details').
		form::hidden(array('type'),$_REQUEST['type']).
		form::hidden(array('id'),$_REQUEST['id']).
		$core->formNonce().
		'</p>'.
		'</div>';
	}
	echo '
	</form>
	</div>';
}

/**************
** New tab behavior 
**************/

$core->callBehavior('adminRateItTabs',$core);

/**************
** Options 
**************/

if (isset($tab['admin'])) {

	echo '<div class="multi-part" id="admin" title="'.$tab['admin'].'">';

	# Save admin options
	if (!empty($_POST['save']['admin']) && isset($_POST['s'])) {
		try {
			$core->blog->settings->setNamespace('rateit');
			$core->blog->settings->put('rateit_active',$_POST['s']['rateit_active'],'boolean','rateit plugin enabled',true,false);
			$core->blog->settings->put('rateit_poststpl',$_POST['s']['rateit_poststpl'],'boolean','rateit template on post',true,false);
			$core->blog->settings->put('rateit_quotient',$_POST['s']['rateit_quotient'],'integer','rateit maximum note',true,false);
			$core->blog->settings->put('rateit_digit',$_POST['s']['rateit_digit'],'integer','rateit note digits number',true,false);
			http::redirect($p_url.'&t=admin&done=1');
		}
		catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}

	$combo_quotient = array();
	for($i=2;$i<21;$i++){ $combo_quotient[$i] = $i; }
	$combo_digit = array();
	for($i=0;$i<5;$i++){ $combo_digit[$i] = $i; }
	# Display
	echo 
	'<p>'.__('Plugin admistration options on this blog').'</p>'.
	'<form method="post" action="'.$p_url.'">'.
	'<p class="field">'.__('Enable plugin').' '.form::combo(array('s[rateit_active]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_active).'</p>'.
	'<p class="field">'.__('Include on entries').' '.form::combo(array('s[rateit_poststpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_poststpl).'</p>'.
	'<p class="field">'.__('Note out of').' '.form::combo(array('s[rateit_quotient]'),$combo_quotient,$core->blog->settings->rateit_quotient).'</p>'.
	'<p class="field">'.__('Number of digits').' '.form::combo(array('s[rateit_digit]'),$combo_digit,$core->blog->settings->rateit_digit).'</p>'.
	'<p>'.
	form::hidden(array('p'),'rateIt').
	form::hidden(array('t'),'admin').
	$core->formNonce().
	'<input type="submit" name="save[admin]" value="'.__('Save').'" /></p>'.
	'</form>'.
	'</div>';
}

/**************
** Uninstall 
**************/

if (isset($tab['uninstall']) && $core->auth->isSuperAdmin()) {

	echo '<div class="multi-part" id="uninstall" title="'.$tab['uninstall'].'">';

	# Save admin options
	if (!empty($_POST['save']['validate']) && isset($_POST['s'])) {
		try {
			if (1 != $understand)
				throw new Exception(__('You must check warning in order to delete plugin.'));

			if (1 == $delete_table)
				rateItInstall::delTable($core);

			if (1 == $delete_settings)
				rateItInstall::delSettings($core);

			rateItInstall::delVersion($core);
			rateItInstall::delModule($core);
		}
		catch (Exception $e) {
			$core->error->add($e->getMessage());
		}

		if (!$core->error->flag())
			http::redirect('plugins.php?removed=1');
	}
	# Confirm options
	if (!empty($_POST['save']['uninstall']) && isset($_POST['s']) && 1 == $understand) {
		echo 
		'<p>'.__('In order to properly uninstall this plugin, you must specify the actions to perform').'</p>'.
		'<form method="post" action="'.$p_url.'">'.
		'<p>'.
		'<label class=" classic">'.sprintf(($understand ? $img_green : $img_red),'-').
		__('You understand that if you delete this plugin, the other plugins that use there table and class will no longer work.').'</label><br />'.
		'<label class=" classic">'.sprintf($img_green,'-').
		__('Delete plugin files').'</label><br />'.
		'<label class=" classic">'.sprintf(($delete_table ? $img_green : $img_red),'-').
		__('Delete plugin database table').'</label><br />'.
		'<label class=" classic">'.sprintf(($delete_settings ? $img_green : $img_red),'-').
		__('Delete plugin settings').'</label><br />'.
		'</p>'.
		'<p>'.
		form::hidden(array('p'),'rateIt').
		form::hidden(array('t'),'uninstall').
		form::hidden(array('s[understand]'),$understand).
		form::hidden(array('s[delete_table]'),$delete_table).
		form::hidden(array('s[delete_settings]'),$delete_settings).
		$core->formNonce().
		'<input type="submit" name="save[validate]" value="'.__('Uninstall').'" />'.
		'<input type="submit" name="save[back]" value="'.__('Back').'" /></p>'.
		'</form>';

	# Option form
	} else {
		if (!empty($_POST['save']['uninstall']) && 1 != $understand)
			$core->error->add(__('You must check warning in order to delete plugin.'));

		echo 
		'<p>'.__('In order to properly uninstall this plugin, you must specify the actions to perform').'</p>'.
		'<form method="post" action="'.$p_url.'">'.
		'<p>'.
		'<label class=" classic">'.form::checkbox(array('s[understand]'),1,$understand).
		__('You understand that if you delete this plugin, the other plugins that use there table and class will no longer work.').'</label><br />'.
		'<label class=" classic">'.form::checkbox(array('s[delete_table]'),1,$delete_table).
		__('Delete plugin database table').'</label><br />'.
		'<label class=" classic">'.form::checkbox(array('s[delete_settings]'),1,$delete_settings).
		__('Delete plugin settings').'</label><br />'.
		'</p><p>'.
		form::hidden('p','rateIt').
		form::hidden('t','uninstall').
		$core->formNonce().
		'<input type="submit" name="save[uninstall]" value="'.__('Uninstall').'" /></p>'.
		'</form>';
	}
	echo '</div>';
}

/**************
** About 
**************/

echo '
<div class="multi-part" id="about" title="'.$tab['about'].'">
<h3>'.__('Version:').'</h3>
<ul><li>rateIt '.$core->plugins->moduleInfo('rateIt','version').'</li></ul>
<h3>'.__('Support:').'</h3>
<ul>
<li><a href="http://blog.jcdenis.com/?q=dotclear+plugin+rateIt">Author\'s blog</a></li>
<li><a href="http://forum.dotclear.net/index.php">Dotclear forum</a></li>
<li><a href="http://lab.dotclear.org/wiki/plugin/rateIt">Dotclear lab</a></li>
</ul>
<h3>'.__('Copyrights:').'</h3>
<ul>
<li><strong>'.__('Files').'</strong><br />
These files are parts of rateIt, a plugin for Dotclear 2.<br />
Copyright (c) 2009 JC Denis and contributors<br />
Licensed under the GPL version 2.0 license.<br />
<a href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.html">http://www.gnu.org/licenses/old-licenses/gpl-2.0.html</a>
</li>
<li><strong>'.__('Images').'</strong><br />
Some icons from Silk icon set 1.3 by Mark James at:<br />
<a href="http://www.famfamfam.com/lab/icons/silk/">http://www.famfamfam.com/lab/icons/silk/</a><br />
under a Creative Commons Attribution 2.5 License<br />
<a href="http://creativecommons.org/licenses/by/2.5/">http://creativecommons.org/licenses/by/2.5/</a>.
</li>
</ul>
<h3>'.__('Tools').'</h3>
<ul>
<li>Traduced with plugin Translater,</li>
<li>Packaged with plugin Packager.</li>
</ul>
</div>
 </body>
</html>';
?>