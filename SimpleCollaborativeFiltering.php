<?php


/**
 * 简易版的协同过滤，仅用于通过观看或购买行为计算相关产品推荐
 * 使用 Tanimoto 相似度算法
 */
class SimpleCollaborativeFiltering {
  /**
   * @var array 记录每一个物品被哪些用户观看过
   */
  private $product_watched_users = [];
  /**
   * @var array 记录数据中所有的物品 ID
   */
  private $all_product_ids = [];

  /**
   * 从文件中加载观看或者购买行为数据，数据格式为
   * 用户ID,物品ID
   * @param string $file_path 文件路径
   * @param boolean $is_gz 文件是否有 gzip 压缩
   */
  public function loadData($file_path, $is_gz) {
    $handle = $is_gz ? gzopen($file_path, 'r') : fopen($file_path, 'r');
    while(true) {
      $line = $is_gz ? gzgets($handle) : fgets($handle);
      if(!$line) break;
      $segs = explode(',', $line);
      $user_id = intval($segs[0]);
      $product_id = intval($segs[1]);
      if(!isset($this->product_watched_users[$product_id])) {
        $this->product_watched_users[$product_id] = [];
      }
      $this->product_watched_users[$product_id][$user_id] = true;
    }
    $is_gz ? gzclose($handle) : fclose($handle);
    $this->all_product_ids = array_keys($this->product_watched_users);
  }

  /**
   * 对所有的物品计算相似度
   */
  private function calculateSimilarityOfAllProducts() {
    // 计算两两物品之间的相似度
    $similarity_of_all_products = [];
    foreach($this->all_product_ids as $product_id1) {
      foreach($this->all_product_ids as $product_id2) {
        // 物品A,B的相似度和顺序无关，避免重复计算
        if($product_id1 <= $product_id2) continue;
        $users_count1 = count($this->product_watched_users[$product_id1]);
        $users_count2 = count($this->product_watched_users[$product_id2]);
        $users_count_both = count(array_intersect_key(
          $this->product_watched_users[$product_id1],
          $this->product_watched_users[$product_id2]
        ));
        if($users_count_both == 0) continue;
        $similarity = $users_count_both / ($users_count1 + $users_count2 - $users_count_both);
        $similarity_of_all_products[$product_id1][$product_id2] = $similarity;
        $similarity_of_all_products[$product_id2][$product_id1] = $similarity;
      }
    }

    // 对相关物品进行相似度进行排序
    foreach($similarity_of_all_products as $product_id1 => $temp) {
      arsort($similarity_of_all_products[$product_id1]);
    }

    return $similarity_of_all_products;
  }

  /**
   * 计算所有物品的相似物品
   * @param int $limit 要计算的相似物品的数量
   * @return array
   */
  public function calculateMostSimilarItemsOfAllProducts($limit) {
    $similarity_of_all_products = $this->calculateSimilarityOfAllProducts();

    $result = [];
    foreach($similarity_of_all_products as $product_id1 => $similarity_of_other_products) {
      // 提取相似度靠前的 $limit 个相似的物品
      $top_similarity_of_other_products = [];
      foreach($similarity_of_other_products as $product_id => $similarity) {
        if(count($top_similarity_of_other_products) == $limit) break;
        $top_similarity_of_other_products[$product_id] = $similarity;
      }
      $result[$product_id1] = $top_similarity_of_other_products;
    }

    return $result;
  }


  /**
   * 为某一个物品计算相似的物品
   * @param int $id 要计算的物品ID
   * @param int $limit 要计算的相似物品的数量
   * @return array
   */
  public function calculateMostSimilarItems($id, $limit) {
    $product_id1 = $id;
    // 计算和其他所有物品的相似度
    $similarity_of_other_products = [];
    foreach($this->all_product_ids as $product_id2) {
      if($product_id1 == $product_id2) continue;
      $users_count1 = count($this->product_watched_users[$product_id1]);
      $users_count2 = count($this->product_watched_users[$product_id2]);
      $users_count_both = count(array_intersect_key(
        $this->product_watched_users[$product_id1],
        $this->product_watched_users[$product_id2]
      ));
      if($users_count_both == 0) continue;
      $similarity = $users_count_both / ($users_count1 + $users_count2 - $users_count_both);
      $similarity_of_other_products[$product_id2] = $similarity;
    }
    // 按照相似度进行排序
    arsort($similarity_of_other_products);

    // 提取相似度靠前的 $limit 个相似的物品
    $result = [];
    foreach($similarity_of_other_products as $product_id => $similarity) {
      if(count($result) == $limit) break;
      $result[$product_id] = $similarity;
    }

    return $result;
  }
}
