<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CORS Tester Tool</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KyZXEJ2Q7nXzVf1E5bmJ0cG4Qyq5ODj5mE4Y5rb3Lh8df9c2Kr7vh2OjlTh1ylVg" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Arial', sans-serif;
        }

        .container {
            max-width: 900px;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #007bff;
            color: white;
            font-size: 1.5rem;
            text-align: center;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .btn-custom {
            background-color: #007bff;
            color: white;
            font-size: 1.1rem;
        }

        .btn-custom:hover {
            background-color: #0056b3;
            transform: scale(1.05);
            transition: transform 0.3s ease;
        }

        .input-group-text {
            background-color: #f1f1f1;
            border: none;
        }

        .card-body {
            padding: 2.5rem;
        }

        .form-control {
            border-radius: 10px;
        }

        .result {
            margin-top: 30px;
        }

        .result p {
            margin: 5px 0;
        }

        .alert {
            border-radius: 10px;
        }

        .preformatted {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-top: 15px;
            overflow-x: auto;
        }

        .loading-indicator {
            display: none;
            width: 100%;
            height: 4px;
            background-color: #007bff;
            animation: loading 2s infinite ease-in-out;
        }

        @keyframes loading {
            0% {
                width: 0;
            }
            50% {
                width: 70%;
            }
            100% {
                width: 100%;
            }
        }

        /* Footer Styling */
        .footer {
            text-align: center;
            padding: 20px 0;
            background-color: #212529;
            color: white;
            position: absolute;
            width: 100%;
            bottom: 0;
        }
    </style>
</head>

<body class="d-flex justify-content-center align-items-center min-vh-100">

    <div style="justify-self: center;" >
        <!-- Header -->
        <div class="card" >
            <div class="card-header">
                <strong>CORS Tester Tool</strong>
            </div>

            <div class="card-body">
                <h5 class="card-title text-center mb-4">Check if an API allows CORS</h5>

                <!-- Loading Indicator -->
                <div id="loading" class="loading-indicator"></div>

                <!-- CORS Test Form -->
                <form id="corsForm">
                    <div class="mb-3">
                        <label for="url" class="form-label">Enter URL to Test CORS</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-link"></i></span>
                            <input type="url" class="form-control" id="url" name="url" placeholder="https://api.example.com" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-custom w-100">Test CORS</button>
                </form>

                <!-- Result Section -->
                <div id="result" class="result mt-4"></div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2025 CORS Tester Tool | Designed with ❤️ by Your Name</p>
    </div>

    <!-- Bootstrap 5 JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha384-oBqDVmMz4fnFO9gybPa8Ay0cB2gFqz2dd5m2Ino/MxI4/voQW5yI8j2aF1dI7dJr" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js" integrity="sha384-pzjw8f+ua7Kw1TIq0p7e5bqXf4mDpkD7GJ03c5hckx5YoV9kX2dVw4fv5Gi5kBhT" crossorigin="anonymous"></script>

    <script>
        // Handle form submission
        document.getElementById('corsForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const url = document.getElementById('url').value;

            // Show loading indicator
            document.getElementById('loading').style.display = 'block';
            document.getElementById('result').innerHTML = '';

            fetch('test-cors.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: 'url=' + encodeURIComponent(url)
            })
                .then(response => response.json())
                .then(data => {
                    document.getElementById('loading').style.display = 'none';

                    const resultDiv = document.getElementById('result');

                    if (data.error) {
                        resultDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    } else {
                        let corsInfo = `
                            <div class="alert alert-info">
                                <strong>CORS Status:</strong> ${data.status}
                            </div>
                            <ul class="list-group">
                                <li class="list-group-item"><strong>Access-Control-Allow-Origin:</strong> ${data.allow_origin || 'Not Found'}</li>
                                <li class="list-group-item"><strong>Access-Control-Allow-Methods:</strong> ${data.allow_methods || 'Not Found'}</li>
                                <li class="list-group-item"><strong>Access-Control-Allow-Headers:</strong> ${data.allow_headers || 'Not Found'}</li>
                                <li class="list-group-item"><strong>Access-Control-Allow-Credentials:</strong> ${data.allow_credentials || 'Not Found'}</li>
                            </ul>
                        `;
                        resultDiv.innerHTML = corsInfo;

                        // Show message if API is accessible to everyone
                        if (data.public_access === 'Yes') {
                            resultDiv.innerHTML += `<div class="alert alert-success mt-3">API is accessible for everyone!</div>`;
                        } else {
                            resultDiv.innerHTML += `<div class="alert alert-warning mt-3">API is not accessible for everyone.</div>`;
                        }

                        // Display API data for debugging
                        resultDiv.innerHTML += `
                            <h4 class="mt-3">API Data (CORS Headers):</h4>
                            <pre class="preformatted">${JSON.stringify(data.api_data, null, 2)}</pre>
                        `;
                    }
                })
                .catch(error => {
                    document.getElementById('loading').style.display = 'none';
                    document.getElementById('result').innerHTML = `<div class="alert alert-danger">An error occurred. Please try again.</div>`;
                });
        });
    </script>

</body>

</html>
