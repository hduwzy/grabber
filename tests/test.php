<?php
require 'start.php';

// var_dump(file_exists(dirname(__FILE__) . "../vendor/autoload.php"));

use GuzzleHttp\Client;
use Wgrabber\Grabber;

// $obj = \phpQuery::newDocumentHTML("<div><p></p></div>");
// print_r($obj);

// exit;

// $obj = new Client();

// $content = $obj->get("http://tieba.baidu.com/f", ['query' => ['kw' => '美图吧']]);
// $data = (string)$content->getBody();
// // $data = iconv('GBK', 'utf8//IGNORE', (string)$content->getBody());
// $dom = phpQuery::newDocumentHTML($data);
// $my = $dom->find('.j_th_tit');
// foreach ($my as $one) {
// 	print_r($one->getAttribute('href') . "\n");
// }
// exit;
// print_r($content);

// $posturl = [];

// $forum = ['美图吧', '李毅吧'];
// foreach ($forum as $name) {
// 	(new Grabber())
// 	->from("http://tieba.baidu.com/f")
// 	->with('get')
// 	->param(['kw' => urlencode($name), 'ie' => 'utf-8', 'pn' => '0:500:50'])
// 	->wfsGrab(
// 		['title' => 'a.j_th_tit'],
// 		function ($data) use (&$posturl){
// 			foreach ($data as $one) {
// 				foreach ($one as $key => $data) {
// 					foreach ($data as $dom) {
// 						$posturl[] = $dom->getAttribute('href');
// 						echo $dom->getAttribute('href') . "\n";
// 					}
// 				}
// 			}
// 		}
// 	);
// }
// echo count($posturl);

$grabber = new Grabber();

$grabber->from('http://tieba.baidu.com/home/get/panel')->with('get')->param(['ie' => 'utf-8', 'un' => ['Only丶Vicky', 'V念夏']]);
$grabber->get('json', function($data){
	print_r($data);
});

