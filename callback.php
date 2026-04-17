<?php

$callbackJSON = file_get_contents('php://input');
file_put_contents("callback_log.txt", $callbackJSON . PHP_EOL, FILE_APPEND);

$callbackData = json_decode($callbackJSON, true);

$conn = new mysqli("sql201.infinityfree.com", "if0_41644534", "gwWoejDbWzErH", "if0_41644534_mpesa_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if(isset($callbackData['Body']['stkCallback'])) {

    $stkCallback = $callbackData['Body']['stkCallback'];
    $resultCode = $stkCallback['ResultCode'];
    $transactionId = $stkCallback['CheckoutRequestID'];

    $status = ($resultCode == 0) ? "SUCCESS" : "FAILED";

    // DEBUG: check if row exists
    $check = $conn->query("SELECT * FROM mpesa_payments WHERE transaction_id='$transactionId'");
    if ($check->num_rows == 0) {
        file_put_contents("callback_log.txt", "No matching transaction: ".$transactionId.PHP_EOL, FILE_APPEND);
    }

    $stmt = $conn->prepare("UPDATE mpesa_payments SET status=? WHERE transaction_id=?");

    if (!$stmt) {
        file_put_contents("callback_log.txt", "Prepare failed: ".$conn->error.PHP_EOL, FILE_APPEND);
        exit;
    }

    $stmt->bind_param("ss", $status, $transactionId);

    if (!$stmt->execute()) {
        file_put_contents("callback_log.txt", "Execute failed: ".$stmt->error.PHP_EOL, FILE_APPEND);
    }
}

$conn->close();
?>
