<?php
// API Base URL
$baseUrl = "https://api.mandbox.com/apitest/v1/contact.php";

// Universal cURL request function
function requestData($url, $method = "GET", $data = []) {
    $ch = curl_init();

    $method = strtoupper($method);

    if ($method === "GET" && !empty($data)) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($method === "POST") {
        curl_setopt($ch, CURLOPT_POST, true);
        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        }
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return json_encode(["error" => $error]);
    }

    curl_close($ch);
    return $response;
}
?>

