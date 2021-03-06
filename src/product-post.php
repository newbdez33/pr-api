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

$app->get('/ping', function ($request, $response, $args) {
    global $q;

    $r = $q->sendMessage(array(
        "QueueUrl" => "https://sqs.ap-northeast-1.amazonaws.com/426901641069/fetch_jobs",
        "MessageBody" => "pong"
    ));
    $msid = $r['MessageId'];
    $data["worker"] = $msid;

    $r2 = $q->sendMessage(array(
        "QueueUrl" => "https://sqs.ap-northeast-1.amazonaws.com/426901641069/daily_queue",
        "MessageBody" => "pong"
    ));
    $msid2 = $r2['MessageId'];
    $data["bot"] = $msid2;
    
    $newResponse = $response->withJson($data);
    return $newResponse;
});

$app->post('/p', function ($request, $response, $args) {
	global $db;
    $parsedBody = $request->getParsedBody();
    $url = $parsedBody['url'];

    //slack
    slack_post_url_notify($url);

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
    	    slack_post_url_notify(print_r($data, true));
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

    //slack_post_url_notify(print_r($data, true));
    $newResponse = $response->withJson($data);
    return $newResponse;
});

function slack_post_url_notify($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://hooks.slack.com/services/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");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"text\":\"POST: {$url}\"}");
    curl_setopt($ch, CURLOPT_POST, 1);

    $headers = array();
    $headers[] = "Content-Type: application/x-www-form-urlencoded";
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close ($ch);
}
