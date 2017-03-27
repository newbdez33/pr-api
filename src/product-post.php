<?php
use Ramsey\Uuid\Uuid;

function putItem($item) {
	global $db;
	$uuid = Uuid::uuid4();
	$data = array(
	        'id'			=> array('S' => $uuid->toString()),
	        'created_at'	=> array('N' => time()),
	        'title'			=> array('S' => $item["title"]),
	        'url'			=> array('S' => $item["url"]),
	        'asin'			=> array('S' => $item["asin"]),
	        'aac'			=> array('S' => $item["aac"])
	    );
	
	$result = $db->putItem(array(
	    'TableName' => 'products',
	    'Item' => $data
	));

	if ( $result ) {
		return $uuid;
	}else {
		return "";
	}

	//TODO SQS add a fetch job
}

$app->post('/p', function ($request, $response, $args) {
	
    $parsedBody = $request->getParsedBody();
    $url = $parsedBody['url'];
    $title = $parsedBody['title'];
    
    try {
        //https://github.com/schmiddim/amazon-asin-parser
        $fetcher = new \Amazon\AsinParser($url);

        $item["asin"]	= $fetcher->getAsin();
        $item["aac"]	= $fetcher->getTld();
        $item["url"]	= $url;
    	$item["title"]	= $title;

        $uuid = putItem($item);
        if ( $uuid != "" ) {
        	$data["pid"] = $uuid;
			$data["raw"] = $item;
        	$data["error"] = "success";
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