<?php namespace http; // vim: se fdm=marker:

abstract class throttle extends \Exception{

  public $hit;

  protected const KEY = 'sdklfj';

  protected $uniq;//FIXME 要纯粹的数据模型对象，不要private变量

  protected static $adapter;//可以省去连接持久层的开销，避免反复new和connect

  abstract protected function incr():int;
  abstract protected function get():?int;
  abstract protected function expire(float $timeout):bool;
  abstract protected function ttl():?float;


  final function __construct(string $uri, int $limit, float $ttl){#{{{
    $this->uniq = $uri;

    [$this->{'X-RateLimit-Limit'},$this->{'X-RateLimit-Reset'}] = [$limit,(int)round(($this->ttl()?:$ttl)+microtime(1))];

    if(($this->hit=$this->get())>=$limit){

      parent::__construct('Too Many Requests',429);

      //FIXME 是不是对Remaining有什么误解？其他库使用 max(0,limit-hit)
      [$this->{'X-RateLimit-Remaining'},$this->{'Retry-After'}] = [0,$this->{'X-RateLimit-Reset'}-time()];

      headers_sent() or header('X-RateLimit-Reset: '.$this->{'X-RateLimit-Reset'},header('X-RateLimit-Remaining: '.$this->{'X-RateLimit-Remaining'},header('X-RateLimit-limit: '.$limit,header('Retry-After: '.date(DATE_RFC7231,$this->{'X-RateLimit-Reset'})))),$this->code);

      throw $this;

    }else{

      $this->incr()===1 and $this->expire($ttl);

      $this->{'X-RateLimit-Remaining'} = $limit - ++$this->hit;

      headers_sent() or header('X-RateLimit-Reset: '.$this->{'X-RateLimit-Reset'},header('X-RateLimit-Remaining: '.$this->{'X-RateLimit-Remaining'},header('X-RateLimit-limit: '.$limit)));

    }

  }#}}}


  final static function sess():\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class implements \ArrayAccess, \Countable{

      private $n = 5;
      private $ttl = 10;

      function __invoke(int $n, float $ttl):self{
        $this->n = $n;
        $this->ttl = $ttl;
        return $this;
      }


      function __construct(){
        session_status()&PHP_SESSION_ACTIVE or session_start();
      }

      function count(){
        return count($_SESSION[self::class]);
      }

      function offsetExists($uri){
        return isset($_SESSION[self::class][$uri]);
      }


      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->ttl) extends throttle{

          protected function incr():int{
            return @++$_SESSION[self::class][$this->uniq][self::KEY]['hit'];
          }

          protected function expire(float $timeout):bool{
            return $_SESSION[self::class][$this->uniq][self::KEY]['exp'] = $timeout+microtime(1);
          }

          protected function get():?int{
            if(isset($_SESSION[self::class][$this->uniq][self::KEY]['exp'])&&!$this->ttl())
              unset($_SESSION[self::class][$this->uniq][self::KEY]);
            return $_SESSION[self::class][$this->uniq][self::KEY]['hit']??null;
          }

          protected function ttl():?float{
            return ($exp=($_SESSION[self::class][$this->uniq][self::KEY]['exp']??0)-microtime(1))>0?$exp:null;
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


  final static function redis(string $host='127.0.0.1', int $port=6379, string $password=''):\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class($host, $port, $password) implements \ArrayAccess{

      public $redis;

      private $n = 5;
      private $ttl = 10;


      function __invoke(int $n, float $ttl):self{
        $this->n = $n;
        $this->ttl = $ttl;
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
        return new class($uri, $this->n, $this->ttl) extends throttle{

          private function k():string{
            return md5($this->uniq.self::KEY.new ip,1);
          }

          protected function incr():int{
            return self::$adapter['redis']->redis->incr($this->k());
          }

          protected function get():?int{
            return self::$adapter['redis']->redis->get($this->k())?:null;
          }

          protected function expire(float $timeout):bool{
            return self::$adapter['redis']->redis->expire($this->k(), $timeout);
          }

          protected function ttl():?float{
            return ($ttl=self::$adapter['redis']->redis->pttl($this->k()))>0?$ttl/1000:null;
          }

        };
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }#}}}


  final static function memcache(string $host='127.0.0.1', int $port=11211):\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class($host, $port) implements \ArrayAccess{

      public $memcache;

      private $n = 5;
      private $ttl = 10;

      function __invoke(int $n, float $ttl):self{
        $this->n = $n;
        $this->ttl = $ttl;
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
        //$this->memcache->close();
      }

      function count():int{
        return $this->memcached->getStats()['total_items'];
      }

      function offsetExists($uri){

      }

      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->ttl) extends throttle{

          private function k():string{
            return md5($this->uniq.self::KEY.new ip,1);//FIXME 并不是所有数据库都支持这样的key字段！！！
          }

          protected function incr():int{
            [$obj,$key] = [self::$adapter['memcache']->memcache,$this->k()];
            return $obj->increment($key)?:$obj->set($key,1);

          }

          protected function get():?int{
            return self::$adapter['memcache']->memcache->get($this->k())?:null;
          }

          /**
           * @todo memcache更变态，甚至没有touch方法。。。一步步滑向无耻的深渊
           */
          protected function expire(float $timeout):bool{
            [$obj,$key] = [self::$adapter['memcache']->memcache,$this->k()];
            return $obj->set($key.'_ttl',$timeout+microtime(1),0,$timeout) && $obj->set($key,$this->get(),0,$timeout);
          }

          protected function ttl():?float{
            return ($ttl=self::$adapter['memcache']->memcache->get($this->k().'_ttl'))?$ttl-microtime(1):null;
          }

        };
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }#}}}


  final static function memcached(string $host='127.0.0.1', int $port=11211, int $timeout=1):\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class($host, $port, $timeout) implements \ArrayAccess{

      public $memcached;

      private $n = 5;
      private $ttl = 10;

      function __invoke(int $n, float $ttl):self{
        $this->n = $n;
        $this->ttl = $ttl;
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
        return new class($uri, $this->n, $this->ttl) extends throttle{

          private function k():string{
            return md5($this->uniq.self::KEY.new ip,0);//FIXME 不支持怪异的字符
          }

          protected function incr():int{
            [$obj,$key] = [self::$adapter['memcached']->memcached,$this->k()];
            return $obj->increment($key)?:$obj->set($key,1);

          }

          protected function get():?int{
            return self::$adapter['memcached']->memcached->get($this->k())?:null;
          }

          /**
           * @param int $timeout memcached要求必须是int，而且如果大于60*60*24*30（30天的秒数），将会当作timestamp
           */
          protected function expire(float $timeout):bool{
            [$obj,$key] = [self::$adapter['memcached']->memcached,$this->k()];
            return $obj->set($key.'_ttl',$timeout+microtime(1),$timeout) && $obj->touch($key, $timeout);
          }

          protected function ttl():?float{
            return ($ttl=self::$adapter['memcached']->memcached->get($this->k().'_ttl'))?$ttl-microtime(1):null;
          }

        };
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }#}}}


  final static function mongodb(string $host='127.0.0.1', int $port=27017, string $db='throttle', string $username='', string $password=''):\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class($host, $port,$db,$username,$password) implements \ArrayAccess{

      public $collection;

      private $n = 5;
      private $ttl = 10;

      /**
       * 因为MongoDB的自增操作可以在子节点的粒度上执行，所以才把exp字段和计数器放在一起
       * 分片/集群怎么传入？
       */
      function __construct(string $host, int $port, string $db, string $username, string $password){
        $obj = new \MongoDB\Client("mongodb://$host:$port/$db");//FIXME 此时设置的$db有什么用？！
        //var_dump($obj);
        $this->collection = $obj->$db->mycoll2;

        echo '<fieldset><legend>items</legend>';
        foreach($this->collection->find() as $k=>$v) var_dump($v);
        echo '</fieldset>';

        /**
         * 回收已过期的计数器，牺牲性能换取无谓无尽的空间浪费
         */
        var_dump($this->collection->createIndex(['exp'=>1],['expireAfterSeconds'=>0]));//FIXME exp无法利用self::EXP常量
      }

      function __invoke(int $n, float $ttl):self{
        $this->n = $n;
        $this->ttl = $ttl;
        return $this;
      }

      function count():int{
        //return $this->redis->dbSize();
      }

      function offsetExists($uri){

      }

      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->ttl) extends throttle{

          private const SCHEME_NAME = 'name';
          private const SCHEME_HIT = 'hit';
          private const SCHEME_EXP = 'exp';

          private function filter():array{
            return [self::SCHEME_NAME=>md5($this->uniq.self::KEY.new ip)];//FIXME ip不可靠,一旦涉及IP，就可以被恶意替换变量，绕过限制
          }

          function incr():int{

            $obj = self::$adapter['mongodb']->collection;

            $hit=$this->get() or $obj->deleteOne($this->filter());

            $obj->updateOne($this->filter(),[
              '$inc'=>[self::SCHEME_HIT=>1],
            ],[
              'upsert'=>true
            ]);

            return ++$hit;
          }

          function expire(float $timeout):bool{

            self::$adapter['mongodb']->collection->updateOne($this->filter(),[
              '$set'=>[
                self::SCHEME_EXP=>new \MongoDB\BSON\UTCDateTime(($timeout+microtime(1))*1000),
              ]
            ],[
              'upsert'=>true
            ]);

            return true;
          }

          function ttl():?float{
            $ttl = self::$adapter['mongodb']->collection->findOne($this->filter(),['projection'=>[self::SCHEME_EXP=>1]])[self::SCHEME_EXP]??null;
            return $ttl?("$ttl"/1000)-microtime(1):null;
          }

          function get():?int{
            return self::$adapter['mongodb']->collection->findOne($this->filter()+[
              self::SCHEME_EXP=>['$gte'=>new \MongoDB\BSON\UTCDateTime],
            ],[
              'projection'=>[self::SCHEME_HIT=>1]
            ])[self::SCHEME_HIT];
          }


        };
      }

      function offsetSet($uri, $value){

      }

      function offsetUnset($uri){

      }

    };
  }#}}}


  final static function apcu():\ArrayAccess{#{{{
    return self::$adapter[__FUNCTION__]??self::$adapter[__FUNCTION__]=new class implements \ArrayAccess{

      private $n = 5;
      private $ttl = 10;

      function __invoke(int $n, float $ttl):self{
        $this->n = $n;
        $this->ttl = $ttl;
        return $this;
      }

      function count():int{
        //return $this->redis->dbSize();
      }

      function offsetExists($uri){

      }

      function offsetGet($uri):throttle{
        return new class($uri, $this->n, $this->ttl) extends throttle{

          private function k(string $str=''):string{
            return md5($this->uniq.self::KEY.new ip.$str,1);
          }

          /**
           * inc愚蠢的在已过期的字段上自增，需要外部介入delete才能正确归零
           */
          protected function incr():int{
            apcu_fetch($this->k('ttl')) or apcu_delete($this->k());
            return apcu_inc($this->k());//仅当key不存在，或其值必须is_int时才正常返回自增值，否则一律false
          }

          protected function get():?int{
            return apcu_fetch($this->k())?:null;
          }

          protected function expire(float $timeout):bool{
            return apcu_store($this->k('ttl'),$timeout+microtime(1),$timeout) && apcu_store($this->k(),$this->get(),$timeout);
          }

          protected function ttl():?float{
            return ($ttl=apcu_fetch($this->k('ttl')))?$ttl-microtime(1):null;
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

}
