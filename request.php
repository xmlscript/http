<?php namespace http; // vim: se fdm=marker:

class request{

  private $handle, $header=[];

  private const CURL_SETOPT_ARRAY = [
      CURLOPT_AUTOREFERER=>true,
      CURLOPT_HEADEROPT=>CURLHEADER_SEPARATE,
      CURLOPT_FOLLOWLOCATION=>true,
      CURLINFO_HEADER_OUT=>true,
      CURLOPT_CONNECTTIMEOUT=>6,
    ];


  final function __construct(string $url){





    curl_setopt_array(
      $this->handle=curl_init(self::normalize($url)), [
      //CURLOPT_AUTOREFERER=>true,
      CURLOPT_HEADEROPT=>CURLHEADER_SEPARATE,
      CURLOPT_FOLLOWLOCATION=>true,
      CURLINFO_HEADER_OUT=>true,
      CURLOPT_CONNECTTIMEOUT=>6,
    ]);
  }


  final function __destruct(){
    curl_close($this->handle);
  }


  final private function handle(){
    return curl_copy_handle($this->handle);
  }


  final static function url(string $url):self{
    return new self($url);
  }


  final private function setopt(int $opt, $value):self{
    curl_setopt($this->handle, $opt, $value);
    return $this;
  }


  final private function setopt_array(array $arr):self{
    curl_setopt_array($this->handle, $arr);
    return $this;
  }


  final function header(string $key, ?string ...$value):self{
    return $this->setopt(CURLOPT_HTTPHEADER, $this->header=array_replace($this->header,[strtolower(trim($key))=>trim($key).': '.implode(', ',array_unique(array_filter($value)))]));
  }


  final function proxy(string $addr):self{
    return $this->setopt(CURLOPT_PROXY, $addr);
  }


  final function timeout(int $sec=30):self{
    return $this->setopt(CURLOPT_TIMEOUT, $sec);
  }


  final function referrer(string $referer='about:client'):self{
    return $this->setopt(CURLOPT_REFERER, $referer);
  }


  final function accept(string ...$mime):self{
    return $this->header('Accept', ...$mime);
  }


  final function language(string $lang, string ...$language):self{
    return $this->header('Accept-Language', $lang, ...$language);
  }


  final function ua(string $ua):self{
    return $this->setopt(CURLOPT_USERAGENT, $ua);
  }


  final function query(array $q):self{
    return $this->setopt(CURLOPT_URL, strstr(curl_getinfo($this->handle,CURLINFO_EFFECTIVE_URL).'?','?',true).'?'.http_build_query($q,'','&',PHP_QUERY_RFC3986));
  }


  final static function har(\stdClass $har){
    return (new self)->curl_setopt_array([
      CURLOPT_CUSTOMREQUEST=>$har->method,
      CURLOPT_URL=>$har->url,
      CURLOPT_HTTPHEADER=>$har->headers,
    ])->response();
  }


  final function fetch(array $query=[]){
    return $this->query($query)->response();
  }


  final private function response(){

    return new class(curl_copy_handle($this->handle)){

      private $header, $body, $cookie;

      final function __construct($handle){
        curl_setopt_array($handle,[
          CURLOPT_PROTOCOLS=>CURLPROTO_HTTP|CURLPROTO_HTTPS,
          CURLOPT_RETURNTRANSFER=>true,
          CURLOPT_HEADER=>false,
          CURLOPT_WRITEHEADER => $header=fopen('php://temp','r+b'),
          CURLOPT_FILE => $this->body=fopen('php://temp','r+b'),
          CURLOPT_COOKIEJAR => $this->cookie = fopen('php://temp','r+b'),
          CURLOPT_COOKIEFILE => $this->cookie,
        ]);

        $exec = curl_exec($handle);
        foreach(curl_getinfo($handle) as $k=>$v)
          $this->$k = $v;
        curl_close($handle);

        if($exec){
          rewind($header);
          foreach(request::http_response_header(explode("\r\n", stream_get_contents($header))) as $k=>$v)
            $this->header[$k] = $v;
          fclose($header);
        }else
          throw new \RuntimeException(curl_error($handle),curl_errno($handle));
      }


      final function __destruct(){
        fclose($this->body) and fclose($this->cookie);
      }


      final function __toString(){
        return $this->body();
      }


      final function har():string{//TODO
        return json_encode([]);
      }


      final function header(string $key=''){
        return $key?array_change_key_case($this->header)[strtolower(trim($key))]??null:$this->header;
      }


      final function body():string{
        rewind($this->body);
        return stream_get_contents($this->body);
      }


      final function stream(){
        rewind($this->body);
        return $this->body;
      }


      final function json(){
        return json_decode($this->body());
      }


      final function xml():?\SimpleXMLElement{
        libxml_use_internal_errors(true);
        return simplexml_load_string($this->body())?:null;
      }

    };

  }



  final function GET(){
    return $this->response();
  }


  final function ping(){
    return $this->setopt(CURLOPT_CONNECT_ONLY,true)->response();
  }


  final function upload(\CURLFile ...$file){
    return $this->setopt_array([
      CURLOPT_CUSTOMREQUEST=>'POST',
      CURLOPT_PUT=>true,
      CURLOPT_UPLOAD=>true,
      CURLOPT_POSTFIELDS=>$file,
    ])->response();
  }


  final function POST(string $body=null){
    return $this->setopt_array([
      CURLOPT_CUSTOMREQUEST=>__FUNCTION__,
      CURLOPT_POST=>true,
      CURLOPT_POSTFIELDS=>$body,
    ])->response();
  }


  final function PUT(string $body=null){
    return $this->setopt_array([
      CURLOPT_CUSTOMREQUEST=>__FUNCTION__,
      CURLOPT_POST=>true,
      CURLOPT_HTTPHEADER=>['Content-Length: '.strlen($body)],
      CURLOPT_POSTFIELDS=>$body,
    ])->response();
  }


  final function PATCH(string $body=null){
    return $this->setopt_array([
      CURLOPT_CUSTOMREQUEST=>__FUNCTION__,
      CURLOPT_POSTFIELDS=>$body,
    ])->response();
  }


  final function DELETE(string $body=null){
    return $this->setopt_array([
      CURLOPT_CUSTOMREQUEST=>__FUNCTION__,
      CURLOPT_POSTFIELDS=>$body,
    ])->response();
  }


  final function HEAD(){
    return $this->setopt($this->handle, CURLOPT_NOBODY, true)->response();
  }


  final function OPTIONS(){
    return $this->setopt($this->handle, CURLOPT_CUSTOMREQUEST, __FUNCTION__)->response();
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
