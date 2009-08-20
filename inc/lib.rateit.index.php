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

class rateItTabs
{
	private static function pictureshow($star)
	{
		$s = getimagesize($star['dir']);
		return
		'<td><div style="'.
		'	display:block;'.
		'	overflow:hidden;'.
		'	text-indent:-999em;'.
		'	margin: 0px;'.
		'	padding: 1px;'.
		'	background: transparent url('.$star['url'].') no-repeat 0 0;'.
		'	width:'.$s[0].'px;'.
		'	height:'.(floor($s[1] /3)-1).'px;'.
		'">&nbsp;</div></td>'.
		'<td><div style="'.
		'	display:block;'.
		'	overflow:hidden;'.
		'	text-indent:-999em;'.
		'	margin: 0px;'.
		'	padding: 1px;'.
		'	background: transparent url('.$star['url'].') no-repeat 0 -'.floor($s[1] /3).'px;'.
		'	width:'.$s[0].'px;'.
		'	height:'.(floor($s[1] /3)-1).'px;'.
		'">&nbsp;</div></td>'.
		'<td><div style="'.
		'	display:block;'.
		'	overflow:hidden;'.
		'	text-indent:-999em;'.
		'	margin: 0px;'.
		'	padding: 1px;'.
		'	background: transparent url('.$star['url'].') no-repeat 0 -'.(floor($s[1] /3) *2).'px;'.
		'	width:'.$s[0].'px;'.
		'	height:'.(floor($s[1] /3)-1).'px;'.
		'">&nbsp;</div></td><td>'.$s[0].'x'.floor($s[1] /3).'</td>';
	}

	public static function requests($core)
	{
		$requests = new ArrayObject();
		$requests->p_url = 'plugin.php?p=rateIt';

		$requests->msg = isset($_REQUEST['done']) ? __('Configuration saved') : '';
		$requests->img_green = '<img alt="" src="images/check-on.png" />';
		$requests->img_red = '<img alt="" src="images/check-off.png" />';

		$requests->user_id = !empty($_GET['user_id']) ? $_GET['user_id'] : '';
		$requests->cat_id = !empty($_GET['cat_id']) ? $_GET['cat_id'] : '';
		$requests->status = isset($_GET['status']) ? $_GET['status'] : '';
		$requests->selected = isset($_GET['selected']) ? $_GET['selected'] : '';
		$requests->sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'post_dt';
		$requests->order = !empty($_GET['order']) ? $_GET['order'] : 'desc';
		$requests->period = !empty($_GET['period']) ? $_GET['period'] : '';

		$requests->page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
		$requests->nb_per_page =  30;
		if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
			$requests->nb_per_page = (integer) $_GET['nb'];
		}

		$requests->tab = isset($_REQUEST['t']) ? $_REQUEST['t'] : '';
		if ('' == $requests->tab)
			$requests->tab = $core->blog->settings->rateit_active ? 'resum' : 'admin';

		$requests->action = isset($_POST['action']) ? $_POST['action'] : '';

		$requests->id = isset($_GET['id']) ? $_GET['id'] : '';
		if (!empty($_POST['id'])) 
			$requests->id = $_POST['id'];

		$requests->type = isset($_GET['type']) ? $_GET['type'] : '';
		if (!empty($_POST['type'])) 
			$requests->type = $_POST['type'];

		return $requests;
	}

	public static function combos($core)
	{
		$combos = new ArrayObject();
		$combos->action = array();
		if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
			$combos->action[__('delete rating')] = 'rateit_empty';
		}
		if ($core->auth->check('publish,contentadmin',$core->blog->id)) {
			$combos->action[__('publish')] = 'publish';
			$combos->action[__('unpublish')] = 'unpublish';
			$combos->action[__('schedule')] = 'schedule';
			$combos->action[__('mark as pending')] = 'pending';
			$combos->action[__('mark as selected')] = 'selected';
			$combos->action[__('mark as unselected')] = 'unselected';
		}
		$combos->action[__('change category')] = 'category';
		if ($core->auth->check('admin',$core->blog->id)) {
			$combos->action[__('change author')] = 'author';
		}
		if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
			$combos->action[__('delete')] = 'delete';
		}

		$combos->categories = array('-'=>'');
		try {
			$categories = $core->blog->getCategories(array('post_type'=>'post'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
		while ($categories->fetch()) {
			$combos->categories[str_repeat('&nbsp;&nbsp;',$categories->level-1).'&bull; '.
				html::escapeHTML($categories->cat_title)] = $categories->cat_id;
		}

		$combos->status = array('-' => '');
		foreach ($core->blog->getAllPostStatus() as $k => $v) {
			$status_combo[$v] = (string) $k;
		}

		$combos->selected = array(
		'-' => '',
		__('selected') => '1',
		__('not selected') => '0'
		);

		$combos->sortby = array(
		__('Date') => 'post_dt',
		__('Votes') => 'rateit_count',
		__('Title') => 'post_title',
		__('Category') => 'cat_title',
		__('Author') => 'user_id',
		__('Status') => 'post_status',
		__('Selected') => 'post_selected'
		);

		$combos->order = array(
		__('Descending') => 'desc',
		__('Ascending') => 'asc'
		);

		return $combos;
	}

	public static function params($core,$requests,$combos)
	{
		$params = array();
		$params['show_filters'] = false;
		$params['limit'] = array((($requests->page-1)*$requests->nb_per_page),$requests->nb_per_page);
		$params['no_content'] = true;
		$params['rateit_type'] = 'post';
		$params['post_type'] = 'post';

		if ($requests->cat_id !== '' && in_array($requests->cat_id,$combos->categories)) {
			$params['cat_id'] = $requests->cat_id;
			$params['show_filters'] = true;
		}

		if ($requests->status !== '' && in_array($requests->status,$combos->status)) {
			$params['post_status'] = $requests->status;
			$params['show_filters'] = true;
		}

		if ($requests->selected !== '' && in_array($requests->selected,$combos->selected)) {
			$params['post_selected'] = $requests->selected;
			$params['show_filters'] = true;
		}

		if ($requests->sortby !== '' && in_array($requests->sortby,$combos->sortby)) {
			if ($requests->order !== '' && in_array($requests->order,$combos->order)) {
				$params['order'] = $requests->sortby.' '.$requests->order;
			}	
			if ($requests->sortby != 'post_dt' || $requests->order != 'desc') {
				$params['show_filters'] = true;
			}
		}
		return $params;
	}

	public static function settingsTab($core,$requests,$combos)
	{
		if (!$core->auth->check('admin',$core->blog->id)) return;

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

				# Destination image according to libImagePath()
				$dest_file = DC_ROOT.'/'.$core->blog->settings->public_path.'/rateIt-default-image.png';

				# Change rate image
				if (isset($_POST['s']['starsimage']) && preg_match('/^star-[0-9]+.png$/',$_POST['s']['starsimage'])) {

					$source = dirname(__FILE__).'/../default-templates/img/stars/'.$_POST['s']['starsimage'];

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
				http::redirect($requests->p_url.'&t=admin&done=1');
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
		'<form method="post" action="'.$requests->p_url.'" enctype="multipart/form-data">'.
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
		'</table>'.
		'<p class="form-note">*'.__('This message replaces stars, leave it empty to not replace stars').'</p>'.
		'<p class="form-note">'.__('In order to change url of public page you can use plugin dcUrlHandlers.').'</p>'.
		'</div>'.
		'<div class="col">'.
		'<h2>'.__('Image').'</h2>';

		$stars_rateit_files = files::scandir(dirname(__FILE__).'/../default-templates/img/stars');
		$stars = libImagePath::getArray($core,'rateIt');

		if (file_exists($stars['theme']['dir'])) {
			echo 
			'<p>'.__('Rating image exists on theme it will be used:').'</p>'.
			form::hidden(array('s[starsimage]'),'theme').
			'<table><tr><th>'.__('negative').'</th><th>'.__('positive').'</th><th>'.__('hover').'</th><th>'.__('size').'</th></tr>'.
			'<tr>'.self::pictureshow($stars['theme']).'</tr></table>';
		} else {
			echo 
			'<p>'.__('Rating image not exists on theme choose one to use:').'</p>'.
			'<table><tr><th>&nbsp;</th><th>'.__('negative').'</th><th>'.__('positive').'</th><th>'.__('hover').'</th><th>'.__('size').'</th></tr>';
			if (file_exists($stars['public']['dir'])) {
				echo 
				'<tr><td>'.form::radio(array('s[starsimage]'),'default',1).'</td>'.
				self::pictureshow($stars['public']).'</tr>';
			}
			elseif (file_exists($stars['module']['dir'])) {
				echo 
				'<tr><td>'.form::radio(array('s[starsimage]'),'default',1).'</td>'.
				self::pictureshow($stars['module']).'</tr>';
			}
			sort($stars_rateit_files);
			foreach($stars_rateit_files AS $f) {
				if (!preg_match('/star-[0-9]+.png/',$f)) continue;

				echo 
				'<tr class="line"><td>'.form::radio(array('s[starsimage]'),$f).'</td>'.
				self::pictureshow(array(
					'dir'=>dirname(__FILE__).'/../default-templates/img/stars/'.$f,
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

	public static function summaryTab($core)
	{
		$rateIt = new rateIt($core);
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
	}

	public static function detailTab($core,$requests)
	{
		if (!$core->auth->check('usage,contentadmin',$core->blog->id) || '' == $requests->type || '' == $requests->id) return;

		$rateIt = new rateIt($core);

		if ($requests->action == 'rateit_del_entry' && !empty($_POST['entries'])) {
			foreach($_POST['entries'] AS $entry) {
				$val = explode('|',$entry);
				$rateIt->del($val[0],$val[1],$val[2]);
			}
			http::redirect($requests->p_url.'&t=details&type='.$requests->type.'&id='.$requests->id.'&done=1');
		}
		$rs = $rateIt->getDetails($requests->type,$requests->id);

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
		'<p>'.sprintf(__('This is detailed list for rating of type "%s" and id "%s"'),$requests->type,$requests->id).'</p>'.
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
			'<input type="submit" name="save" value="'.__('ok').'" />'.
			form::hidden(array('p'),'rateIt').
			form::hidden(array('t'),'details').
			form::hidden(array('type'),$requests->type).
			form::hidden(array('id'),$requests->id).
			$core->formNonce().
			'</p>'.
			'</div>';
		}
		echo '
		</form>
		</div>';
	}

	public static function postTab($core,$requests,$params,$combos)
	{
		if (!$core->auth->check('usage,contentadmin',$core->blog->id)) return;

		if ($core->auth->check('admin',$core->blog->id)
		&& !empty($_POST['save']['post']) && isset($_POST['s'])) {
			try {
				$core->blog->settings->setNamespace('rateit');
				$core->blog->settings->put('rateit_post_active',$_POST['s']['rateit_post_active'],'boolean','Enabled post rating',true,false);
				$core->blog->settings->put('rateit_poststpl',$_POST['s']['rateit_poststpl'],'boolean','rateit template on post on post page',true,false);
				$core->blog->settings->put('rateit_homepoststpl',$_POST['s']['rateit_homepoststpl'],'boolean','rateit template on post on home page',true,false);
				$core->blog->settings->put('rateit_tagpoststpl',$_POST['s']['rateit_tagpoststpl'],'boolean','rateit template on post on tag page',true,false);
				$core->blog->settings->put('rateit_categorypoststpl',$_POST['s']['rateit_categorypoststpl'],'boolean','rateit template on post on category page',true,false);
				$core->blog->settings->put('rateit_categorylimitposts',$_POST['s']['rateit_categorylimitposts'],'integer','rateit limit post vote to one category',true,false);
				$core->blog->triggerBlog();
				http::redirect($requests->p_url.'&t=post&done=1');
			}
			catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}

		$pager_base_url = 
		$requests->p_url.
		'&amp;t=post'.
		'&amp;cat_id='.$requests->cat_id.
		'&amp;status='.$requests->status.
		'&amp;selected='.$requests->selected.
		'&amp;sortby='.$requests->sortby.
		'&amp;order='.$requests->order.
		'&amp;nb='.$requests->nb_per_page.
		'&amp;page=%s';

		try {
			$rateIt = new rateIt($core);
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
			'<form method="post" action="'.$requests->p_url.'">'.
			'<table>'.
			'<tr><td>'.__('Enable posts rating').'</td><td>'.form::combo(array('s[rateit_post_active]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_post_active).'</td></tr>'.
			'<tr><td>'.__('Include on entries pages').'*</td><td>'.form::combo(array('s[rateit_poststpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_poststpl).'</td></tr>'.
			'<tr><td>'.__('Include on home page').'*</td><td>'.form::combo(array('s[rateit_homepoststpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_homepoststpl).'</td></tr>'.
			'<tr><td>'.__('Include on tag page').'*</td><td>'.form::combo(array('s[rateit_tagpoststpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_tagpoststpl).'</td></tr>'.
			'<tr><td>'.__('Include on categories page').'*</td><td>'.form::combo(array('s[rateit_categorypoststpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_categorypoststpl).'</td></tr>'.
			'<tr><td>'.__('Limit to one category').'</td><td>'.form::combo(array('s[rateit_categorylimitposts]'),$combos->categories,$core->blog->settings->rateit_categorylimitposts).'</td></tr>'.
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
		'<div id="post-entries-content">';

		if ($posts->isEmpty())
			echo '<p class="message">'.__('There is no post rating at this time').'</p>';
		else {
			'<p>'.__('This is the list of all entries having rating').'</p>';
			if (!$params['show_filters']) { 
				echo dcPage::jsLoad('js/filter-controls.js').'<p><a id="filter-control" class="form-control" href="#">'.__('Filters').'</a></p>';
			}
			echo 
			'<form action="'.$requests->p_url.'" method="get" id="filters-form">'.
			'<fieldset><legend>'.__('Filters').'</legend>'.
			'<div class="three-cols">'.
			'<div class="col">'.
			'<label>'.__('Category:').form::combo('cat_id',$combos->categories,$requests->cat_id).'</label> '.
			'<label>'.__('Status:').form::combo('status',$combos->status,$requests->status).'</label> '.
			'<label>'.__('Selected:').form::combo('selected',$combos->selected,$requests->selected).'</label> '.
			'</div>'.
			'<div class="col">'.
			'<label>'.__('Order by:').form::combo('sortby',$combos->sortby,$requests->sortby).'</label> '.
			'<label>'.__('Sort:').form::combo('order',$combos->order,$requests->order).'</label>'.
			'</div>'.
			'<div class="col">'.
			'<p><label class="classic">'.form::field('nb',3,3,$requests->nb_per_page).' '.__('Entries per page').'</label> '.
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

			$post_list->display($requests->page,$requests->nb_per_page,
				'<form action="posts_actions.php" method="post" id="form-actions">'.
				'%s'.
				'<div class="two-cols">'.
				'<p class="col checkboxes-helpers"></p>'.
				'<p class="col right">'.__('Selected entries action:').' '.
				form::combo(array('action'),$combos->action).
				'<input type="submit" value="'.__('ok').'" />'.
				form::hidden(array('cat_id'),$requests->cat_id).
				form::hidden(array('status'),$requests->status).
				form::hidden(array('selected'),$requests->selected).
				form::hidden(array('sortby'),$requests->sortby).
				form::hidden(array('order'),$requests->order).
				form::hidden(array('page'),$requests->page).
				form::hidden(array('nb'),$requests->nb_per_page).
				form::hidden(array('redir'),$requests->p_url.'&amp;t=post').
				$core->formNonce().'</p>'.
				'</div>'.
				'</form>'
			);
		}
		echo '</div></div>';
	}

	public static function categoryTab($core,$requests)
	{
		try {
			$rateIt = new rateIt($core);
			$categories = $core->blog->getCategories(array('post_type'=>'post'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}

		if (isset($_POST['action']) && $_POST['action'] == 'rateit_cat_empty' && isset($_POST['entries'])) {
			foreach($_POST['entries'] as $cat_id) {
				$rateIt->del('category',$cat_id);
			}
		}

		if ($core->auth->check('admin',$core->blog->id)
		&& isset($_POST['action']) && $_POST['action'] == 'rateit_cat_active') {
			$core->blog->settings->setNameSpace('rateit');
			$core->blog->settings->put('rateit_category_active',true,'boolean','rateit category addon enabled',true,false);
			$core->blog->triggerBlog();
			http::redirect('plugin.php?p=rateIt&t=category');
		}

		if ($core->auth->check('admin',$core->blog->id)
		&& isset($_POST['action']) && $_POST['action'] == 'rateit_cat_unactive') {
			$core->blog->settings->setNameSpace('rateit');
			$core->blog->settings->put('rateit_category_active',false,'boolean','rateit category addon enabled',true,false);
			$core->blog->triggerBlog();
			http::redirect('plugin.php?p=rateIt&t=category');
		}

		echo '<div class="multi-part" id="category" title="'.__('Categories').'">';

		if ($core->auth->check('admin',$core->blog->id)
		&& !$core->blog->settings->rateit_category_active) {
			echo
			'<form action="plugin.php" method="post" id="form-categories-active"><p>'.
			'<input type="submit" name="save_category" value="'.__('Activate addon category').'" />'.
			form::hidden(array('action'),'rateit_cat_active').
			form::hidden(array('p'),'rateIt').
			form::hidden(array('t'),'category').
			$core->formNonce().
			'</p></form>';
		}
		if ($core->blog->settings->rateit_category_active) {
			if ($core->auth->check('admin',$core->blog->id)) {
				echo
				'<form action="plugin.php" method="post" id="form-categories-unactive"><p>'.
				'<input type="submit" name="save_category" value="'.__('Disactivate addon category').'" />'.
				form::hidden(array('action'),'rateit_cat_unactive').
				form::hidden(array('p'),'rateIt').
				form::hidden(array('t'),'category').
				$core->formNonce().
				'</p></form>';
			}

			$table = '';
			while ($categories->fetch()) {
				$rs = $rateIt->get('category',$categories->cat_id);
				if (!$rs->total) continue;
				$table .= 
				'<tr class="line">'.
				'<td class="nowrap">'.form::checkbox(array('entries[]'),$categories->cat_id,'','','',false).'</td>'.
				'<td class="maximal"><a href="plugin.php?p=rateIt&amp;t=post&amp;cat_id='.$categories->cat_id.'">
					'.html::escapeHTML($categories->cat_title).'</a></td>'.
				'<td class="nowrap">'.$rs->note.'</td>'.
				'<td class="nowrap"><a title="'.__('Show rating details').'" href="plugin.php?p=rateIt&amp;t=details&amp;type=category&amp;id='.$categories->cat_id.'">'.$rs->total.'</a></td>'.
				'<td class="nowrap">'.$rs->max.'</td>'.
				'<td class="nowrap">'.$rs->min.'</td>'.
				'<td class="nowrap">'.$categories->cat_id.'</td>'.
				'<td class="nowrap">'.$categories->level.'</td>'.
				'<td class="nowrap">'.$categories->nb_post.'</td>'.
				'</tr>';
			}

			if ($table=='')
				echo '<p class="message">'.__('There is no category rating at this time').'</p>';
			else {
				echo 
				'<p>'.__('This is a list of all the categories having rating').'</p>'.
				'<form action="plugin.php" method="post" id="form-categories">'.
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

				if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
					echo 
					'<div class="two-cols">'.
					'<p class="col checkboxes-helpers"></p>'.
					'<p class="col right">'.__('Selected categories action:').' '.
					form::combo(array('action'),array(__('delete rating') => 'rateit_cat_empty')).
					'<input type="submit" name="save" value="'.__('ok').'" />'.
					form::hidden(array('p'),'rateIt').
					form::hidden(array('t'),'category').
					$core->formNonce().
					'</p>'.
					'</div>';
				}
				echo '</form>';
			}
		}
		echo '</div>';
	}

	public static function commentTab($core,$requests)
	{
		if ($core->auth->check('admin',$core->blog->id)
		&& !empty($_POST['save']['comment']) && isset($_POST['s'])) {
			try {
				$core->blog->settings->setNamespace('rateit');
				$core->blog->settings->put('rateit_comment_active',$_POST['s']['rateit_comment_active'],'boolean','Enable comments rating',true,false);
				$core->blog->settings->put('rateit_commentstpl',$_POST['s']['rateit_commentstpl'],'boolean','Use comments behavior',true,false);
				$core->blog->triggerBlog();
				http::redirect($requests->p_url.'&t=comment&done=1');
			}
			catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}

		try {
			$rateIt = new rateIt($core);
			$comments = $core->blog->getComments(array('post_type'=>'post'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}

		if (isset($_POST['action']) && $_POST['action'] == 'rateit_comment_empty' && isset($_POST['entries'])) {

			foreach($_POST['entries'] as $comment_id) {
				$rateIt->del('comment',$comment_id);
			}
		}

		echo '<div class="multi-part" id="comment" title="'.__('Comments').'">';

		if ($core->auth->check('admin',$core->blog->id)) {
			echo 
			'<h2 id="comment-options-title">'.__('Settings for comments').'</h2>'.
			'<div id="comment-options-content">'.
			'<form method="post" action="'.$requests->p_url.'">'.
			'<table>'.
			'<tr><td>'.__('Enable comments rating').'</td><td>'.form::combo(array('s[rateit_comment_active]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_comment_active).'</td></tr>'.
			'<tr><td>'.__('Include on comments').'*</td><td>'.form::combo(array('s[rateit_commentstpl]'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_commentstpl).'</td></tr>'.
			'</table>'.
			'<p>'.
			form::hidden(array('p'),'rateIt').
			form::hidden(array('t'),'comment').
			$core->formNonce().
			'<input type="submit" name="save[comment]" value="'.__('Save').'" /></p>'.
			'</form>'.
			'<p class="form-note">* '.__('To use this option you must have behavior "publicCommentAfterContent" in your theme').'</p>'.
			'</div>';
		}

		$table = '';
		while ($comments->fetch()) {
			$rs = $rateIt->get('comment',$comments->comment_id);
			if (!$rs->total) continue;
			$table .= 
			'<tr class="line">'.
			'<td class="nowrap">'.form::checkbox(array('entries[]'),$comments->comment_id,'','','',false).'</td>'.
			'<td class="maximal"><a href="post.php?id='.$comments->post_id.'">
				'.html::escapeHTML($comments->post_title).'</a></td>'.
			'<td class="nowrap">'.$rs->note.'</td>'.
			'<td class="nowrap"><a title="'.__('Show rating details').'" href="plugin.php?p=rateIt&amp;t=details&amp;type=comment&amp;id='.$comments->comment_id.'">'.$rs->total.'</a></td>'.
			'<td class="nowrap">'.$rs->max.'</td>'.
			'<td class="nowrap">'.$rs->min.'</td>'.
			'<td class="nowrap">'.$comments->comment_id.'</td>'.
			'<td class="nowrap">'.$comments->comment_author.'</td>'.
			'<td class="nowrap">'.dt::dt2str(__('%Y-%m-%d %H:%M'),$comments->comment_dt,$core->auth->getInfo('user_tz')).'</td>'.
			'</tr>';
		}

		echo 
		'<h2 id="comment-entries-title">'.__('List of comments').'</h2>'.
		'<div id="comment-entries-content">';

		if ($table=='')
			echo '<p class="message">'.__('There is no comment rating at this time').'</p>';
		else {
			echo 
			'<p>'.__('This is a list of all the comments having rating').'</p>'.
			'<form action="plugin.php" method="post" id="form-comments">'.
			'<table class="clear"><tr>'.
			'<th colspan="2">'.__('Title').'</th>'.
			'<th>'.__('Note').'</th>'.
			'<th>'.__('Votes').'</th>'.
			'<th>'.__('Higher').'</th>'.
			'<th>'.__('Lower').'</th>'.
			'<th>'.__('Id').'</th>'.
			'<th>'.__('Author').'</th>'.
			'<th>'.__('Date').'</th>'.
			'</tr>'.
			$table.
			'</table>';

			if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
				echo 
				'<div class="two-cols">'.
				'<p class="col checkboxes-helpers"></p>'.
				'<p class="col right">'.__('Selected comments action:').' '.
				form::combo(array('action'),array(__('delete rating') => 'rateit_comment_empty')).
				'<input type="submit" name="save[comment]" value="'.__('ok').'" />'.
				form::hidden(array('p'),'rateIt').
				form::hidden(array('t'),'comment').
				$core->formNonce().
				'</p>'.
				'</div>';
			}
			echo '</form>';
		}
		echo '</div></div>';
	}

	public static function tagTab($core,$requests)
	{
		if (!$core->plugins->moduleExists('metadata')) return;

		try {
			$rateIt = new rateIt($core);
			$objMeta = new dcMeta($core);
			$metas = $objMeta->getMeta('tag',null);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}

		if (isset($_POST['action']) && $_POST['action'] == 'rateit_tag_empty' && isset($_POST['entries'])) {

			foreach($_POST['entries'] as $tag_id) {
				$rateIt->del('tag',$tag_id);
			}
		}

		if ($core->auth->check('admin',$core->blog->id)
		&& isset($_POST['action']) && $_POST['action'] == 'rateit_tag_active') {

			$core->blog->settings->setNameSpace('rateit');
			$core->blog->settings->put('rateit_tag_active',true,'boolean','rateit tag addon enabled',true,false);
			$core->blog->triggerBlog();
			http::redirect('plugin.php?p=rateIt&t=tag');
		}

		if ($core->auth->check('admin',$core->blog->id)
		&& isset($_POST['action']) && $_POST['action'] == 'rateit_tag_unactive') {

			$core->blog->settings->setNameSpace('rateit');
			$core->blog->settings->put('rateit_tag_active',false,'boolean','rateit tag addon enabled',true,false);
			$core->blog->triggerBlog();
			http::redirect('plugin.php?p=rateIt&t=tag');
		}

		echo 
		'<div class="multi-part" id="tag" title="'.__('Tags').'">';

		if ($core->auth->check('admin',$core->blog->id)
		&& !$core->blog->settings->rateit_tag_active) {
			echo
			'<form action="plugin.php" method="post" id="form-tags-active"><p>'.
			'<input type="submit" name="save_tag" value="'.__('Activate addon tag').'" />'.
			form::hidden(array('action'),'rateit_tag_active').
			form::hidden(array('p'),'rateIt').
			form::hidden(array('t'),'tag').
			$core->formNonce().
			'</p></form>';
		}
		if ($core->blog->settings->rateit_tag_active) {
			if ($core->auth->check('admin',$core->blog->id)) {
				echo
				'<form action="plugin.php" method="post" id="form-tags-unactive"><p>'.
				'<input type="submit" name="save_tag" value="'.__('Disactivate addon tag').'" />'.
				form::hidden(array('action'),'rateit_tag_unactive').
				form::hidden(array('p'),'rateIt').
				form::hidden(array('t'),'tag').
				$core->formNonce().
				'</p></form>';
			}

			$table = '';
			while ($metas->fetch()) {
				$rs = $rateIt->get('tag',$metas->meta_id);
				if (!$rs->total) continue;
				$table .= 
				'<tr class="line">'.
				'<td class="nowrap">'.form::checkbox(array('entries[]'),$metas->meta_id,'','','',false).'</td>'.
				'<td class="maximal"><a href="plugin.php?p=metadata&amp;m=tag_posts&amp;tag='.$metas->meta_id.'">
					'.html::escapeHTML($metas->meta_id).'</a></td>'.
				'<td class="nowrap"><a title="'.__('Show rating details').'" href="plugin.php?p=rateIt&amp;t=details&amp;type=tag&amp;id='.$metas->meta_id.'">'.$rs->total.'</a></td>'.
				'<td class="nowrap">'.$rs->note.'</td>'.
				'<td class="nowrap">'.$rs->max.'</td>'.
				'<td class="nowrap">'.$rs->min.'</td>'.
				'</tr>';
			}

			echo 
			'<p>'.__('This is a list of all the tags having rating').'</p>'.
			'<form action="plugin.php" method="post" id="form-tags">';

			if ($table=='')
				echo '<p class="message">'.__('There is no tag rating at this time').'</p>';
			else {
				echo 
				'<table class="clear"><tr>'.
				'<th colspan="2">'.__('Title').'</th>'.
				'<th>'.__('Votes').'</th>'.
				'<th>'.__('Note').'</th>'.
				'<th>'.__('Higher').'</th>'.
				'<th>'.__('Lower').'</th>'.
				'</tr>'.
				$table.
				'</table>';
			}

			if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
				echo 
				'<div class="two-cols">'.
				'<p class="col checkboxes-helpers"></p>'.
				'<p class="col right">'.__('Selected tags action:').' '.
				form::combo(array('action'),array(__('delete rating') => 'rateit_tag_empty')).
				'<input type="submit" name="save" value="'.__('ok').'" />'.
				form::hidden(array('p'),'rateIt').
				form::hidden(array('t'),'tag').
				$core->formNonce().
				'</p>'.
				'</div>';
			}
			echo '</form>';
		}
		echo '</div>';
	}

	public static function galleryTab($core,$requests)
	{
		if (!$core->plugins->moduleExists('gallery')) return;

		try {
			$rateIt = new rateIt($core);
			$galObject = new dcGallery($core);
			$galleries = $galObject->getGalleries();
			$galleries_items = $galObject->getGalItems();
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}

		if (isset($_POST['action']) && $_POST['action'] == 'rateit_gal_empty' && isset($_POST['entries'])) {
			foreach($_POST['entries'] as $gal_id) {
				$rateIt->del('gal',$gal_id);
			}
		}

		if (isset($_POST['action']) && $_POST['action'] == 'rateit_galitem_empty' && isset($_POST['entries'])) {
			foreach($_POST['entries'] as $galitem_id) {
				$rateIt->del('galitem',$galitem_id);
			}
		}

		# Save admin options
		if ($core->auth->check('admin',$core->blog->id)
		&& !empty($_POST['save_gal'])) {
			try {
				$core->blog->settings->setNamespace('rateit');
				$core->blog->settings->put('rateit_gal_active',$_POST['rateit_gal_active'],'boolean','rateit addon gallery enabled',true,false);
				$core->blog->settings->put('rateit_galitem_active',$_POST['rateit_galitem_active'],'boolean','rateit addon gallery item enabled',true,false);
				$core->blog->settings->put('rateit_galtpl',$_POST['rateit_galtpl'],'boolean','rateit template galleries page',true,false);
				$core->blog->settings->put('rateit_galitemtpl',$_POST['rateit_galitemtpl'],'boolean','rateit template gallery items page',true,false);
				$core->blog->triggerBlog();
				http::redirect('plugin.php?p=rateIt&t=gal&done=1');
			}
			catch (Exception $e) {
				$core->error->add($e->getMessage());
			}
		}

		echo '<div class="multi-part" id="gal" title="'.__('Galleries').'">';

		if ($core->auth->check('admin',$core->blog->id)) {
			echo
			'<h2 id="gallery-options-title">'.__('Settings for galleries').'</h2>'.
			'<div id="gallery-options-content">'.
			'<form method="post" action="plugin.php">'.
			'<table>'.
			'<tr><td>'.__('Enable addon gallery').'</td><td>'.form::combo(array('rateit_gal_active'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_gal_active).'</td></tr>'.
			'<tr><td>'.__('Enable addon gallery item').'</td><td>'.form::combo(array('rateit_galitem_active'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_galitem_active).'</td></tr>'.
			'<tr><td>'.__('Include on galleries page').'*</td><td>'.form::combo(array('sateit_galtpl'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_galtpl).'</td></tr>'.
			'<tr><td>'.__('Include on gallery items pages').'*</td><td>'.form::combo(array('rateit_galitemtpl'),array(__('no')=>0,__('yes')=>1),$core->blog->settings->rateit_galitemtpl).'</td></tr>'.
			'</table>'.
			'<p>'.
			form::hidden(array('p'),'rateIt').
			form::hidden(array('t'),'gal').
			$core->formNonce().
			'<input type="submit" name="save_gal" value="'.__('Save').'" /></p>'.
			'</form>'.
			'<p class="form-note">* '.__('To use this option you must have behavior "publicEntryAfterContent" in your theme').'</p>'.
			'</div>';
		}

		if ($core->blog->settings->rateit_gal_active) {

			$table = '';
			while ($galleries->fetch()) {
				$rs = $rateIt->get('gal',$galleries->post_id);
				if (!$rs->total) continue;
				$table .= 
				'<tr class="line">'.
				'<td class="nowrap">'.form::checkbox(array('entries[]'),$galleries->post_id,'','','',false).'</td>'.
				'<td class="maximal"><a href="plugin.php?p=gallery&amp;m=gal&amp;id='.$galleries->post_id.'">
					'.html::escapeHTML($galleries->post_title).'</a></td>'.
				'<td class="nowrap"><a title="'.__('Show rating details').'" href="plugin.php?p=rateIt&amp;t=details&amp;type=gal&amp;id='.$galleries->post_id.'">'.$rs->total.'</a></td>'.
				'<td class="nowrap">'.$rs->note.'</td>'.
				'<td class="nowrap">'.$rs->max.'</td>'.
				'<td class="nowrap">'.$rs->min.'</td>'.
				'</tr>';
			}

			echo 
			'<h2 id="gallery-gals-title">'.__('List of galleries').'</h2>'.
			'<div id="gallery-gals-content">'.
			'<p>'.__('This is a list of all the galleries having rating').'</p>'.
			'<form action="plugin.php" method="post" id="form-gal">';

			if ($table=='')
				echo '<p class="message">'.__('There is no gallery rating at this time').'</p>';
			else {
				echo 
				'<table class="clear"><tr>'.
				'<th colspan="2">'.__('Title').'</th>'.
				'<th>'.__('Votes').'</th>'.
				'<th>'.__('Note').'</th>'.
				'<th>'.__('Higher').'</th>'.
				'<th>'.__('Lower').'</th>'.
				'</tr>'.
				$table.
				'</table>';
			}

			if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
				echo 
				'<div class="two-cols">'.
				'<p class="col checkboxes-helpers"></p>'.
				'<p class="col right">'.__('Selected galeries action:').' '.
				form::combo(array('action'),array(__('delete rating') => 'rateit_gal_empty')).
				'<input type="submit" name="save" value="'.__('ok').'" />'.
				form::hidden(array('p'),'rateIt').
				form::hidden(array('t'),'gal').
				$core->formNonce().
				'</p>'.
				'</div>';
			}
			echo '</form></div>';
		}

		if ($core->blog->settings->rateit_galitem_active) {

			$table = '';
			while ($galleries_items->fetch()) {
				$rs = $rateIt->get('galitem',$galleries_items->post_id);
				if (!$rs->total) continue;
				$table .= 
				'<tr class="line">'.
				'<td class="nowrap">'.form::checkbox(array('entries[]'),$galleries_items->post_id,'','','',false).'</td>'.
				'<td class="maximal"><a href="plugin.php?p=gallery&amp;m=item&amp;id='.$galleries_items->post_id.'">
					'.html::escapeHTML($galleries_items->post_title).'</a></td>'.
				'<td class="nowrap"><a title="'.__('Show rating details').'" href="plugin.php?p=rateIt&amp;t=details&amp;type=galitem&amp;id='.$galleries_items->post_id.'">'.$rs->total.'</a></td>'.
				'<td class="nowrap">'.$rs->note.'</td>'.
				'<td class="nowrap">'.$rs->max.'</td>'.
				'<td class="nowrap">'.$rs->min.'</td>'.
				'</tr>';
			}

			echo 
			'<h2 id="gallery-galitems-title">'.__('List of images').'</h2>'.
			'<div id="gallery-galitems-content">'.
			'<p>'.__('This is a list of all the galleries items having rating').'</p>'.
			'<form action="plugin.php" method="post" id="form-galitem">';

			if ($table=='')
				echo '<p class="message">'.__('There is no gallery item rating at this time').'</p>';
			else {
				echo 
				'<table class="clear"><tr>'.
				'<th colspan="2">'.__('Title').'</th>'.
				'<th>'.__('Votes').'</th>'.
				'<th>'.__('Note').'</th>'.
				'<th>'.__('Higher').'</th>'.
				'<th>'.__('Lower').'</th>'.
				'</tr>'.
				$table.
				'</table>';
			}

			if ($core->auth->check('delete,contentadmin',$core->blog->id)) {
				echo 
				'<div class="two-cols">'.
				'<p class="col checkboxes-helpers"></p>'.
				'<p class="col right">'.__('Selected galeries items action:').' '.
				form::combo(array('action'),array(__('delete rating') => 'rateit_galitem_empty')).
				'<input type="submit" name="save" value="'.__('ok').'" />'.
				form::hidden(array('p'),'rateIt').
				form::hidden(array('t'),'gal').
				$core->formNonce().
				'</p>'.
				'</div>';
			}
			echo '</form></div>';
		}
		echo '</div>';
	}
}

class rateItExtList
{
	protected $core;
	protected $rs;
	protected $rs_count;
	protected $base_url;

	public function __construct($core,$rs,$rs_count,$base_url=null)
	{
		$this->core =& $core;
		$this->rs =& $rs;
		$this->rs_count = $rs_count;
		$this->base_url = $base_url;
		$this->html_prev = __('&#171;prev.');
		$this->html_next = __('next&#187;');

		$this->html_none = '<p><strong>'.__('No entry').'</strong></p>';
		$this->html = '%1$s';
		$this->html_pager =  '<p>'.__('Page(s)').' : %1$s</p>';
		$this->html_table = '<table class="clear">%1$s%2$s</table>';
		$this->html_headline = '<tr %2$s>%1$s</tr>';
		$this->html_headcell = '<th %2$s>%1$s</th>';
		$this->html_line = '<tr %2$s>%1$s</tr>';
		$this->html_cell = '<td %2$s>%1$s</td>';
		$this->headlines = '';
		$this->headcells = '';
		$this->lines = '';
		$this->cells = '';

		$this->rateit = new rateIt($core);

		$this->init();
	}

	public function headline($cells,$head='')
	{
		$line = '';
		foreach($cells AS $content => $extra) {
			$line .= sprintf($this->html_headcell,$content,$extra);
		}
		$this->headlines .= sprintf($this->html_headline,$line,$head);
	}

	public function line($cells,$head='')
	{
		$line = '';
		foreach($cells AS $k => $cell) {
			$line .= sprintf($this->html_cell,$cell[0],$cell[1]);
		}
		$this->lines .= sprintf($this->html_line,$line,$head);
	}

	public function display($page,$nb_per_page,$enclose_block='')
	{
		if ($this->rs->isEmpty()) {
			echo $this->html_none;
		} else {
			$pager = new pager($page,$this->rs_count,$nb_per_page,10);
			$pager->base_url = $this->base_url;
			$pager->html_prev = $this->html_prev;
			$pager->html_next = $this->html_next;
			$pager->var_page = 'page';

			while ($this->rs->fetch()) {
				$this->setLine();
			}

			echo
			sprintf($this->html,
				sprintf($enclose_block,
					sprintf($this->html_pager,$pager->getLinks()).
						sprintf($this->html_table,$this->headlines,$this->lines).
					sprintf($this->html_pager,$pager->getLinks())));
		}
	}
}

# Display admin posts list class
class rateItPostsList extends rateItExtList
{
	protected $core;
	protected $rs;
	protected $rs_count;
	protected $base_url;

	public function init()
	{
		self::headline(array(
			__('Title') => 'colspan="2"',
			__('Votes') => '',
			__('Note') => '',
			__('Higher') => '',
			__('Lower') => '',
			__('Published on') => '',
			__('Category') => '',
			__('Author') => '',
			__('Status') => ''));
	}
	
	public function setLine()
	{
		if ($this->rs->cat_title)
			$cat_title = html::escapeHTML($this->rs->cat_title);
		else
			$cat_title = __('None');

		$img = '<img alt="%1$s" title="%1$s" src="images/%2$s" />';
		switch ($this->rs->post_status) {
			case 1:  $img_status = sprintf($img,__('published'),'check-on.png'); break;
			case 0:  $img_status = sprintf($img,__('unpublished'),'check-off.png'); break;
			case -1: $img_status = sprintf($img,__('scheduled'),'scheduled.png'); break;
			case -2: $img_status = sprintf($img,__('pending'),'check-wrn.png'); break;
		}

		$protected = '';
		if ($this->rs->post_password)
			$protected = sprintf($img,__('protected'),'locker.png');

		$selected = '';
		if ($this->rs->post_selected)
			$selected = sprintf($img,__('selected'),'selected.png');

		$attach = '';
		$nb_media = $this->rs->countMedia();
		if ($nb_media > 0) {
			$attach_str = $nb_media == 1 ? __('%d attachment') : __('%d attachments');
			$attach = sprintf($img,sprintf($attach_str,$nb_media),'attach.png');
		}
		
		$q = $this->core->blog->settings->rateit_quotient;
		$d = $this->core->blog->settings->rateit_digit;
		
		$r = $this->rateit->get('post',$this->rs->post_id);

		self::line(
			array(
				# Title
				array(form::checkbox(array('entries[]'),$this->rs->post_id,'','','',!$this->rs->isEditable()),'class="nowrap"'),
				array('<a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'">'.html::escapeHTML($this->rs->post_title).'</a>','class="maximal"'),
				# Votes
				array('<a title="'.__('Show rating details').'" href="plugin.php?p=rateIt&amp;t=details&amp;type=post&amp;id='.$this->rs->post_id.'">'.$r->total.'</a>','class="nowrap"'),
				# Note
				array($r->note,'class="nowrap"'),
				# Higher
				array($r->max,'class="nowrap"'),
				# Lower
				array($r->min,'class="nowrap"'),
				# Post date
				array(dt::dt2str(__('%Y-%m-%d %H:%M'),$this->rs->post_dt,$this->core->auth->getInfo('user_tz')),'class="nowrap"'),
				# Category
				array($cat_title,'class="nowrap"'),
				# Author
				array($this->rs->user_id,'class="nowrap"'),
				# Status
				array($img_status.' '.$selected.' '.$protected.' '.$attach,'class="nowrap status"')
			),
			'class="line'.($this->rs->post_status != 1 ? ' offline' : '').'" '
		);
	}
}
?>