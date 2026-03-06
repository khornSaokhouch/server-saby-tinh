<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home Page</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        /* Navbar */
        .navbar {
            background-color: #4CAF50;
            padding: 15px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
        }
        .navbar a:hover {
            text-decoration: underline;
        }

        /* Hero Section */
        .hero {
            background-color: #e8f5e9;
            text-align: center;
            padding: 80px 20px;
        }
        .hero h1 {
            font-size: 3em;
            margin-bottom: 20px;
            color: #2e7d32;
        }
        .hero p {
            font-size: 1.2em;
            color: #555;
        }
        .hero button {
            margin-top: 20px;
            padding: 10px 25px;
            font-size: 1em;
            border: none;
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }
        .hero button:hover {
            background-color: #388e3c;
        }

        /* Content Cards */
        .cards {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 50px 20px;
            gap: 20px;
        }
        .card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            padding: 20px;
            width: 250px;
            text-align: center;
        }
        .card h3 {
            margin-bottom: 15px;
            color: #333;
        }
        .card p {
            color: #666;
        }

        /* Footer */
        .footer {
            background-color: #4CAF50;
            color: white;
            text-align: center;
            padding: 20px;
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <div class="navbar">
        <div class="logo">MyWebsite</div>
        <div>
            <a href="#">Home</a>
            <a href="#">About</a>
            <a href="#">Services</a>
            <a href="#">Contact</a>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero">
        <h1>Welcome to My Website</h1>
        <p>Explore our services and find out how we can help you grow.</p>
        <button onclick="alert('Explore clicked!')">Explore Now</button>
    </div>

    <!-- Content Cards -->
    <div class="cards">
        <div class="card">
            <h3>Service One</h3>
            <p>High quality service to help you succeed in your projects.</p>
        </div>
        <div class="card">
            <h3>Service Two</h3>
            <p>Professional support and consulting for your business.</p>
        </div>
        <div class="card">
            <h3>Service Three</h3>
            <p>Innovative solutions tailored to your needs and goals.</p>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        &copy; <?php echo date("Y"); ?> MyWebsite. All rights reserved.
    </div>

</body>
</html>
