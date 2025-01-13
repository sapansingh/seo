<?php
// Function to get the domain from a URL (used for filtering internal links)
function getDomain($url) {
    $parsed_url = parse_url($url);
    return isset($parsed_url['host']) ? $parsed_url['host'] : '';
}

// Function to crawl a website and get internal URLs (limit to 2000 links with parallel requests)
function crawlWebsite($start_url, $limit = 2000, $max_depth = 3) {
    $urls = [];
    $visited = [];
    $queue = [['url' => $start_url, 'depth' => 0]];

    // Get the domain of the start URL to filter internal links
    $domain = getDomain($start_url);

    // Initialize cURL multi handle
    $mh = curl_multi_init();
    $curl_handles = [];

    while (!empty($queue) && count($urls) < $limit) {
        // Process the queue and add cURL handles for parallel requests
        $new_queue = [];
        foreach ($queue as $item) {
            $current_url = $item['url'];
            $current_depth = $item['depth'];

            // Skip if we have already visited this URL or exceeded max depth
            if (in_array($current_url, $visited) || $current_depth >= $max_depth) {
                continue;
            }

            // Mark the current URL as visited
            $visited[] = $current_url;

            // Initialize a cURL session
            $ch = curl_init($current_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set timeout for the request
            curl_multi_add_handle($mh, $ch);

            // Store the cURL handle for later
            $curl_handles[] = ['url' => $current_url, 'handle' => $ch, 'depth' => $current_depth];

            // Add the current item to the new queue to process the next round
            $new_queue[] = ['url' => $current_url, 'depth' => $current_depth + 1];
        }

        // Process the cURL multi handle
        do {
            curl_multi_exec($mh, $active);
        } while ($active);

        // Process the responses
        foreach ($curl_handles as $key => $handle_data) {
            $response = curl_multi_getcontent($handle_data['handle']);
            $current_url = $handle_data['url'];
            $current_depth = $handle_data['depth'];

            // If the response is empty, log the error and skip processing
            if (empty($response)) {
                // Optionally log the error or continue to the next URL
                error_log("Error: No content retrieved from $current_url");
                curl_multi_remove_handle($mh, $handle_data['handle']);
                curl_close($handle_data['handle']);
                continue;
            }

            // Get the domain of the link
            $dom = new DOMDocument();
            @$dom->loadHTML($response); // Suppress warnings due to malformed HTML

            // Ensure that the DOM document loaded successfully
            if (!$dom) {
                error_log("Error: Failed to load HTML from $current_url");
                continue;
            }

            $links = $dom->getElementsByTagName('a');

            // Loop through the links and add valid internal links to the queue
            foreach ($links as $link) {
                $href = $link->getAttribute('href');

                // Skip empty href or JavaScript links
                if (empty($href) || strpos($href, 'javascript:') === 0) {
                    continue;
                }

                // If the href is relative, make it absolute
                if (strpos($href, 'http') !== 0) {
                    $href = rtrim($current_url, '/') . '/' . ltrim($href, '/');
                }

                // Ensure the link is internal and has the same domain
                if (filter_var($href, FILTER_VALIDATE_URL) && getDomain($href) == $domain && !in_array($href, $visited)) {
                    $urls[] = $href;
                }
            }

            // Remove the cURL handle
            curl_multi_remove_handle($mh, $handle_data['handle']);
            curl_close($handle_data['handle']);
        }

        // Reset the queue for the next round of requests
        $queue = $new_queue;
    }

    curl_multi_close($mh);

    return array_unique($urls); // Remove any duplicate URLs
}

// Handle the form submission and generate the sitemap
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['site_url'])) {
    // Get the URL submitted via the form
    $site_url = $_POST['site_url'];

    // Validate the URL
    if (!filter_var($site_url, FILTER_VALIDATE_URL)) {
        echo "<div class='error'>Invalid URL! Please enter a valid website URL.</div>";
        exit;
    }

    // Start crawling the provided URL to fetch all internal URLs
    echo "<div id='loader' class='loader'></div>";
    echo "<p>Generating Sitemap... Please wait.</p>";
    flush();
    ob_flush();

    // Start crawling and generating the sitemap
    $urls = crawlWebsite($site_url);

    if (empty($urls)) {
        echo "<div class='error'>No URLs found for the provided website.</div>";
        exit;
    }

    // Path to save the generated sitemap
    $sitemap_file = 'sitemap.xml';

    // Start creating the XML content
    $xml_content = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml_content .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

    // Add each URL to the sitemap
    foreach ($urls as $url) {
        $xml_content .= "<url>" . PHP_EOL;
        $xml_content .= "<loc>$url</loc>" . PHP_EOL;
        $xml_content .= "<changefreq>daily</changefreq>" . PHP_EOL; // Frequency of updates, adjust as needed
        $xml_content .= "<priority>0.5</priority>" . PHP_EOL; // Priority (0.0 to 1.0, adjust as needed)
        $xml_content .= "</url>" . PHP_EOL;
    }

    // Close the XML tag
    $xml_content .= '</urlset>' . PHP_EOL;

    // Save the XML content to a file
    file_put_contents($sitemap_file, $xml_content);

    // Output the result with URLs, download link, and count of URLs
    echo "<div class='success'>
            <h2>Sitemap generated successfully!</h2>
            <p>Your sitemap is ready. <a href='$sitemap_file' target='_blank'>Click here to download the sitemap.xml</a></p>
            <h3>URLs in the Sitemap (<strong>" . count($urls) . "</strong> URLs):</h3>
            <ul>";
            
    // Display the URLs in the generated sitemap
    foreach ($urls as $url) {
        echo "<li><a href='$url' target='_blank'>$url</a></li>";
    }

    echo "</ul></div>";
} else {
    // Show the HTML form for URL submission
    echo '
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Generate Google Sitemap</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f4f7fc;
                color: #333;
                margin: 0;
                padding: 0;
            }
            header {
                background-color: #4CAF50;
                color: white;
                text-align: center;
                padding: 20px;
            }
            .container {
                width: 80%;
                margin: 20px auto;
                padding: 30px;
                background-color: white;
                border-radius: 8px;
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }
            h1 {
                text-align: center;
                color: #333;
            }
            .form-container {
                display: flex;
                flex-direction: column;
                gap: 15px;
                margin-top: 20px;
            }
            label {
                font-weight: bold;
                color: #555;
            }
            input[type="url"] {
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 5px;
                font-size: 16px;
                width: 100%;
                box-sizing: border-box;
            }
            input[type="submit"] {
                background-color: #4CAF50;
                color: white;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s ease;
            }
            input[type="submit"]:hover {
                background-color: #45a049;
            }
            .success, .error {
                margin: 20px auto;
                padding: 20px;
                background-color: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 8px;
                max-width: 600px;
                text-align: center;
            }
            .success {
                background-color: #e0f7e0;
                border-color: #2d6a2f;
                color: #2d6a2f;
            }
            .error {
                background-color: #fbe1e1;
                border-color: #d9534f;
                color: #d9534f;
            }
            a {
                color: #4CAF50;
                text-decoration: none;
            }
            a:hover {
                text-decoration: underline;
            }
            /* Loader Styles */
            .loader {
                border: 16px solid #f3f3f3;
                border-top: 16px solid #3498db;
                border-radius: 50%;
                width: 60px;
                height: 60px;
                animation: spin 2s linear infinite;
                margin: 20px auto;
            }
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
        </style>
    </head>
    <body>
        <header>
            <h1>Generate Google Sitemap</h1>
        </header>
        <div class="container">
            <form action="generate_sitemap.php" method="POST" class="form-container">
                <label for="site_url">Enter Your Website URL:</label>
                <input type="url" name="site_url" id="site_url" placeholder="https://www.yoursite.com" required>
                <input type="submit" value="Generate Sitemap">
            </form>
        </div>
    </body>
    </html>';
}
?>
