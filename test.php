<?php

ini_set("memory_limit", "1024M");
include_once __DIR__ . '/SimpleCollaborativeFiltering.php';


$recommender = new SimpleCollaborativeFiltering();
$recommender->loadData(__DIR__ . '/play-log.gz', true);

// 计算某一个视频的相关视频（前10个）
var_dump($recommender->calculateMostSimilarItems(23186, 10));

// 计算所有视频的相关视频（前10个），用于批量计算然后转移存储至 mysql 等其他数据库提供接口服务
// $suggestions = $recommender->calculateMostSimilarItemsOfAllProducts(10);


