<?php
// Trang mẫu được cấp để kiểm tra môi trường Linux, PHP và Shell.
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>CommunityPass</title>
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="lab-intro">
<a class="skip-link" href="#main">Đến nội dung chính</a>
<header class="site-header">
<div class="header-inner">
<h1 class="brand">CommunityPass</h1>
<p>Phần mềm đăng ký tham dự sự kiện.</p>
</div>
</header>
<main id="main" class="lab-intro-main">
<div class="page-heading">
<h2>Mã nguồn PHP đã chạy trên máy của bạn</h2>
<p>Đây là trang mẫu được cấp để kiểm tra môi trường. Bài thực hành tập trung vào Shell Script, không phải mã PHP.</p>
</div>
<section class="env-info">
<h3>Thông tin môi trường</h3>
<ul>
<li>PHP: <?php echo PHP_VERSION; ?></li>
<li>Hệ điều hành: <?php echo PHP_OS; ?></li>
<li>Thư mục gốc: <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? '(không xác định)'); ?></li>
</ul>
</section>
</main>
</body>
</html>
