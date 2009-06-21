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

	public function __construct(&$core)
	{
		$this->core =& $core;
		$this->table = $core->prefix.'rateit';
		$this->quotient = $core->blog->settings->rateit_quotient;
		$this->digit = $core->blog->settings->rateit_digit;
		$this->types = explode(',',$core->blog->settings->rateit_types);
	}

	public function set($type,$id,$ip,$note)
	{
		if (!in_array($type,$this->types))
			return false;

		$cur = $this->core->con->openCursor($this->table);
		$this->core->con->writeLock($this->table);

		$cur->blog_id = $this->core->blog->id;
		$cur->rateit_type = (string) $type;
		$cur->rateit_id = (integer) $id;
		$cur->rateit_ip = (string) $ip;
		$cur->rateit_note = $note;
		$cur->rateit_quotient = $this->quotient;
		$cur->rateit_time = date('Y-m-d H:i:00');

		$cur->insert();
		$this->core->con->unlock();
		return true;
	}
	
	public function get($type=null,$id=null,$ip=null)
	{
		$req=
			'SELECT '.
			'SUM(rateit_note / rateit_quotient) as rateit_sum, '.
			'MAX(rateit_note / rateit_quotient) as rateit_max, '.
			'MIN(rateit_note / rateit_quotient) as rateit_min, '.
			'(SUM(rateit_note / rateit_quotient) / COUNT(rateit_note)) as rateit_note, '.
			'COUNT(rateit_note) as rateit_total, '.
			'rateit_quotient '.
			'FROM '.$this->table.' WHERE blog_id=\''.$this->core->blog->id.'\' ';
		if ($type!=null)
			$req .= 'AND rateit_type=\''.$this->core->con->escape($type).'\' ';
		if ($id!=null)
			$req .= 'AND rateit_id=\''.$this->core->con->escape($id).'\' ';
		if ($ip!=null)
			$req .= 'AND rateit_ip=\''.$this->core->con->escape($ip).'\' ';

		$req .= 'GROUP BY rateit_id';

		$rs = $this->core->con->select($req);
		$rs->toStatic();

		$res = new ArrayObject();
		$res->max = self::trans($rs->rateit_max);
		$res->min = self::trans($rs->rateit_min);
		$res->note = self::trans($rs->rateit_note);
		$res->total = $rs->rateit_total;
		$res->sum = $rs->rateit_sum;
		$res->quotient = $this->quotient;
		$res->digit = $this->digit;

		return $res;
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
	}

	public function trans($note)
	{
		return round($note * $this->quotient,$this->digit);
	}

	public function getPostsByRate($p=array(),$count_only=false)
	{
		if (!isset($p['columns'])) $p['columns'] = array();
		$p['columns'][] = 'SUM(RI.rateit_note / RI.rateit_quotient) as rateit_sum';
		$p['columns'][] = 'MAX(RI.rateit_note / RI.rateit_quotient) as rateit_max';
		$p['columns'][] = 'MIN(RI.rateit_note / RI.rateit_quotient) as rateit_min';
		$p['columns'][] = '(SUM(RI.rateit_note / RI.rateit_quotient) / COUNT(RI.rateit_note)) as rateit_note';
		$p['columns'][] = 'COUNT(RI.rateit_note) as rateit_total';

		if (!isset($p['from'])) $p['from'] = '';
			$p['from'] .= ' LEFT OUTER JOIN '.$this->table.' RI ON P.post_id = RI.rateit_id ';

		if (!isset($p['sql'])) $p['sql'] = '';

		if (!empty($p['rateit_type'])) {
			$p['sql'] .= "AND RI.rateit_type = '".$this->core->con->escape($p['rateit_type'])."' ";
			unset($p['rateit_type']);
		}

		if (isset($p['meta_id'])) {
			$p['from'] .= ', '.$this->core->prefix.'meta META ';
			$p['sql'] .= 'AND META.post_id = P.post_id ';
			$p['sql'] .= "AND META.meta_id = '".$this->core->con->escape($p['meta_id'])."' ";
			
			if (!empty($p['meta_type'])) {
				$params['sql'] .= "AND META.meta_type = '".$this->core->con->escape($p['meta_type'])."' ";
				unset($p['meta_type']);
			}
			unset($p['meta_id']);		
		}
		if (!$count_only)
			$p['sql'] .= 'GROUP BY RI.rateit_id ';

		return $this->core->blog->getPosts($p,$count_only);
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