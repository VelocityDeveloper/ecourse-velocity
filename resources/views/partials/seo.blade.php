{{-- Search and sharing tags of a public page, built by App\Support\Seo. --}}
<meta name="description" content="{{ $seo['description'] }}">
@if ($seo['keywords'])
<meta name="keywords" content="{{ $seo['keywords'] }}">
@endif
<meta name="robots" content="{{ $seo['robots'] }}">
<link rel="canonical" href="{{ $seo['url'] }}">
@if ($seo['google_verification'])
<meta name="google-site-verification" content="{{ $seo['google_verification'] }}">
@endif
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:locale" content="id_ID">
<meta property="og:site_name" content="{{ $seo['site_name'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['url'] }}">
@if ($seo['image'])
<meta property="og:image" content="{{ $seo['image'] }}">
<meta property="og:image:alt" content="{{ $seo['title'] }}">
@endif
@if ($seo['article'])
@if ($seo['article']['published_time'])
<meta property="article:published_time" content="{{ $seo['article']['published_time'] }}">
@endif
@if ($seo['article']['modified_time'])
<meta property="article:modified_time" content="{{ $seo['article']['modified_time'] }}">
@endif
@if ($seo['article']['author'])
<meta property="article:author" content="{{ $seo['article']['author'] }}">
@endif
@endif
<meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
@if ($seo['image'])
<meta name="twitter:image" content="{{ $seo['image'] }}">
@endif
@foreach ($seo['json_ld'] as $schema)
<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endforeach
