<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['url'])) {
    $url = filter_var(trim($_POST['url']), FILTER_VALIDATE_URL);
    
    if (!$url) {
        echo json_encode(['error' => 'Invalid URL']);
        exit;
    }

    // Initialize curl session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    
    // Execute request and get response headers
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headers = curl_getinfo($ch, CURLINFO_HEADER_OUT);
    curl_close($ch);
    
    // Parse the response headers
    $corsHeaders = [
        'allow_origin' => null,
        'allow_methods' => null,
        'allow_headers' => null,
        'allow_credentials' => null
    ];

    // Check for CORS headers
    if (preg_match('/Access-Control-Allow-Origin: (.+?)(\r|\n)/', $headers, $matches)) {
        $corsHeaders['allow_origin'] = trim($matches[1]);
    }
    if (preg_match('/Access-Control-Allow-Methods: (.+?)(\r|\n)/', $headers, $matches)) {
        $corsHeaders['allow_methods'] = trim($matches[1]);
    }
    if (preg_match('/Access-Control-Allow-Headers: (.+?)(\r|\n)/', $headers, $matches)) {
        $corsHeaders['allow_headers'] = trim($matches[1]);
    }
    if (preg_match('/Access-Control-Allow-Credentials: (.+?)(\r|\n)/', $headers, $matches)) {
        $corsHeaders['allow_credentials'] = trim($matches[1]);
    }
    
    // Determine if the URL supports CORS and if it's publicly accessible
    $corsStatus = 'CORS Not Supported';
    $publicAccess = 'No';

    if ($statusCode == 200) {
        if ($corsHeaders['allow_origin'] === '*' || $corsHeaders['allow_origin'] !== null) {
            $corsStatus = 'CORS Supported';
            $publicAccess = 'Yes';
        }
    }

    // Return the results including the actual CORS headers
    echo json_encode([
        'status' => $corsStatus,
        'public_access' => $publicAccess,
        'allow_origin' => $corsHeaders['allow_origin'] ?? 'Not Found',
        'allow_methods' => $corsHeaders['allow_methods'] ?? 'Not Found',
        'allow_headers' => $corsHeaders['allow_headers'] ?? 'Not Found',
        'allow_credentials' => $corsHeaders['allow_credentials'] ?? 'Not Found',
        'api_data' => $corsHeaders // Adding API data with CORS headers
    ]);
}
else {
    echo json_encode(['error' => 'No URL provided']);
}
?>
