<?php
require '../sources/autoload.php';
require '../sources/config.php';

$requestPathParts = array();
if (isset($_SERVER['PATH_INFO']))
  $requestPathParts = explode('/', $_SERVER['PATH_INFO']);

// GET /api/v1/play/STATE
//  <- {"status":"askquestion","question":TEXT,"choices":[{"name":NAME,"state":STATE},...]}
//  <- {"status":"makeguess","guess":GUESS,"object":OBJID,"stateIfWrong":STATE}
//  <- {"status":"giveup","state":STATE}
if (count($requestPathParts) == 1) {
  $query = isset($_GET['state']) ? $_GET['state']: ''; #TODO urlencode or anything here?
  $NQ = new NineteenQuestions($query);

}



###########

if (isset($_GET['query']))  {
//    $accountKey = '...';
    $ServiceRootURL =  'https://api.datamarket.azure.com/Bing/Search/v1/';
    $WebSearchURL = $ServiceRootURL . 'Image?$format=json&Query=';
    $cred = sprintf('Authorization: Basic %s', base64_encode($accountKey . ":" . $accountKey) );
    $context = stream_context_create(array(
        'http' => array('header' => $cred)
    ));
    $request = $WebSearchURL . urlencode( '\'' . $_GET["query"] . '\'');
    $response = file_get_contents($request, 0, $context);
    $jsonobj = json_decode($response);
    echo('<ul ID="resultList">');
    foreach($jsonobj->d->results as $value) {
        echo "<li><img src=\"{$value->Thumbnail->MediaUrl}\">";
    }
    echo("</ul>");
}

?>
