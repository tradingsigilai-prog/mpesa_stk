<?php
include 'token.php';

// DB connection
$conn = new mysqli("sql201.infinityfree.com", "if0_41644534", "gwWoejDbWzErH", "if0_41644534_mpesa_db");

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}

// Generate token
$token = generateToken();

// Timestamp & password
$timestamp = date("YmdHis");
$shortCode = 174379;
$passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";
$password = base64_encode($shortCode . $passkey . $timestamp);

// Request data
$data = [
    "BusinessShortCode" => $shortCode,
    "Password" => $password,
    "Timestamp" => $timestamp,
    "TransactionType" => "CustomerPayBillOnline",
    "Amount" => 1,
    "PartyA" => "254707224513", // Use sandbox test number if needed
    "PartyB" => $shortCode,
    "PhoneNumber" => "254707224513",
    "CallBackURL" => "http://testmmoney.page.gd/callback.php", // FIXED URL
    "AccountReference" => "Adams Booboo",
    "TransactionDesc" => "Payment for goods"
];

// Initialize CURL
$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest");
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer " . $token
]);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// Execute request
$response = curl_exec($curl);

// Handle CURL errors
if ($response === false) {
    die("CURL Error: " . curl_error($curl));
}

curl_close($curl);

// Decode response
$responseData = json_decode($response, true);

// Debug (optional)
// file_put_contents("stk_log.txt", $response . PHP_EOL, FILE_APPEND);

// Check if request accepted
if (isset($responseData['ResponseCode']) && $responseData['ResponseCode'] == "0") {

    $checkoutId = $responseData['CheckoutRequestID'];
    $merchantId = $responseData['MerchantRequestID'];
    $status = "PENDING";

    // Save to DB
    $stmt = $conn->prepare("INSERT INTO mpesa_payments (transaction_id, merchant_request_id, status) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $checkoutId, $merchantId, $status);
    $stmt->execute();

    // Return success response to frontend
    echo json_encode([
        "success" => true,
        "message" => "STK Push sent",
        "checkout_id" => $checkoutId
    ]);

} else {
    // Return error
    echo json_encode([
        "success" => false,
        "message" => $responseData['errorMessage'] ?? "STK Push failed",
        "full_response" => $responseData
    ]);
}

$conn->close();
?>