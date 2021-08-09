<?php

/**
 * @file
 * The second step of the workflow.
 *
 * This file should be requested by the 3rd-party system that registers the
 * users. When a proper $_GET['email'] parameter is passed to the script, we
 * remove the subscriber from the TRIAL workflow. Then we tag the subscriber
 * with the "Foodakai 2019 - Trial confirmed" tag.
 */

/**
 * Check if the given user exist in a specific list.
 *
 * @param string $list_id
 * @param string $authToken
 * @param string $email_address
 *
 * @return bool
 */
function check_if_member_exist_in_audience($list_id, $authToken, $email_address){
  $ch = curl_init("https://us10.api.mailchimp.com/3.0/lists?email={$email_address}");
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
  curl_close($ch);

  return $response_arr['lists'][0]['id'] == $list_id;
}

header('Content-type:application/json;charset=utf-8');

// This is the default ID of the list in MailChimp.
$list_id = LIST_ID;
// This is the id of the workflow.
$workflow_id = WORKFLOW_ID;
// This is the API key.
$authToken = TOKEN;
// This is the id of the tag.
$segment_id = SEGMENT_ID;

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
  'email_address' => $_GET['email'],
);

// If the email does not exist in the first audience (Foodakai Report) it will
// in the Foodakai Report Imported. Also change the workflow id as the audience it
// is linked to a different workflow (Remind free trial (immediately)).
if(check_if_member_exist_in_audience($list_id, $authToken, $email_address) === FALSE){
  $list_id = '6c4cacad12';
  $workflow_id = 'a08cffe4f2';
}

// Unsubscribe member from workflow.
$ch = curl_init('https://us10.api.mailchimp.com/3.0/automations/' . $workflow_id . '/removed-subscribers');
curl_setopt_array($ch, array(
  CURLOPT_POST => TRUE,
  CURLOPT_RETURNTRANSFER => TRUE,
  CURLOPT_HTTPHEADER => array(
    "Authorization: apikey {$authToken}",
    'Content-Type: application/json',
  ),
  CURLOPT_POSTFIELDS => json_encode($postData),
));
$response = curl_exec($ch);
$response_arr = json_decode($response, TRUE);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response_arr['status'] === 400 && $response_arr['title'] === 'Invalid Resource') {
  $message = [
    'status' => 'error',
    'type' => $response_arr['title'],
    'message' => $response_arr['detail'],
    'code' => 2,
  ];
  echo json_encode($message);
  // Keep a log for debugging reasons...
  error_log(__FILE__ . ":{$email_address}:{$httpcode}:{$response_arr['detail']}");
  die();
}

if ($response_arr['status'] === 401 && $response_arr['title'] === 'API Key Invalid') {
  $message = [
    'status' => 'error',
    'type' => $response_arr['title'],
    'message' => $response_arr['detail'],
    'code' => 3,
  ];
  echo json_encode($message);
  // Keep a log for debugging reasons...
  error_log(__FILE__ . ":{$email_address}:{$httpcode}:{$response_arr['detail']}");
  die();
}

if ($response_arr['status'] === 404 && $response_arr['title'] === 'Resource Not Found') {
  $message = [
    'status' => 'error',
    'type' => $response_arr['title'],
    'message' => $response_arr['detail'],
    'code' => 4,
  ];
  echo json_encode($message);
  // Keep a log for debugging reasons...
  error_log(__FILE__ . ":{$email_address}:{$httpcode}:{$response_arr['detail']}");
  die();
}

if ($httpcode === 204) {
  // Setup cURL to add tag to member.
  $ch = curl_init("https://us10.api.mailchimp.com/3.0/lists/{$list_id}/segments/{$segment_id}/members");
  curl_setopt_array($ch, array(
    CURLOPT_POST => TRUE,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_HTTPHEADER => array(
      "Authorization: apikey {$authToken}",
      'Content-Type: application/json',
    ),
    CURLOPT_POSTFIELDS => json_encode($postData),
  ));

  $message = [
    'status' => 'success',
    'type' => 'Unsubscribe from workflow',
    'message' => "Email {$postData['email_address']} successfully unsubscribe from the workflow!",
    'code' => 5,
  ];
  echo json_encode($message);
  // Keep a log for debugging reasons...
  error_log(__FILE__ . ":{$email_address}:{$httpcode}:{$response_arr['detail']}");
  die();
}

$message = [
  'status' => 'error',
  'type' => 'Unsubscribe from workflow',
  'message' => "Email {$postData['email_address']} was not unsubscribed from the workflow.",
  'code' => 6,
];
echo json_encode($message);
die();
