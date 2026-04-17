<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include 'token.php';

$token = generateToken();
$timestamp = date("YmdHis");
$shortCode = 174379;
$passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";
$password = base64_encode($shortCode.$passkey.$timestamp);

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest");
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer ".$token
]);

$data = [
    "BusinessShortCode" => $shortCode,
    "Password" => $password,
    "Timestamp" => $timestamp,
    "TransactionType" => "CustomerPayBillOnline",
    "Amount" => 1,
    "PartyA" => "254705100679",   // Customer phone
    "PartyB" => $shortCode,
    "PhoneNumber" => "254705100679",
    "CallBackURL" => "http://testmmoney.page.gd/callback.php",
    "AccountReference" => "Adams Booboo",
    "TransactionDesc" => "Payment for goods"
];

curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);

if ($response === false) {
    die("Curl error: " . curl_error($curl));
}

curl_close($curl);

$responseData = json_decode($response, true);

// DEBUG (optional)
// file_put_contents("stk_log.txt", $response . PHP_EOL, FILE_APPEND);

if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == "0") {

    $checkoutId = $responseData['CheckoutRequestID'];
    $merchantId = $responseData['MerchantRequestID'];
    $status = "PENDING";

    // DB connection
    $conn = new mysqli("sql201.infinityfree.com", "if0_41644534", "gwWoejDbWzErH", "if0_41644534_mpesa_db");

    if ($conn->connect_error) {
        die("DB error: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO mpesa_payments (transaction_id, merchant_request_id, status) VALUES (?, ?, ?)");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sss", $checkoutId, $merchantId, $status);
    $stmt->execute();

    echo json_encode([
        "success" => true,
        "checkout_id" => $checkoutId
    ]);

} else {
    echo $response; // show error
}
?>
