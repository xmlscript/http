<?php namespace http;

class ip{

  private $trust;

  function __construct(string ...$trust){
    $this->trust = $trust;
  }


  function __debugInfo():array{
    return [-1=>$_SERVER['REMOTE_ADDR']??'']+array_reverse(explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']??''));
  }


  function __toString():string{
    foreach($this->__debugInfo() as $ip)
      if($this->valid($ip)&&array_search($ip,$this->trust)!==false)
        continue;
      else
        return $ip;
    return $ip;
  }

  private function valid(string $ip):bool{
    return filter_var($ip, FILTER_VALIDATE_IP,FILTER_FLAG_NO_RES_RANGE);
  }


  static function ipecho():?string{
    return $this->valid($ip=request::url('http://ipecho.net/plain')->fetch()->body())?$ip:null;
  }

}
