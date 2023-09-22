<?php
$vncResponse = json_decode($httpClient->request("GET", "/api/v1/virtualservers/{$virtualserver->uuid}/vnc")->getBody()->getContents());
?>

<iframe style="width: 100%; height:80vh; border: none;" src="{{ $vncResponse->data->url; }}"></iframe>