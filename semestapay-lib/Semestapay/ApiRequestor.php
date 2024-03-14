<?php

class Semestapay_ApiRequestor {

  public static function get($url, $headers=null)
  {
    return self::remoteCall($url, $headers, null, false);
  }

  public static function post($url,  $headers=null, $body)
  {
    return self::remoteCall($url,  $headers, $body, true);
  }

  public static function remoteCall($url, $headers, $body=null, $post = true)
  {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);

    if ($headers) {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }else{
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: application/json',        
      ));
    }

    if ($post) {
      curl_setopt($ch, CURLOPT_POST, 1);

      if ($body) {
        $body = json_encode($body);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      }
      else {
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
      }
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    

    $result = curl_exec($ch);
    //curl_close($ch);

    if ($result === FALSE) {
      throw new Exception('CURL Error: ' . curl_error($ch), curl_errno($ch));
    }
    else {
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $result_array = json_decode($result);
      return $result_array;
    }
  }
}
