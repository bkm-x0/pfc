<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipment Management System — Home</title>
    <link rel="stylesheet" href="public/css/style.css">
    <style>
        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f1117 0%, #1a1d2a 50%, #0f1117 100%);
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(91, 127, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(91, 127, 255, 0.08) 0%, transparent 50%);
            animation: pulse 8s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 900px;
            padding: 40px 20px;
        }

        .hero-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--accent) 0%, #7b9aff 100%);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            margin: 0 auto 30px;
            box-shadow: 0 20px 60px rgba(91, 127, 255, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .hero h1 {
            font-family: var(--font-display);
            font-size: 4rem;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #fff 0%, #a0b0ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.3rem;
            color: var(--text-secondary);
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .hero-btn {
            padding: 16px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: all var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }

        .hero-btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #7b9aff 100%);
            color: white;
            box-shadow: 0 10px 30px rgba(91, 127, 255, 0.3);
        }

        .hero-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(91, 127, 255, 0.4);
        }

        .hero-btn-secondary {
            background: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border);
        }

        .hero-btn-secondary:hover {
            background: var(--bg-card-hover);
            border-color: var(--accent);
        }

        /* Features Section */
        .features {
            padding: 100px 20px;
            background: var(--bg-base);
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .features-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .features-header h2 {
            font-family: var(--font-display);
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--text-primary);
        }

        .features-header p {
            font-size: 1.1rem;
            color: var(--text-secondary);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .feature-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 40px 30px;
            transition: all var(--transition);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(91, 127, 255, 0.1) 0%, rgba(91, 127, 255, 0.05) 100%);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.4rem;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .feature-card p {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            padding: 80px 20px;
            background: var(--bg-card);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .stats-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            text-align: center;
        }

        .stat-item h3 {
            font-family: var(--font-display);
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 10px;
        }

        .stat-item p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }

        /* Footer */
        .footer {
            padding: 40px 20px;
            background: var(--bg-base);
            text-align: center;
            color: var(--text-muted);
        }

        .footer p {
            margin-bottom: 10px;
        }

        .footer a {
            color: var(--accent);
            text-decoration: none;
        }

        .footer a:hover {
            color: var(--accent-hover);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .features-header h2 {
                font-size: 2rem;
            }

            .stat-item h3 {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-icon">🖥️</div>
            <h1>Equipment Management System</h1>
            <p>
                Streamline your IT asset management with our powerful, intuitive platform. 
                Track equipment, manage inventory, and empower your team with real-time insights.
            </p>
            <div class="hero-buttons">
                <a href="public/pages/login.html" class="hero-btn hero-btn-primary">
                    <span>🔐</span> Sign In
                </a>
                <a href="public/pages/register.html" class="hero-btn hero-btn-secondary">
                    <span>✨</span> Create Account
                </a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="features-container">
            <div class="features-header">
                <h2>Powerful Features</h2>
                <p>Everything you need to manage your equipment efficiently</p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">📦</div>
                    <h3>Inventory Management</h3>
                    <p>Track all your equipment with detailed information, serial numbers, and real-time status updates.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">👥</div>
                    <h3>User Management</h3>
                    <p>Role-based access control for admins and clients. Secure authentication and authorization.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🛒</div>
                    <h3>Shopping Cart</h3>
                    <p>Clients can browse available products and add them to their cart for easy ordering.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">📊</div>
                    <h3>Analytics Dashboard</h3>
                    <p>Get insights with comprehensive statistics and reports on equipment usage and availability.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🖼️</div>
                    <h3>Image Management</h3>
                    <p>Upload multiple images per product with secure storage and primary image selection.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h3>Secure & Reliable</h3>
                    <p>Built with security best practices including password hashing, SQL injection prevention, and XSS protection.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-container">
            <div class="stat-item">
                <h3>100%</h3>
                <p>Secure</p>
            </div>
            <div class="stat-item">
                <h3>24/7</h3>
                <p>Available</p>
            </div>
            <div class="stat-item">
                <h3>Fast</h3>
                <p>Performance</p>
            </div>
            <div class="stat-item">
                <h3>Easy</h3>
                <p>To Use</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Equipment Management System. All rights reserved.</p>
        <p>
            <a href="public/pages/login.html">Sign In</a> | 
            <a href="public/pages/register.html">Register</a> | 
            <a href="README.md">Documentation</a>
        </p>
    </footer>
</body>
</html>
