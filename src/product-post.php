<?php
use Aws\DynamoDb\Marshaler;

function putItem($item) {
	global $db;

	$marshaler = new Marshaler();
    $data = $marshaler->marshalItem($item);
	$result = $db->putItem(array(
	    'TableName' => 'products_amazon',
	    'Item' => $data
	));

    return $result;

	//TODO SQS add a fetch job
}

$app->post('/p', function ($request, $response, $args) {
	global $db;
    $parsedBody = $request->getParsedBody();
    $url = $parsedBody['url'];
    $title = $parsedBody['title'];
    if ( is_null($title )) {
        $title = "商品抓取中";
    }
    if ( in_array('photo', $parsedBody)) $photo = $parsedBody['photo'];
    if ( in_array('currency', $parsedBody)) $currency = $parsedBody['currency'];
    
    try {

        //https://github.com/schmiddim/amazon-asin-parser
        $fetcher = new \Amazon\AsinParser($url);

        $item["asin"]       = $fetcher->getAsin();
        $result = getItem($item["asin"]);
        if ( is_object($result) && is_array($result["Item"]) ) {
            $data = jsonObjectFromItem($result["Item"]);
            $newResponse = $response->withJson($data);
            return $newResponse;
        }

        $item["aac"]        = $fetcher->getTld();
        $item["url"]        = $url;
    	$item["title"]      = $title;
        if ( !is_null($photo )) {
            $item["photo"]      = $photo;
        }
        if ( !is_null($currency )) {
            $item["currency"]      = $currency;
        }
        $item["created_at"] = time();
        $result = putItem($item);
        if ( $result ) {
        	$data = $item;
        }else {
        	$data["error"] = "data error, please try again.";
        }

    }catch( Exception $e ) {
        // print_r($e); exit;
        $data["url"] = $url;
        $data["error"] = $e->getMessage();
    }

    $newResponse = $response->withJson($data);
    return $newResponse;
});