<?php
# -- BEGIN LICENSE BLOCK ----------------------------------
# This file is part of rateIt, a plugin for Dotclear 2.
#
# Copyright(c) 2014 Nicolas Roudaire <nikrou77@gmail.com> http://www.nikrou.net
#
# Copyright (c) 2009-2010 JC Denis and contributors
# jcdenis@gdwd.com
#
# Licensed under the GPL version 2.0 license.
# A copy of this license is available in LICENSE file or at
# http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
# -- END LICENSE BLOCK ------------------------------------

# rateIt admin behaviors
class postRateItModuleAdmin
{
	public static function adminRateItModuleUpdate($core,$type,$action,$page_url,$hidden_fields) {
		if ($type != 'post' || $action != 'save_module_post') return;

		$core->blog->settings->rateit->put('rateit_post_active',!empty($_POST['rateit_post_active']),'boolean','Enabled post rating',true,false);
		$core->blog->settings->rateit->put('rateit_poststpl',!empty($_POST['rateit_poststpl']),'boolean','rateit template on post on post page',true,false);
		$core->blog->settings->rateit->put('rateit_homepoststpl',!empty($_POST['rateit_homepoststpl']),'boolean','rateit template on post on home page',true,false);
		$core->blog->settings->rateit->put('rateit_tagpoststpl',!empty($_POST['rateit_tagpoststpl']),'boolean','rateit template on post on tag page',true,false);
		$core->blog->settings->rateit->put('rateit_categorypoststpl',!empty($_POST['rateit_categorypoststpl']),'boolean','rateit template on post on category page',true,false);
		$core->blog->settings->rateit->put('rateit_categorylimitposts',$_POST['rateit_categorylimitposts'],'integer','rateit limit post vote only to one category',true,false);
		$core->blog->settings->rateit->put('rateit_categorylimitinvert',!empty($_POST['rateit_categorylimitinvert']),'boolean','rateit limit post vote only to other categories',true,false);

		$core->blog->triggerBlog();
		return 'save_setting';
	}

	public static function adminRateItModuleSettingsTab($core,$type,$page_url,$hidden_fields) {
		if ($type != 'post') return;

		$combo_categories = array('-'=>'');
		try {
			$categories = $core->blog->getCategories(array('post_type'=>'post'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
		while ($categories->fetch()) {
			$combo_categories[str_repeat('&nbsp;&nbsp;',$categories->level-1).'&bull; '.
                              html::escapeHTML($categories->cat_title)] = $categories->cat_id;
		}

		echo
            '<form method="post" action="'.$page_url.'">'.
            '<p><label class="classic">'.
            form::checkbox(array('rateit_post_active'),1,$core->blog->settings->rateit->rateit_post_active).
            __('Enable posts rating').'</label></p>'.
            '<p><label class="classic">'.
            form::checkbox(array('rateit_poststpl'),1,$core->blog->settings->rateit->rateit_poststpl).
            __('Include on entries pages').' *</label></p>'.
            '<p><label class="classic">'.
            form::checkbox(array('rateit_homepoststpl'),1,$core->blog->settings->rateit->rateit_homepoststpl).
            __('Include on home page').' *</label></p>'.
            '<p><label class="classic">'.
            form::checkbox(array('rateit_tagpoststpl'),1,$core->blog->settings->rateit->rateit_tagpoststpl).
            __('Include on tag page').' *</label></p>'.
            '<p><label class="classic">'.
            form::checkbox(array('rateit_categorypoststpl'),1,$core->blog->settings->rateit->rateit_categorypoststpl).
            __('Include on categories page').' *</label></p>'.
            '<p><label>'.__('Limit to one category:').' '.
            form::combo(array('rateit_categorylimitposts'),$combo_categories,$core->blog->settings->rateit->rateit_categorylimitposts).'</label></p>'.
            '<p><label class="classic">'.
            form::checkbox(array('rateit_categorylimitinvert'),1,$core->blog->settings->rateit->rateit_categorylimitinvert).
            __('Invert and exclude this category').'</label></p>'.

            '<p><input type="submit" name="save" value="'.__('save').'" />'.
            $hidden_fields.
            form::hidden(array('action'),'save_module_post').
            '</p>'.
            '</form>'.
            '<p class="form-note">* '.__('To use this option you must have behavior "publicEntryAfterContent" in your theme').'</p>';

		return 1;
	}

	public static function adminRateItModuleRecordsTab($core,$type,$page_url,$hidden_fields) {
		if ($type != 'post') return;

        $form_filter_title = __('Show filters and display options');

		# Combos
		$combo_action = array();
		$combo_action[__('Reviews')][__('Delete')] = 'rateit_empty';
		$combo_action[__('Status')][__('Publish')] = 'publish';
		$combo_action[__('Status')][__('Unpublish')] = 'unpublish';
		$combo_action[__('Status')][__('Schedule')] = 'schedule';
		$combo_action[__('Status')][__('Mark as pending')] = 'pending';
		$combo_action[__('Mark')][__('Mark as selected')] = 'selected';
		$combo_action[__('Mark')][__('Mark as unselected')] = 'unselected';
		$combo_action[__('Change')][__('Change category')] = 'category';
		$combo_action[__('Change')][__('Change author')] = 'author';
		$combo_action[__('Delete')][__('Delete')] = 'delete';

		$combo_categories = array('-'=>'');
		try {
			$categories = $core->blog->getCategories(array('post_type'=>'post'));
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}
		while ($categories->fetch()) {
			$combo_categories[str_repeat('&nbsp;&nbsp;',$categories->level-1).'&bull; '.
                              html::escapeHTML($categories->cat_title)] = $categories->cat_id;
		}

		$combo_status = array('-' => '');
		foreach ($core->blog->getAllPostStatus() as $k => $v) {
			$status_combo[$v] = (string) $k;
		}

		$combo_selected = array(
			'-' => '',
			__('selected') => '1',
			__('not selected') => '0'
		);

		$combo_sortby = array(
			__('Date') => 'post_dt',
			__('Votes') => 'rateit_count',
			__('Title') => 'post_title',
			__('Category') => 'cat_title',
			__('Author') => 'user_id',
			__('Status') => 'post_status',
			__('Selected') => 'post_selected'
		);

		$combo_order = array(
			__('Descending') => 'desc',
			__('Ascending') => 'asc'
		);

		# Filters
		$cat_id = !empty($_GET['cat_id']) ? $_GET['cat_id'] : '';
		$status = isset($_GET['status']) ? $_GET['status'] : '';
		$selected = isset($_GET['selected']) ? $_GET['selected'] : '';
		$sortby = !empty($_GET['sortby']) ? $_GET['sortby'] : 'post_dt';
		$order = !empty($_GET['order']) ? $_GET['order'] : 'desc';
		$page = !empty($_GET['page']) ? (integer) $_GET['page'] : 1;
		$nb_per_page =  30;
		if (!empty($_GET['nb']) && (integer) $_GET['nb'] > 0) {
			$nb_per_page = (integer) $_GET['nb'];
		}

		$pager_base_url = $page_url.
            '&amp;cat_id='.$cat_id.
            '&amp;status='.$status.
            '&amp;selected='.$selected.
            '&amp;sortby='.$sortby.
            '&amp;order='.$order.
            '&amp;nb='.$nb_per_page.
            '&amp;page=%s';


		# Params
		$params = array();
		$params['show_filters'] = false;
		$params['limit'] = array((($page-1)*$nb_per_page),$nb_per_page);
		$params['no_content'] = true;
		$params['rateit_type'] = 'post';
		$params['post_type'] = 'post';

		if ($cat_id !== '' && in_array($cat_id,$combo_categories)) {
			$params['cat_id'] = $cat_id;
			$params['show_filters'] = true;
		}
		if ($status !== '' && in_array($status,$combo_status)) {
			$params['post_status'] = $status;
			$params['show_filters'] = true;
		}
		if ($selected !== '' && in_array($selected,$combo_selected)) {
			$params['post_selected'] = $selected;
			$params['show_filters'] = true;
		}
		if ($sortby !== '' && in_array($sortby,$combo_sortby)) {
			if ($order !== '' && in_array($order,$combo_order)) {
				$params['order'] = $sortby.' '.$order;
			}
			if ($sortby != 'post_dt' || $order != 'desc') {
				$params['show_filters'] = true;
			}
		}

		# Get records
		try {
			$posts = $core->rateIt->getPostsByRate($params);
			$counter = $core->rateIt->getPostsByRate($params,true);
			$post_list = new rateItPostsList($core,$posts,$counter->f(0),$pager_base_url);
		} catch (Exception $e) {
			$core->error->add($e->getMessage());
		}

		echo '<p>'.__('This is the list of all entries having rating').'</p>';
		if (!$params['show_filters']) {
			echo
                dcPage::jsLoad('js/filter-controls.js').
                '<script type="text/javascript">'.
                '//<![CDATA['."\n".
                dcPage::jsVar('dotclear.msg.show_filters', $params['show_filters'] ? 'true':'false').
                dcPage::jsVar('dotclear.msg.filter_posts_list',$form_filter_title).
                dcPage::jsVar('dotclear.msg.cancel_the_filter',__('Cancel filters and display options'))."\n".
                '//]]>'.
                '</script>';
		}
		echo
            '<form action="'.$page_url.'" method="get" id="filters-form">'.
            '<h3 class="out-of-screen-if-js">'.$form_filter_title.'</h3>'.
            '<div class="table">'.
            '<div class="cell">'.
            '<h4>'.__('Filters').'</h4>'.
            '<p><label for="cat_id" class="ib">'.__('Category:').'</label>'.
            form::combo('cat_id',$combo_categories,$cat_id).'</p>'.
            '<p><label for="status" class="ib">'.__('Status:').'</label>'.
            form::combo('status',$combo_status,$status).'</label></p>'.
            '<p><label for="selected" class="ib">'.__('Selected:').'</label>'.
            form::combo('selected',$combo_selected,$selected).'</p>'.
            '</div>'.
            '<div class="cell filters-sibling-cell">'.
            '<p><label for="sortby" class="ib">'.__('Order by:').'</label>'.
            form::combo('sortby',$combo_sortby,$sortby).'</p>'.
            '<p><label for="order" class="ib">'.__('Sort:').'</label>'.
            form::combo('order',$combo_order,$order).'</p>'.
            '</div>'.
            '<div class="cell filters-options">'.
            '<h4>'.__('Display options').'</h4>'.
            '<p><span class="label ib">'.__('Show').'&nbsp;</span>'.
            '<label for="nb" class="classic">'.
            form::field('nb',3,3,$nb_per_page).' '.__('Events per page').'</label>'.
            '</p>'.
            '</div>'.
            '</div>'.
            '<p><input type="submit" value="'.__('Apply filters and display options').'" />'.
            '<br class="clear" />'.
            $hidden_fields.
            '</p>'.
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
                            form::hidden(array('redir'),$page_url).
                            $core->formNonce().'</p>'.
                            '</div>'.
                            '</form>'
		);

		return 1;
	}

	public static function adminRateItWidgetVote($w)
	{
		$w->rateit->setting('enable_post',__('Enable vote for entries'),
                            1,'check');
		$w->rateit->setting('title_post',__('Title for entries:'),
                            __('Rate this entry'),'text');
	}

	public static function adminRateItWidgetRank($types)
	{
		$types[] = array(__('entries') => 'post');
	}
}

# DC admin behaviors
class postRateItAdmin
{
	public static function adminBeforePostDelete($post_id)
	{
		$post_id = (integer) $post_id;
		if (!$post_id) return;

		global $core;

		$core->rateIt->del('post',$post_id);
	}

	public static function adminPostsActionsCombo($args)
	{
		global $core;
		if ($core->blog->settings->rateit->rateit_active
            && $core->auth->check('admin',$core->blog->id))
		{
			$args[0][__('Reviews')][__('Remove ratings')] = 'rateit_empty';
		}
	}

	public static function adminPostsActions($core,$posts,$action,$redir)
	{
		if ($action != 'rateit_do_empty') return;

		try
		{
			while ($posts->fetch())
			{
				$core->rateIt->del('post',$posts->post_id);
			}
			$core->blog->triggerBlog();
			http::redirect($redir);
		}
		catch (Exception $e)
		{
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
		foreach($_POST['entries'] as $post)
		{
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

# Admin post records list
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

		$q = $this->core->blog->settings->rateit->rateit_quotient;
		$d = $this->core->blog->settings->rateit->rateit_digit;

		$r = $this->core->rateIt->get('post',$this->rs->post_id);

		self::line(
			array(
				# Title
				array(form::checkbox(array('entries[]'),$this->rs->post_id,'','','',!$this->rs->isEditable()),'class="nowrap"'),
				array('<a href="'.$this->core->getPostAdminURL($this->rs->post_type,$this->rs->post_id).'">'.html::escapeHTML($this->rs->post_title).'</a>','class="maximal"'),
				# Votes
				array('<a title="'.__('Show rating details').'" href="plugin.php?p=rateIt&amp;part=detail&amp;type=post&amp;id='.$this->rs->post_id.'">'.$r->total.'</a>','class="nowrap"'),
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
