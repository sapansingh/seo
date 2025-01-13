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

function get_internal_external_links($html, $base_url) {
    preg_match_all('/<a href="([^"]+)"/i', $html, $matches);
    $links = $matches[1] ?? [];
    $internal_links = [];
    $external_links = [];

    foreach ($links as $link) {
        $parsed_url = parse_url($link);
        if (isset($parsed_url['host']) && $parsed_url['host'] !== parse_url($base_url, PHP_URL_HOST)) {
            $external_links[] = $link;
        } else {
            $internal_links[] = $link;
        }
    }

    return ['internal' => $internal_links, 'external' => $external_links];
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
                internalLinksList: " . json_encode($links['internal']) . ",
                externalLinksList: " . json_encode($links['external']) . ",
                imageAltTexts: " . json_encode($imageAltTexts) . ",
                issues: " . json_encode($issues) . "
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
            background-color: #f9f9f9;
            font-family: 'Arial', sans-serif;
        }
        .container {
            margin-top: 50px;
        }
        h1 {
            color: #4CAF50;
        }
        .card {
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .chart-container {
            margin-top: 30px;
            max-width: 100%;
            height: 250px;
        }
        .table-container {
            max-height: 300px;
            overflow-y: auto;
        }
        table td, table th {
            word-wrap: break-word;
            max-width: 200px;
            overflow: hidden;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .form-control {
            border-radius: 8px;
            padding: 10px;
        }
        .btn {
            border-radius: 8px;
        }
        .seo-result {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .seo-result h3 {
            color: #28a745;
        }
        .seo-issues {
            background-color: #f8d7da;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        .seo-issues ul {
            list-style-type: none;
        }
        .seo-issues li {
            margin-bottom: 10px;
        }
        .navbar {
            margin-bottom: 30px;
        }
        @media (max-width: 768px) {
            .chart-container {
                height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar Section -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">SEO Checker</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container" style="margin-top: 100px;">
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
