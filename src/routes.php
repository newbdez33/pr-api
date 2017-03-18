<?php
require "../aws.inc.php";

use ApaiIO\Configuration\GenericConfiguration;
use ApaiIO\ApaiIO;
use ApaiIO\Operations\Lookup;
// Routes

$app->get('/[{name}]', function ($request, $response, $args) {
    // Sample log message
    $this->logger->info("Slim-Skeleton '/' route");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $args);
});

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

$app->get('/p/{pid}', function ($request, $response, $args) {
	global $fake_data;
	$pid = $request->getAttribute('pid');
	$fake_data["pid"] = $pid;
    $newResponse = $response->withJson($fake_data);
    return $newResponse;
});

$app->post('/p', function ($request, $response, $args) {
	
    $parsedBody = $request->getParsedBody();
    $q = $parsedBody['q'];
    $data = array("q"=>$q);
    try {
        $fetcher = new \Amazon\AsinParser($q);
        $data["asin"] = $fetcher->getAsin();
        $data["aac"] = $fetcher->getTld();

    }catch( Exception $e ) {
        // print_r($e); exit;
        $data["error"] = $e->getMessage();
    }
    
    try {
        $conf = new GenericConfiguration();
        $client = new \GuzzleHttp\Client();
        $req = new \ApaiIO\Request\GuzzleRequest($client);

        $conf
            ->setCountry('co.jp')
            ->setAccessKey(AWS_ASSESS_KEY)
            ->setSecretKey(AWS_SECRET_KEY)
            ->setAssociateTag(AWS_ASSOCIATE_TAG)
            ->setRequest($req);
    } catch ( Exception $e ) {
        $data["error"] = $e->getMessage();
    }

    $apaiIo = new ApaiIO($conf);
    $lookup = new Lookup();
    $lookup->setItemId($data["asin"]);
    $lookup->setResponseGroup(array('Small', 'Offers')); // More detailed information
    $r = $apaiIo->runOperation($lookup);
    print_r($r); exit;

    $newResponse = $response->withJson($data);
    return $newResponse;
});