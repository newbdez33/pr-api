<?php
require "../aws.inc.php";
use Aws\DynamoDb\DynamoDbClient;

//init db
$db = DynamoDbClient::factory(array(
    'credentials' => array(
        'key'    => J_ASSESS_KEY,
        'secret' => J_SECRET_KEY,
    ),
    'region' => 'ap-northeast-1'
));

// Routes
$app->get('/[{name}]', function ($request, $response, $args) {
    // global $db;

    // // Sample log message
    // $this->logger->info("Main '/' route");

    // $result = $db->scan(array(
    //     'TableName' => 'products'
    // ));
    // print_r($result); exit;
    // $desc = "当前正在追踪 {$result['Table']['ItemCount']} 个商品";
    // $args["desc"] = $desc;
    // Render index view
    return $this->renderer->render($response, 'index.phtml', ["desc" => $desc]);
});


include "product-get.php";
include "product-post.php";