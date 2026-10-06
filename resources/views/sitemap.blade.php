{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($pages as $page)
    <url>
        <loc>{{ $page['url'] }}</loc>
@if ($page['lastmod'])
        <lastmod>{{ \Illuminate\Support\Carbon::parse($page['lastmod'])->toAtomString() }}</lastmod>
@endif
        <priority>{{ $page['priority'] }}</priority>
@if ($page['image'])
        <image:image><image:loc>{{ $page['image'] }}</image:loc></image:image>
@endif
    </url>
@endforeach
</urlset>
