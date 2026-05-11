<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FreshBox - Delicious Meals Delivered</title>
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <style>
        body { 
            background-color: #fdfcf7; 
            font-family: 'Arial Black', Gadget, sans-serif;
        }
        .navbar { 
            background: white !important; 
            padding: 15px 0; 
            border-bottom: 1px solid #eee; 
        }
        .navbar-brand { 
            font-weight: 900; 
            color: #2c7a2c !important; 
            font-size: 1.8rem; 
            letter-spacing: -1px;
            display: flex;
            align-items: center;
        }
        .nav-link { 
            font-weight: 600; 
            color: #444 !important; 
            margin: 0 15px; 
            font-family: 'Segoe UI', sans-serif;
        }
        .btn-login { 
            border: 2px solid #2c7a2c; 
            color: #2c7a2c; 
            font-weight: bold; 
            border-radius: 8px; 
            padding: 8px 25px; 
            text-decoration: none; 
            font-family: 'Segoe UI', sans-serif;
            transition: 0.3s;
        }
        .btn-login:hover { background: #2c7a2c; color: white; }

        .hero-section { padding: 80px 0; }
        .hero-title { 
            font-size: 4.5rem; 
            font-weight: 900; 
            color: #212529; 
            line-height: 0.9; 
            margin-bottom: 25px;
            letter-spacing: -3px;
        }
        .hero-subtitle { 
            font-size: 1.25rem; 
            color: #555; 
            margin-bottom: 35px; 
            font-family: 'Segoe UI', sans-serif;
            font-weight: 500;
        }
        .main-hero-img {
            width: 100%;
            max-width: 550px;
            height: auto;
            border-radius: 50px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.2);
            transition: 0.4s;
        }
        .main-hero-img:hover { transform: scale(1.02); }

        .btn-main { 
            background: #212529; 
            color: white; 
            padding: 18px 45px; 
            font-weight: bold; 
            border-radius: 12px; 
            text-decoration: none; 
            display: inline-block;
            font-size: 1.1rem;
            font-family: 'Segoe UI', sans-serif;
            transition: 0.3s;
        }
        .btn-main:hover { background: #000; color: white; transform: translateY(-3px); }

        .promo-text {
            margin-top: 15px;
            color: #888;
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase;
            font-family: 'Segoe UI', sans-serif;
        }

        /* Plans Section */
        .plans-section { padding: 80px 0; background: white; }
        .plans-section h2 { font-weight: 900; letter-spacing: -1px; }

        .plan-card {
            border: 2px solid #e0e0e0;
            border-radius: 20px;
            padding: 35px 25px;
            text-align: center;
            transition: 0.3s;
            background: #fdfcf7;
            font-family: 'Segoe UI', sans-serif;
        }
        .plan-card:hover { border-color: #2c7a2c; transform: translateY(-6px); box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .plan-card.featured { border-color: #2c7a2c; background: white; position: relative; }
        .plan-card.vip-card { border-color: #ffd700; background: #fffef5; }

        .featured-badge {
            position: absolute; top: -14px; left: 50%; transform: translateX(-50%);
            background: #2c7a2c; color: white;
            padding: 4px 20px; border-radius: 20px; font-size: .8rem; font-weight: bold;
            font-family: 'Segoe UI', sans-serif; white-space: nowrap;
        }
        .plan-price { font-size: 3rem; font-weight: 900; color: #212529; }
        .plan-price span { font-size: 1rem; color: #888; font-weight: normal; }
        .plan-features { list-style: none; padding: 0; text-align: left; margin: 20px 0; font-family: 'Segoe UI', sans-serif; }
        .plan-features li { padding: 6px 0; border-bottom: 1px solid #f0f0f0; font-size: .95rem; }
        .plan-features li:last-child { border: none; }

        /* How it works */
        .how-section { padding: 80px 0; background: #fdfcf7; }
        .step-circle {
            width: 60px; height: 60px; border-radius: 50%;
            background: #2c7a2c; color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; font-weight: 900;
            margin: 0 auto 15px;
        }

        footer { background: #212529; color: #aaa; padding: 30px 0; font-family: 'Segoe UI', sans-serif; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#">
            <span style="margin-right: 8px;">📦</span> FreshBox
        </a>
        <div class="d-flex align-items-center">
            <div class="d-none d-md-flex">
                <a href="#plans" class="nav-link">Our Plans</a>
                <a href="#how" class="nav-link">How It Works</a>
            </div>
            <a href="../php/views/auth/login.php" class="btn-login ms-3">Log in</a>
        </div>
    </div>
</nav>

<header class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-start">
                <h1 class="hero-title">NOTHING<br>HITS LIKE<br>HOME COOKING</h1>
                <p class="hero-subtitle">Cook up 100+ delicious recipes — <br>ready in under 30 minutes! Flexible plans for everyone.</p>
                <a href="register.html" class="btn-main shadow-lg">See Pricing & Plans</a>
                <p class="promo-text small">No commitment. Skip or cancel anytime.</p>
            </div>
            <div class="col-md-6 text-center">
                <img src="../images/e747b6b30bad12bfeef25c52bfdc381f.jpg" class="main-hero-img" alt="Delicious Food">
            </div>
        </div>
    </div>
</header>

<section class="plans-section" id="plans">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">Choose Your Plan</h2>
        <p class="text-center text-muted mb-5" style="font-family:'Segoe UI',sans-serif;">Start with any plan. Upgrade or cancel anytime.</p>
        <div class="row g-4 justify-content-center">

            <!-- Standard -->
            <div class="col-md-4">
                <div class="plan-card">
                    <h5 class="text-muted text-uppercase" style="font-family:'Segoe UI',sans-serif;letter-spacing:2px;">Standard</h5>
                    <div class="plan-price my-3">299 <span>EGP/mo</span></div>
                    <ul class="plan-features">
                        <li>✅ Medium box</li>
                        <li>✅ 2 swaps/month</li>
                        <li>✅ 5–6 fresh items</li>
                        <li>✅ Recipe cards included</li>
                        <li>✅ Skip or pause anytime</li>
                    </ul>
                    <a href="../php/views/auth/register.php" class="btn btn-outline-success w-100 py-2 fw-bold">Get Started</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="plan-card featured position-relative">
                    <span class="featured-badge">⭐ Most Popular</span>
                    <h5 class="text-muted text-uppercase" style="font-family:'Segoe UI',sans-serif;letter-spacing:2px;">Premium</h5>
                    <div class="plan-price my-3">499 <span>EGP/mo</span></div>
                    <ul class="plan-features">
                        <li>✅ Large box</li>
                        <li>✅ 4 swaps/month</li>
                        <li>✅ 8–10 premium items</li>
                        <li>✅ Recipe cards included</li>
                        <li>✅ Skip or pause anytime</li>
                    </ul>
                    <a href="../php/views/auth/register.php" class="btn btn-success w-100 py-2 fw-bold">Get Started</a>
                </div>
            </div>

            <!-- VIP -->
            <div class="col-md-4">
                <div class="plan-card vip-card">
                    <h5 class="text-uppercase" style="color:#b8860b;font-family:'Segoe UI',sans-serif;letter-spacing:2px;">⭐ VIP</h5>
                    <div class="plan-price my-3" style="color:#b8860b;">799 <span style="color:#888;">EGP/mo</span></div>
                    <ul class="plan-features">
                        <li>✅ Large box</li>
                        <li>✅ 6 swaps/month</li>
                        <li>✅ Early swap access</li>
                        <li>✅ Exclusive VIP-only items</li>
                        <li>✅ Priority support</li>
                    </ul>
                    <a href="../php/views/auth/register.php" class="btn w-100 py-2 fw-bold" style="background:#ffd700;color:#333;border:none;">Go VIP 👑</a>
                </div>
            </div>

        </div>
    </div>
</section>

<section class="how-section" id="how">
    <div class="container">
        <h2 class="text-center fw-bold mb-2">How It Works</h2>
        <p class="text-center text-muted mb-5" style="font-family:'Segoe UI',sans-serif;">3 simple steps to your first box</p>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="step-circle">1</div>
                <h5 class="fw-bold">Choose Your Plan</h5>
                <p class="text-muted" style="font-family:'Segoe UI',sans-serif;">Pick Standard, Premium, or VIP based on your needs and budget.</p>
            </div>
            <div class="col-md-4">
                <div class="step-circle">2</div>
                <h5 class="fw-bold">Customize Your Box</h5>
                <p class="text-muted" style="font-family:'Segoe UI',sans-serif;">Swap items, add extras, and set your preferences before delivery.</p>
            </div>
            <div class="col-md-4">
                <div class="step-circle">3</div>
                <h5 class="fw-bold">Enjoy Your Delivery</h5>
                <p class="text-muted" style="font-family:'Segoe UI',sans-serif;">Fresh ingredients delivered to your door every month. Cook & enjoy!</p>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="fw-bold text-white fs-5">📦 FreshBox</div>
        <div>© 2026 FreshBox. All rights reserved.</div>
        <div class="d-flex gap-3">
            <a href="../php/views/auth/login.php" class="text-decoration-none" style="color:#aaa;">Log in</a>
            <a href="../php/views/auth/register.php" class="text-decoration-none" style="color:#aaa;">Sign up</a>
        </div>
    </div>
</footer>

<script src="../js/bootstrap.bundle.min.js"></script>
</body>
</html>