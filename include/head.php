<?php
// include 'admin/include/config.php';
require_once __DIR__ . '/../admin/include/config.php';

// include 'cart_functions.php';
require_once __DIR__ . '/../cart_functions.php';

if (session_status() === PHP_SESSION_NONE) session_start();
// Skip ngrok browser-warning interstitial for all PHP responses
// (fixes CSS/JS/AJAX being blocked when accessing site via ngrok URL)
if (!headers_sent()) {
    header('ngrok-skip-browser-warning: true');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<!-- Meta Pixel Code -->
<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1409753117715795');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1409753117715795&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->
<!-- End Meta Pixel Code -->
<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">

<!-- ══ SEO: PRIMARY SIGNALS ══════════════════════════════════════════════ -->
<title>Kapraa | Best Online Clothing Store in Faisalabad, Pakistan</title>
<meta name="description" content="Kapraa — Pakistan's top online clothing store in Faisalabad. Shop women's suits, men's wear, kids' clothes, unstitched lawn &amp; luxury pret. Nationwide delivery. COD available.">
<meta name="keywords" content="online clothing store Pakistan, best clothing store Faisalabad, buy clothes online Pakistan, women suits Pakistan, unstitched lawn suits, Pakistani fashion store, branded suits Faisalabad, ladies clothes online, men clothing Pakistan, kids wear Pakistan, Kapraa, kapraa clothing, top fashion store Pakistan, Faisalabad online shopping, pret collection Pakistan, luxury suits Pakistan, COD clothing Pakistan">
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="author" content="Kapraa">
<meta name="geo.region" content="PK-PB">
<meta name="geo.placename" content="Faisalabad, Pakistan">

<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- ══ FAVICON ══════════════════════════════════════════════════════════ -->
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $url_domain; ?>/assets/images/512.png?v=4">
<link rel="icon" type="image/png" sizes="192x192" href="<?php echo $url_domain; ?>/assets/images/512.png?v=4">
<link rel="apple-touch-icon" sizes="512x512" href="<?php echo $url_domain; ?>/assets/images/512.png?v=4">
<link rel="shortcut icon" href="<?php echo $url_domain; ?>/assets/images/512.png?v=4">

<!-- ══ OPEN GRAPH (Facebook / WhatsApp previews) ════════════════════════ -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="Kapraa">
<meta property="og:title" content="Kapraa | Best Online Clothing Store in Faisalabad, Pakistan">
<meta property="og:description" content="Shop premium Pakistani clothes at Kapraa — women's suits, men's wear, kids' clothes, unstitched lawn &amp; luxury pret. Nationwide delivery. COD available.">
<meta property="og:image" content="https://kapraa.pk/assets/images/512.png">
<meta property="og:image:width" content="512">
<meta property="og:image:height" content="512">
<meta property="og:url" content="https://kapraa.pk/">
<meta property="og:locale" content="en_PK">

<!-- ══ TWITTER CARD ═════════════════════════════════════════════════════ -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@kapraaofficial">
<meta name="twitter:title" content="Kapraa | Best Online Clothing Store in Pakistan">
<meta name="twitter:description" content="Premium Pakistani clothing — women's suits, men's wear, kids' clothes. Nationwide delivery. COD available.">
<meta name="twitter:image" content="https://kapraa.pk/assets/images/512.png">

<!-- ══ CANONICAL URL ════════════════════════════════════════════════════ -->
<?php $canonicalUrl = 'https://kapraa.pk' . strtok($_SERVER['REQUEST_URI'], '?'); ?>
<link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

<!-- ══ DNS PREFETCH (faster external resource load) ════════════════════ -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//fonts.gstatic.com">
<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">

<!-- ══ CSS FILES ════════════════════════════════════════════════════════ -->
<link rel="stylesheet" href="<?php echo $url_domain; ?>/assets/css/plugins.css?v=2.3">
<link rel="stylesheet" href="<?php echo $url_domain; ?>/assets/css/style.css?v=2.5">
<link rel="stylesheet" href="<?php echo $url_domain; ?>/assets/css/responsive.css?v=2.3">
<link rel="stylesheet" href="<?php echo $url_domain; ?>/assets/css/animations.css?v=1.1">

<!-- ══ GOOGLE FONTS — non-blocking ══════════════════════════════════════ -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet"></noscript>

<!-- Font Awesome — non-blocking -->
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"></noscript>

<!-- ══ STRUCTURED DATA — Google Knowledge Panel + Rich Results ══════════ -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "ClothingStore",
  "name": "Kapraa",
  "alternateName": "Kapraa Official",
  "description": "Pakistan's top online clothing store based in Faisalabad. Offering women's suits, men's wear, kids' clothes, unstitched lawn and luxury pret with nationwide delivery.",
  "url": "https://kapraa.pk",
  "logo": {
    "@type": "ImageObject",
    "url": "https://kapraa.pk/assets/images/512.png",
    "width": 512,
    "height": 512
  },
  "image": "https://kapraa.pk/assets/images/512.png",
  "telephone": "+923209815213",
  "email": "kapraaofficial@gmail.com",
  "priceRange": "$$",
  "currenciesAccepted": "PKR",
  "paymentAccepted": "Cash, Bank Transfer",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "Faisalabad",
    "addressLocality": "Faisalabad",
    "addressRegion": "Punjab",
    "addressCountry": "PK"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "31.4145",
    "longitude": "73.1026"
  },
  "openingHoursSpecification": {
    "@type": "OpeningHoursSpecification",
    "dayOfWeek": ["Monday","Tuesday","Wednesday","Thursday","Friday","Saturday","Sunday"],
    "opens": "10:00",
    "closes": "22:00"
  },
  "sameAs": [
    "https://www.facebook.com/kapraaofficial",
    "https://www.instagram.com/kapraaofficial"
  ],
   "hasOfferCatalog": {
    "@type": "OfferCatalog",
    "name": "Kapraa Clothing Collections",
    "itemListElement": [
      { "@type": "OfferCatalog", "name": "Women's Suits Pakistan" },
      { "@type": "OfferCatalog", "name": "Unstitched Lawn Suits" },
      { "@type": "OfferCatalog", "name": "Men's Clothing Pakistan" },
      { "@type": "OfferCatalog", "name": "Kids Wear Pakistan" }
    ]
  }
}
</script>

<!-- BreadcrumbList for homepage -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Kapraa",
  "url": "https://kapraa.pk",
  "potentialAction": {
    "@type": "SearchAction",
 "target": "https://kapraa.pk/products?search_query={search_term_string}",
    "query-input": "required name=search_term_string"
  }
}
</script>

</head>

<body class="template-index diva home7-creative" style="margin:0;padding:0;">

<div class="pageWrapper" style="margin:0;padding:0;">

<!-- MAIN H1 (REQUIRED FOR SEO) -->
<h1 style="display:none;">Kapraa – Best Online Clothing Store in Faisalabad, Pakistan | Women Suits, Men Wear, Kids Clothes</h1>
