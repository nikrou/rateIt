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

	public static function uninstallTab($core)
	{
		if (!$core->auth->isSuperAdmin()) return;

		$understand = isset($_POST['s']['understand']) ? $_POST['s']['understand'] : 0;
		$delete_table = isset($_POST['s']['delete_table']) ? $_POST['s']['delete_table'] : 0;
		$delete_settings = isset($_POST['s']['delete_settings']) ? $_POST['s']['delete_settings'] : 0;

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
				$core->blog->settings->put('rateit_module_prefix',$_POST['s']['rateit_module_prefix'],'string','rateit prefix url for files',true,false);
				$core->blog->settings->put('rateit_post_prefix',$_POST['s']['rateit_post_prefix'],'string','rateit prefix url for post form',true,false);
				$core->blog->settings->put('rateit_rest_prefix',$_POST['s']['rateit_rest_prefix'],'string','rateit prefix url for rest service',true,false);

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
			'<input type="submit" name="save[details]" value="'.__('ok').'" />'.
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
			'<input type="submit" name="save[category]" value="'.__('Activate addon category').'" />'.
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
				'<input type="submit" name="save[category]" value="'.__('Disactivate addon category').'" />'.
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
					'<input type="submit" name="save[category]" value="'.__('ok').'" />'.
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
}
?>