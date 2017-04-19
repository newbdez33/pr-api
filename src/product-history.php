<?php
use Aws\DynamoDb\Marshaler;

function getItems($pid) {
    global $db;
    $result = $db->query(array(
        'TableName' => 'prices_history',
        'KeyConditionExpression' => "asin = :a",
        'ExpressionAttributeValues' => array(
                ":a" => ['S' => $pid],
        ),
    ));
    return $result;
}


$app->get('/h/{pid}', function ($request, $response, $args) {
	
	$pid = $request->getAttribute('pid');
    try {
        $result = getItems($pid);
    }catch(Exception $e) {
        //TODO
        echo $e->getResponse(); exit;
        print_r($e); exit;
    }
    
    print_r($result); exit;
    if ( is_object($result) && is_array($result["Item"]) ) {
        $data = jsonObjectFromItem($result["Item"]);
    }else {
        $data["error"] = "the product is not found.";
    }
	
    $newResponse = $response->withJson($data);
    return $newResponse;
});