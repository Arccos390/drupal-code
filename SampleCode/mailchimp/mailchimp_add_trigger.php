<?php

/**
 * @file
 * The first step of the workflow process.
 *
 * Instead of using the path of the whitepaper's PDF in MailChimp, a link to
 * this file should be provided instead.
 */

/**
 * Redirects to a specified url.
 *
 * @param string $url
 *   The url to redirect to.
 */
function redirect($url) {
  ob_start();
  header('Location: ' . $url);
  ob_end_flush();
  die();
}

/**
 * Check if the email exist in our file. If it exist returns TRUE. If not
 * the csv file is updated with the new email and return FALSE.
 *
 * @param string $email
 *   The data of the email as it is stored in the mailchimp member list.
 *
 * @return bool
 */
function check_if_mail_exist_in_file($email){
  $file_name = 'email_records.csv';
  $handle = TRUE;
  if(!file_exists($file_name)){
    $fh = fopen($file_name, 'wb') or $handle = FALSE;
    fputcsv($fh, array($email));
    fclose($fh);
    return FALSE;
  }

  $fh = fopen($file_name, 'rb') or $handle = FALSE;
  if($handle === TRUE){
    $csv_file = [];
    while (($data = fgetcsv($fh, 5000)) !== FALSE) {
      $num = count($data);
      $csv_file[] = $data;
      for ($c=0; $c < $num; $c++) {
        //If the email exist then return TRUE.
        if($data[$c] == $email){
          fclose($fh);
          return TRUE;
        }
      }
    }
    $fh = fopen($file_name, 'wb') or $handle = FALSE;
    foreach ($csv_file as $line ) {
      fputcsv($fh, $line);
    }
    fputcsv($fh, array($email));
    fclose($fh);
  }
  return FALSE;
}

header('Content-type:application/json;charset=utf-8');

// This is the ID of the list in MailChimp.
$list_id = LIST_ID;
// This is the API key.
$authToken = TOKEN;
// The URL to redirect to.
$url = URL_REDIRECT;
if (empty($_GET['email'])) {
  $message = [
    'status' => 'error',
    'type' => 'Invalid Resource',
    'message' => 'Email parameter is required.',
    'code' => 1,
  ];
  echo json_encode($message);
  die();
}

// Make sure email addresses with placeholders work.
$email_address = str_replace(' ', '+', $_GET['email']);

// The data to send to the API.
$postData = array(
  'email_address' => $email_address,
);

// Setup cURL to initialize the trial 1 queue.
$ch = curl_init('https://us10.api.mailchimp.com/3.0/automations/bb6a240ddd/emails/bd06af3a2f/queue');
curl_setopt_array($ch, array(
  CURLOPT_POST => TRUE,
  CURLOPT_RETURNTRANSFER => TRUE,
  CURLOPT_HTTPHEADER => array(
    "Authorization: apikey {$authToken}",
    'Content-Type: application/json',
  ),
  CURLOPT_POSTFIELDS => json_encode($postData),
));

// Send the request.
$response = curl_exec($ch);
$response_arr = json_decode($response, TRUE);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Keep a log for debugging reasons...
error_log(__FILE__ . ":{$email_address}:{$httpcode}:{$response_arr['detail']}");

if ($httpcode === 204 ||
  ($httpcode === 400 && $response_arr['detail'] === 'The subscriber has already been triggered for this email.') ||
  ($httpcode === 400 && $response_arr['detail'] === 'You’ve already sent this email to the subscriber.')) {

  if (check_if_mail_exist_in_file($email_address) === FALSE) {
    // Prepare the data of the member.
    for ($offset_num = 0; $offset_num < 1000; $offset_num += 100) {
      // Setup cURL to initialize the trial 1 queue.
      $ch = curl_init("https://us10.api.mailchimp.com/3.0/lists/{$list_id}/members?count=100&offset={$offset_num}");
      curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => [
          "Authorization: apikey {$authToken}",
          'Content-Type: application/json',
        ],
      ]);
      // Send the request.
      $response = curl_exec($ch);
      $response_arr = json_decode($response, TRUE);
      $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      curl_close($ch);

      $first_name = '';
      $last_name = '';
      $phone = '';
      $company_name = '';
      $job_title = '';
      foreach ($response_arr['members'] as $member) {
        if ($member['email_address'] === $email_address) {
          $first_name = $member['merge_fields']['FNAME'];
          $last_name = $member['merge_fields']['LNAME'];
          $phone = $member['merge_fields']['PHONE'];
          $company_name = $member['merge_fields']['CNAME'];
          $job_title = $member['merge_fields']['JTITLE'];
          break 2;
        }
      }
    }
    error_log(__FILE__ . ":{$email_address}:{$httpcode}:Get the email's information from the list.");
    // First name is required field so it should not be null.
    if(!empty($first_name)) {
      // The message
      $msg = "New member downloaded the foodakai report!\nEmail: {$email_address}\nFirst Name: {$first_name}\nLast Name: {$last_name}\nPhone: {$phone}\nCompany Name: {$company_name}\nJob Title: {$job_title}";
      // Send email
      mail('kontogiannis.thodoris@agroknow.com', 'Mailchimp: Download report', $msg);
      sleep(0.5);
      mail('stoitsis@agroknow.com', 'Mailchimp: Download report', $msg);
      sleep(0.5);
      mail('m.polos@e-sepia.gr', 'Mailchimp: Download report', $msg);
    }
    else{
      error_log(__FILE__ . "Email {$email_address} does not exist to the mailchimp list.");
    }
  }
  redirect($url);
}

// Report to the initial website in case of error to avoid having this script
// displayed.
redirect('http://reports.foodakai.com');
