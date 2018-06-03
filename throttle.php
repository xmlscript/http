<?php namespace http; // vim: se fdm=marker:

class throttle{

  static function sess():\ArrayAccess{
    return new class implements \ArrayAccess{

      function offsetExists($uri){
        return isset($_SESSION[self::class][$uri]);
      }

      function offsetGet($uri){
        return new class($uri) extends \Exception implements \ArrayAccess{#{{{

          function offsetExists($offset){
            return isset($this->$offset);
          }

          function offsetGet($offset){
            return $this->$offset;
          }

          function offsetSet($offset,$value){
            // Readonly
          }

          function offsetUnset($offset){
            //Readonly
          }

          private const RETRY = 'retry';
          private const COUNTER = 'count';
          private const FREEZE = 'freeze';

          //public $limit;//最大请求数量 FIXME 仅在invoke时传入，还有时间窗口变量
          public $remaining;//计算得到本轮剩余N次
          public $retry=0;//计算得到还剩N秒

          //必须保持在持久层
          private $counter=0;//总计数器
          private $freeze;//冻结时间

          private $uri;


          function __get(string $prop):?int{
            switch($prop){
              case 'retry':
                return max(0,$this->freeze-$_SERVER['REQUEST_TIME_FLOAT']);
              case 'freeze':
              case 'counter':
                return $_SESSION[self::class][$this->uri][$prop]??0;
              default:
                return null;
            }
          }

          function __set(string $prop, int $value):void{
            switch($prop){
              case 'freeze':
              case 'counter':
                $_SESSION[self::class][$this->url][$prop] = $value;
              default:
                $this->$prop = $value;
            }
          }

          function __construct(string $uri){
            $this->uri = $uri;
          }

          /**
           * 总计数器++，同步更新冻结时间
           * @todo 但是
           */
          function __invoke(){
            //同步各项到持久层

            if($this->retry){
              $this->{'X-RateLimit-Limit'} = $n;
              $this->{'X-RateLimit-Remaining'} = $this->counter % $n ^ $n;
              $this->{'Retry-After'} = $this->retry;
              parent::__construct('',429);
              throw $this;
            }else{
              ++$this->counter;
            }
          }

          /**
           * 时段内次数耗尽再冻结，无条件++，冻结到本时段结束
           * @todo 本sess类在无cookie支持时，不仅没有效果，反而白白消耗资源
           * @param int $n
           * @param float $len 时间窗口，从首次请求时间开始算起
           * @return bool 在上述参数组合约束下，是否还能继续操作？
           */
          static function freeze(int $n, float $len):bool{
            self::retry($this->uri) or
            $_SESSION[self::class][$this->uri][self::FREEZE] = $_SERVER['REQUEST_TIME_FLOAT'] + ((@++$_SESSION[self::class][$this->uri][self::COUNTER]%$n)?0:$len);
          }


          /**
           * 次数耗尽再冻结，仅错误时++，冻结到N秒之后
           * @todo 本sess类在无cookie支持时，不仅没有效果，反而白白消耗资源
           * @param int $n
           * @param float $sleep 固定N秒，从分段计数器溢出开始算起
           * @return bool 在上述参数组合约束下，是否还能继续操作？
           */
          static function freeze2(int $n, float $sleep):bool{
            self::retry($this->uri) or
            $_SESSION[self::class][$this->uri][self::FREEZE] = $_SERVER['REQUEST_TIME_FLOAT'] + ((@++$_SESSION[self::class][$this->uri][self::COUNTER]%$n)?0:$sleep);
          }

        };//}}}
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){
        unset($_SESSION[self::class][$uri]);
      }

    };
  }


  /**
   * 可以利用自动过期特性
   */
  static function redis(string $host='127.0.0.1', int $port=6379):\ArrayAccess{
    return new class($host, $port) implements \ArrayAccess{

      function __construct(string $host, int $port){

      }

      function offsetExists($uri){

      }

      function offsetGet($uri){
        return new class($uri) extends \Exception implements \ArrayAccess{#{{{

        };#}}}
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }


  static function mongo(\MongoDB\Collection $collection):self{
    return new class($host, $port) implements \ArrayAccess{

      function __construct(string $host, int $port){

      }

      function offsetExists($uri){

      }

      function offsetGet($uri){
        return new class($uri) extends \Exception implements \ArrayAccess{#{{{

        };#}}}
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }


  static function tmp():self{
    return new class() implements \ArrayAccess{

      function __construct(){

      }

      function offsetExists($uri){

      }

      function offsetGet($uri){
        return new class($uri) extends \Exception implements \ArrayAccess{#{{{

        };#}}}
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }


  /**
   * 直接用PDO对象？还是分别传入dsn，username，password，options？？？
   * @todo 需要指明一个专用table名
   */
  static function PDO(\PDO $pdo):self{
    return new class($host, $port) implements \ArrayAccess{

      function __construct(string $host, int $port){

      }

      function offsetExists($uri){

      }

      function offsetGet($uri){

      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }


  /**
   * 这应该是开发者应用层做的事情
   */
  function test(string $request_uri){
    if($retry=self::retry($request_uri)){
      header('X-RateLimit-limit: '.self::limit($request_uri));//指定时间内最大请求次数
      header('X-RateLimit-Remaining: '.self::remaining($request_uri));//指定时间内剩余请求次数
      header("Retry-After: $retry");
      throw new \Error('Too Many Request',429);
    }
  }

}
