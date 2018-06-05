<?php
require 'config.php';

class Utils {

  /**
  * Чтение страницы по URL
  */
  public function readUrl($url, $cookie = false){
     $ch = curl_init();

     curl_setopt($ch, CURLOPT_URL, $url);
     // откуда пришли на эту страницу
     curl_setopt($ch, CURLOPT_REFERER, $url);
     //запрещаем делать запрос с помощью POST и соответственно разрешаем с помощью GET
     curl_setopt($ch, CURLOPT_POST, 0);
     curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
     curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120);
     curl_setopt($ch, CURLOPT_TIMEOUT, 240);

     if ($cookie) {
      //отсылаем серверу COOKIE полученные от него при авторизации
      curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookie.txt');
     }

     curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:47.0) Gecko/20100101 Firefox/47.0");
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

     $result = curl_exec($ch);
     //$info = curl_getinfo($ch);
     curl_close($ch);

     //var_dump($info);

     return $result;
  }

  public static function logMessage($message, $type) {
		file_put_contents($type.'.txt', $message, FILE_APPEND);
	}

  public function saveImage($url, $prefix = false) {
    $filename = basename($url);
    $folder   = Config::$IMAGES_DIR;

    if($prefix != false) {
      $filename = substr(md5($prefix), 0, 10).'_'.$filename;
    }

    if(!file_exists($folder)){
      mkdir($folder);
    }

    if(!file_exists($folder.$filename)){
      file_put_contents($folder.$filename, file_get_contents($url));
    }

    return $filename;
  }

  public function get_chunk_info(){
    $int = 0;
    if(file_exists('./temp/chank_count.int')) {
      $int = intval(file_get_contents('./temp/chank_count.int'));
    }
    return $int;
  }

  public function set_chunk_info($value){
    return file_put_contents('./temp/chank_count.int', $value);
  }
}
