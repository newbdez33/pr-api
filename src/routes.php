<?php
// Routes

$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

$fake_data = array(
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

$app->get('/p/{pid}', function ($request, $response, $args) {
	global $fake_data;
	$pid = $request->getAttribute('pid');
	$fake_data["pid"] = $pid;
    $newResponse = $response->withJson($fake_data);
    return $newResponse;
});

$app->post('/p', function ($request, $response, $args) {
	global $fake_data;
    $parsedBody = $request->getParsedBody();
    $q = $parsedBody['q'];
    $fake_data["pid"] = "0";
    $fake_data["q"] = $q;
    $newResponse = $response->withJson($fake_data);
    return $newResponse;
});