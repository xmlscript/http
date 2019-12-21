<?php namespace http; // vim: se fdm=marker:

/**
 * @todo 检测特定<meta>
 */
class request{

  protected static $handle;
  protected static $cookie;

  protected static $opts = [//{{{

    //CURLOPT_AUTOREFERER => true,
    //CURLOPT_COOKIESESSION => true,
    //CURLOPT_CERTINFO => true,
    //CURLOPT_CONNECT_ONLY => true,
    //CURLOPT_CRLF => true,
    //CURLOPT_DNS_USE_GLOBAL_CACHE => true,
    //CURLOPT_FAILONERROR => true,
    //CURLOPT_SSL_FALSESTART => true,
    //CURLOPT_FILETIME => true,
    //CURLOPT_FOLLOWLOCATION => true,
    //CURLOPT_FORBID_REUSE => true,
    //CURLOPT_FRESH_CONNECT => true,
    #CURLOPT_FTP_USE_EPRT => true,
    #CURLOPT_FTP_USE_EPSV => true,
    #CURLOPT_FTP_CREATE_MISSING_DIRS => true,
    #CURLOPT_FTPAPPEND => true,
    #CURLOPT_TCP_NODELAY => true,
    #CURLOPT_FTPASCII => true,
    #CURLOPT_FTPLISTONLY => true,
    //CURLOPT_HEADER => true,
    //CURLINFO_HEADER_OUT => true,
    //CURLOPT_HTTPGET => true,
    //CURLOPT_HTTPPROXYTUNNEL => true,
    //CURLOPT_NETRC => true,
    //CURLOPT_NOBODY => true, // TRUE 时将不输出 BODY 部分。同时 Mehtod 变成了 HEAD。修改为 FALSE 时不会变成 GET。 
    //CURLOPT_NOPROGRESS => true, //默认自动 TRUE，只有为了调试才需要改变设置。 
    //CURLOPT_NOSIGNAL => true, // TRUE 时忽略所有的 cURL 传递给 PHP 进行的信号。在 SAPI 多线程传输时此项被默认启用，所以超时选项仍能使用。 
    //CURLOPT_PATH_AS_IS => true,
    //CURLOPT_PIPEWAIT => true,
    //CURLOPT_POST => true, //TRUE 时会发送 POST 请求，类型为：application/x-www-form-urlencoded，是 HTML 表单提交时最常见的一种。 
    //CURLOPT_PUT => true, // TRUE 时允许 HTTP 发送文件。要被 PUT 的文件必须在 CURLOPT_INFILE和CURLOPT_INFILESIZE 中设置。 
    //CURLOPT_RETURNTRANSFER => true,
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
    //CURLOPT_FTPSSLAUTH => 000,
    //CURLOPT_HEADEROPT => 000,
    //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_NONE,
    //CURLOPT_HTTPAUTH => 000,
    //CURLOPT_INFILESIZE => 000, // 希望传给远程站点的文件尺寸，字节(byte)为单位。 注意无法用这个选项阻止 libcurl 发送更多的数据，确切发送什么取决于 CURLOPT_READFUNCTION。 
    //CURLOPT_LOW_SPEED_LIMIT => 000, // 传输速度，每秒字节（bytes）数，根据CURLOPT_LOW_SPEED_TIME秒数统计是否因太慢而取消传输。 
    //CURLOPT_LOW_SPEED_TIME => 000, // 当传输速度小于CURLOPT_LOW_SPEED_LIMIT时(bytes/sec)，PHP会判断是否因太慢而取消传输。 
    //CURLOPT_MAXCONNECTS => 000,
    //CURLOPT_MAXREDIRS => 000, // 指定最多的 HTTP 重定向次数，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的。 
    //CURLOPT_PORT => 000,
    //CURLOPT_POSTREDIR => 000, //位掩码， 1 (301 永久重定向), 2 (302 Found) 和 4 (303 See Other) 设置 CURLOPT_FOLLOWLOCATION 时，什么情况下需要再次 HTTP POST 到重定向网址。
    //CURLOPT_PROTOCOLS => 000, // CURLPROTO_*的位掩码。 启用时，会限制 libcurl 在传输过程中可使用哪些协议。 这将允许你在编译libcurl时支持众多协议，但是限制只用允许的子集。默认 libcurl 将使用所有支持的协议。 参见CURLOPT_REDIR_PROTOCOLS。 可用的协议选项为： CURLPROTO_HTTP、 CURLPROTO_HTTPS、 CURLPROTO_FTP、 CURLPROTO_FTPS、 CURLPROTO_SCP、 CURLPROTO_SFTP、 CURLPROTO_TELNET、 CURLPROTO_LDAP、 CURLPROTO_LDAPS、 CURLPROTO_DICT、 CURLPROTO_FILE、 CURLPROTO_TFTP、 CURLPROTO_ALL。 
    //CURLOPT_PROXYAUTH => 000, //HTTP 代理连接的验证方式。使用在CURLOPT_HTTPAUTH中的位掩码。 当前仅仅支持 CURLAUTH_BASIC和CURLAUTH_NTLM。 
    //CURLOPT_PROXYPORT => 000, // 代理服务器的端口。端口也可以在CURLOPT_PROXY中设置。 
    //CURLOPT_PROXYTYPE => 000, // 可以是 CURLPROXY_HTTP (默认值) CURLPROXY_SOCKS4、 CURLPROXY_SOCKS5、 CURLPROXY_SOCKS4A 或 CURLPROXY_SOCKS5_HOSTNAME。 
    //CURLOPT_REDIR_PROTOCOLS => 000, //CURLPROTO_* 值的位掩码。如果被启用，位掩码会限制 libcurl 在 CURLOPT_FOLLOWLOCATION开启时，使用的协议。 默认允许除 FILE 和 SCP 外所有协议。 这和 7.19.4 前的版本无条件支持所有支持的协议不同。关于协议常量，请参照CURLOPT_PROTOCOLS。 
    //CURLOPT_RESUME_FROM => 000, //TODO 在恢复传输时，传递字节为单位的偏移量（用来断点续传）。 
    //CURLOPT_SSL_OPTIONS => 000,
    //CURLOPT_SSL_VERIFYHOST => 000,//设置为 1 是检查服务器SSL证书中是否存在一个公用名(common name)。译者注：公用名(Common Name)一般来讲就是填写你将要申请SSL证书的域名 (domain)或子域名(sub domain)。 设置成 2，会检查公用名是否存在，并且是否与提供的主机名匹配。 0 为不检查名称。 在生产环境中，这个值应该是 2（默认值）。 
    //CURLOPT_SSLVERSION => 000, //CURL_SSLVERSION_DEFAULT (0), CURL_SSLVERSION_TLSv1 (1), CURL_SSLVERSION_SSLv2 (2), CURL_SSLVERSION_SSLv3 (3), CURL_SSLVERSION_TLSv1_0 (4), CURL_SSLVERSION_TLSv1_1 (5) ， CURL_SSLVERSION_TLSv1_2 (6) 中的其中一个。 你最好别设置这个值，让它使用默认值。 设置为 2 或 3 比较危险，在 SSLv2 和 SSLv3 中有弱点存在。 
    //CURLOPT_STREAM_WEIGHT => 000, //TODO 设置 stream weight 数值 ( 1 和 256 之间的数字). 
    //CURLOPT_TIMECONDITION => 000,//TODO 设置如何对待 CURLOPT_TIMEVALUE。 使用 CURL_TIMECOND_IFMODSINCE，仅在页面 CURLOPT_TIMEVALUE 之后修改，才返回页面。没有修改则返回 "304 Not Modified" 头，假设设置了 CURLOPT_HEADER 为 TRUE。CURL_TIMECOND_IFUNMODSINCE则起相反的效果。 默认为 CURL_TIMECOND_IFMODSINCE。 
    //CURLOPT_TIMEOUT => 000, // 允许 cURL 函数执行的最长秒数。 
    //CURLOPT_TIMEOUT_MS => 000,
    //CURLOPT_TIMEVALUE => 000, //TODO 秒数，从 1970年1月1日开始。这个时间会被 CURLOPT_TIMECONDITION使。默认使用CURL_TIMECOND_IFMODSINCE。 
    //CURLOPT_MAX_RECV_SPEED_LARGE => 000, // 如果下载速度超过了此速度(以每秒字节数来统计) ，即传输过程中累计的平均数，传输就会降速到这个参数的值。默认不限速。 
    //CURLOPT_MAX_SEND_SPEED_LARGE => 000, // 如果上传的速度超过了此速度(以每秒字节数来统计)，即传输过程中累计的平均数 ，传输就会降速到这个参数的值。默认不限速。 
    //CURLOPT_SSH_AUTH_TYPES => 000,
    //CURLOPT_IPRESOLVE => 000,
    //CURLOPT_FTP_FILEMETHOD => 000, // 告诉 curl 使用哪种方式来获取 FTP(s) 服务器上的文件。可能的值有： CURLFTPMETHOD_MULTICWD、 CURLFTPMETHOD_NOCWD 和 CURLFTPMETHOD_SINGLECWD。 

    //CURLOPT_CAINFO => '',// 一个保存着1个或多个用来让服务端验证的证书的文件名。这个参数仅仅在和CURLOPT_SSL_VERIFYPEER一起使用时才有意义。
    //CURLOPT_CAPATH => '',// 一个保存着多个CA证书的目录。这个选项是和CURLOPT_SSL_VERIFYPEER一起使用的。 
    //CURLOPT_COOKIE => '',//TODO 设定 HTTP 请求中"Cookie: "部分的内容。多个 cookie 用分号分隔，分号后带一个空格(例如， "fruit=apple; colour=red")。 
    //CURLOPT_COOKIEFILE => '', //TODO 包含 cookie 数据的文件名，cookie 文件的格式可以是 Netscape 格式，或者只是纯 HTTP 头部风格，存入文件。如果文件名是空的，不会加载 cookie，但 cookie 的处理仍旧启用。 
    //CURLOPT_COOKIEJAR => '', //TODO 连接结束后，比如，调用 curl_close 后，保存 cookie 信息的文件。 
    //CURLOPT_CUSTOMREQUEST => '', //TODO HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。 不确定服务器支持这个自定义方法则不要使用它
    //CURLOPT_DEFAULT_PROTOCOL => '', //TODO URL不带协议的时候，使用的默认协议。 
    //CURLOPT_DNS_INTERFACE => '',
    //CURLOPT_DNS_LOCAL_IP4 => '',
    //CURLOPT_DNS_LOCAL_IP6 => '',
    //CURLOPT_EGDSOCKET => '',
    //CURLOPT_ENCODING => '', //TODO HTTP请求头中"Accept-Encoding: "的值。 这使得能够解码响应的内容。 支持的编码有"identity"，"deflate"和"gzip"。如果为空字符串""，会发送所有支持的编码类型。 
    //CURLOPT_FTPPORT => '',
    //CURLOPT_INTERFACE => '', //TODO 发送的网络接口（interface），可以是一个接口名、IP 地址或者是一个主机名。 
    //CURLOPT_KEYPASSWD => '',
    //CURLOPT_KRB4LEVEL => '',
    //CURLOPT_LOGIN_OPTIONS => '',
    //CURLOPT_PINNEDPUBLICKEY => '',
    //CURLOPT_POSTFIELDS => '', //TODO 全部数据使用HTTP协议中的 "POST" 操作来发送。 要发送文件，在文件名前面加上@前缀并使用完整路径。 文件类型可在文件名后以 ';type=mimetype' 的格式指定。 这个参数可以是 urlencoded 后的字符串，类似'para1=val1&para2=val2&...'，也可以使用一个以字段名为键值，字段数据为值的数组。 如果value是一个数组，Content-Type头将会被设置成multipart/form-data。 从 PHP 5.2.0 开始，使用 @ 前缀传递文件时，value 必须是个数组。 从 PHP 5.5.0 开始, @ 前缀已被废弃，文件可通过 CURLFile 发送。 设置 CURLOPT_SAFE_UPLOAD 为 TRUE 可禁用 @ 前缀发送文件，以增加安全性。 
    //CURLOPT_PRIVATE => '',
    //CURLOPT_PROXY => '',
    //CURLOPT_PROXY_SERVICE_NAME => '',
    //CURLOPT_PROXYUSERPWD => '',
    //CURLOPT_RANDOM_FILE => '', //TODO 一个被用来生成 SSL 随机数种子的文件名。 
    //CURLOPT_RANGE => '', //TODO 以"X-Y"的形式，其中X和Y都是可选项获取数据的范围，以字节计。HTTP传输线程也支持几个这样的重复项中间用逗号分隔如"X-Y,N-M"。 
    //CURLOPT_REFERER => '', //TODO 在HTTP请求头中"Referer: "的内容。 
    //CURLOPT_SERVICE_NAME => '',
    //CURLOPT_SSH_HOST_PUBLIC_KEY_MD5 => '',
    //CURLOPT_SSH_PUBLIC_KEYFILE => '',
    //CURLOPT_SSH_PRIVATE_KEYFILE => '',
    //CURLOPT_SSL_CIPHER_LIST => '',
    //CURLOPT_SSLCERT => '',
    //CURLOPT_SSLCERTPASSWD => '',
    //CURLOPT_SSLCERTTYPE => '',
    //CURLOPT_SSLENGINE => '',
    //CURLOPT_SSLENGINE_DEFAULT => '',
    //CURLOPT_SSLKEY => '',
    //CURLOPT_SSLKEYPASSWD => '',
    //CURLOPT_SSLKEYTYPE => '',
    //CURLOPT_UNIX_SOCKET_PATH => '',
    //CURLOPT_URL => '',
    //CURLOPT_USERAGENT => '',
    //CURLOPT_USERNAME => '',
    //CURLOPT_USERPWD => '', //TODO 传递一个连接中需要的用户名和密码，格式为："[username]:[password]"。 
    //CURLOPT_XOAUTH2_BEARER => '', //TODO

    //CURLOPT_CONNECT_TO => [], //TODO  连接到指定的主机和端口，替换 URL 中的主机和端口。接受指定字符串格式的数组： HOST:PORT:CONNECT-TO-HOST:CONNECT-TO-PORT。 
    //CURLOPT_HTTP200ALIASES => [], //TODO  HTTP 200 响应码数组，数组中的响应码被认为是正确的响应，而非错误。 
    //CURLOPT_HTTPHEADER => [], //TODO 设置 HTTP 头字段的数组。格式： array('Content-type: text/plain', 'Content-length: 100') 
    //CURLOPT_POSTQUOTE => [],
    //CURLOPT_PROXYHEADER => [],
    //CURLOPT_QUOTE => [],
    //CURLOPT_RESOLVE  => [], //TODO 提供自定义地址，指定了主机和端口。 包含主机、端口和 ip 地址的字符串，组成 array 的，每个元素以冒号分隔。格式： array("example.com:80:127.0.0.1") 

    //CURLOPT_FILE => res,
    //CURLOPT_INFILE => res,
    //CURLOPT_STDERR => res,
    //CURLOPT_WRITEHEADER => res,

    //CURLOPT_HEADERFUNCTION
    //CURLOPT_PASSWDFUNCTION
    //CURLOPT_PROGRESSFUNCTION
    //CURLOPT_READFUNCTION
    //CURLOPT_WRITEFUNCTION

    //CURLOPT_SHARE

  ];//}}}


  function __construct(){
    static::$handle = curl_init();
    static::$cookie = tempnam('/tmp','xlxx');
  }

  function __destruct(){
    curl_close(static::$handle);
  }


  final private static function response(array $opts=[]){//{{{

    return new class($opts) extends request{

      private $header, $body;

      function __construct($opts){

        $set = curl_setopt_array(static::$handle, static::$opts+$opts+[
          CURLOPT_PROTOCOLS=>CURLPROTO_HTTP|CURLPROTO_HTTPS,
          CURLOPT_RETURNTRANSFER=>true,
          CURLOPT_HEADER=>false,
          CURLOPT_WRITEHEADER => $header=fopen('php://temp','r+b'),
          CURLOPT_FILE => $this->body=fopen('php://temp','r+b'),

          CURLOPT_COOKIEJAR => static::$cookie,
          CURLOPT_COOKIEFILE => static::$cookie,

          CURLOPT_AUTOREFERER=>true,
          //CURLOPT_HEADEROPT=>CURLHEADER_SEPARATE,//FIXME 7.1.8
          CURLOPT_FOLLOWLOCATION=>true,
          CURLINFO_HEADER_OUT=>true,
          CURLOPT_CONNECTTIMEOUT=>6,
        ]);


        $exec = curl_exec(static::$handle);
        //foreach(curl_getinfo($handle) as $k=>$v)
          //$this->$k = $v;

        if($exec){
          rewind($header);
          foreach(request::http_response_header(explode("\r\n", stream_get_contents($header))) as $k=>$v)
            $this->header[$k] = $v;
          fclose($header);
        }else
          throw new \RuntimeException(curl_error(static::$handle)?:'opt err.',curl_errno(static::$handle));
      }


      function __destruct(){
        fclose($this->body);
      }


      function __toString(){
        return $this->body();
      }


      function har():string{//TODO
        return json_encode([]);
      }


      function header(string $key=''){
        return $key?array_change_key_case($this->header)[strtolower(trim($key))]??null:$this->header;
      }


      function body():string{
        rewind($this->body);
        return stream_get_contents($this->body);
      }


      function stream(){
        rewind($this->body);
        return $this->body;
      }

    };

  }//}}}


  final static function GET(string $url){
    return static::response([
      CURLOPT_URL=>self::normalize($url),
      CURLOPT_NOBODY => false,
    ]);
  }


  final function ping():object{
    return $this->response([
      CURLOPT_CONNECT_ONLY => true,
      CURLOPT_URL => self::normalize($url),
      CURLOPT_NOBODY => true,
    ]);
  }


  final function upload(string $url, \CURLFile ...$file):object{
    return $this->response([
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_PUT => true,
      CURLOPT_UPLOAD => true,
      CURLOPT_POSTFIELDS => $file,
      CURLOPT_URL => self::normalize($url),
      CURLOPT_NOBODY => false,
    ]);
  }


  final function POST(string $url, string $body=null):object{
    return $this->response([
      CURLOPT_CUSTOMREQUEST => __FUNCTION__,
      CURLOPT_POST => true, //FIXME 
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_URL => self::normalize($url),
      CURLOPT_NOBODY => false,
      CURLOPT_HTTPHEADER => ['Content-Length: '.strlen($body)],
    ]);
  }


  final function PUT(string $url, string $body=null):object{
    return $this->response([
      CURLOPT_CUSTOMREQUEST => __FUNCTION__,
      CURLOPT_POST => true,
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_URL => self::normalize($url),
      CURLOPT_NOBODY => false,
      CURLOPT_HTTPHEADER => ['Content-Length: '.strlen($body)],
    ]);
  }


  final function PATCH(string $url, string $body=null):object{
    return $this->response([
      CURLOPT_CUSTOMREQUEST=>__FUNCTION__,
      CURLOPT_POSTFIELDS=>$body,
      CURLOPT_URL => self::normalize($url),
      CURLOPT_NOBODY => false,
      CURLOPT_HTTPHEADER => ['Content-Length: '.strlen($body)],
    ]);
  }


  final function DELETE(string $url, string $body=null):object{
    return $this->response([
      CURLOPT_CUSTOMREQUEST=>__FUNCTION__,
      CURLOPT_POSTFIELDS=>$body,
      CURLOPT_URL => self::normalize($url),
      CURLOPT_NOBODY => false,
      CURLOPT_HTTPHEADER => ['Content-Length: '.strlen($body)],
    ]);
  }


  final function HEAD(string $url):object{
    return $this->response([
      CURLOPT_CUSTOMREQUEST, __FUNCTION__,
      CURLOPT_NOBODY => true,
      CURLOPT_URL => self::normalize($url),
    ]);
  }


  final function OPTIONS(string $url):object{
    return $this->response([
      CURLOPT_CUSTOMREQUEST, __FUNCTION__,
      CURLOPT_NOBODY => false,
      CURLOPT_URL => self::normalize($url),
    ]);
  }


  /**
   * https://url.spec.whatwg.org/#example-url-parsing
   */
  final static function normalize(string $url):string{#{{{

    if(isset(
      $_SERVER['REQUEST_SCHEME'],
      $_SERVER['REQUEST_HOST'],
      $_SERVER['REQUEST_PORT'],
      $_SERVER['REQUEST_URI'],
      $_SERVER['SERVER_PORT']
    ) || PHP_SAPI ==='cli')
      return $url;

    $arr = parse_url($url);
    if($arr===false) return '';

    $a = [
      $arr['scheme']??$_SERVER['REQUEST_SCHEME'],
      '://',
      $arr['user']??'',
      ':',
      $arr['pass']??'',
      '@',
      $arr['host']??$_SERVER['HTTP_HOST'],
      ':',
      $arr['port']??$_SERVER['SERVER_PORT'],
      substr($_SERVER['REQUEST_URI'],0, strrpos($_SERVER['REQUEST_URI'],'/')+1),
      $arr['path']??'',
      '?',
      $arr['query']??'',
      '#',
      $arr['fragment']??'',
    ];

    if(!in_array($a[0], ['http','https'])) return '';

    if($a[12]==='')            unset($a[11],$a[12]);
    if($a[14]==='')            unset($a[13],$a[14]);
    if($a[8]==80)              unset($a[7],$a[8]);
    if($a[4]==='')             unset($a[3],$a[4]);
    if($a[2]==='')             unset($a[2],$a[3],$a[4],$a[5]);
    if(strpos($a[10],'/')===0) unset($a[9]);

    while(strpos($a[10],'../')===0){
      $a[10] = substr($a[10],3);
      $a[9] = dirname($a[9]);
      if($a[9]!=='/') $a[9] .= '/';
    }

    while(strpos($a[10],'./')===0){
      $a[10] = substr($a[10],2);
    }

    if($a[10]==='.') unset($a[10]);

    return implode($a);
  }#}}}


  /**
   * @see <HTTP: The Definitive Guide> P398
   */
  final static function q(string $str=null):array{#{{{
    if(empty($str)) return [];
    $result = $tmp = [];
    foreach(explode(',',$str) as $item){
      if(strpos($item,';')===false){
        $tmp[] = $item;
      }else{
        $tmp[] = strstr($item,';',true);
        $q = filter_var(explode('q=',$item)[1], FILTER_VALIDATE_FLOAT);
        if($q!==false&&$q>0&&$q<=1)
          foreach($tmp as $v)
            $result[$v] = $q;
        $tmp = [];
      }
    }
    $result += array_fill_keys(array_filter(array_map('trim',$tmp)),0.5);
    arsort($result);
    return $result;
  }#}}}


  final static function http_response_header(?array $http_response_header):\Generator{
    foreach(array_filter($http_response_header,'is_string') as $header)
      if(preg_match_all('/(?P<key>[\w-]+): (?P<val>.*)$/U',$header, $out))
        yield $out['key'][0] => $out['val'][0];
  }

}
