<?php
session_start();
include('config/database.php'); // Include your database connection
include('config/utilities.php'); // Include your database connection

// Process login request (your existing PHP login code stays here)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login_id = $_POST['login_id'];
    $password = $_POST['password'];
    $error_message = "無効なログインIDまたはパスワードです！";
    try {
        $stmt = $conn->prepare("SELECT * FROM admin_access WHERE agent_login_id = :login_id LIMIT 1");
        $stmt->bindParam(':login_id', $login_id);
        $stmt->execute();
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['agent_password'])) {
            $_SESSION['admin'] = $admin['agent_name'];
            $_SESSION['agent_id'] = $admin['agent_id'];
            $_SESSION['access_level'] = $admin['access_level'];
            $_SESSION['agent_market'] = $admin['agent_market'];
            $_SESSION['agent_credit_limit'] = $admin['agent_credit_limit'];
            $_SESSION['agent_leader'] = $admin['agent_leader'];
            $_SESSION['agent_login_id'] = $admin['agent_login_id'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = $error_message;
        }
    } catch (PDOException $e) {
        $error = $error_message;
    }
}
?>


<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="utf-8">
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <title>ケングループ - ERP</title>
  <meta name="description" content="">
  <meta name="keywords" content="">

  <!-- Favicons -->
  <link href="assets/img/favicon.png" rel="icon">
  <link href="assets/img/apple-touch-icon.png" rel="apple-touch-icon">

  <!-- Fonts -->
  <link href="https://fonts.googleapis.com" rel="preconnect">
  <link href="https://fonts.gstatic.com" rel="preconnect" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&family=Raleway:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

  <!-- Vendor CSS Files -->
  <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/vendor/bootstrap-icons/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/vendor/aos/aos.css" rel="stylesheet">
  <link href="assets/vendor/swiper/swiper-bundle.min.css" rel="stylesheet">
  <link href="assets/vendor/glightbox/css/glightbox.min.css" rel="stylesheet">

  <!-- Main CSS File -->
  <link href="assets/css/main.css" rel="stylesheet">

  <!-- =======================================================
  * Template Name: Day
  * Template URL: https://bootstrapmade.com/day-multipurpose-html-template-for-free/
  * Updated: Aug 07 2024 with Bootstrap v5.3.3
  * Author: BootstrapMade.com
  * License: https://bootstrapmade.com/license/
  ======================================================== -->
</head>

<body class="index-page">

  <header id="header" class="header fixed-top">

    <div class="topbar d-flex align-items-center">
      <div class="container d-flex justify-content-center justify-content-md-between">
        <div class="contact-info d-flex align-items-center">
          <i class="bi bi-envelope d-flex align-items-center"><a href="mailto:sales@navbright.tech">sales@navbright.tech</a></i>
          <i class="bi bi-phone d-flex align-items-center ms-4"><span>+81 5589 55488 55</span></i>
        </div>
        <div class="social-links d-none d-md-flex align-items-center">
          <a href="#" class="twitter"><i class="bi bi-twitter-x"></i></a>
          <a href="#" class="facebook"><i class="bi bi-facebook"></i></a>
          <a href="#" class="instagram"><i class="bi bi-instagram"></i></a>
          <a href="#" class="linkedin"><i class="bi bi-linkedin"></i></a>
        </div>
      </div>
    </div><!-- End Top Bar -->

    <div class="branding d-flex align-items-center">

      <div class="container position-relative d-flex align-items-center justify-content-between">
        <a href="index.php" class="logo d-flex align-items-center">
          <h1 class="sitename">ケングループ - ERP</h1>
        </a>

        <nav id="navmenu" class="navmenu">
          <ul>
            <li><a href="#hero" class="active">ホーム</a></li>
            <li><a href="#about">私たちについて</a></li>
            <li><a href="#services">サービス</a></li>
            <li><a href="#portfolio">ポートフォリオ</a></li>
            <li><a href="#team">チーム</a></li>
            <li><a href="#contact">お問い合わせ</a></li>
            <li><a href="#" data-bs-toggle="modal" data-bs-target="#loginModal">ログイン</a></li>
          </ul>
          <i class="mobile-nav-toggle d-xl-none bi bi-list"></i>
        </nav>
      </div>

    </div>

  </header>

  <main class="main">

    <!-- Hero Section -->
    <section id="hero" class="hero section dark-background">

      <img src="assets/img/hero-bg.jpg" alt="" data-aos="fade-in">

      <div class="container" data-aos="fade-up" data-aos-delay="100">
        <div class="row justify-content-start">
          <div class="col-lg-8">
            <h2>ケングループへようこそ</h2>
            <p>私たちは、Bootstrapを使用してウェブサイトを制作する才能あるデザイナーチームです</p>
            <a href="#about" class="btn-get-started">始めましょう</a>
          </div>
        </div>
      </div>

    </section><!-- /Hero Section -->

    <!-- About Section -->
    <section id="about" class="about section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <span>私たちについて<br></span>
        <h2>私たちについて<br></h2>
        <p>ケングループは、2022年に沖縄で設立されたビジネスコンサルティング会社です。私たちは、CEOの西本健人、CFOの近川志田、CTOのジョセフィンによって率いられています。</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-6 order-1 order-lg-2" data-aos="fade-up" data-aos-delay="100">
            <img src="assets/img/about.jpg" class="img-fluid" alt="">
          </div>

          <div class="col-lg-6 order-2 order-lg-1 content" data-aos="fade-up" data-aos-delay="200">
            <h3>革新的なビジネスソリューションを提供</h3>
            <p class="fst-italic">
              私たちは、企業の成長と成功をサポートするために、革新的なビジネスソリューションを提供しています。
            </p>
            <ul>
              <li><i class="bi bi-check-circle"></i> <span>クライアントのニーズに応じたカスタマイズされたコンサルティングサービス。</span></li>
              <li><i class="bi bi-check-circle"></i> <span>最新のテクノロジーとデータ分析に基づく戦略的アプローチ。</span></li>
              <li><i class="bi bi-check-circle"></i> <span>持続可能なビジネス成長を実現するためのソリューション提供。</span></li>
            </ul>
            <a href="#" class="read-more"><span>もっと読む</span><i class="bi bi-arrow-right"></i></a>
          </div>

        </div>

      </div>

    </section><!-- /About Section -->

    <!-- Team Section -->
    <section id="team" class="team section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <span>チーム</span>
        <h2>チーム</h2>
        <p>ケングループの優れたチームメンバーをご紹介します。</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row">

          <div class="col-lg-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="100">
            <div class="member">
              <img src="assets/img/team/team-1.jpg" class="img-fluid" alt="">
              <div class="member-content">
                <h4>西本健人</h4>
                <span>CEO</span>
                <p>
                  ケングループを率いるCEOであり、ビジネス戦略と成長をサポートします。
                </p>
                <div class="social">
                  <a href=""><i class="bi bi-twitter-x"></i></a>
                  <a href=""><i class="bi bi-facebook"></i></a>
                  <a href=""><i class="bi bi-instagram"></i></a>
                  <a href=""><i class="bi bi-linkedin"></i></a>
                </div>
              </div>
            </div>
          </div><!-- End Team Member -->

          <div class="col-lg-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="200">
            <div class="member">
              <img src="assets/img/team/team-2.jpg" class="img-fluid" alt="">
              <div class="member-content">
                <h4>近川志田</h4>
                <span>CFO</span>
                <p>
                  財務管理と持続可能な経済戦略をサポートするCFOです。
                </p>
                <div class="social">
                  <a href=""><i class="bi bi-twitter-x"></i></a>
                  <a href=""><i class="bi bi-facebook"></i></a>
                  <a href=""><i class="bi bi-instagram"></i></a>
                  <a href=""><i class="bi bi-linkedin"></i></a>
                </div>
              </div>
            </div>
          </div><!-- End Team Member -->

          <div class="col-lg-4 col-md-6 d-flex" data-aos="fade-up" data-aos-delay="300">
            <div class="member">
              <img src="assets/img/team/team-3.jpg" class="img-fluid" alt="">
              <div class="member-content">
                <h4>ジョセフィン</h4>
                <span>CTO</span>
                <p>
                  テクノロジーの革新とビジネス効率化を推進するCTOです。
                </p>
                <div class="social">
                  <a href=""><i class="bi bi-twitter-x"></i></a>
                  <a href=""><i class="bi bi-facebook"></i></a>
                  <a href=""><i class="bi bi-instagram"></i></a>
                  <a href=""><i class="bi bi-linkedin"></i></a>
                </div>
              </div>
            </div>
          </div><!-- End Team Member -->

        </div>

      </div>

    </section><!-- /Team Section -->

    <!-- Cards Section -->
    <section id="cards" class="cards section">

      <div class="container">

        <div class="row no-gutters">

          <div class="col-lg-4 col-md-6 card" data-aos="fade-up" data-aos-delay="100">
            <span>01</span>
            <h4>ビジネス戦略</h4>
            <p>お客様のニーズに基づいたビジネス戦略の策定と実行をサポートします。</p>
          </div><!-- End Card Item -->

          <div class="col-lg-4 col-md-6 card" data-aos="fade-up" data-aos-delay="200">
            <span>02</span>
            <h4>マーケティングソリューション</h4>
            <p>市場分析に基づいた効果的なマーケティング戦略を提供します。</p>
          </div><!-- End Card Item -->

          <div class="col-lg-4 col-md-6 card" data-aos="fade-up" data-aos-delay="300">
            <span>03</span>
            <h4>技術サポート</h4>
            <p>最新のテクノロジーを活用して、ビジネスの効率化を図ります。</p>
          </div><!-- End Card Item -->

          <div class="col-lg-4 col-md-6 card" data-aos="fade-up" data-aos-delay="400">
            <span>04</span>
            <h4>財務管理</h4>
            <p>効果的な財務管理ソリューションを提供し、経営の健全性を確保します。</p>
          </div><!-- End Card Item -->

          <div class="col-lg-4 col-md-6 card" data-aos="fade-up" data-aos-delay="400">
            <span>05</span>
            <h4>市場拡大支援</h4>
            <p>国内外の市場拡大を目指す企業をサポートします。</p>
          </div><!-- End Card Item -->

          <div class="col-lg-4 col-md-6 card" data-aos="fade-up" data-aos-delay="600">
            <span>06</span>
            <h4>人材開発</h4>
            <p>従業員のスキル向上と成長を促進するプログラムを提供します。</p>
          </div><!-- End Card Item -->

        </div>

      </div>

    </section><!-- /Cards Section -->

    <!-- Clients Section -->
    <section id="clients" class="clients section light-background">

      <div class="container">

        <div class="swiper init-swiper">
          <script type="application/json" class="swiper-config">
            {
              "loop": true,
              "speed": 600,
              "autoplay": {
                "delay": 5000
              },
              "slidesPerView": "auto",
              "pagination": {
                "el": ".swiper-pagination",
                "type": "bullets",
                "clickable": true
              },
              "breakpoints": {
                "320": {
                  "slidesPerView": 2,
                  "spaceBetween": 40
                },
                "480": {
                  "slidesPerView": 3,
                  "spaceBetween": 60
                },
                "640": {
                  "slidesPerView": 4,
                  "spaceBetween": 80
                },
                "992": {
                  "slidesPerView": 6,
                  "spaceBetween": 120
                }
              }
            }
          </script>
          <div class="swiper-wrapper align-items-center">
            <div class="swiper-slide"><img src="assets/img/clients/client-1.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="assets/img/clients/client-2.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="assets/img/clients/client-3.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="assets/img/clients/client-4.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="assets/img/clients/client-5.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="assets/img/clients/client-6.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="assets/img/clients/client-7.png" class="img-fluid" alt=""></div>
            <div class="swiper-slide"><img src="assets/img/clients/client-8.png" class="img-fluid" alt=""></div>
          </div>
        </div>

      </div>

    </section><!-- /Clients Section -->

    <!-- Services Section -->
    <section id="services" class="services section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <span>サービス</span>
        <h2>サービス</h2>
        <p>ケングループは、お客様のニーズに合わせた幅広いサービスを提供しています。</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="row gy-4">

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
            <div class="service-item  position-relative">
              <div class="icon">
                <i class="bi bi-activity"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>ビジネスコンサルティング</h3>
              </a>
              <p>企業の成長を促進するためのコンサルティングサービスを提供します。</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-broadcast"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>マーケティング戦略</h3>
              </a>
              <p>データに基づく効果的なマーケティング戦略を策定し、実行します。</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-easel"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>デジタルトランスフォーメーション</h3>
              </a>
              <p>デジタル技術を活用してビジネスプロセスを最適化します。</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="400">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-bounding-box-circles"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>人材育成</h3>
              </a>
              <p>企業の持続的な成長を支える人材育成プログラムを提供します。</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="500">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-calendar4-week"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>プロジェクト管理</h3>
              </a>
              <p>プロジェクトの計画から実行、完了までの管理をサポートします。</p>
            </div>
          </div><!-- End Service Item -->

          <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="600">
            <div class="service-item position-relative">
              <div class="icon">
                <i class="bi bi-chat-square-text"></i>
              </div>
              <a href="#" class="stretched-link">
                <h3>カスタマーサポート</h3>
              </a>
              <p>お客様のニーズに対応する迅速で信頼性の高いカスタマーサポートを提供します。</p>
            </div>
          </div><!-- End Service Item -->

        </div>

      </div>

    </section><!-- /Services Section -->

    <!-- Call To Action Section -->
    <section id="call-to-action" class="call-to-action section dark-background">

      <img src="assets/img/cta-bg.jpg" alt="">

      <div class="container">
        <div class="row justify-content-center" data-aos="zoom-in" data-aos-delay="100">
          <div class="col-xl-10">
            <div class="text-center">
              <h3>お問い合わせ</h3>
              <p>私たちのサービスに関心をお持ちですか？ぜひお問い合わせください。</p>
              <a class="cta-btn" href="#">お問い合わせ</a>
            </div>
          </div>
        </div>
      </div>

    </section><!-- /Call To Action Section -->

    <!-- Portfolio Section -->
    <section id="portfolio" class="portfolio section">

      <!-- Section Title -->
      <div class="container section-title" data-aos="fade-up">
        <span>ポートフォリオ</span>
        <h2>ポートフォリオ</h2>
        <p>私たちが手掛けたプロジェクトをご覧ください。</p>
      </div><!-- End Section Title -->

      <div class="container">

        <div class="isotope-layout" data-default-filter="*" data-layout="masonry" data-sort="original-order">

          <ul class="portfolio-filters isotope-filters" data-aos="fade-up" data-aos-delay="100">
            <li data-filter="*" class="filter-active">すべて</li>
            <li data-filter=".filter-app">アプリ</li>
            <li data-filter=".filter-product">カード</li>
            <li data-filter=".filter-branding">ウェブ</li>
          </ul><!-- End Portfolio Filters -->

          <div class="row gy-4 isotope-container" data-aos="fade-up" data-aos-delay="200">

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-app">
              <img src="assets/img/masonry-portfolio/masonry-portfolio-1.jpg" class="img-fluid" alt="">
              <div class="portfolio-info">
                <h4>アプリ 1</h4>
                <p>Lorem ipsum, dolor sit</p>
                <a href="assets/img/masonry-portfolio/masonry-portfolio-1.jpg" title="アプリ 1" data-gallery="portfolio-gallery-app" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                <a href="portfolio-details.html" title="詳細" class="details-link"><i class="bi bi-link-45deg"></i></a>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-product">
              <img src="assets/img/masonry-portfolio/masonry-portfolio-2.jpg" class="img-fluid" alt="">
              <div class="portfolio-info">
                <h4>製品 1</h4>
                <p>Lorem ipsum, dolor sit</p>
                <a href="assets/img/masonry-portfolio/masonry-portfolio-2.jpg" title="製品 1" data-gallery="portfolio-gallery-product" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                <a href="portfolio-details.html" title="詳細" class="details-link"><i class="bi bi-link-45deg"></i></a>
              </div>
            </div><!-- End Portfolio Item -->

            <div class="col-lg-4 col-md-6 portfolio-item isotope-item filter-branding">
              <img src="assets/img/masonry-portfolio/masonry-portfolio-3.jpg" class="img-fluid" alt="">
              <div class="portfolio-info">
                <h4>ブランディング 1</h4>
                <p>Lorem ipsum, dolor sit</p>
                <a href="assets/img/masonry-portfolio/masonry-portfolio-3.jpg" title="ブランディング 1" data-gallery="portfolio-gallery-branding" class="glightbox preview-link"><i class="bi bi-zoom-in"></i></a>
                <a href="portfolio-details.html" title="詳細" class="details-link"><i class="bi bi-link-45deg"></i></a>
              </div>
            </div><!-- End Portfolio Item -->
