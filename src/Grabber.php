<?php
namespace Wgrabber;

use GuzzleHttp\Client;

class Grabber {
	private $httpclient;

	private $baseurl;
	private $method;
	private $param;

	public function __construct()
	{
		$this->httpclient = new Client();
	}


	public function from($baseurl)
	{
		$this->baseurl = $baseurl;
		return $this;
	}

	public function with($method)
	{
		if (in_array(strtolower($method), ['get', 'post'])) {
			$this->method = strtolower($method);
		} else {
			$this->method = 'get';
		}
		return $this;
	}

	public function cookie()
	{

	}

	public function param($param)
	{
		$this->param = $param;
		return $this;
	}


	protected function resolvedParam()
	{
		$resolved = [];
		$needResolvekey = [];
		$arrayKey = [];
		$normalKey = [];

		foreach ($this->param as $key => $val) {
			if (is_array($val) ){
				$arrayKey[$key] = $val;
			} elseif (preg_match('/[0-9]+:[0-9]+(:[0-9]+)?/', $val)) {
				$needResolvekey[$key] = $val;
			} else {
				$normalKey[$key] = $val;
			}
		}

		foreach ($needResolvekey as $key => $val) {
			$values = explode(':', $val);
			switch (count($values)) {
				case 2:
					$arrayKey[$key] = range((int)$values[0], (int)$values[1]);
					break;
				case 3:
					$arrayKey[$key] = range((int)$values[0], (int)$values[1], (int)$values[2]);
					break;
				default : break;
			}
		}

		foreach ($arrayKey as $key => $values) {
			if (empty($resolved)) {
				foreach ($values as $item) {
					$temp = [$key => $item];
					$resolved[] = $temp;
				}
			} else {
				$resolvedTemp = [];
				foreach ($values as $item) {
					foreach ($resolved as $one) {
						$temp = [$key => $item];
						$temp = array_merge($one, $temp);
						$resolvedTemp[] = $temp;
					}
				}
				$resolved = $resolvedTemp;
			}
		}
		foreach ($resolved as &$p) {
			$p = array_merge($p, $normalKey);
		}

		if (empty($resolved)) {
			return [$this->param];
		} else {
			return $resolved;
		}
	}


	public function dfsGrab($selectors, \Closure $action = null)
	{

	}

	public function wfsGrab($selectors, \Closure $action = null)
	{
		$params = $this->resolvedParam();
		$resultData = [];
		foreach ($params as $param) {
			$result = $this->httpclient->get($this->baseurl, ['query' => $param, 'timeout' => 2]);
			if (false === $result) {
				continue;
			}
// 			$result = iconv('GBK', 'utf8', (string)$result->getBody());
			$result = (string)$result->getBody();
			$dom = \phpQuery::newDocumentHTML($result);
			$temp = [];
			foreach ($selectors as $key => $selector) {
				$data = $dom->find($selector);
				$temp[$key] = $data;
			}
			$resultData[] = $temp;
		}

		if (null === $action) {
			return $resultData;
		} else {
			return $action($resultData);
		}
	}

	public function get($type="json", \Closure $action = null)
	{
		$params = $this->resolvedParam();
		$resultData = [];
		foreach ($params as $param) {
			$result = $this->httpclient->get($this->baseurl, ['query' => $param, 'timeout' => 2]);
			if (false === $result) {
				continue;
			}

			$result = (string)$result->getBody();
			$parseName = 'parse' . ucfirst($type);
			if (method_exists($this, $parseName)) {
				$resultData[] = $this->$parseName($result);
			} else {
				$resultData[] = $result;
			}
		}

		if (null === $action) {
			return $resultData;
		} else {
			return $action($resultData);
		}
	}

	protected function parseJson($data)
	{
		return json_decode($data);
	}

	protected function parsePhp($data)
	{
		return unserialize($data);
	}

	protected function parseJsonp($data)
	{
		$matches = [];
		if (preg_match('/[_a-zA-Z][_a-zA-Z0-9]*?\((.*?)\).*/', $data, $matches)) {
			return json_decode($matches[1]);
		}
		return $data;
	}
}