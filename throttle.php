<?php namespace http; // vim: se fdm=marker:

abstract class throttle extends \Exception implements \ArrayAccess, \Countable{

  protected const COUNTER = 'count';//这个字段持久化计数器++
  protected const FREEZE = 'freeze';//这个字段持久化冻结时间戳，设置时一定时在当前时间+N秒的未来时间

  protected const LIMIT = 'limit';//这个源自适配器传入的私有变量

  protected $uri;

  private static $adapter;//可以省去连接持久层的开销，避免反复new和connect

  const ALGO_LEAKY_BUCKET = 0;
  const ALGO_TIME_WINDOW = 1;


  /**
   * @param string $uri unique字段
   * @param int $n
   * @param int $sec
   * @param int $algo 目前支持两种算法：分段限次，超次罚时
   */
  final function __construct(string $uri, int $n, int $sec, int $algo){
    $this->uri = $uri;
    header("X-RateLimit-limit: $n");//指定时间内最大请求次数
    header('X-RateLimit-Remaining: '.$this['remaining']);//指定时间内剩余请求次数


    /*
     * 三种属性：
     *   1、持久化
     *     - 总计数器，可能用于log，构造时必须++
     *     - FREEZE = `现在 + 秒数`
     *   2、外部传参
     *     - 分段限次，无论成败都将输出header
     *     - $sec 各种算法计算条件的基础参数，可能是惩罚N秒，也可能是分段限时
     *   3、计算得到
     *     - 分段计数器 = `总计数器 % 分段限次`
     *     - 剩余次数 = `总计数器 % 分段限次 ^ 分段限次`，无论成败都将输出header
     *     - 剩余秒数 = `max(0,FREEZE-现在)`，仅失败时输出Try-After
     * 上述各种数值，都必须随时get
     */
    $this[self::COUNTER] += 1;//无条件++

    switch($algo){
      case self::ALGO_TIME_WINDOW:
        break;

      case self::ALGO_LEAKY_BUCKET:
      default:
        $this->retry() or
        $this[self::FREEZE] = time() + ($this[self::COUNTER]%$n?0:$sec);
        break;
    }

  }


  /**
   * TODO 所有属性东一个西一个，不整齐，想办法统一暴露出来
   */
  private function retry():int{
    return max(0,$this[self::FREEZE]-time());
  }


  /**
   * 如果不是立即执行对象，则会贻误catch
   */
  final function __destruct(){
    if($retry=$this->retry()){
      header("Retry-After: $retry");
      parent::__construct('Too Many Request',429);
      throw $this;//注意！对象被转移到catch里了，仍然可以获取各项属性
    }
  }


  /**
   * 不是返回属性总数，而是返回实时计算的剩余时间？？？不合理就算了
   */
  final function count():int{
    return $this[self::COUNTER];
  }



  /**
   * 服务端为每个用户都临时持久化一套记录，无论是那种算法，都应该超时删除，减少用量，时之对应当时的访问量
   * @return 临时适配器，仅仅是为了下标路由到具体的throttle对象
   */
  final static function sess():\ArrayAccess{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class implements \ArrayAccess, \Countable{

      private $n = 5; //0是不限次数随便请求
      private $sec = 10; //0是不限时间随便请求
      private $algo = throttle::ALGO_LEAKY_BUCKET;

      /**
       * 合并两个算法
       * 时段内次数耗尽再冻结，无条件++，冻结到本时段结束
       * 次数耗尽再冻结，仅错误时++，冻结到N秒之后
       *
       * @param int $n 允许在单位时间内发起多少次请求数量
       * @param int $sec 时间窗口，从首次请求时间开始算起
       */
      function __invoke(int $n, int $sec, int $algo=throttle::ALGO_LEAKY_BUCKET):self{
        //FIXME 此时更改参数，能否自动同步在self::$adapter里面？？？？
        $this->n = $n;
        $this->sec = $sec;
        $this->algo = $algo;
        return $this;
      }


      function __construct(){
        //不确定之前有没有开启
        //不知道之后要不要关闭
        session_start();
      }

      /**
       * 一共记录了多少uri的规则，每个用户的规则各不相同，需要分别存储，并立即设置过期时间
       */
      function count(){
        return count($_SESSION[self::class]);
      }

      /**
       * sess的过期时间取决于session生命时长设置，redis/memcached/mongo自带过期，而其他持久层无法自动过期，不要支持吧
       */
      function offsetExists($uri){
        return isset($_SESSION[self::class][$uri]);
      }


      function offsetGet($uri):throttle{//第二次匿名对象是初始化对应的key
        return new class($uri, $this->n, $this->sec, $this->algo) extends throttle{#{{{

          function offsetExists($offset):bool{
            return isset($_SESSION[self::class][$this->uri][$offset]);
          }

          function offsetGet($offset){
            return $_SESSION[self::class][$this->uri][$offset]??null;
          }

          function offsetSet($offset,$value):void{
            $_SESSION[self::class][$this->uri][$offset] = (int)$value;
          }

          function offsetUnset($offset):void{
            unset($_SESSION[self::class][$this->uri][$offset]);
          }

        };//}}}
      }


      /**
       * throttle对象在set时，依赖这个方法提供的适配
       */
      function offsetSet($uri, $value){
        //FIXME 要求必须传入一个可被解析的throttle对象，否则没有意义
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

      function offsetGet($uri):\ArrayAccess{
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
   * 可以利用自动过期特性
   */
  static function memcached(string $host='127.0.0.1', int $port=6379):\ArrayAccess{
    return new class($host, $port) implements \ArrayAccess{

      function __construct(string $host, int $port){

      }

      function offsetExists($uri){

      }

      function offsetGet($uri):\ArrayAccess{
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

      function offsetGet($uri):\ArrayAccess{
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
   * 没有自动过期
   */
  static function tmp():self{
    return new class() implements \ArrayAccess{

      function __construct(){

      }

      function offsetExists($uri){

      }

      function offsetGet($uri):\ArrayAccess{
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
   * @todo 没有自动过期，需要在查询时判断过期并删除
   */
  static function PDO(\PDO $pdo):self{
    return new class($host, $port) implements \ArrayAccess{

      function __construct(string $host, int $port){

      }

      function offsetExists($uri){

      }

      function offsetGet($uri):\ArrayAccess{
        return new class($uri) extends \Exception implements \ArrayAccess{#{{{

        };#}}}
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }


  static function apcu():self{
    return new class implements \ArrayAccess{

      function offsetExists($uri){

      }

      function offsetGet($uri):\ArrayAccess{
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
   * 有其他第三方库还支持用纯对象来暂存状态，也许在swoole这种cli环境才有些用处吧。。。
   */
  static function obj():self{
    return new class implements \ArrayAccess{

      function offsetExists($uri){

      }

      function offsetGet($uri):\ArrayAccess{
        return new class($uri) extends \Exception implements \ArrayAccess{#{{{

        };#}}}
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }

}
