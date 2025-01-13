<?php

// Function to check if a URL exists and returns a successful HTTP response code
function check_url_status($url) {
    $headers = get_headers($url, 1);
    if (strpos($headers[0], '200') !== false) {
        return true;
    }
    return false;
}

// Function to check for the existence of specific pages
function check_page_exists($page_url) {
    return check_url_status($page_url);
}

// Function to check if meta tags are present in the head section of the website
function check_meta_tags($url) {
    $content = file_get_contents($url);
    
    // Check for title and description meta tags
    if (strpos($content, '<title>') !== false && strpos($content, '<meta name="description"') !== false) {
        return true;
    }
    return false;
}

// Function to check if there is sufficient content on the page
function check_content_availability($url) {
    $content = file_get_contents($url);

    // Strip HTML tags to check pure text length
    $text_content = strip_tags($content);

    // Count the number of words (to assess substantial content)
    $word_count = str_word_count($text_content);

    // Check if the word count is above a threshold (e.g., 300 words)
    if ($word_count > 300) {
        return true;
    }
    return false;
}

// Function to check for the presence of policy-related content (e.g., Privacy Policy)
function check_policy_content($url) {
    $content = file_get_contents($url);
    
    // Check if the content contains words typically found in a Privacy Policy
    $policy_keywords = ['privacy', 'terms', 'policy', 'cookie', 'data protection'];
    
    foreach ($policy_keywords as $keyword) {
        if (stripos($content, $keyword) !== false) {
            return true;
        }
    }
    return false;
}

$website_url = "";  // Variable to store URL input
$result = "";       // Variable to store result messages

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the submitted URL and sanitize it
    $website_url = trim($_POST['website_url']);

    // Basic validation for URL
    if (filter_var($website_url, FILTER_VALIDATE_URL)) {
        $result = "<h3>Results for: $website_url</h3>";

        // 1. Check if the website is up and has a 200 status code
        $result .= check_url_status($website_url) ? "✅ Website is reachable and has a valid HTTP status (200 OK).<br>" : "❌ Website is unreachable or returns an error status.<br>";

        // 2. Check if privacy-policy page exists
        $privacy_policy_url = $website_url . '/privacy-policy';
        $result .= check_page_exists($privacy_policy_url) ? "✅ Privacy Policy page exists.<br>" : "❌ Privacy Policy page not found.<br>";

        // 3. Check if about page exists
        $about_page_url = $website_url . '/about';
        $result .= check_page_exists($about_page_url) ? "✅ About Us page exists.<br>" : "❌ About Us page not found.<br>";

        // 4. Check if contact page exists
        $contact_page_url = $website_url . '/contact';
        $result .= check_page_exists($contact_page_url) ? "✅ Contact Us page exists.<br>" : "❌ Contact Us page not found.<br>";

        // 5. Check for meta tags (title, description)
        $result .= check_meta_tags($website_url) ? "✅ Meta tags (title and description) are present in the website's HTML.<br>" : "❌ Meta tags (title and description) are missing.<br>";

        // 6. Check for significant content
        $result .= check_content_availability($website_url) ? "✅ Website contains sufficient content (based on word count).<br>" : "❌ Website has insufficient content.<br>";

        // 7. Check for policy content (e.g., Privacy, Terms, Cookie Policy)
        $result .= check_policy_content($website_url) ? "✅ Policy-related content (privacy, terms, cookies) found.<br>" : "❌ Policy-related content not found.<br>";
    } else {
        $result = "❌ Invalid URL. Please enter a valid website URL.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google AdSense Eligibility Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            padding-top: 80px; /* Offset for fixed navbar */
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-size: 1.8em;
            font-weight: bold;
        }
        .navbar-nav .nav-link {
            font-size: 1.1em;
        }
        .container {
            max-width: 800px;
            margin-top: 60px;
        }
        h1 {
            font-size: 2.5em;
            font-weight: 600;
            color: #343a40;
            margin-bottom: 30px;
        }
        p {
            font-size: 1.2em;
            margin-bottom: 30px;
            color: #6c757d;
        }
        .form-control {
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #ced4da;
            margin-bottom: 20px;
            font-size: 1.1em;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }
        .btn-primary {
            padding: 12px 25px;
            font-size: 1.1em;
            border-radius: 8px;
            background-color: #007bff;
            border: none;
            width: 100%;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .result {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff;
            border: 2px solid #ccc;
            border-radius: 8px;
            color: <?= $resultColor ?>;
            font-size: 1.2em;
            text-align: center;
            font-weight: bold;
        }
        .result strong {
            font-size: 1.3em;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 1.1em;
            color: #6c757d;
        }
    </style>
</head>
<body>
<?php include("nav.php"); ?>
<div class="container" style="margin-top: 200px;">
    <h2>Google AdSense Eligibility Check</h2>
    <p class="text-center">Enter the URL of the website you want to check:</p>

    <!-- Form to input the website URL -->
    <form action="" method="POST">
        <div class="form-group">
            <input type="text" name="website_url" class="form-control" placeholder="Enter website URL (e.g., https://example.com)" value="<?= htmlspecialchars($website_url) ?>" required>
        </div>
        <div class="form-group text-center">
            <button type="submit" class="btn btn-primary">Check Eligibility</button>
        </div>
    </form>

    <!-- Display the result of the eligibility check -->
    <div class="result">
        <?php
        // Display the result message if there are any
        if ($result != "") {
            echo $result;
        }
        ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
