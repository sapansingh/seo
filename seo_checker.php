<?php

// Include necessary SEO functions
function fetch_page_content($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $html = curl_exec($ch);
    curl_close($ch);
    return $html;
}






function get_meta_tag($html, $name) {
    preg_match('/<meta name="' . $name . '" content="(.*?)"/i', $html, $matches);
    return $matches[1] ?? null;
}

function get_title($html) {
    preg_match("/<title>(.*)<\/title>/i", $html, $matches);
    return $matches[1] ?? null;
}
function get_canonical_tag($html) {
    preg_match('/<link rel="canonical" href="(.*?)"/i', $html, $matches);
    return $matches[1] ?? null;
}

function get_charset($html) {
    preg_match('/<meta charset="(.*?)"/i', $html, $matches);
    return $matches[1] ?? null;
}

function get_author($html) {
    preg_match('/<meta name="author" content="(.*?)"/i', $html, $matches);
    return $matches[1] ?? null;
}

function get_robots($html) {
    preg_match('/<meta name="robots" content="(.*?)"/i', $html, $matches);
    return $matches[1] ?? null;
}

function get_h1_tags($html) {
    preg_match_all("/<h1[^>]*>(.*?)<\/h1>/i", $html, $matches);
    return $matches[1] ?? [];
}

function get_image_alt_text($html) {
    preg_match_all('/<img[^>]* src="([^"]+)"[^>]* alt="(.*?)"[^>]*>/i', $html, $matches);
    $images = [];
    foreach ($matches[1] as $key => $src) {
        $images[] = ['src' => $src, 'alt' => $matches[2][$key] ?? 'No alt text'];
    }
    return $images;
}


function seo_issues($title, $metaDescription, $metaKeywords, $h1Tags, $imageAltCount, $totalImages) {
    $issues = [];

    if (empty($title)) {
        $issues[] = 'Missing or empty title tag. The title tag is important for search engines and users.';
    }

    if (empty($metaDescription)) {
        $issues[] = 'Missing meta description. A meta description helps improve click-through rates in search results.';
    }

    if (empty($metaKeywords)) {
        $issues[] = 'Missing meta keywords. Although not as important as before, it is still helpful for some search engines.';
    }

    if (empty($h1Tags)) {
        $issues[] = 'Missing H1 tags. H1 tags are important for page structure and SEO.';
    }

    if ($imageAltCount === 0) {
        $issues[] = 'No images with alt text found. Adding alt text to images improves SEO and accessibility.';
    }

    if ($totalImages === 0) {
        $issues[] = 'No images found. Adding relevant images can improve SEO and user experience.';
    }

    return $issues;
}

// Get page load time
function get_page_load_time($url) {
    $start_time = microtime(true);
    file_get_contents($url);
    $end_time = microtime(true);
    return round($end_time - $start_time, 2);
}

function get_internal_external_links($html, $base_url) {
    preg_match_all('/<a href="([^"]+)"/i', $html, $matches);
    $links = $matches[1] ?? [];
    $internal_links = [];
    $external_links = [];

    foreach ($links as $link) {
        // Check if the link is absolute or relative
        if (parse_url($link, PHP_URL_SCHEME) === null) {
            // It's a relative link; prepend the base URL
            $link = rtrim($base_url, '/') . '/' . ltrim($link, '/');
        }

        $parsed_url = parse_url($link);
        if (isset($parsed_url['host']) && $parsed_url['host'] !== parse_url($base_url, PHP_URL_HOST)) {
            $external_links[] = $link;
        } else {
            $internal_links[] = $link;
        }
    }

    return ['internal' => $internal_links, 'external' => $external_links];
}
function check_link_status($url) {
    // Initialize cURL session
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true); // We only want the headers, not the body.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the result.
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Set a timeout.
    curl_exec($ch);

    // Get the response code
    $response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check if curl_exec() returned false (failure)
    if ($response_code === 0) {
        curl_close($ch);
        return 'Request Failed';
    }

    // Close the cURL session
    curl_close($ch);

    return $response_code;
}


function check_links($links) {
    $valid_links = [];
    $broken_links = [];

    foreach ($links as $link) {
        $status_code = check_link_status($link);
        
        // If status code is 200 or in the 2xx range, consider it valid
        if ($status_code >= 200 && $status_code < 300) {
            $valid_links[] = ['url' => $link, 'status_code' => $status_code];
        } else {
            // Anything else (e.g., 404, 500) is considered broken
            $broken_links[] = ['url' => $link, 'status_code' => $status_code];
        }
    }

    return ['valid' => $valid_links, 'broken' => $broken_links];
}



// Handle the POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get the URL from POST request
    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);

    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo "Invalid URL format.";
        exit;
    }

    // Fetch the website's content using cURL
    $html = fetch_page_content($url);
    if (!$html) {
        echo "Unable to fetch the website's content.";
        exit;
    }

    // Extract SEO elements
    $title = get_title($html);
    $metaDescription = get_meta_tag($html, "description");
    $metaKeywords = get_meta_tag($html, "keywords");
    $h1Tags = get_h1_tags($html);
    $imageAltTexts = get_image_alt_text($html);
    $links = get_internal_external_links($html, $url);

    // Prepare chart data
    $internalLinksCount = count($links['internal']);
    $externalLinksCount = count($links['external']);
    $imageAltCount = count($imageAltTexts);
    $totalImages = count($imageAltTexts);

    // Check for SEO issues
    $issues = seo_issues($title, $metaDescription, $metaKeywords, $h1Tags, $imageAltCount, $totalImages);

    // Get page load time
    $pageLoadTime = get_page_load_time($url);

    // Check mobile-friendliness
    $canonical = get_canonical_tag($html);
    $charset = get_charset($html);
    $author = get_author($html);
    $robots = get_robots($html);
    $internal_links_status = check_links($links['internal']);
    $external_links_status = check_links($links['external']);

    // Pass the SEO analysis data to JavaScript for rendering charts
    echo "<script>
    var seoData = {
        title: '$title',
        metaDescription: '$metaDescription',
        metaKeywords: '$metaKeywords',
        h1Tags: " . json_encode($h1Tags) . ",
        internalLinks: $internalLinksCount,
        externalLinks: $externalLinksCount,
        imageAltCount: $imageAltCount,
        totalImages: $totalImages,
        canonical: '$canonical',
        charset: '$charset',
        author: '$author',
        robots: '$robots',
        internalLinksList: " . json_encode($links['internal']) . ",
        externalLinksList: " . json_encode($links['external']) . ",
        imageAltTexts: " . json_encode($imageAltTexts) . ",
        issues: " . json_encode($issues) . ",
        pageLoadTime: $pageLoadTime,
          validInternalLinks: " . json_encode($internal_links_status['valid']) . ",
        brokenInternalLinks: " . json_encode($internal_links_status['broken']) . ",
        validExternalLinks: " . json_encode($external_links_status['valid']) . ",
        brokenExternalLinks: " . json_encode($external_links_status['broken']) . "
       
    };
</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced SEO Checker</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <!-- Navbar Section -->
    <?php include("nav.php"); ?>
    <!-- Main Content -->
    <div class="container-fluid" style="margin-top: 200px;">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card p-4 seo-result">
                    <h1 class="text-center">Advanced SEO Checker</h1>
                    <form method="POST" action="" class="mt-4">
                        <div class="mb-3">
                            <label for="url" class="form-label">Enter URL to Check SEO:</label>
                            <input type="text" class="form-control" id="url" name="url" placeholder="https://example.com" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Check SEO</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
        <div class="row mt-5">
        <div class="col-lg-6 col-md-12">
        <div class="card p-4 seo-result">
            <h3>SEO Analysis for: <?php echo htmlspecialchars($url); ?></h3>
            <p><strong>Title:</strong> <?php echo $title ?: 'No title tag found'; ?></p>
            <p><strong>Meta Description:</strong> <?php echo $metaDescription ?: 'No meta description found'; ?></p>
            <p><strong>Meta Keywords:</strong> <?php echo $metaKeywords ?: 'No meta keywords found'; ?></p>
            <p><strong>H1 Tags:</strong> <?php echo count($h1Tags) ? implode(', ', $h1Tags) : 'No H1 tags found'; ?></p>
            <p><strong>Canonical:</strong> <?php echo $canonical ?: 'No canonical link tag found'; ?></p>
            <p><strong>Charset:</strong> <?php echo $charset ?: 'No charset specified'; ?></p>
            <p><strong>Author:</strong> <?php echo $author ?: 'No author meta tag found'; ?></p>
            <p><strong>Robots:</strong> <?php echo $robots ?: 'No robots meta tag found'; ?></p>
            <p><strong>Page Load Time:</strong> <?php echo $pageLoadTime; ?> seconds</p>
               
            </p>
        </div>
    </div>

    
            <div class="col-lg-6 col-md-12">
                <div class="card p-4 seo-result">
                    <h3>SEO Visuals</h3>
                    <!-- Internal vs External Links Chart -->
                    <div class="chart-container">
                        <canvas id="linkChart"></canvas>
                    </div>
                    <!-- Image Alt Texts Chart -->
                    <div class="chart-container">
                        <canvas id="imageAltChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEO Issues and Advice Section -->
        <?php if (count($issues) > 0): ?>
        <div class="row mt-5">
            <div class="col-md-12">
                <div class="card p-4 seo-issues">
                    <h3>SEO Issues Found</h3>
                    <ul>
                        <?php foreach ($issues as $issue): ?>
                            <li><?php echo $issue; ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <h4>SEO Optimization Tips</h4>
                    <ul>
                        <li><strong>Title Tag:</strong> Ensure the title tag is concise (50-60 characters) and includes your primary keywords.</li>
                        <li><strong>Meta Description:</strong> Write a compelling meta description (150-160 characters) that summarizes the page content.</li>
                        <li><strong>H1 Tags:</strong> Make sure to use only one H1 tag per page and include your main keyword.</li>
                        <li><strong>Image Alt Text:</strong> Add descriptive alt text to all images to improve accessibility and SEO.</li>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
<!-- Results Section -->
<?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
    <div class="row mt-5">
        <div class="col-md-6" style="max-height: 300px; overflow-y: auto;">
            <div class="card p-4">
                <h3>Internal Links</h3>
                <ul>
                    <?php if (!empty($links['internal'])): ?>
                        <?php foreach ($links['internal'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link); ?>" target="_blank"><?php echo htmlspecialchars($link); ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No internal links found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="col-md-6" >
            <div class="card p-4">
                <h3>External Links</h3>
                <ul>
                    <?php if (!empty($links['external'])): ?>
                        <?php foreach ($links['external'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link); ?>" target="_blank"><?php echo htmlspecialchars($link); ?></a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No external links found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>
<?php if ($_SERVER["REQUEST_METHOD"] == "POST"): ?>
    <div class="row mt-5">
        <!-- Valid Internal Links -->
        <div class="col-md-6">
            <div class="card p-4" style="max-height: 300px; overflow-y: auto;">
                <h3>Valid Internal Links</h3>
                <ul>
                    <?php if (!empty($internal_links_status['valid'])): ?>
                        <?php foreach ($internal_links_status['valid'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['url']); ?> (Status: <?php echo $link['status_code']; ?>)</a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No valid internal links found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Broken Internal Links -->
        <div class="col-md-6">
            <div class="card p-4" style="max-height: 300px; overflow-y: auto;">
                <h3>Broken Internal Links</h3>
                <ul>
                    <?php if (!empty($internal_links_status['broken'])): ?>
                        <?php foreach ($internal_links_status['broken'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['url']); ?> (Status: <?php echo $link['status_code']; ?>)</a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No broken internal links found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="row mt-5">
        <!-- Valid External Links -->
        <div class="col-md-6">
            <div class="card p-4" style="max-height: 300px; overflow-y: auto;">
                <h3>Valid External Links</h3>
                <ul>
                    <?php if (!empty($external_links_status['valid'])): ?>
                        <?php foreach ($external_links_status['valid'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['url']); ?> (Status: <?php echo $link['status_code']; ?>)</a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No valid external links found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <!-- Broken External Links -->
        <div class="col-md-6">
            <div class="card p-4" style="max-height: 300px; overflow-y: auto;">
                <h3>Broken External Links</h3>
                <ul>
                    <?php if (!empty($external_links_status['broken'])): ?>
                        <?php foreach ($external_links_status['broken'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank"><?php echo htmlspecialchars($link['url']); ?> (Status: <?php echo $link['status_code']; ?>)</a></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li>No broken external links found.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>




        <!-- Display Images Section -->
        <div class="row mt-5">
            <div class="col-md-12">
                <div class="card p-4">
                    <h3>Images Found on the Page</h3>
                    <div class="row">
                        <?php foreach ($imageAltTexts as $image): ?>
                            <div class="col-md-4">
                                <img src="<?php echo htmlspecialchars($image['src']); ?>" alt="<?php echo htmlspecialchars($image['alt']); ?>" class="img-fluid" />
                                <p><strong>Alt Text:</strong> <?php echo htmlspecialchars($image['alt']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>

    </div>

    <script>
        // Render Link Chart
        var linkChart = new Chart(document.getElementById('linkChart').getContext('2d'), {
            type: 'pie',
            data: {
                labels: ['Internal Links', 'External Links'],
                datasets: [{
                    data: [seoData.internalLinks, seoData.externalLinks],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderColor: ['#28a745', '#dc3545'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ": " + tooltipItem.raw;
                            }
                        }
                    }
                }
            }
        });

        // Render Image Alt Text Chart
        var imageAltChart = new Chart(document.getElementById('imageAltChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Images with Alt Text', 'Images without Alt Text'],
                datasets: [{
                    label: 'Images Analysis',
                    data: [seoData.imageAltCount, seoData.totalImages - seoData.imageAltCount],
                    backgroundColor: ['#007bff', '#ffc107'],
                    borderColor: ['#007bff', '#ffc107'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ": " + tooltipItem.raw;
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
