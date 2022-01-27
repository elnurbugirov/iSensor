<?php


namespace App\Traits;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Trait ApiResponder
 * @package App\Traits
 */
trait ApiResponder
{

    /**
     * @param $message
     * @param int $code
     * @return JsonResponse
     */
    public function successResponse($message, $code = Response::HTTP_OK)
    {
        return response()->json(['success' => $message], $code);
    }

    /**
     * @param $message
     * @param int $code
     * @return JsonResponse
     */
    public function errorResponse($message, $code = Response::HTTP_BAD_REQUEST)
    {
        return response()->json(['error' => $message], $code);
    }

    /**
     * @param $data
     * @param int $code
     * @return JsonResponse
     */
    public function dataResponse($data, $code = Response::HTTP_OK)
    {
        return response()->json($data, $code);
    }
}
