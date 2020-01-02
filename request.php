<?php namespace http; // vim: se fdm=marker:

class request implements \ArrayAccess, \Countable{

  private static $handler = null;

 
  final function offsetExists($k):bool{
    return isset($this->$k);
  }
  final function offsetGet($k){
    return $this->$k;
  }
  final function offsetSet($k, $v){
    $this->$k = $v;
  }
  final function offsetUnset($k){
    unset($this->$k);
  }

  final function count():int{
    return count((array)$this);
  }

 //{{{

  //CURLOPT_COOKIESESSION => true,
  //CURLOPT_CERTINFO => true,
  //CURLOPT_CRLF => true,
  //CURLOPT_DNS_USE_GLOBAL_CACHE => true,
  //CURLOPT_SSL_FALSESTART => true,
  //CURLOPT_FORBID_REUSE => true,
  //CURLOPT_FRESH_CONNECT => true,
  #CURLOPT_TCP_NODELAY => true,
  //CURLOPT_HTTPPROXYTUNNEL => true,
  //CURLOPT_NETRC => true,
  //CURLOPT_NOPROGRESS => true, //默认自动 TRUE，只有为了调试才需要改变设置。 
  //CURLOPT_NOSIGNAL => true, // TRUE 时忽略所有的 cURL 传递给 PHP 进行的信号。在 SAPI 多线程传输时此项被默认启用，所以超时选项仍能使用。 
  //CURLOPT_PIPEWAIT => true,
  //CURLOPT_POST => true, //TRUE 时会发送 POST 请求，类型为：application/x-www-form-urlencoded，是 HTML 表单提交时最常见的一种。 
  //CURLOPT_PUT => true, // TRUE 时允许 HTTP 发送文件。要被 PUT 的文件必须在 CURLOPT_INFILE和CURLOPT_INFILESIZE 中设置。 
  //CURLOPT_SASL_IR => true,
  //CURLOPT_SSL_ENABLE_ALPN => true,
  //CURLOPT_SSL_ENABLE_NPN => true,
  //CURLOPT_SSL_VERIFYPEER => true,
  //CURLOPT_SSL_VERIFYSTATUS => true,
  //CURLOPT_TCP_FASTOPEN => true,
  //CURLOPT_TFTP_NO_OPTIONS => true,
  //CURLOPT_TRANSFERTEXT => true,
  //CURLOPT_UNRESTRICTED_AUTH => true,
  //CURLOPT_UPLOAD => true,
  //CURLOPT_VERBOSE => true,

  //CURLOPT_BUFFERSIZE => 000,
  //CURLOPT_CONNECTTIMEOUT => 000,
  //CURLOPT_CONNECTTIMEOUT_MS => 000,
  //CURLOPT_DNS_CACHE_TIMEOUT => 120,
  //CURLOPT_EXPECT_100_TIMEOUT_MS => 1000,
  //CURLOPT_HEADEROPT => 000,
  //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
  //CURLOPT_HTTPAUTH => 000,
  //CURLOPT_INFILESIZE => 000, // 希望传给远程站点的文件尺寸，字节(byte)为单位。 注意无法用这个选项阻止 libcurl 发送更多的数据，确切发送什么取决于 CURLOPT_READFUNCTION。 
  //CURLOPT_LOW_SPEED_LIMIT => 000, // 传输速度，每秒字节（bytes）数，根据CURLOPT_LOW_SPEED_TIME秒数统计是否因太慢而取消传输。 
  //CURLOPT_LOW_SPEED_TIME => 000, // 当传输速度小于CURLOPT_LOW_SPEED_LIMIT时(bytes/sec)，PHP会判断是否因太慢而取消传输。 
  //CURLOPT_MAXCONNECTS => 000,
  //CURLOPT_MAXREDIRS => 000, // 指定最多的 HTTP 重定向次数，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的。 
  //CURLOPT_PORT => 000,
  //CURLOPT_PROXYAUTH => 000, //HTTP 代理连接的验证方式。使用在CURLOPT_HTTPAUTH中的位掩码。 当前仅仅支持 CURLAUTH_BASIC和CURLAUTH_NTLM。 
  //CURLOPT_PROXYPORT => 000, // 代理服务器的端口。端口也可以在CURLOPT_PROXY中设置。 
  //CURLOPT_PROXYTYPE => 000, // 可以是 CURLPROXY_HTTP (默认值) CURLPROXY_SOCKS4、 CURLPROXY_SOCKS5、 CURLPROXY_SOCKS4A 或 CURLPROXY_SOCKS5_HOSTNAME。 
  //CURLOPT_REDIR_PROTOCOLS => 000, //CURLPROTO_* 值的位掩码。如果被启用，位掩码会限制 libcurl 在 CURLOPT_FOLLOWLOCATION开启时，使用的协议。 默认允许除 FILE 和 SCP 外所有协议。 这和 7.19.4 前的版本无条件支持所有支持的协议不同。关于协议常量，请参照CURLOPT_PROTOCOLS。 
  //CURLOPT_SSL_OPTIONS => 000,
  //CURLOPT_SSL_VERIFYHOST => 000,//设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)。译者注：公用名(Common Name)一般来讲就是填写你将要申请SSL证书的域名 (domain)或子域名(sub domain)。 设置成 2，会检查公用名是否存在，并且是否与提供的主机名匹配。 0 为不检查名称。 在生产环境中，这个值应该是 2（默认值）。 
  //CURLOPT_SSLVERSION => 000, //CURL_SSLVERSION_DEFAULT (0), CURL_SSLVERSION_TLSv1 (1), CURL_SSLVERSION_SSLv2 (2), CURL_SSLVERSION_SSLv3 (3), CURL_SSLVERSION_TLSv1_0 (4), CURL_SSLVERSION_TLSv1_1 (5) ， CURL_SSLVERSION_TLSv1_2 (6) 中的其中一个。 你最好别设置这个值，让它使用默认值。 设置为 2 或 3 比较危险，在 SSLv2 和 SSLv3 中有弱点存在。 
  //CURLOPT_TIMEOUT => 000, // 允许 cURL 函数执行的最长秒数。 
  //CURLOPT_TIMEOUT_MS => 000,
  //CURLOPT_MAX_RECV_SPEED_LARGE => 000, // 如果下载速度超过了此速度(以每秒字节数来统计) ，即传输过程中累计的平均数，传输就会降速到这个参数的值。默认不限速。 
  //CURLOPT_MAX_SEND_SPEED_LARGE => 000, // 如果上传的速度超过了此速度(以每秒字节数来统计)，即传输过程中累计的平均数 ，传输就会降速到这个参数的值。默认不限速。 
  //CURLOPT_SSH_AUTH_TYPES => 000,
  //CURLOPT_IPRESOLVE => 000,

  //CURLOPT_CAINFO => '',// 一个保存着1个或多个用来让服务端验证的证书的文件名。这个参数仅仅在和CURLOPT_SSL_VERIFYPEER一起使用时才有意义。
  //CURLOPT_CAPATH => '',// 一个保存着多个CA证书的目录。这个选项是和CURLOPT_SSL_VERIFYPEER一起使用的。 
  //CURLOPT_DNS_INTERFACE => '',
  //CURLOPT_DNS_LOCAL_IP4 => '',
  //CURLOPT_DNS_LOCAL_IP6 => '',
  //CURLOPT_EGDSOCKET => '',
  //CURLOPT_KEYPASSWD => '',
  //CURLOPT_KRB4LEVEL => '',
  //CURLOPT_PINNEDPUBLICKEY => '',
  //CURLOPT_PRIVATE => '',
  //CURLOPT_PROXY => '',
  //CURLOPT_PROXY_SERVICE_NAME => '',
  //CURLOPT_PROXYUSERPWD => '',
  //CURLOPT_SERVICE_NAME => '',
  //CURLOPT_SSL_CIPHER_LIST => '',
  //CURLOPT_SSLCERT => '',
  //CURLOPT_SSLCERTPASSWD => '',
  //CURLOPT_SSLCERTTYPE => '',
  //CURLOPT_SSLENGINE => '',
  //CURLOPT_SSLENGINE_DEFAULT => '',
  //CURLOPT_SSLKEY => '',
  //CURLOPT_SSLKEYPASSWD => '',
  //CURLOPT_SSLKEYTYPE => '',

  //CURLOPT_HTTP200ALIASES => [], //TODO  HTTP 200 响应码数组，数组中的响应码被认为是正确的响应，而非错误。 
  //CURLOPT_POSTQUOTE => [],
  //CURLOPT_PROXYHEADER => [],

  //CURLOPT_FILE => res,
  //CURLOPT_INFILE => res,
  //CURLOPT_STDERR => res,

  //CURLOPT_HEADERFUNCTION
  //CURLOPT_PASSWDFUNCTION
  //CURLOPT_PROGRESSFUNCTION
  //CURLOPT_READFUNCTION
  //CURLOPT_WRITEFUNCTION

  //CURLOPT_SHARE

  //}}}


  final function __construct(){
    static::$handler = curl_share_init();
    curl_share_setopt(static::$handler, CURLSHOPT_SHARE, CURL_LOCK_DATA_COOKIE);
  }

  final function __destruct(){
    curl_share_close(static::$handler);
  }


  final function __set($k, string $v){
    $this->$k = $v;
  }


  final private function response(array $opts=[]):object{//{{{

    $arr = [];
    foreach($this as $k=>$v)
      if(strcasecmp($k,'Host'))
        $arr[] = "$k: $v";
    //var_dump($arr);

    $opts += [
      CURLOPT_HTTPHEADER => $arr, //FIXME 整段迁移到匿名类构造里，哪种好？
      CURLOPT_SHARE => static::$handler, //FIXME 这里设置来得及吗？
    ];

    /**
     * @fixme handler还是原来那个吗？var_dump一下试试
     */
    return new class($this, $opts)
      implements \ArrayAccess, \JsonSerializable, \Countable{

      private $stream;

      function offsetExists($k):bool{
        return isset($this->$k);
      }
      function offsetGet($k){
        return $this->$k;
      }
      function offsetSet($k, $v){
        $this->$k = $v;
      }
      function offsetUnset($k){
        unset($this->$k);
      }

      function __get(string $k):?string{
        return $this->$k??null;
      }

      function count():int{
        return count((array)$this);
      }

      function __construct(request $req, array $opt){

        $handle = curl_init();

        curl_setopt_array($handle, $opt+[
          CURLOPT_PROTOCOLS=>CURLPROTO_HTTP|CURLPROTO_HTTPS,
          CURLOPT_REDIR_PROTOCOLS=>CURLPROTO_HTTP|CURLPROTO_HTTPS,
          CURLOPT_RETURNTRANSFER=>true,
          CURLOPT_HEADER=>false,
          CURLOPT_ENCODING => '',
          CURLOPT_DEFAULT_PROTOCOL => 'http',

          CURLOPT_AUTOREFERER=>true,
          CURLOPT_FOLLOWLOCATION=>true,
          CURLOPT_FILETIME=>true,

          CURLOPT_WRITEHEADER => $tmp_header=fopen('php://temp','r+b'),
          CURLOPT_FILE => $this->stream = fopen('php://temp','r+b'),//FIXME r+b 还是w
          CURLINFO_HEADER_OUT=>true,
          //CURLOPT_SASL_IR => true,
        ]);

        curl_exec($handle);
        $errno = curl_errno($handle);

        if($errno !== CURLE_OK) throw new \RuntimeException(curl_strerror($errno),$errno);


        curl_close($handle);

        foreach($req as $k=>$v) unset($req->$k);

        rewind($tmp_header);
        //echo '<pre>--',stream_get_contents($tmp_header),'--</pre>';

        rewind($tmp_header);
        foreach(request::http_response_header(explode("\r\n",end(explode("\r\n\r\n",trim(stream_get_contents($tmp_header)))))) as $k => $v)
          if(isset($this->$k)){
            if(is_string($this->$k))
              $this->$k = [$this->$k];
            $this->$k[] = $v;
          }else
            $this->$k = $v;


        //echo '<pre>--',curl_getinfo($this->stream,CURLINFO_HEADER_OUT),'--</pre>';
        foreach(request::http_response_header(explode("\r\n",curl_getinfo($this->stream,CURLINFO_HEADER_OUT))) as $k=>$v)
          $req->$k = $v;

      }

      function __destruct(){
        fclose($this->stream);
      }


      /**
       * @todo 本想直接转换成har文件，但又无法和浏览器环境一一对应
       */
      function jsonSerialize():array{
        return curl_getinfo($this->stream);
      }

      function __toString(){
        $id = spl_object_hash($this);
        rewind($this->stream);
        return stream_get_contents($this->stream);
      }

      function __invoke(Callable $fn, int ...$code):self{
        $status = curl_getinfo($this->stream,CURLINFO_HTTP_CODE);
        if(empty($code) || in_array($status,$code))
          $fn("$this",$status);
        return $this;
      }

    };

  }//}}}


  final function GET(string $url){
    return self::response([
      CURLOPT_URL=>$url,
    ]);
  }


  final function POST(string $url, array $form=[]):object{

    $query = http_build_query($form);
    parse_str($query,$arr);

    if($query!==http_build_query($arr))
      throw new \InvalidArgumentException('表单的name使用了错误的字符或未显式指定嵌套数组的下标',400);

    foreach($body as $k=>$v){
      if(!(is_scalar($v) ^ $v instanceof \CURLFile))
        throw new \InvalidArgumentException('数组的值只接受字符串或CURLFile对象',400);
    }

    return self::response([
      CURLOPT_URL=>$url,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => array_filter($body,'is_scalar')===$form?$query:$form,
      CURLOPT_POSTREDIR => CURL_REDIR_POST_ALL,
    ]);
  }


  final function PUT(string $url, string $body):object{
    return self::response([
      CURLOPT_CUSTOMREQUEST => __FUNCTION__,
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_NOBODY => true,
      CURLOPT_URL=>$url,
    ]);
  }


  final function PATCH(string $url, string $body):object{
    return self::response([
      CURLOPT_CUSTOMREQUEST=>__FUNCTION__,
      CURLOPT_POSTFIELDS=>$body,
      CURLOPT_URL=>$url,
    ]);
  }


  final function DELETE(string $url):object{
    return self::response([
      CURLOPT_CUSTOMREQUEST=>__FUNCTION__,
      CURLOPT_URL=>$url,
    ]);
  }


  final function HEAD(string $url):object{
    return self::response([
      CURLOPT_NOBODY => true,
      CURLOPT_URL=>$url,
    ]);
  }


  final function OPTIONS(string $url):object{
    return self::response([
      CURLOPT_CUSTOMREQUEST => __FUNCTION__,
      CURLOPT_NOBODY => true,
      CURLOPT_URL=>$url,
    ]);
  }


  final function TRACE(string $url):object{
    return self::response([
      CURLOPT_CUSTOMREQUEST => __FUNCTION__,
      CURLOPT_NOBODY => true,
      CURLOPT_URL=>$url,
    ]);
  }


  final function CONNECT(string $url):object{
    return self::response([
      CURLOPT_CUSTOMREQUEST => __FUNCTION__,
      CURLOPT_URL=>$url,
    ]);
  }


  final static function http_response_header(?array $http_response_header):\Generator{
    foreach(array_filter($http_response_header,'is_string') as $header)
      if(preg_match_all('/(?P<key>[\w-]+): (?P<val>.*)$/U',$header, $out))
        yield $out['key'][0] => $out['val'][0];
  }

}
