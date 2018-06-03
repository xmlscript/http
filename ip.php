<?php namespace http;

class ip{

  private $trust;

  function __construct(string ...$trust){
    $this->trust = $trust;
  }


  /**
   * 返回不信任ip
   */
  private function REMOTE_ADDR():?string{
    return isset($_SERVER[__FUNCTION__])&&
      filter_var($_SERVER[__FUNCTION__], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)&&//合法IP
    array_search($_SERVER[__FUNCTION__],$this->trust)===false//不在信任列表里
                ?$_SERVER[__FUNCTION__]:null;
  }


  private function HTTP_X_FORWARDED_FOR():?string{
    return filter_var(explode(', ',$_SERVER['HTTP_X_FORWARDED_FOR']??null)[0], FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)?$_SERVER[__FUNCTION__]:null;
  }


  function __toString():string{
    return $this->REMOTE_ADDR()??//合法的不信任ip
           $this->HTTP_X_FORWARDED_FOR()??//既然信任，就无条件信任到底，返回第一条ip
           $_SERVER['REMOTE_ADDR']??//被辜负了信任之后
           '';//彻底失信
  }

}
