<?php namespace http; // vim: se fdm=marker:

abstract class throttle extends \Exception{

  protected const KEY = 'sdklfj';

  protected $uniq;//FIXME 要纯粹的数据模型对象，不要private变量
  protected $key;

  protected static $adapter;//可以省去连接持久层的开销，避免反复new和connect

  const ALGO_LEAKY_BUCKET = 0;
  const ALGO_TIME_WINDOW = 1;

  public $hit=0;//当前分段计数器
  public $remaining=0;//剩余次数
  public $retry;//FIXME 怎么做成$Retry-After

  abstract function __get(string $key):?float;
  abstract function __set(string $key, float $value):void;

  /**
   * @param string $uri unique字段
   * @param int $limit
   * @param int $sec
   * @param int $algo 目前支持两种算法：分段限次，超次罚时
   */
  final function __construct(string $uri, int $limit, int $sec, int $algo){#{{{
    $this->uniq = $uri;

    $limit=abs($limit);

    /**
     * 种种迹象表明，时间戳放在小数位非常容易处理，10个字符长度，time*10**10就可以还原，也可用用time()/10**10来比较
     * 但是谨防尾数为0时，被忽略的问题
     * 计数器在整数位，方便++
     */

    if($this->retry = max(0,str_pad(ltrim(strstr($this->{self::KEY},'.'),'.'),10,0)-time())){//TODO 把小数位拿出来比较

      header('Retry-After: '.$this->retry, parent::__construct('Too Many Requests', 429), $this->code);

      throw $this;

    }else{

      header('X-RateLimit-limit: '.$limit);
      header('X-RateLimit-Remaining: '.$this->remaining=$limit-(int)$this->hit=($this->{self::KEY}++%$limit)+1);

      switch($algo){
        case self::ALGO_TIME_WINDOW:
          break;

        case self::ALGO_LEAKY_BUCKET:
        default:

          $this->remaining or $this->{self::KEY} = (float)((int)$this->{self::KEY}.'.'.(time()+$sec));
          break;
      }

    }

  }#}}}


  final static function sess():\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class implements \ArrayAccess, \Countable{

      private $n = 5;
      private $sec = 10;
      private $algo = throttle::ALGO_LEAKY_BUCKET;

      function __invoke(int $n, int $sec, int $algo=throttle::ALGO_LEAKY_BUCKET):self{
        $this->n = $n;
        $this->sec = $sec;
        $this->algo = $algo;
        return $this;
      }


      function __construct(){
        session_start();
      }

      function count(){
        return count($_SESSION[self::class]);
      }

      function offsetExists($uri){
        return isset($_SESSION[self::class][$uri]);
      }


      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->sec, $this->algo) extends throttle{

          function __isset(string $key):bool{
            return isset($_SESSION[self::class][$this->uniq][self::KEY]);
          }

          function __get(string $key):?float{
            return $_SESSION[self::class][$this->uniq][self::KEY]??null;
          }

          function __set(string $key, float $value):void{
            strcasecmp(self::KEY,$key) or $_SESSION[self::class][$this->uniq][self::KEY] = $value;
          }

          function __unset(string $key){
            unset($_SESSION[self::class][$this->uniq][self::KEY]);
          }

        };
      }


      function offsetSet($uri, $value){
        $_SESSION[self::class][$uri] = $value;
      }


      function offsetUnset($uri){
        unset($_SESSION[self::class][$uri]);
      }

    };
  }#}}}


  static function redis(string $host='127.0.0.1', int $port=6379, string $password=''):\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class($host, $port, $password) implements \ArrayAccess{

      public $redis;

      private $n = 5;
      private $sec = 10;
      private $algo = throttle::ALGO_LEAKY_BUCKET;


      function __invoke(int $n, int $sec, int $algo=throttle::ALGO_LEAKY_BUCKET):self{
        $this->n = $n;
        $this->sec = $sec;
        $this->algo = $algo;
        return $this;
      }

      function __construct(string $host, int $port, string $password=''){
        $this->redis = new \Redis;
        @$this->redis->connect($host,$port) and $this->redis->auth($password);
      }

      function count():int{
        return $this->redis->dbSize();
      }

      function offsetExists($uri){

      }

      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->sec, $this->algo) extends throttle{

          private function k():string{
            return md5($this->uniq.self::KEY.new ip,1);//FIXME 并不是所有数据库都支持这样的key字段！！！
          }

          function __isset(string $key):bool{
            return strcasecmp(self::KEY,$key) or self::$adapter['redis']->redis->exists($this->k());
          }

          function __unset(string $key):void{
            strcasecmp(self::KEY,$key) or self::$adapter['redis']->redis->delete($this->k());
          }

          function __get(string $key):?float{
            //FIXME 考虑用select方法选择一个专用数据库
            //FIXME 其实sess使用了一个隐藏参数cookie，这里只能用ip代替，但是ip并不可信，可能误伤！！！
            return self::$adapter['redis']->redis->get($this->k())?:null;
          }

          function __set(string $key, float $value):void{
            strcasecmp(self::KEY,$key) or self::$adapter['redis']->redis->setex($this->k(), 300, $value);
          }

        };
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }#}}}


  static function memcache(string $host='127.0.0.1', int $port=11211):\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class($host, $port) implements \ArrayAccess{

      public $memcache;

      private $n = 5;
      private $sec = 10;
      private $algo = throttle::ALGO_LEAKY_BUCKET;

      function __invoke(int $n, int $sec, int $algo=throttle::ALGO_LEAKY_BUCKET):self{
        $this->n = $n;
        $this->sec = $sec;
        $this->algo = $algo;
        return $this;
      }

      /**
       * @param string $host
       * @param int $port 当使用unix://path/to/memcached.sock来使用unix或socket时，port必须是0
       * @param int $timeout=1 文档说修改默认值要三思，时间太久将失去缓存意义
       */
      function __construct(string $host, int $port){
        $this->memcache = new \Memcache;//可以使用类，也可以使用函数，但是函数的文档都指向了类方法。。。
        $this->memcache->addServer($host,$port);
      }

      function __destruct(){
        $this->memcache->quit();
      }

      function count():int{
        return $this->memcached->getStats()['total_items'];
      }

      function offsetExists($uri){

      }

      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->sec, $this->algo) extends throttle{


          private function k():string{
            return md5($this->uniq.self::KEY.new ip,1);//FIXME 并不是所有数据库都支持这样的key字段！！！
          }

          function __isset(string $key):bool{
            return strcasecmp(self::KEY,$key) or self::$adapter['memcache']->memcache->get($this->k())!==false;
          }

          function __unset(string $key):void{
            strcasecmp(self::KEY,$key) or self::$adapter['memcache']->memcache->delete($this->k());
          }

          function __get(string $key):?float{
            //FIXME 其实sess使用了一个隐藏参数cookie，这里只能用ip代替，但是ip并不可信，可能误伤！！！
            //FIXME 其他客户端意外或刻意存入了非法值？？？
            return self::$adapter['memcache']->memcache->get($this->k())?:null;
          }

          function __set(string $key, float $value):void{
            strcasecmp(self::KEY,$key) or self::$adapter['memcache']->memcache->set($this->k(), $value, 0, 300);
          }

        };
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }#}}}


  static function memcached(string $host='127.0.0.1', int $port=11211, int $timeout=1):\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class($host, $port, $timeout) implements \ArrayAccess{

      public $memcached;

      private $n = 5;
      private $sec = 10;
      private $algo = throttle::ALGO_LEAKY_BUCKET;

      function __invoke(int $n, int $sec, int $algo=throttle::ALGO_LEAKY_BUCKET):self{
        $this->n = $n;
        $this->sec = $sec;
        $this->algo = $algo;
        return $this;
      }

      function __construct(string $host, int $port, int $timeout){
        $this->memcached = new \Memcached('persistent_id');
        $this->memcached->addServer($host,$port,0);//FIXME 允许添加多个服务器，而且可以单独设置权重
      }

      function __destruct(){
        $this->memcached->quit();
      }

      function count():int{
        return $this->memcached->getStats()['total_items'];
      }

      function offsetExists($uri){

      }

      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->sec, $this->algo) extends throttle{

          private function k():string{
            return md5($this->uniq.self::KEY.new ip,0);//FIXME 不支持怪异的字符
          }

          function __isset(string $key):bool{
            return strcasecmp(self::KEY,$key) or self::$adapter['memcached']->memcached->exists($this->k());
          }

          function __unset(string $key):void{
            strcasecmp(self::KEY,$key) or self::$adapter['memcached']->memcached->delete($this->k());
          }

          function __get(string $key):?float{
            //FIXME 其实sess使用了一个隐藏参数cookie，这里只能用ip代替，但是ip并不可信，可能误伤！！！
            return self::$adapter['memcached']->memcached->get($this->k())?:null;
          }

          function __set(string $key, float $value):void{
            strcasecmp(self::KEY,$key) or self::$adapter['memcached']->memcached->set($this->k(), $value, 300);
          }

        };
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }#}}}


  static function mongo(string $host='127.0.0.1', int $port=27017):\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class($host, $port) implements \ArrayAccess{

      public $redis;

      private $n = 5;
      private $sec = 10;
      private $algo = throttle::ALGO_LEAKY_BUCKET;

      function __invoke(int $n, int $sec, int $algo=throttle::ALGO_LEAKY_BUCKET):self{
        $this->n = $n;
        $this->sec = $sec;
        $this->algo = $algo;
        return $this;
      }

      /**
       * 比较复杂，还有分片配置，不如直接传入Collection方便
       */
      function __construct(string $host, int $port, string $password=''){
        $this->mongo = new \MongoDB\Client("mongodb://$host:$port");
      }

      function count():int{
        //return $this->redis->dbSize();
      }

      function offsetExists($uri){

      }

      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->sec, $this->algo) extends throttle{

          private function k():string{
            return md5($this->uniq.self::KEY.new ip,1);//FIXME 并不是所有数据库都支持这样的key字段！！！
          }

          function __isset(string $key):bool{
            //return strcasecmp(self::KEY,$key) or self::$adapter['redis']->redis->exists($this->k());
          }

          function __unset(string $key):void{
            //strcasecmp(self::KEY,$key) or self::$adapter['redis']->redis->delete($this->k());
          }

          function __get(string $key):?float{
            //FIXME 其实sess使用了一个隐藏参数cookie，这里只能用ip代替，但是ip并不可信，可能误伤！！！
            //return self::$adapter['redis']->redis->get($this->k())?:null;
          }

          function __set(string $key, float $value):void{
            //strcasecmp(self::KEY,$key) or self::$adapter['redis']->redis->setex($this->k(), 300, $value);
          }

        };
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }#}}}


  static function apcu():\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class implements \ArrayAccess{

      private $n = 5;
      private $sec = 10;
      private $algo = throttle::ALGO_LEAKY_BUCKET;

      function __invoke(int $n, int $sec, int $algo=throttle::ALGO_LEAKY_BUCKET):self{
        $this->n = $n;
        $this->sec = $sec;
        $this->algo = $algo;
        return $this;
      }

      function count():int{
        //return $this->redis->dbSize();
      }

      function offsetExists($uri){

      }

      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->sec, $this->algo) extends throttle{

          private function k():string{
            return md5($this->uniq.self::KEY.new ip,1);//FIXME 并不是所有数据库都支持这样的key字段！！！
          }

          function __isset(string $key):bool{
            return strcasecmp(self::KEY,$key) or apcu_exists($this->k());
          }

          function __unset(string $key):void{
            strcasecmp(self::KEY,$key) or apcu_delete($this->k());
          }

          function __get(string $key):?float{
            //FIXME 其实sess使用了一个隐藏参数cookie，这里只能用ip代替，但是ip并不可信，可能误伤！！！
            return apcu_fetch($this->k())?:null;
          }

          function __set(string $key, float $value):void{
            //strcasecmp(self::KEY,$key) or apcu_add($this->k(), $value, 3000);
            strcasecmp(self::KEY,$key) or apcu_store($this->k(), $value, 3000);
          }

        };
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }#}}}




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
