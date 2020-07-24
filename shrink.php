<?php

# composer require elasticsearch/elasticsearch
# require_once __DIR__.'/vendor/autoload.php';

use Elasticsearch\ClientBuilder;

define('ELASTICSEARCH_IP', '127.0.0.1');
define('ELASTICSEARCH_PORT', '9200');

$hosts = array(ELASTICSEARCH_IP.":".ELASTICSEARCH_PORT);
try {
       $client = ClientBuilder::create()->setHosts($hosts)->build();
} catch (Exception $e) {
        echo "Elastcisearch Not Connecting";
        exit;
}

$from_index = 'old-index';
$to_index = 'new-index'

$params = [
    // Example of another param
    'v' => true,
    'index' => $from_index.'*'
];

$indices = $client->cat()->indices($params); #to get list of old indices 

foreach($indices as $i){ #loop through old index
	$old_index_name = $i['index'];
	$new_index_name = str_replace($from, $to, $old_index_name);
  
	echo PHP_EOL.'---------------------'.PHP_EOL.' '.$old_index_name.PHP_EOL;
  
	$opt = @shell_exec('curl -XPUT -H "Content-type:application/json" '.(ELASTICSEARCH_IP.":".ELASTICSEARCH_PORT).'/'.$old_index_name.'/_settings -d \'{"settings": {"index.blocks.write": true,"index.blocks.read_only_allow_delete": null }}\'');
	print_r($opt);
  
	$opt = @shell_exec('curl -XPOST -H "Content-type:application/json" '.(ELASTICSEARCH_IP.":".ELASTICSEARCH_PORT).'/'.$old_index_name.'/_shrink/'.$new_index_name.'?copy_settings=true -d \'{"settings": {"index.blocks.write": null,"index.number_of_replicas": 1,"index.number_of_shards": 1, "index.codec": "best_compression" },"aliases": {"my_search_indices": {}}}\'');
	print_r($opt);
}
