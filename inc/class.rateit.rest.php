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

class rateItRest
{
	public static function service()
	{
		$core =& $GLOBALS['core'];
		$core->rest->addFunction('rateItVote',array('rateItRest','vote'));
		$core->rest->serve();
	}

	public static function vote(&$core,$get,$post)
	{
		$type = isset($post['voteType']) ? $post['voteType'] : null;
		$id = isset($post['voteId']) ? $post['voteId'] : null;
		$note = isset($post['voteNote']) ? $post['voteNote'] : null;
		$ip = $_SERVER['REMOTE_ADDR'];

		$rsp = new xmlTag();

		if (!$core->blog->settings->rateit_active)
			throw new Exception(__('Rating is disabled on this blog'));

		if ($type === null || $id === null || $note === null)
			throw new Exception(__('Rating failed because of missing informations'));

		$types = explode(',',$core->blog->settings->rateit_types);
		if (!in_array($type,$types))
			throw new Exception(__('Rating failed because of a wrong type of entry'));

		$ss = new rateIt($core);
		$rs = $ss->get($type,$id,$ip);
		if ($rs->total == 0)
			$ss->set($type,$id,$ip,$note);
		else
			throw new Exception(__('You have already voted'));

		$rs = $ss->get($type,$id);
		$xv = new xmlTag('item');
		$xv->type = $type;
		$xv->id = $id;
		$xv->ip = $ip;
		$xv->sum = $rs->sum;
		$xv->max = $rs->max;
		$xv->min = $rs->min;
		$xv->total = $rs->total;
		$xv->note = $rs->note;
		$xv->quotient = $rs->quotient;
		$rsp->insertNode($xv);

		return $rsp;
	}

}

?>