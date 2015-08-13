<?php
/**
 * grabber
 * @package hduwzy/grabber
 * @author hduwzy
 */
namespace Wgrabber;

use GuzzleHttp\Client;

class Grabber {
	// GuzzleHttpClient instance
	private $httpclient;

	// base uri
	private $baseurl;

	// request method
	private $method;

	// request params
	private $param;

	// 参数key
	private $paramKey;

	// 请求超时时间
	private $timeout;

	/**
	 * constructor
	 */
	public function __construct()
	{
		$this->httpclient = new Client();
		$this->timeout = 3;
	}


	/**
	 * 设置请求链接
	 * @param $baseurl string
	 * @return $this
	 */
	public function from($baseurl)
	{
		$this->baseurl = $baseurl;
		return $this;
	}

	/**
	 * 设置请求
	 * @param $method
	 * @return $this
	 */
	public function with($method)
	{
		if (in_array(strtolower($method), ['get', 'post'])) {
			$this->method = strtolower($method);
		} else {
			$this->method = 'get';
		}
		switch ($this->method) {
			case 'get' :
				$this->paramKey = 'query';
				break;
			case 'post':
				$this->paramKey = 'form_params';
				break;
			default:
				$this->paramKey = 'query';
				break;
		}
		return $this;
	}

	/**
	 * 设置超时时间
	 * @param int $time seconds
	 * @return \Wgrabber\Grabber
	 */
	public function timeout($time)
	{
		$this->timeout = $time;
		return $this;
	}

	public function cookie()
	{

	}

	/**
	 * 设置请求参数
	 * @param $param 参数
	 * @return $this
	 */
	public function param($param)
	{
		$this->param = $param;
		return $this;
	}


	/**
	 * 解析参数，形成请求参数数组
	 * @return array
	 */
	protected function resolvedParam()
	{
		$resolved = [];
		$needResolvekey = [];
		$arrayKey = [];
		$normalKey = [];

		// 循环给出的参数，判断需要被枚举的参数
		foreach ($this->param as $key => $val) {
			if (is_array($val) ){
				// 用数组的形式给出多个参数
				$arrayKey[$key] = $val;
			} elseif (preg_match('/[0-9]+:[0-9]+(:[0-9]+)?/', $val)) {
				//用范围的形式给出多个参数
				$needResolvekey[$key] = $val;
			} else {
				// 一般参数
				$normalKey[$key] = $val;
			}
		}

		foreach ($needResolvekey as $key => $val) {
			// 解析范围参数:1、"1:5"-> range(1,5); 2、"1:10:2" -> range(1,10,2)
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

		/**
		 * 由 ['a' => [1,2], 'b' => [3,4,5]]  得到
		 * [
		 * 		['a' => 1, 'b' => 3],
		 * 		['a' => 1, 'b' => 4],
		 * 		['a' => 1, 'b' => 5],
		 * 		['a' => 2, 'b' => 3],
		 * 		['a' => 2, 'b' => 4],
		 * 		['a' => 2, 'b' => 5],
		 * ]
		 */
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

		// 将一般参数合并到上面产生的组合中
		foreach ($resolved as &$p) {
			$p = array_merge($p, $normalKey);
		}

		if (empty($resolved)) {
			return [$this->param];
		} else {
			return $resolved;
		}
	}

//TODO...
// 	protected function select($selectors, $contents)
// 	{
// 		$rootDom = \phpQuery::newDocumentHTML($contents);
// 		foreach ($selectors as $selector => $fields) {
// 			$doms = $rootDom->find($selector);
// 		}
// 	}

	/**
	 * 深度优先抓取
	 * @param $selector phpQuery选择器
	 * @param $action Closure 获得匹配数据后执行的操作，为null的情况则返回会的数据
	 * @return array
	 */
	public function dfsGrab($selectors, \Closure $action = null)
	{

	}


	/**
	 * 深度优先抓取
	 * @param $selector phpQuery选择器
	 * @param $action Closure 获得匹配数据后执行的操作，为null的情况则返回会的数据
	 * @return array
	 */
	public function wfsGrab($selectors, \Closure $action = null)
	{
		$params = $this->resolvedParam();
		$resultData = [];
		foreach ($params as $param) {
// 			$result = $this->httpclient->get($this->baseurl, [$this->paramKey => $param, 'timeout' => $this->timeout]);
			$result = call_user_func_array(
				[$this->httpclient, $this->method],
				[$this->baseurl, [$this->paramKey => $param, 'timeout' => $this->timeout]]
			);
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

	/**
	 * 抓取数据接口
	 * @param $type 指定接口返回的数据类型，调用不同的数据解析方法
	 * @param  $action Closure 获得匹配数据后执行的操作，为null的情况则返回会的数据
	 * @return mix
	 */
	public function get($type="json", \Closure $action = null)
	{
		$params = $this->resolvedParam();
		$resultData = [];
		foreach ($params as $param) {
// 			$result = $this->httpclient->get($this->baseurl, ['query' => $param, 'timeout' => 2]);
			$result = call_user_func_array(
				[$this->httpclient, $this->method],
				[$this->baseurl, [$this->paramKey => $param, 'timeout' => $this->timeout]]
			);
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

	/**
	 * 内部方法，解析get返回的json数据
	 */
	protected function parseJson($data)
	{
		return json_decode($data);
	}

	/**
	 * 内部方法，解析get返回的php序列化数据
	 */
	protected function parsePhp($data)
	{
		return unserialize($data);
	}

	/**
	 * 内部方法，解析get返回的jsonp数据
	 */
	protected function parseJsonp($data)
	{
		$matches = [];
		if (preg_match('/[_a-zA-Z][_a-zA-Z0-9]*?\((.*?)\).*/', $data, $matches)) {
			return json_decode($matches[1]);
		}
		return $data;
	}
}