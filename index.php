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
__('Votes') => 'rateit_count',
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
$params['post_type'] = 'post';

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

$tab = isset($_REQUEST['t']) ? $_REQUEST['t'] : '';
if (empty($tab))
	$tab = $core->blog->settings->rateit_active ? 'resum' : 'admin';

echo 
'<html>'.
'<head>'.
'<title>'.__('Rate it').'</title>'.
dcPage::jsToolBar().
dcPage::jsPageTabs($tab).
'<script type="text/javascript">'."
    $(function() {
		$('#post-options-title').toggleWithLegend($('#post-options-content'),{cookie:'dcx_rateit_admin_post_options'});
		$('#post-entries-title').toggleWithLegend($('#post-entries-content'),{cookie:'dcx_rateit_admin_post_entries'});
    });".
'</script>';

# --BEHAVIOR-- adminRateItHeader
$core->callBehavior('adminRateItHeader',$core);

echo
'</head>'.
'<body>'.
'<h2>'.html::escapeHTML($core->blog->name).' &rsaquo; '.__('Rate it').'</h2>'.
(!empty($msg) ? '<p class="message">'.$msg.'</p>' : '');


$rateIt = new rateIt($core);

/**************
** Summary
**************/

$rateit_types = $rateIt->getTypes();
$i = $total = 0;
$last = $sort = array();

foreach($rateit_types AS $type) {

	$rs = $core->con->select(
	'SELECT rateit_note,rateit_quotient,rateit_time,rateit_ip,rateit_id '.
	'FROM '.$core->prefix.'rateit WHERE blog_id=\''.$core->blog->id.'\' '.
	'AND rateit_type=\''.$core->con->escape($type).'\' '.
	'ORDER BY rateit_time DESC '.$core->con->limit(1));
	
	$count = $rateIt->getCount($type);
	$total += $count;

	if ($rs->isEmpty()) {
		$sort[] = $i;
		$last[$i] = array('type' => $type,'count' => $count,
			'date' => '-','note' => '-','ip' => '-','id' => '-');
		$i++;
	} else {
		$sort[] = strtotime($rs->rateit_time);
		$last[strtotime($rs->rateit_time)] = array(
			'type' => $type,
			'count' => $count,
			'date' => dt::dt2str(__('%Y-%m-%d %H:%M'),$rs->rateit_time,$core->auth->getInfo('user_tz')),
			'note' => ($rs->rateit_note / $rs->rateit_quotient * $core->blog->settings->rateit_quotient).'/'.$core->blog->settings->rateit_quotient,
			'ip' => $rs->rateit_ip,
			'id' => $rs->rateit_id
		);
	}
}

echo '
<div class="multi-part" id="resum" title="'.__('Summary').'">
<p>'.sprintf(__('There is a total of %s votes on this blog.'),$total).'</p>
<table><tr>
<th colspan="2">'.__('Total').'</th>
<th colspan="4">'.__('Last').'</th>
<tr>
<th>'.__('Type').'</th>
<th>'.__('Votes').'</th>
<th>'.__('Date').'</th>
<th>'.__('Note').'</th>
<th>'.__('Ip').'</th>
<th>'.__('Id').'</th></tr>';
rsort($sort);
foreach($sort AS $k) {
	echo 
	'<tr class="line">'.
	'<td class="nowrap">'.$last[$k]['type'].'</td>'.
	'<td class="maximal">'.$last[$k]['count'].'</td>'.
	'<td class="nowrap">'.$last[$k]['date'].'</td>'.
	'<td class="nowrap">'.$last[$k]['note'].'</td>'.
	'<td class="nowrap">'.$last[$k]['ip'].'</td>'.
	'<td class="nowrap">'.$last[$k]['id'].'</td>'.
	'</tr>';
}

echo '</table></div>';

/**************
** Details
**************/

if ($core->auth->check('usage,contentadmin',$core->blog->id) && isset($_REQUEST['type']) && isset($_REQUEST['id'])) {

	$rateIt = new rateIt($core);

	if (isset($_POST['action']) && $_POST['action'] == 'rateit_del_entry' && !empty($_POST['entries'])) {
		foreach($_POST['entries'] AS $entry) {
			$val = explode('|',$entry);
			$rateIt->del($val[0],$val[1],$val[2]);
		}
		http::redirect($p_url.'&t=details&type='.$_REQUEST['type'].'&id='.$_REQUEST['id'].'&done=1');
	}
	$rs = $rateIt->getDetails($_REQUEST['type'],$_REQUEST['id']);

	$lines = '';
	while ($rs->fetch()) {
		$lines .= 
		'<tr class="line">'.
		'<td class="nowrap">'.form::checkbox(array('entries[]'),$rs->rateit_type.'|'.$rs->rateit_id.'|'.$rs->rateit_ip,'','','',false).'</td>'.
		'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$rs->rateit_time,$core->auth->getInfo('user_tz')).'</td>'.
		'<td class="nowrap">'.$rs->rateit_note.'</td>'.
		'<td class="nowrap">'.$rs->rateit_quotient.'</td>'.
		'<td class="nowrap maximal">'.$rs->rateit_ip.'</td>'.
		'<td class="nowrap">'.$rs->rateit_type.'</td>'.
		'<td class="nowrap">'.$rs->rateit_id.'</td>'.
		'</tr>';
	}

	echo 
	'<div class="multi-part" id="details" title="'.__('Details').'">'.
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
		'<p class="col right">'.__('Selected entries action:').' '.
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
** Entries
**************/

if ($core->auth->check('usage,contentadmin',$core->blog->id)) {

	if ($core->auth->check('admin',$core->blog->id)
	&& !empty($_POST['save']['post']) && isset($_POST['s'])) {
		try {
			$core->blog->settings->setNamespace('rateit');
			$core->blog->settings->put('rateit_poststpl',$_POST['s']['rateit_poststpl'],'boolean','rateit template on post on post page',true,false);
			$core->blog->settings->put('rateit_homepoststpl',$_POST['s']['rateit_homepoststpl'],'boolean','rateit template on post on home page',true,false);
			$core->blog->settings->put('rateit_tagpoststpl',$_POST['s']['rateit_tagpoststpl'],'boolean','rateit template on post on tag page',true,false);
			$core->blog->settings->put('rateit_categorypoststpl',$_POST['s']['rateit_categorypoststpl'],'boolean','rateit template on post on category page',true,false);
			$core->blog->settings->put('rateit_categorylimitposts',$_POST['s']['rateit_categorylimitposts'],'integer','rateit limit post vote to one category',true,false);
			$core->blog->triggerBlog();
			http::redirect($p_url.'&t=post&done=1');
		}
		catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
	}

	$pager_base_url = $p_url.
	'&amp;t=post'.
	'&amp;cat_id='.$cat_id.
	'&amp;status='.$status.
	'&amp;selected='.$selected.
	'&amp;sortby='.$sortby.
	'&amp;order='.$order.
	'&amp;nb='.$nb_per_page.
	'&amp;page=%s';

	try {
		$posts = $rateIt->getPostsByRate($params);
		$counter = $rateIt->getPostsByRate($params,true);
		$post_list = new rateItPostsList($core,$posts,$counter->f(0),$pager_base_url);
	} catch (Exception $e) {
		$core->error->add($e->getMessage());
	}

	echo '<div class="multi-part" id="post" title="'.__('Entries').'">';

	if ($core->auth->check('admin',$core->blog->id)) {
		echo 
		'<h2 id="post-options-title">'.__('Settings for entries').'</h2>'.
		'<div id="post-options-content">'.
		'<form method="post" action="'.$p_url.'">'.
		'<table>'.
		'<tr><td>'.__('Include on entries pages').'*</td><td>'.form::combo(array('s[rateit_poststpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_poststpl).'</td></tr>'.
		'<tr><td>'.__('Include on home page').'*</td><td>'.form::combo(array('s[rateit_homepoststpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_homepoststpl).'</td></tr>'.
		'<tr><td>'.__('Include on tag page').'*</td><td>'.form::combo(array('s[rateit_tagpoststpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_tagpoststpl).'</td></tr>'.
		'<tr><td>'.__('Include on categories page').'*</td><td>'.form::combo(array('s[rateit_categorypoststpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_categorypoststpl).'</td></tr>'.
		'<tr><td>'.__('Limit to one category').'</td><td>'.form::combo(array('s[rateit_categorylimitposts]'),$categories_combo,$core->blog->settings->rateit_categorylimitposts).'</td></tr>'.
		'</table>'.
		'<p>'.
		form::hidden(array('p'),'rateIt').
		form::hidden(array('t'),'post').
		$core->formNonce().
		'<input type="submit" name="save[post]" value="'.__('Save').'" /></p>'.
		'</form>'.
		'<p class="form-note">* '.__('To use this option you must have behavior "publicEntryAfterContent" in your theme').'</p>'.
		'</div>';
	}

	echo 
	'<h2 id="post-entries-title">'.__('List of entries').'</h2>'.
	'<div id="post-entries-content">'.
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
	echo '</div></div>';
}

/**************
** New tab behavior 
**************/

# --BEHAVIOR-- adminRateItTabs
$core->callBehavior('adminRateItTabs',$core);

/**************
** Options 
**************/

if ($core->auth->check('admin',$core->blog->id)) {

	echo '<div class="multi-part" id="admin" title="'.__('Settings').'">';

	# Save admin options
	if (!empty($_POST['save']['admin']) && isset($_POST['s'])) {
		try {
			$core->blog->settings->setNamespace('rateit');
			$core->blog->settings->put('rateit_active',$_POST['s']['rateit_active'],'boolean','rateit plugin enabled',true,false);
			$core->blog->settings->put('rateit_userident',$_POST['s']['rateit_userident'],'integer','rateit use cookie and/or ip',true,false);
			$core->blog->settings->put('rateit_dispubjs',$_POST['s']['rateit_dispubjs'],'boolean','disable rateit public javascript',true,false);
			$core->blog->settings->put('rateit_quotient',$_POST['s']['rateit_quotient'],'integer','rateit maximum note',true,false);
			$core->blog->settings->put('rateit_digit',$_POST['s']['rateit_digit'],'integer','rateit note digits number',true,false);
			$core->blog->settings->put('rateit_msgthanks',$_POST['s']['rateit_msgthanks'],'string','rateit message when voted',true,false);
			$core->blog->settings->put('rateit_module_prefix',$_POST['s']['rateit_module_prefix'],'string','rateit prefix url for files',true,false);
			$core->blog->settings->put('rateit_post_prefix',$_POST['s']['rateit_post_prefix'],'string','rateit prefix url for post form',true,false);
			$core->blog->settings->put('rateit_rest_prefix',$_POST['s']['rateit_rest_prefix'],'string','rateit prefix url for rest service',true,false);

			# Destination image according to libImagePath()
			$dest_file = DC_ROOT.'/'.$core->blog->settings->public_path.'/rateIt-default-image.png';

			# Change rate image
			if (isset($_POST['s']['starsimage']) && preg_match('/^star-[0-9]+.png$/',$_POST['s']['starsimage'])) {

				$source = dirname(__FILE__).'/default-templates/img/stars/'.$_POST['s']['starsimage'];

				if (file_exists($source))
					file_put_contents($dest_file,file_get_contents($source));
			}
			# Upload rate image
			if (isset($_POST['s']['starsimage']) && $_POST['s']['starsimage'] == 'user' && $_FILES['starsuserfile']['tmp_name']) {

				if (2 == $_FILES['starsuserfile']['error'])
					throw new Exception(__('Maximum file size exceeded'));

				if (0 != $_FILES['starsuserfile']['error'])
					throw new Exception(__('Something went wrong while download file'));

				if ($_FILES['starsuserfile']['type'] != 'image/x-png')
					throw new Exception(__('Image must be in png format'));

				move_uploaded_file($_FILES['starsuserfile']['tmp_name'],$dest_file);
			}
			$core->blog->triggerBlog();
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
	$combo_userident = array(__('Ip')=>0,__('Cookie')=>2,__('Both ip and cookie')=>1);
	# Display
	echo 
	'<p>'.__('Administration of options of this extension on this blog').'</p>'.
	'<form method="post" action="'.$p_url.'" enctype="multipart/form-data">'.
	'<div class="two-cols">'.
	'<div class="col">'.
	'<h2>'.__('Options').'</h2>'.
	'<table>'.
	'<tr><th colspan="2">'.__('Extension').'</th></tr>'.
	'<tr><td>'.__('Enable plugin').'</td><td>'.form::combo(array('s[rateit_active]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_active).'</td></tr>'.
	'<tr><td>'.__('Disable public javascript').'</td><td>'.form::combo(array('s[rateit_dispubjs]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_dispubjs).'</td></tr>'.
	'<tr><td>'.__('Identify users by').'</td><td>'.form::combo(array('s[rateit_userident]'),$combo_userident,$core->blog->settings->rateit_userident).'</td></tr>'.
	'<tr><th colspan="2">'.__('Note').'</th></tr>'.
	'<tr><td>'.__('Note out of').'</td><td>'.form::combo(array('s[rateit_quotient]'),$combo_quotient,$core->blog->settings->rateit_quotient).'</td></tr>'.
	'<tr><td>'.__('Number of digits').'</td><td>'.form::combo(array('s[rateit_digit]'),$combo_digit,$core->blog->settings->rateit_digit).'</td></tr>'.
	'<tr><td>'.__('Message of thanks').'*</td><td>'.form::field(array('s[rateit_msgthanks]'),40,255,html::escapeHTML($core->blog->settings->rateit_msgthanks),'',2).'</td></tr>'.
	'<tr><th colspan="2">'.__('URL prefix').'**</th></tr>'.
	'<tr><td>'.__('Files').'</td><td>'.form::field(array('s[rateit_module_prefix]'),40,50,$core->url->getBase('rateItmodule')).'</td></tr>'.
	'<tr><td>'.__('Post form').'</td><td>'.form::field(array('s[rateit_post_prefix]'),40,50,$core->url->getBase('rateItpostform')).'</td></tr>'.
	'<tr><td>'.__('Rest service').'</td><td>'.form::field(array('s[rateit_rest_prefix]'),40,50,$core->url->getBase('rateItservice')).'</td></tr>'.
	'</table>'.
	'<p class="form-note">*'.__('This message replaces stars, leave it empty to not replace stars').'</p>'.
	'<p class="form-note">**'.__('Change these prefixes only if you have any conflicts with other links.').'</p>'.
	'</div>'.
	'<div class="col">'.
	'<h2>'.__('Image').'</h2>';

	$stars_rateit_files = files::scandir(dirname(__FILE__).'/default-templates/img/stars');
	$stars = libImagePath::getArray($core,'rateIt');

	if (file_exists($stars['theme']['dir'])) {
		echo 
		'<p>'.__('Rating image exists on theme it will be used:').'</p>'.
		form::hidden(array('s[starsimage]'),'theme').
		'<table><tr><th>'.__('negative').'</th><th>'.__('positive').'</th><th>'.__('hover').'</th><th>'.__('size').'</th></tr>'.
		'<tr>'.rateit_demo($stars['theme']).'</tr></table>';
	} else {
		echo 
		'<p>'.__('Rating image not exists on theme choose one to use:').'</p>'.
		'<table><tr><th>&nbsp;</th><th>'.__('negative').'</th><th>'.__('positive').'</th><th>'.__('hover').'</th><th>'.__('size').'</th></tr>';
		if (file_exists($stars['public']['dir'])) {
			echo 
			'<tr><td>'.form::radio(array('s[starsimage]'),'default',1).'</td>'.
			rateit_demo($stars['public']).'</tr>';
		}
		elseif (file_exists($stars['module']['dir'])) {
			echo 
			'<tr><td>'.form::radio(array('s[starsimage]'),'default',1).'</td>'.
			rateit_demo($stars['module']).'</tr>';
		}
		sort($stars_rateit_files);
		foreach($stars_rateit_files AS $f) {
			if (!preg_match('/star-[0-9]+.png/',$f)) continue;

			echo 
			'<tr class="line"><td>'.form::radio(array('s[starsimage]'),$f).'</td>'.
			rateit_demo(array(
				'dir'=>dirname(__FILE__).'/default-templates/img/stars/'.$f,
				'url'=>'index.php?pf=rateIt/default-templates/img/stars/'.$f)
			).'</tr>';
		}
		echo 
		'<tr class="line"><td>'.form::radio(array('s[starsimage]'),'user').'</td>'.
		'<td colspan="4">'.form::hidden(array('MAX_FILE_SIZE'),30000).'<input type="file" name="starsuserfile" /></td></tr>'.
		'</table>'.
		'<p class="form-note">'.__('Please read the README file before uploading image').'</p>';
	}
	echo
	'</div>'.
	'</div>'.
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

if ($core->auth->isSuperAdmin()) {

	echo '<div class="multi-part" id="uninstall" title="'.__('Uninstall').'">';

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
		__('You understand that if you delete this plugin, the other plugins that use there tables and classes will no longer work.').'</label><br />'.
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
		__('You understand that if you delete this plugin, the other plugins that use there tables and classes will no longer work.').'</label><br />'.
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
<div class="multi-part" id="about" title="'.__('About').'">
<h3>'.__('Version:').'</h3>
<ul><li>rateIt '.$core->plugins->moduleInfo('rateIt','version').'</li></ul>
<h3>'.__('Support:').'</h3>
<ul>
<li><a href="http://dotclear.jcdenis.com/">Author\'s blog</a></li>
<li><a href="http://forum.dotclear.net/viewtopic.php?id=39801">Dotclear forum</a></li>
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
<li>Traduced with Dotclear plugin Translater,</li>
<li>Packaged with Dotclear plugin Packager.</li>
<li>Used jQuery Star Rating Plugin v3.12 by <a href="http://www.fyneworks.com/jquery/star-rating/">Fyneworks</a></li>
</ul>
<h3>'.__('Special thanks to').'</h3>
<ul>
<li>BG - <a href="http://bg-web.fr/dotclear/">http://bg-web.fr/dotclear/</a></li>
<li>jmh2o - <a href="http://www.levertpays.be/">http://www.levertpays.be/</a></li>
</ul>
</div>
<hr class="clear"/>
<p class="right">
rateIt - '.$core->plugins->moduleInfo('rateIt','version').'&nbsp;
<img alt="'.__('Rate it').'" src="index.php?pf=rateIt/icon.png" />
</p>
</body></html>';

function rateit_demo($star)
{
	$s = getimagesize($star['dir']);
	return
	'<td><div style="
		display:block;
		overflow:hidden;
		text-indent:-999em;
		margin: 0px;
		padding: 1px;
		background: transparent url('.$star['url'].') no-repeat 0 0;
		width:'.$s[0].'px;
		height:'.(floor($s[1] /3)-1).'px;
	">&nbsp;</div></td>
	<td><div style="
		display:block;
		overflow:hidden;
		text-indent:-999em;
		margin: 0px;
		padding: 1px;
		background: transparent url('.$star['url'].') no-repeat 0 -'.floor($s[1] /3).'px;
		width:'.$s[0].'px;
		height:'.(floor($s[1] /3)-1).'px;
	">&nbsp;</div></td>
	<td><div style="
		display:block;
		overflow:hidden;
		text-indent:-999em;
		margin: 0px;
		padding: 1px;
		background: transparent url('.$star['url'].') no-repeat 0 -'.(floor($s[1] /3) *2).'px;
		width:'.$s[0].'px;
		height:'.(floor($s[1] /3)-1).'px;
	">&nbsp;</div></td><td>'.$s[0].'x'.floor($s[1] /3).'</td>';
}
?>