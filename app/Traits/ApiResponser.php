<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

trait ApiResponser{

    protected function token($personalAccessToken, $message = null, $code = 200)
    {
        $tokenData = [
            'access_token' => $personalAccessToken->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse($personalAccessToken->token->expires_at)->toDateTimeString(),
            'user' => Auth::user()
        ];

        return $this->success($message, $tokenData, $code);
    }

    protected function success($message = null, $data = null, $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    protected function error($message = null, $code = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message
        ], $code);
    }

     /**
     * Build success response
     * @param  ** string|array $data
     * @param ** int $code
     * @return Illuminate\Http\JsonResponse
     */
    public function successObject($data="", $msg="OK")
    {
      return  $response = [
          'status' => true,
          'message' => $msg,
          'data' => $data,
      ];
    }
}
