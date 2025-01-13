<?php
// Port checking function
function check_port($host, $port) {
    if (!is_numeric($port) || $port <= 0 || $port > 65535) {
        return false; // Invalid port range
    }

    $connection = @fsockopen($host, (int)$port, $errno, $errstr, 2); // Timeout 2 seconds.
    
    if (is_resource($connection)) {
        fclose($connection); // Close the connection if successful
        return true;  // Port is open
    } else {
        return false;  // Port is closed or unreachable
    }
}

$host = "";
$port = "";
$checkResult = "";
$resultColor = "#000000";  // Default text color (black)

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $host = $_POST['host'];
    $port = $_POST['port'];

    if ($port == "custom") {
        $port = $_POST['customPort']; // Get the custom port input
    }

    if (filter_var($host, FILTER_VALIDATE_IP)) {
        if (check_port($host, $port)) {
            $checkResult = "Port {$port} is OPEN on {$host}.";
            $resultColor = "#4CAF50";  // Green for open port
        } else {
            $checkResult = "Port {$port} is CLOSED on {$host}.";
            $resultColor = "#f44336";  // Red for closed port
        }
    } else {
        $checkResult = "Invalid IP address.";
        $resultColor = "#f44336";  // Red for invalid IP
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Port Checker and SEO Tool - BlueLog</title>

    <!-- SEO Meta Tags -->
    <meta name="description" content="Port Checker and SEO Tool for analyzing open or closed ports and improving website SEO.">
    <meta name="keywords" content="port checker, website SEO, check open port, open port checker, SEO tool">
    <meta name="robots" content="index, follow">

    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="Port Checker and SEO Tool">
    <meta property="og:description" content="Analyze open and closed ports on any IP address and improve your website's SEO with our easy-to-use tools.">
    <meta property="og:image" content="image.jpg">
    <meta property="og:url" content="https://bluelog.in/port-checker.php">

    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:title" content="Port Checker and SEO Tool">
    <meta name="twitter:description" content="Analyze open and closed ports on any IP address and improve your website's SEO with our easy-to-use tools.">
    <meta name="twitter:image" content="image.jpg">
    <meta name="twitter:card" content="summary_large_image">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

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

<!-- Main Content -->
<div class="container">
    <h1>Port Checker Tool</h1>
    <p>Check whether a port is open or closed on any given IP address.</p>

    <!-- Port Checker Form -->
    <form method="POST">
        <div class="row mb-4">
            <div class="col-md-6">
                <label for="host">IP Address:</label>
                <input type="text" name="host" id="host" value="<?= htmlspecialchars($host) ?>" class="form-control" placeholder="Enter IP address" required>
            </div>
            <div class="col-md-6">
                <label for="port">Port:</label>
                <select name="port" id="port" class="form-control" onchange="enableCustomPortInput(this.value)">
                    <option value="80" <?= $port == "80" ? 'selected' : '' ?>>80 (HTTP)</option>
                    <option value="443" <?= $port == "443" ? 'selected' : '' ?>>443 (HTTPS)</option>
                    <option value="21" <?= $port == "21" ? 'selected' : '' ?>>21 (FTP)</option>
                    <option value="22" <?= $port == "22" ? 'selected' : '' ?>>22 (SSH)</option>
                    <option value="25" <?= $port == "25" ? 'selected' : '' ?>>25 (SMTP)</option>
                    <option value="110" <?= $port == "110" ? 'selected' : '' ?>>110 (POP3)</option>
                    <option value="3306" <?= $port == "3306" ? 'selected' : '' ?>>3306 (MySQL)</option>
                    <option value="5432" <?= $port == "5432" ? 'selected' : '' ?>>5432 (PostgreSQL)</option>
                    <option value="3389" <?= $port == "3389" ? 'selected' : '' ?>>3389 (RDP)</option>
                    <option value="8080" <?= $port == "8080" ? 'selected' : '' ?>>8080 (HTTP Proxy)</option>
                    <option value="custom" <?= empty($port) || !in_array($port, ["80", "443", "21", "22", "25", "110", "3306", "5432", "3389", "8080"]) ? 'selected' : '' ?>>Custom Port</option>
                </select>
            </div>
        </div>

        <!-- Custom Port Input (only visible if 'Custom Port' is selected) -->
        <input type="number" name="customPort" id="customPort" class="form-control" value="<?= !in_array($port, ["80", "443", "21", "22", "25", "110", "3306", "5432", "3389", "8080"]) ? htmlspecialchars($port) : '' ?>" placeholder="Enter custom port" <?= !in_array($port, ["80", "443", "21", "22", "25", "110", "3306", "5432", "3389", "8080"]) ? '' : 'style="display:none;"' ?>>

        <button type="submit" class="btn btn-primary mt-3">Check Port</button>
    </form>

    <!-- Display Result -->
    <?php if ($checkResult): ?>
        <div class="result mt-3">
            <strong>Result:</strong> <?= htmlspecialchars($checkResult) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2025 BlueLog. All Rights Reserved.</p>
</div>

<!-- Bootstrap JS (including Popper) -->
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>

<!-- Custom JS for handling the display of custom port input -->
<script>
    function enableCustomPortInput(selectedPort) {
        var customPortInput = document.getElementById('customPort');
        if (selectedPort === "custom") {
            customPortInput.style.display = 'block';  // Show custom port input
        } else {
            customPortInput.style.display = 'none';  // Hide custom port input
        }
    }

    // Auto-detect the user's IP address and set it in the IP input field
    window.onload = function() {
        fetch('https://api.ipify.org?format=json')
            .then(response => response.json())
            .then(data => {
                document.getElementById('host').value = data.ip;
            });
    };
</script>

</body>
</html>
