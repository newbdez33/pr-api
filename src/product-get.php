<?php
use Aws\DynamoDb\Marshaler;

$fake_data = array(
        "acc" => "co.jp",
        "asin" => "asin",
    	"photo" => "http://www.fakeimage.com/1.png",
    	"created_at" => "2015-08-05T08:40:51.620Z",
    	"title" => "サッポロ 麦とホップ 350ml×24本",
    	"currency" => "JPY",
    	"prices" => array(
        	"highest" => 2767,
        	"lowest" => 2440,
        	"average" => 2748,
        	"current" => 2767
        ),
    	"history" => array(
            "2017-01-01" => 2766,
            "2017-01-02" => 2765,
            "2017-01-03" => 2764,
            "2017-01-04" => 2763
    	)
);

function jsonObjectFromItem($item) {
    $marshaler = new Marshaler();
    $data = $marshaler->unmarshalItem($item);
    return $data;
}

function getItem($pid) {
    global $db;
    $result = $db->getItem(array(
        // 'ConsistentRead' => true,
        'TableName' => 'products_amazon',
        'Key'       => array(
            'asin'   => array('S' => $pid)
        )
    ));
    return $result;
}


$app->get('/p/{pid}', function ($request, $response, $args) {
	
	$pid = $request->getAttribute('pid');
    $result = getItem($pid);
    // print_r($result); exit;
    if ( is_object($result) && is_array($result["Item"]) ) {
        $data = jsonObjectFromItem($result["Item"]);
    }else {
        $data["error"] = "the product is not found.";
    }
	
    $newResponse = $response->withJson($data);
    return $newResponse;
});