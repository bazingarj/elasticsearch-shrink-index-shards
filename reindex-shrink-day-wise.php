<?php


define('ELASTICSEARCH_IP', '127.0.0.1');
define('ELASTICSEARCH_PORT', 9200);


$startDay = '2020-06-01';   # select start days  
$days = 30;                 # select how many many days from start date


$currentIndex = "current-index-";
$tmpIndex = 'tmp-index-';
$finalIndex = 'final-index-';

function shrinkIt($old_index_name){
	global $tmpIndex, $finalIndex;
	
	$new_index_name = str_replace($tmpIndex, $finalIndex, $old_index_name);

	echo PHP_EOL.'---------------------'.PHP_EOL.' '.$old_index_name.PHP_EOL;
	
  $opt = @shell_exec('curl -XPUT -H "Content-type:application/json" '.(ELASTICSEARCH_IP.":".ELASTICSEARCH_PORT).'/'.$old_index_name.'/_settings -d \'{"settings": {"index.blocks.write": true,"index.blocks.read_only_allow_delete": null }}\'');
	print_r($opt);
  
	$opt = @shell_exec('curl -XPOST -H "Content-type:application/json" '.(ELASTICSEARCH_IP.":".ELASTICSEARCH_PORT).'/'.$old_index_name.'/_shrink/'.$new_index_name.'?copy_settings=true -d \'{"settings": {"index.blocks.write": null,"index.number_of_replicas": 1,"index.number_of_shards": 1, "index.codec": "best_compression" },"aliases": {"my_search_indices": {}}}\'');
	print_r($opt);

	if(!empty($old_index_name) and strlen($old_index_name) > strlen($tmpIndex)){
		$opt = @shell_exec('curl -XDELETE '.ELASTICSEARCH_IP.':'.ELASTICSEARCH_PORT.'/'.$old_index_name);
	}

}
for($i = 0; $i< $days; $i++){
	$day = date('Y-m-d', strtotime('+'.$i.' days', strtotime($startDay)));
	echo $day.PHP_EOL;
  
	$index = $tmpIndex.$day;
	$opt = shell_exec('curl '.ELASTICSEARCH_IP.':'.ELASTICSEARCH_PORT.'/_reindex -H "Content-Type:application/json" -d \'{"source":{ "index":"'.$currentIndex.'",  "query":{"match" : { "eventParams.requestDate":{ "query":"'.$day.'" }}}},"dest":{"index":"'.$index.'"}}\'');
	print_r($opt);
  
	shrinkIt($index);
}
