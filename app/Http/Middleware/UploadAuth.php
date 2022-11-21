<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Log;

class UploadAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $matches = $this->resolve($request->header('Authorization'));
        // 验证用户有效性
        if (!$matches || !array_key_exists($matches[1], config('upload.username'))) {
            return response()->json(['error' => 'Unauthenticated.', 'err_code' => 401], 401);
        }

        $username = $matches[1];
        $password = config('upload.username.' . $username);
        $sign = $matches[2];

        if(!$this->checkSign($sign, $password, $request)) {
            return response()->json(['error' => 'Unauthenticated.', 'err_code' => 401], 401);
        }
        $request->query->add(['username' => $username]);
        return $next($request);
    }

    private function resolve($header)
    {
        if (!preg_match('/(.*):(.*)/', $header, $matches)) {
            return false;
        }
        return $matches;
    }

    private function checkSign($sign, $password, Request $request)
    {
        $timestamp = $request->header('Date');
        $data = [$timestamp, $password];
        $userSign = md5(implode('&', $data));

        if ($userSign != $sign) {
            return false;
        }
        return true;
    }
}
