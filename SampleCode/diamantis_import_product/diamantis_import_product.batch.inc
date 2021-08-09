<?php

/**
 * @file
 * Contains the batch callbacks of this module.
 */


/**
 * Batch process callback.
 *
 * @param array $columns
 *   The column.
 * @param string $column_name_key
 *   The column's key.
 * @param array $context
 *   The batch context.
 */
function diamantis_import_product_save($columns, $column_name_key, &$context) {
  if (isset($context['results']['err_message'])) {
    // Do not proceed to next node save.
    return;
  }

  $row = [];
  foreach ($columns as $inner_key => $value) {
    $row[$column_name_key[$inner_key]] = $value;
  }

  $node = node_load($row[DIP_COLUMN_NID]);

  // Check if node entity exists.
  if (!$node) {
    $context['results']['err_message'] = format_string('No product found with id @nid', ['@nid' => $row[DIP_COLUMN_NID]]);
    $context['finished'] = 1.0;
    $context['success'] = FALSE;
    return;
  }

  // Check if commerce_product entity exists.
  $product = commerce_product_load_by_sku($row[DIP_COLUMN_PRODUCT_SKU]);
  if (!$product) {
    $context['results']['err_message'] = format_string('Product id was not found. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
    $context['finished'] = 1.0;
    $context['success'] = FALSE;
    return;
  }

  if (isset($row[DIP_COLUMN_PRICE])) {
    // It will convert 75,33 € to 75.33.
    $cost = price_to_float($row[DIP_COLUMN_PRICE]);
    // Check if price is greater than 0.
    if (!$cost || !is_numeric($cost) || (is_numeric($cost) && $cost < 0)) {
      $context['results']['err_message'] = format_string('Invalid price. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $product->commerce_price[LANGUAGE_NONE][0]['amount'] = $cost * 100;
    $product->commerce_price[LANGUAGE_NONE][0]['currency_code'] = 'EUR';
  }
  watchdog('dip', '<pre>@print_r</pre>', array(
    '@print_r' => print_r($product, TRUE),
  ), WATCHDOG_DEBUG);

  // Check if title is not empty
  if (isset($row[DIP_COLUMN_TITLE])) {
    if (empty($row[DIP_COLUMN_TITLE])) {
      $context['results']['err_message'] = format_string('No title was passed for product @nid', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $node->title = $row[DIP_COLUMN_TITLE];
  }

  if (isset($row[DIP_COLUMN_DISPLAY_FRONTPAGE])) {
    // Check for correct values
    if ($row[DIP_COLUMN_DISPLAY_FRONTPAGE] !== '1' && $row[DIP_COLUMN_DISPLAY_FRONTPAGE] !== '0') {
      $context['results']['err_message'] = format_string('Only values 1 for \'yes\' and 0 for \'no\' are accepted in \'Προβολή στην Αρχική Σελίδα\' column. (NID:@nid)', ['@nid' => $row['Nid']]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $node->field_product_is_featured[LANGUAGE_NONE][0]['value'] = $row[DIP_COLUMN_DISPLAY_FRONTPAGE];
  }

  if (isset($row[DIP_COLUMN_SKROUTZ])) {
    // Check for correct values.
    if ($row[DIP_COLUMN_SKROUTZ] !== '1' && $row[DIP_COLUMN_SKROUTZ] !== '0') {
      $context['results']['err_message'] = format_string("Only values 1 for 'yes' and 0 for 'no' are accepted in 'Skroutz Προώθηση' column. (NID:@nid)", ['@nid' => $row['Nid']]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $node->field_skroutz[LANGUAGE_NONE][0]['value'] = $row[DIP_COLUMN_SKROUTZ];
  }

  if (array_key_exists(DIP_COLUMN_SPECIAL_CATEGORY, $row)) {
    // If the column exists but is null, this is also a valid value.
    if ($row[DIP_COLUMN_SPECIAL_CATEGORY]) {
      $tid = db_query('SELECT tid FROM {taxonomy_term_data} t INNER JOIN {taxonomy_vocabulary} v ON t.vid = v.vid AND v.machine_name = :vocabulary WHERE t.name = :name', [
        ':vocabulary' => 'product_category_special',
        ':name' => $row[DIP_COLUMN_SPECIAL_CATEGORY],
      ])->fetchField();
      if (!$tid) {
        $context['results']['err_message'] = format_string('Invalid value found for %column column. (NID:@nid)', [
          '@nid' => $row[DIP_COLUMN_NID],
          '%column' => DIP_COLUMN_SPECIAL_CATEGORY,
        ]);
        $context['finished'] = 1.0;
        $context['success'] = FALSE;
        return;
      }
      $node->field_product_category_special[LANGUAGE_NONE][0]['tid'] = $tid;
    }
    else {
      $node->field_product_category_special = array();
    }
  }

  if (isset($row[DIP_COLUMN_MANUFACTURER])) {
    $term = taxonomy_get_term_by_name($row[DIP_COLUMN_MANUFACTURER], 'manufacturer');
    if (empty($term)) {
      $node->field_product_manufacturer = [];
    }
    else {
      $node->field_product_manufacturer[LANGUAGE_NONE][0]['tid'] = array_values($term)[0]->tid;
    }
  }

  if (array_key_exists(DIP_COLUMN_MANUFACTURER, $row)) {
    // If the column exists but is null, this is also a valid value.
    if ($row[DIP_COLUMN_MANUFACTURER]) {
      $tid = db_query('SELECT tid FROM {taxonomy_term_data} t INNER JOIN {taxonomy_vocabulary} v ON t.vid = v.vid AND v.machine_name = :vocabulary WHERE t.name = :name', [
        ':vocabulary' => 'manufacturer',
        ':name' => $row[DIP_COLUMN_MANUFACTURER],
      ])->fetchField();
      if (!$tid) {
        $context['results']['err_message'] = format_string('Invalid value found for %column column. (NID:@nid)', [
          '@nid' => $row[DIP_COLUMN_NID],
          '%column' => DIP_COLUMN_MANUFACTURER,
        ]);
        $context['finished'] = 1.0;
        $context['success'] = FALSE;
        return;
      }
      $node->field_product_manufacturer[LANGUAGE_NONE][0]['tid'] = $tid;
    }
    else {
      $node->field_product_manufacturer = array();
    }
  }

  if (isset($row[DIP_COLUMN_GUARANTEE])) {
    /* Supports updating title & url, title only or url only.
     * Allowed formats are the following:
     *   - TITLE#URL
     *   - TITLE#
     *   - TITLE
     *   - #URL
     */
    $guarantee = explode('#', $row[DIP_COLUMN_GUARANTEE]);
    if (!empty($guarantee[0]) && !empty($guarantee[1])) {
      $node->field_product_warranty_link[LANGUAGE_NONE][0]['title'] = $guarantee[0];
      $node->field_product_warranty_link[LANGUAGE_NONE][0]['url'] = $guarantee[1];
    }
    elseif (!empty($guarantee[0])) {
      $node->field_product_warranty_link[LANGUAGE_NONE][0]['title'] = $guarantee[0];
      $node->field_product_warranty_link[LANGUAGE_NONE][0]['url'] = NULL;
    }
    elseif (!empty($guarantee[1])) {
      $node->field_product_warranty_link[LANGUAGE_NONE][0]['title'] = NULL;
      $node->field_product_warranty_link[LANGUAGE_NONE][0]['url'] = $guarantee[1];
    }
    else {
      $node->field_product_warranty_link = [];
    }
  }

  if (isset($row[DIP_COLUMN_PROVIDER])) {
    $term = taxonomy_get_term_by_name($row[DIP_COLUMN_PROVIDER], 'supplier');
    if (empty($term)) {
      $node->field_product_supplier = [];
    }
    else {
      $node->field_product_supplier[LANGUAGE_NONE][0]['tid'] = array_values($term)[0]->tid;
    }
  }

  if (isset($row[DIP_COLUMN_CLIENT_MESSAGE])) {
    $node->field_product_message[LANGUAGE_NONE][0]['value'] = $row['Μήνυμα για Επισκέπτη'];
  }

  if (isset($row[DIP_COLUMN_VIDEO])) {
    if (empty($row[DIP_COLUMN_VIDEO])) {
      $node->field_product_video = [];
    }
    else {
      $node->field_product_video[LANGUAGE_NONE][0]['video_url'] = $row[DIP_COLUMN_VIDEO];
    }
  }

  if (empty($row[DIP_COLUMN_PRODUCT_SKU])) {
    $context['results']['err_message'] = format_string('Empty SKU. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
    $context['finished'] = 1.0;
    $context['success'] = FALSE;
    return;
  }

  if (isset($row[DIP_COLUMN_MPN])) {
    $product->field_product_mpn[LANGUAGE_NONE][0]['value'] = $row[DIP_COLUMN_MPN];
  }

  if (isset($row[DIP_COLUMN_WEIGHT_FOR_NODE])) {
    $node->weight_weight = $row[DIP_COLUMN_WEIGHT_FOR_NODE];
  }

  if (array_key_exists(DIP_COLUMN_SEE_MORE, $row)) {
    if (empty($row[DIP_COLUMN_SEE_MORE])) {
      $node->field_product_related_products = [];
    }
    else {
      $nids = explode(',', $row[DIP_COLUMN_SEE_MORE]);
      foreach ($nids as $nid) {
        // Check if the related node id exists.
        $exists = db_query('SELECT 1 FROM {node} WHERE nid = :nid AND type = :type', [
          ':type' => 'product_display',
          ':nid' => $nid,
        ])->fetchField();
        if ($exists) {
          $node->field_product_related_products[LANGUAGE_NONE][] = [
            'target_id' => $nid,
          ];
        }
        else {
          $context['results']['err_message'] = format_string('Invalid related product code %related_product_nid. (NID:@nid)', [
            '%related_product_nid' => $nid,
            '@nid' => $row[DIP_COLUMN_NID],
          ]);
          $context['finished'] = 1.0;
          $context['success'] = FALSE;
          return;
        }
      }
    }
  }

  if (isset($row[DIP_COLUMN_PRODUCT_TITLE])) {
    if (empty($row[DIP_COLUMN_PRODUCT_TITLE])) {
      $context['results']['err_message'] = format_string('Empty product title. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $product->title = $row[DIP_COLUMN_PRODUCT_TITLE];
  }

  if (!empty($row[DIP_COLUMN_INITIAL_PRICE]) && empty($row[DIP_COLUMN_PRICE])) {
    $context['results']['err_message'] = format_string('Starting price not allowed without a ending price. Product: @nid', [
      '@nid' => $row[DIP_COLUMN_NID],
    ]);
  }

  if (isset($row[DIP_COLUMN_INITIAL_PRICE], $cost)) {
    $initial_price = price_to_float($row[DIP_COLUMN_INITIAL_PRICE]);
    // Using the following condition, we also catch "0.00" as 0.
    if (empty($initial_price) || ($initial_price == (int) $initial_price && $initial_price == 0)) {
      $product->field_product_initial_price = [];
    }
    elseif ($initial_price && is_numeric($initial_price) && $initial_price > 0 && $initial_price > $cost) {
      $product->field_product_initial_price[LANGUAGE_NONE][0]['amount'] = $initial_price * 100;
      $product->field_product_initial_price[LANGUAGE_NONE][0]['currency_code'] = 'EUR';
    }
    else {
      $context['results']['err_message'] = format_string('Invalid starting price. Make sure you also check that the final price should be greater than the starting price. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
  }

  if (isset($row[DIP_COLUMN_OUT_OF_STOCK_NOTIFICATION])) {
    if (in_array($row[DIP_COLUMN_OUT_OF_STOCK_NOTIFICATION], [0, 1], FALSE)) {
      $product->field_product_out_of_stock_alert[LANGUAGE_NONE][0]['value'] = $row[DIP_COLUMN_OUT_OF_STOCK_NOTIFICATION];
    }
    else {
      $context['results']['err_message'] = format_string('Only values 1 for \'yes\' and 0 for \'no\' are accepted in \'Ειδοποίηση Out of Stock\' column. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
  }

  if (isset($row[DIP_COLUMN_STOCK])) {
    if (is_numeric($row[DIP_COLUMN_STOCK]) && $row[DIP_COLUMN_STOCK] >= 0) {
      $product->commerce_stock[LANGUAGE_NONE][0]['value'] = $row[DIP_COLUMN_STOCK];
    }
    else {
      $context['results']['err_message'] = format_string('Invalid Stock value. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
  }

  if (isset($row[DIP_COLUMN_STATUS])) {
    // Check for correct values.
    if ($row[DIP_COLUMN_STATUS] !== '1' && $row[DIP_COLUMN_STATUS] !== '0') {
      $context['results']['err_message'] = format_string("Only values 1 for 'yes' and 0 for 'no' are accepted in 'Κατάσταση' column. (NID:@nid)", ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }

    if (!$row[DIP_COLUMN_STATUS] && $product->commerce_stock[LANGUAGE_NONE][0]['value'] > 0) {
      $context['results']['err_message'] = format_string('You cannot disable the product when it has stock [Current Stock: @stock]. (NID:@nid)', [
        '@nid' => $row[DIP_COLUMN_NID],
        '@stock' => $product->commerce_stock[LANGUAGE_NONE][0]['value'],
      ]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $product->status = $row[DIP_COLUMN_STATUS];
  }

  if (isset($row[DIP_COLUMN_NOT_IMMEDIATE_DELIVERY])) {
    // Check for correct values.
    if ($row[DIP_COLUMN_NOT_IMMEDIATE_DELIVERY] !== '1' && $row[DIP_COLUMN_NOT_IMMEDIATE_DELIVERY] !== '0') {
      $context['results']['err_message'] = format_string('Only values 1 for \'yes\' and 0 for \'no\' are accepted in \'Μη Άμεσα Διαθέσιμο\' column. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $product->commerce_stock_override[LANGUAGE_NONE][0]['value'] = $row[DIP_COLUMN_NOT_IMMEDIATE_DELIVERY];
  }

  if (isset($row[DIP_COLUMN_AVAILABILITY])) {
    $term = taxonomy_get_term_by_name($row[DIP_COLUMN_AVAILABILITY], 'product_status');
    if (empty($term)) {
      $product->field_product_status = [];
    }
    else {
      $product->field_product_status[LANGUAGE_NONE][0]['tid'] = array_values($term)[0]->tid;
    }
  }

  if (isset($row[DIP_COLUMN_CATEGORY_1], $row[DIP_COLUMN_CATEGORY_2], $row[DIP_COLUMN_CATEGORY_3])) {
    $term_categories_1 = taxonomy_get_term_by_name($row[DIP_COLUMN_CATEGORY_1], 'product_category');
    $term_categories_2 = taxonomy_get_term_by_name($row[DIP_COLUMN_CATEGORY_2], 'product_category');
    $term_categories_3 = taxonomy_get_term_by_name($row[DIP_COLUMN_CATEGORY_3], 'product_category');

    if (empty($term_categories_1)) {
      $context['results']['err_message'] = format_string('Invalid name on category 1. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    if (empty($term_categories_2)) {
      $context['results']['err_message'] = format_string('Invalid name on category 2. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    if (empty($term_categories_3)) {
      $context['results']['err_message'] = format_string('Invalid name on category 3. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }

    $temp = array_reverse($term_categories_1);
    $parent_1 = array_pop($temp);

    foreach ($term_categories_2 as $term_category_2) {
      $temp_parents = taxonomy_get_parents($term_category_2->tid);
      $temp = array_reverse($temp_parents);
      $temp_parent = array_pop($temp);
      if ($temp_parent->tid === $parent_1->tid) {
        $parent_2 = $term_category_2;
        break;
      }
    }

    if (!isset($parent_2)) {
      $context['results']['err_message'] = format_string('Something went wrong on category 2. Could not find parent for category 1. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    foreach ($term_categories_3 as $term_category_3) {
      $temp_parents = taxonomy_get_parents($term_category_3->tid);
      $temp = array_reverse($temp_parents);
      $temp_parent = array_pop($temp);
      if ($temp_parent->tid === $parent_2->tid) {
        $child_3 = $term_category_3;
        break;
      }
    }

    if (!isset($child_3)) {
      $context['results']['err_message'] = format_string('Something went wrong on category 3. Could not find parent for category 2. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $node->field_product_category[LANGUAGE_NONE][0]['tid'] = $child_3->tid;
  }

  if (isset($row[DIP_COLUMN_CASH_ON_DELIVERY])) {
    // Check if values are in list.
    $field_product_cash_on_delivery_info = field_info_field('field_product_cash_on_delivery');
    $list_values = list_allowed_values($field_product_cash_on_delivery_info);
    $list_values = array_flip($list_values);
    if (!isset($list_values[$row[DIP_COLUMN_CASH_ON_DELIVERY]])) {
      $context['results']['err_message'] = format_string('Only values @allowed_values are accepted in @column column. Product: @nid', [
        '@allowed_values' => [
          'Δωρεάν Αντικαταβολή',
          'Αντικαταβολή',
          'Όχι Αντικαταβολή',
        ],
        '@nid' => $row[DIP_COLUMN_NID],
      ]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $product->field_product_cash_on_delivery[LANGUAGE_NONE][0]['value'] = $list_values[$row[DIP_COLUMN_CASH_ON_DELIVERY]];
  }

  if (isset($row[DIP_COLUMN_SHIPPING])) {
    // Check if values are in list.
    $field_product_shipping_list_info = field_info_field('field_product_shipping_list');
    $list_values = list_allowed_values($field_product_shipping_list_info);
    $list_values = array_flip($list_values);
    if ($row[DIP_COLUMN_SHIPPING] === '') {
      $product->field_product_shipping_list = [];
    }
    elseif (!isset($list_values[$row[DIP_COLUMN_SHIPPING]])) {
      $context['results']['err_message'] = format_string("Only values 'Δωρεάν Μεταφορικά' and 'Κατόπιν Συνεννόησης' are accepted in 'Μεταφορικά' column. (NID:@nid)", [
        '@nid' => $row[DIP_COLUMN_NID],
      ]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    else {
      $product->field_product_shipping_list[LANGUAGE_NONE][0]['value'] = $list_values[$row[DIP_COLUMN_SHIPPING]];
    }
  }

  if (isset($row[DIP_COLUMN_LARGER_THAN_1_5M])) {
    // Check for correct values.
    if ($row[DIP_COLUMN_LARGER_THAN_1_5M] !== '1' && $row[DIP_COLUMN_LARGER_THAN_1_5M] !== '0') {
      $context['results']['err_message'] = format_string("Only values 1 for 'yes' and 0 for 'no' are accepted in \'Μεγαλύτερο από 1,5m\' column. (NID:@nid)", ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $product->field_product_larger_than_meters[LANGUAGE_NONE][0]['value'] = $row[DIP_COLUMN_LARGER_THAN_1_5M];
  }

  if (isset($row[DIP_COLUMN_WEIGHT])) {
    $weight = str_replace(['kg', ','], ['', '.'], $row[DIP_COLUMN_WEIGHT]);
    if (!($weight > 0)) {
      $context['results']['err_message'] = format_string('Invalid Βάρος value. (NID:@nid)', ['@nid' => $row[DIP_COLUMN_NID]]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
    $product->field_product_weight[LANGUAGE_NONE][0]['weight'] = $weight;
  }

  if (isset($row[DIP_COLUMN_PUBLISHED])) {
    if (in_array($row[DIP_COLUMN_PUBLISHED], array(0, 1), FALSE)) {
      $node->status = $row[DIP_COLUMN_PUBLISHED];
    }
    else {
      $context['results']['err_message'] = t("Only values 1 for 'yes' and 0 for 'no' are accepted in '@column' column. Also it can not be empty. (NID:@nid)", [
        '@nid' => $row[DIP_COLUMN_NID],
        '@column' => DIP_COLUMN_PUBLISHED,
      ]);
      $context['finished'] = 1.0;
      $context['success'] = FALSE;
      return;
    }
  }

  try {
    commerce_product_save($product);
    node_save($node);
    $context['results']['saved_values'][] = $node->nid . '|' . $product->sku;
  }
  catch (Exception $e) {
    watchdog_exception('import_product_error', $e);
    $context['results']['err_message'] = format_string('Something went wrong upon saving. (Node: #@nid / Product: #@product_sku)', [
      '@nid' => $node->nid,
      '@product_sku' => $product->sku,
    ]);
    $context['finished'] = 1.0;
    $context['success'] = FALSE;
    return;
  }
}

/**
 * Batch finish callback.
 */
function diamantis_import_product_finished($success, $results, $operations) {
  if (!empty($results['err_message'])) {
    form_set_error('import_file', $results['err_message']);
  }
  else {
    drupal_set_message('Import csv file was successful.');
  }
  if (!empty($results['saved_values'])) {
    drupal_set_message(format_string('@lines were updated', ['@lines' => count($results['saved_values'])]));
  }
}

/**
 * It will convert various formats to numbers.
 *
 * Examples:
 *   - 75,33 € to 75.33
 *   - 75.33 € to 75.33
 *   - 75,33 to 75.33
 *   - 75 to 75
 *   - 76,363 twtsgdgfdgwerwer #r3212! to 76.363
 *
 * @param string $price
 *   The price string to get the price from.
 *
 * @return float|false
 *   Returns the correct price.
 */
function price_to_float($price) {
  if (preg_match('/^([0-9]+[.,]?[0-9]*)/', $price, $matches)) {
    $float = str_replace(',', '.', $matches[1], $count);
    if ($count <= 1) {
      return $float;
    }
  }
  return FALSE;
}
