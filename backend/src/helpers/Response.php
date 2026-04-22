<?php

class JsonResponse
{
    public static function success($data = null, $message = null)
    {
        http_response_code(200);
        $response = ['status' => 'ok'];
        if ($data !== null)
            $response['data'] = $data;
        if ($message !== null)
            $response['message'] = $message;
        echo json_encode($response);
    }

    public static function error($message, $code = 500)
    {
        http_response_code($code);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
    }

    public static function created($message, $data = null)
    {
        http_response_code(201);
        echo json_encode([
            'status' => 'ok',
            'message' => $message,
            'data' => $data
        ]);
    }
}

// JsonResponse::success(null, 'Transition updated');
// JsonResponse::success($data);
//  JsonResponse::error('Transition not found', 404);
// JsonResponse::error('No fields to update', 400);