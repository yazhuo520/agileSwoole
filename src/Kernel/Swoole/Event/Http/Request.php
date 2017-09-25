<?php


namespace Kernel\Swoole\Event\Http;

use Kernel\Core;
use Kernel\Swoole\Event\Event;
use Kernel\Swoole\Event\EventTrait;

class Request implements Event
{
        use EventTrait;
        /* @var  \swoole_http_server $server*/

        protected $server;
        protected $request;
        protected $response;

        public function __construct(\swoole_http_server $server)
        {
                $this->server = $server;
        }

        public function doEvent(\swoole_http_request $request, \swoole_http_response $response)
        {
                if(isset($request->server['request_uri']) and $request->server['request_uri'] == '/favicon.ico') {
                        $response->end(json_encode(['code'=>0]));
                        return;
                }
                $_POST = json_decode($request->rawContent(), true);
                try {
                        $data = ['code'=>0,'response'=> Core::getInstant()->get('route')->dispatch(
                                $request->server['request_uri'],strtolower($request->server['request_method'])
                        )];
                }catch (\Exception $exception) {
                        $data = ['code'=>$exception->getCode()>0?$exception->getCode():1, 'response'=>$exception->getMessage()];
                }
                if(isset($data['response']['view'])) {
                        $res = $data['response'];
                        if(isset($res['response'])) {
                                $res['response_alisa'] = $res['response'];
                                unset($res['response']);
                        }
                        extract($res);
                        ob_start();
                        include ($data['response']['view']);
                        $content = ob_get_contents();
                        ob_clean();
                } else {
                        $content = json_encode($data);
                }
                $response->end($content);
        }

        public function doClosure()
        {
                if($this->callback != null) {
                        $this->params = [$this->request, $this->response];
                        return call_user_func_array($this->callback, $this->params);
                }
                return ['code'=>0];
        }
}