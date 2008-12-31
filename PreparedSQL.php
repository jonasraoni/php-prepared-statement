<?php
/*
 * Prepared Statement: A class to emulate prepared statements syntax where it's not available.
 * Jonas Raoni Soares da Silva <http://raoni.org>
 * https://github.com/jonasraoni/php-prepared-statement
 */
 
class PreparedSQL{
	private $params = array();
	private $named = array();
	private $sql = '';

	const NAMED_SIGN = ':';
	const INDEXED_SIGN = '?';
	const ESCAPE_PREFIX = '\\';

	public function __construct($sql){
		for($this->sql = $sql, $q = null, $i = -1, $l = strlen($sql); ++$i < $l;){
			if(($c = $sql[$i]) == self::ESCAPE_PREFIX && $q && ++$i)
				continue;
			if($c == "'" || $c == '"'){
				$q = $c == $q ? null : ($q ? $q : $c);
				continue;
			}
			if(!$q && ($c == self::NAMED_SIGN || $c == self::INDEXED_SIGN)){
				$start = $i;
				if($n = $c == self::NAMED_SIGN){
					for($s = ''; ++$i < $l && preg_match('/\w/', $sql[$i]); $s .= $sql[$i]);
					if(!strlen($s))
						continue;//throw new Exception('Empty named parameter');
					$this->named[$s][] = count($this->params);
				}
				$this->params[] = array($start, +!$n + $i - $start, null);
			}
		}
		if($q)
			throw new Exception('End quote expected');
	}
	public function getParams(){
		$r = array();
		foreach($this->params as $k=>$v)
			$r[$k] = $v[2];
		return $r;
	}
	public function get($n){
		if(is_int($n) && isset($this->params[$n]))
			return $this->params[$n][2];
		else if($r = isset($this->named[$n])){
			$r = array();
			foreach($this->named[$n] as $i)
				$r[] = $this->get($i);
			return $r;
		}
		else
			return null;
	}
	public function set($n, $value){
		if(is_int($n) && isset($this->params[$n]))
			$this->params[$n][2] = $value;
		else if($r = isset($this->named[$n = strtolower($n)]))
			foreach($this->named[$n] as $i)
				$this->set($i, $value);
	}
	public function replace($replacer){
		for($s = '', $i = -1, $j = 0, $l = count($this->params); ++$i < $l;){
			$s .= substr($this->sql, $j, $this->params[$i][0] - $j) . call_user_func($replacer, $i);
			$j = $this->params[$i][0] + $this->params[$i][1];
		}
		return $s . substr($this->sql, $j);
	}
	private function defaultReplacer($i){
		return $this->get($i);
	}
	public function __toString(){
		return $this->replace(array($this, 'defaultReplacer'));
	}
}