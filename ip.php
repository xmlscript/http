<?php namespace http;

class ip{

  private $trust;

  function __construct(string ...$trust){
    $this->trust = $trust;
  }


  /**
   * 生成一个代理信任链，第一条绝对可信
   */
  function __debugInfo():array{
    return [-1=>$_SERVER['REMOTE_ADDR']??'']+array_reverse(explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']??''));
  }


  function __toString():string{
    foreach($this->__debugInfo() as $ip)
      if(filter_var($ip, FILTER_VALIDATE_IP,FILTER_FLAG_NO_RES_RANGE)&&array_search($ip,$this->trust)!==false)
        continue;
      else
        return $ip;
    return $ip;
  }

}
