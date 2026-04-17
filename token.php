<?php
function generateToken() {
    $consumerKey = "zj3wKh8U4MBenP9P6iuGcBzbimuQlQEUBBu8FZfNLdxaK1Nt";
    $consumerSecret = "7hpnBeWJqnvhsUAKUTIHM0BjR5FhCFaxOXlcBmsmcmHG6HiPQrPvhOKOpftf6TB0";
    $credentials = base64_encode($consumerKey.":".$consumerSecret);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Basic ".$credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response);
    return $result->access_token;
}
?>
