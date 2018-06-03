<?php namespace http;

class ip{


  private function REMOTE_ADDR():?string{
    return isset($_SERVER[__FUNCTION__])&&filter_var($_SERVER[__FUNCTION__], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)?null:$_SERVER[__FUNCTION__];
  }


  private function HTTP_X_FORWARDED_FOR():?string{
    return isset($_SERVER[__FUNCTION__])&&filter_var(end(explode($_SERVER['HTTP_X_FORWARDED_FOR'],', ')), FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE)?$_SERVER[__FUNCTION__]:null;
  }


  function __toString():string{
    return $this->REMOTE_ADDR()??
           $this->X_FORWARDED_FOR()??
           $_SERVER['REMOTE_ADDR']??
           '';
  }

}
