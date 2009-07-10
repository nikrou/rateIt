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

class rateIt
{
	public $core;
	private $table;
	private $quotient;
	private $digit;
	private $types;
	private $ident;
	public $ip;

	public function __construct(&$core)
	{
		$this->core =& $core;
		$this->table = $core->prefix.'rateit';
		$this->quotient = $core->blog->settings->rateit_quotient;
		$this->digit = $core->blog->settings->rateit_digit;

		$types = new ArrayObject();
		$types[] = 'post';

		# --BEHAVIOR-- addRateItType
		$core->callBehavior('addRateItType',$types);

		$this->types = (array) $types;
		$this->ident = (integer) $core->blog->settings->rateit_userident;

		if ($this->ident == 2)
			$this->ip = $core->getNonce();
		else
			$this->ip = $_SERVER['REMOTE_ADDR'];
	}

	public function set($type,$id,$note)
	{
		if (!in_array($type,$this->types))
			return false;

		$cur = $this->core->con->openCursor($this->table);
		$this->core->con->writeLock($this->table);

		$cur->blog_id = $this->core->blog->id;
		$cur->rateit_type = (string) $type;
		$cur->rateit_id = (string) $id;
		$cur->rateit_ip = (string) $this->ip;
		$cur->rateit_note = $note;
		$cur->rateit_quotient = $this->quotient;
		$cur->rateit_time = date('Y-m-d H:i:00');

		$cur->insert();
		$this->core->con->unlock();
		$this->core->blog->triggerBlog();

		if ($this->ident > 0)
			setcookie('rateit-'.$type.'-'.$id,1,(time()+3600*365));
		
		return true;
	}
	
	public function get($type=null,$id=null,$ip=null)
	{
		$req=
			'SELECT rateit_note, rateit_quotient '.
			'FROM '.$this->table.' WHERE blog_id=\''.$this->core->con->escape($this->core->blog->id).'\' ';
		if ($type!=null)
			$req .= 'AND rateit_type=\''.$this->core->con->escape($type).'\' ';
		if ($id!=null)
			$req .= 'AND rateit_id=\''.$this->core->con->escape($id).'\' ';
		if ($ip!=null)
			$req .= 'AND rateit_ip=\''.$this->core->con->escape($ip).'\' ';

		$rs = $this->core->con->select($req);
		$rs->toStatic();

		$note = $sum = $max = $total = 0;
		$min = 10000;
		while($rs->fetch()){
			$note = $rs->rateit_note / $rs->rateit_quotient;
			$sum += $note;
			$max = $max < $note ? $note : $max;
			$min = $min > $note ? $note : $min;
			$total += 1;
		}
		if ($rs->count())
			$note = $sum / $total;
		else
			$min = 0;

		$res = new ArrayObject();
		$res->max = self::trans($max);
		$res->min = self::trans($min);
		$res->note = self::trans($note);
		$res->total = $total;
		$res->sum = $sum;
		$res->quotient = $this->quotient;
		$res->digit = $this->digit;

		return $res;
	}

	public function voted($type=null,$id=null)
	{
		$rs = $this->core->con->select(
			'SELECT COUNT(*) '.
			'FROM '.$this->table.' '.
			'WHERE blog_id=\''.$this->core->con->escape($this->core->blog->id).'\' '.
			'AND rateit_ip=\''.$this->core->con->escape($this->ip).'\' '.
			($type!=null ? 
			'AND rateit_type=\''.$this->core->con->escape($type).'\' ' : '').
			($id!=null ? 
			'AND rateit_id=\''.$this->core->con->escape($id).'\' ' : '')
		);
		$sql = (boolean) $rs->f(0);
		$cookie = false;
		if ($this->ident > 0 && $id !== null && $type !== null)
			$cookie = isset($_COOKIE['rateit-'.$type.'-'.$id]);

		return $sql || $cookie;
	}

	public function del($type=null,$id=null,$ip=null)
	{
		$req = 
			'DELETE FROM '.$this->table.' '.
			'WHERE blog_id=\''.$this->core->con->escape($this->core->blog->id).'\' ';
		if ($type!=null)
			$req .= 'AND rateit_type=\''.$this->core->con->escape($type).'\' ';
		if ($id!=null)
			$req .= 'AND rateit_id=\''.$this->core->con->escape($id).'\' ';
		if ($ip!=null)
			$req .= 'AND rateit_ip=\''.$this->core->con->escape($ip).'\' ';

		$rs = $this->core->con->select($req);
		$this->core->blog->triggerBlog();
	}

	public function trans($note)
	{
		return round($note * $this->quotient,$this->digit);
	}

	public function getPostsByRate($params=array(),$count_only=false)
	{
		$params['columns'][] = 'COUNT(rateit_id) as rateit_count';

		$params['from'] = 'INNER JOIN '.$this->table.' ON CAST(P.post_id as char)=rateit_id ';

		if (!isset($params['sql'])) $params['sql'] = '';

		if (!empty($params['rateit_type'])) {
			$params['sql'] .= "AND rateit_type = '".$this->core->con->escape($params['rateit_type'])."' ";
			unset($params['rateit_type']);
		}

		if (!empty($params['post_type'])) {
			$params['sql'] .= "AND post_type = '".$this->core->con->escape($params['post_type'])."' ";
			unset($params['post_type']);
		}

		$params['sql'] .= 'GROUP BY rateit_id, rateit_type ';
		if (!$count_only) {

			if (!empty($params['no_content'])) {
				$c_req = '';
			} else {
				$c_req =
				'post_excerpt, post_excerpt_xhtml, '.
				'post_content, post_content_xhtml, post_notes, ';
			}

			if (!empty($params['columns']) && is_array($params['columns'])) {
				$c = $params['columns'];
				$cols = array();
				foreach($c AS $k => $v) {
					if (!preg_match('/(\sas\s)/',$v)) $cols[] = $v;
				}
				if (!empty($cols)) 
					$c_req .= implode(', ',$cols).', ';
			}

			$params['sql'] .= ', '.
			'P.post_id, P.blog_id, P.user_id, P.cat_id, post_dt, '.
			'post_tz, post_creadt, post_upddt, post_format, post_password, '.
			'post_url, post_lang, post_title, '.$c_req.
			'post_type, post_meta, post_status, post_selected, post_position, '.
			'post_open_comment, post_open_tb, nb_comment, nb_trackback, '.
			'U.user_name, U.user_firstname, U.user_displayname, U.user_email, '.
			'U.user_url, '.
			'C.cat_title, C.cat_url, C.cat_desc ';
		}

		return $this->core->blog->getPosts($params,$count_only);
	}

	public function getRates($params,$count_only=false)
	{
		if ($count_only)
			$strReq = 'SELECT count(RI.rateit_id) ';
		else {
			$strReq =
			'SELECT DISTINCT '.
			'SUM(RI.rateit_note / RI.rateit_quotient) as rateit_sum, '.
			'MAX(RI.rateit_note / RI.rateit_quotient) as rateit_max, '.
			'MIN(RI.rateit_note / RI.rateit_quotient) as rateit_min, '.
			'(SUM(RI.rateit_note / RI.rateit_quotient) / COUNT(RI.rateit_note)) as rateit_avg, ';

			if (!empty($params['columns']) && is_array($params['columns'])) 
				$strReq .= implode(', ',$params['columns']).', ';

			$strReq .= 
			'COUNT(RI.rateit_id) as rateit_total ';
		}

		$strReq .=
		'FROM '.$this->table.' RI ';
		
		if (!empty($params['from']))
			$strReq .= $params['from'].' ';

		$strReq .=
		" WHERE RI.blog_id = '".$this->core->con->escape($this->core->blog->id)."' ";

		# rate type
		if (isset($params['rateit_type'])) {

			if (is_array($params['rateit_type']) && !empty($params['rateit_type']))
				$strReq .= 'AND RI.rateit_type '.$this->core->con->in($params['rateit_type']);
			elseif ($params['rateit_type'] != '')
				$strReq .= "AND RI.rateit_type = '".$this->core->con->escape($params['rateit_type'])."' ";
		} else
			$strReq .= "AND RI.rateit_type = 'post' ";

		# rate id
		if (!empty($params['rateit_id'])) {

			if (is_array($params['rateit_id']))
				array_walk($params['rateit_id'],create_function('&$v,$k','if($v!==null){$v=(integer)$v;}'));
			else
				$params['rateit_id'] = array((integer) $params['rateit_id']);

			$strReq .= 'AND RI.rateit_id '.$this->core->con->in($params['rateit_id']);
		}

		# rate ip
		if (!empty($params['rateit_ip'])) {

			if (is_array($params['rateit_ip']))
				array_walk($params['rateit_ip'],create_function('&$v,$k','if($v!==null){$v=(integer)$v;}'));
			else
				$params['rateit_ip'] = array((integer) $params['rateit_ip']);

			$strReq .= 'AND RI.rateit_ip '.$this->core->con->in($params['rateit_ip']);
		}

		if (!empty($params['sql']))
			$strReq .= $params['sql'].' ';

		if (!$count_only) {
			$strReq .= 'GROUP BY RI.rateit_id ';
			if (!empty($params['groups']) && is_array($params['groups'])) 
				$strReq .= ', '.implode(', ',$params['groups']).' ';

			if (!empty($params['order']))
				$strReq .= 'ORDER BY '.$this->core->con->escape($params['order']).' ';
			else
				$strReq .= 'ORDER BY rateit_time DESC ';
		}

		if (!$count_only && !empty($params['limit']))
			$strReq .= $this->core->con->limit($params['limit']);

		$rs = $this->core->con->select($strReq);

		# --BEHAVIOR-- rateitGetRates
		$this->core->callBehavior('rateitGetRates',$rs);

		return $rs;
	}

	public function getDetails($type=null,$id=null,$ip=null)
	{
		$req=
			'SELECT rateit_id,rateit_type,rateit_note,rateit_quotient,rateit_ip,rateit_time '.
			'FROM '.$this->table.' WHERE blog_id=\''.$this->core->blog->id.'\' ';
		if ($type!=null)
			$req .= 'AND rateit_type=\''.$this->core->con->escape($type).'\' ';
		if ($id!=null)
			$req .= 'AND rateit_id=\''.$this->core->con->escape($id).'\' ';
		if ($ip!=null)
			$req .= 'AND rateit_ip=\''.$this->core->con->escape($ip).'\' ';

		$rs = $this->core->con->select($req);
		$rs->toStatic();

		return $rs;
	}
}

?>