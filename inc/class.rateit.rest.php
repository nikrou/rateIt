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

if (!defined('DC_RC_PATH')){return;}

class rateItRest
{
	public static function vote($core,$get,$post)
	{
		$type = isset($post['voteType']) ? $post['voteType'] : null;
		$id = isset($post['voteId']) ? $post['voteId'] : null;
		$note = isset($post['voteNote']) ? $post['voteNote'] : null;

		$rsp = new xmlTag();

		if (!$core->blog->settings->rateit_active)
			throw new Exception(__('Rating is disabled on this blog'));

		if ($type === null || $id === null || $note === null)
			throw new Exception(__('Rating failed because of missing informations'));

		$types = new ArrayObject();
		$types[] = 'post';
		$types[] = 'comment';
		$types[] = 'category';
		$types[] = 'tag';
		$types[] = 'gal';
		$types[] = 'galitem';


		# --BEHAVIOR-- addRateItType
		$core->callBehavior('addRateItType',$types);


		$types = (array) $types;

		if (!in_array($type,$types))
			throw new Exception(__('Rating failed because of a wrong type of entry'));

		$rateIt = new rateIt($core);
		$voted = $rateIt->voted($type,$id);
		if ($voted)
			throw new Exception(__('You have already voted'));
		else
			$rateIt->set($type,$id,$note);

		$rs = $rateIt->get($type,$id);
		$xv = new xmlTag('item');
		$xv->type = $type;
		$xv->id = $id;
		$xv->ip = $rateIt->ip;
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