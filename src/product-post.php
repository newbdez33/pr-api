<?php
use Aws\DynamoDb\Marshaler;
use Aws\Sqs\SqsClient;

$q = SqsClient::factory(array(
    'credentials' => array(
        'key'    => J_ASSESS_KEY,
        'secret' => J_SECRET_KEY,
    ),
    'region' => 'ap-northeast-1'
));

function putItem($item) {
	global $db, $q;

    $r = $q->sendMessage(array(
        "QueueUrl" => "https://sqs.ap-northeast-1.amazonaws.com/426901641069/fetch_jobs",
        "MessageBody" => json_encode($item)
    ));
    $msid = $r['MessageId'];
    //TODO log & error handling

	$marshaler = new Marshaler();
    $data = $marshaler->marshalItem($item);
	$result = $db->putItem(array(
	    'TableName' => 'products_amazon',
	    'Item' => $data
	));
    //Error handling

    return $result;
}

$app->post('/p', function ($request, $response, $args) {
	global $db;
    $parsedBody = $request->getParsedBody();
    $url = $parsedBody['url'];
    $url = str_replace("/gp/aw/d/", "/dp/", $url);  //convert mobile url to desktop url
    $this->logger->info("p: {$url}");
    
    try {

        //https://github.com/schmiddim/amazon-asin-parser
        $fetcher = new \Amazon\AsinParser($url);

        $item["asin"]       = $fetcher->getAsin();
        $item["url"]        = $url;
        $result = getItem($item["asin"]);
        if ( is_object($result) && is_array($result["Item"]) ) {

            $obj = jsonObjectFromItem($result["Item"]);
            if ( $obj["title"] != "" ) {
                $data = $obj;
            }
            $data["asin"] = $item["asin"];
            $newResponse = $response->withJson($data);
            return $newResponse;
            
        }
        $item["aac"]        = $fetcher->getTld();
        $item["created_at"] = time();
        $item["updated_at"] = time();
        $result = putItem($item);

        if ( $result ) {
        	$data["asin"] = $item["asin"];
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