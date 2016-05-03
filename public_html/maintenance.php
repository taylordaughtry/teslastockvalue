<?php
header('HTTP/1.1 503 Service Temporarily Unavailable', true, 503);
header('Status: 503 Service Temporarily Unavailable');
header('Retry-After: 300');
?>
<!doctype html>
<html lang="en" id="global" itemscope itemtype="http://schema.org/WebPage">
<head>
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<title>Site Offline | Site Name</title>

	<link rel="stylesheet" href="/assets/css/style.min.css">
	<link rel="stylesheet" href="/assets/css/maintenance.min.css">

	<link rel="apple-touch-icon" href="/assets/img/icons/touch.png">

	<!--[if lte IE 9]>
	<script src="/assets/js/ie9.min.js"></script>
	<![endif]-->

	<script>
	// Tracking code
	</script>
</head>
<body>
	<main class="maintenance" role="main">
		<img src="/assets/img/logo.svg" alt="Site Name" class="maintenance__logo">
		<p class="maintenance__copy">This site will be offline momentarily while we perform some updates.</p>
		<a href="/" class="button">Click here to try again</a>
	</main>
</body>
</html>