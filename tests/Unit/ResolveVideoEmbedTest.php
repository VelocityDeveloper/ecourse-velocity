<?php

use App\Actions\ResolveVideoEmbed;

test('video links are turned into embeddable player urls', function (?string $url, ?string $expected) {
    expect((new ResolveVideoEmbed)($url))->toBe($expected);
})->with([
    'youtube watch' => ['https://www.youtube.com/watch?v=abcdefghijk&t=30', 'https://www.youtube-nocookie.com/embed/abcdefghijk'],
    'youtube short link' => ['https://youtu.be/abcdefghijk', 'https://www.youtube-nocookie.com/embed/abcdefghijk'],
    'youtube shorts' => ['https://youtube.com/shorts/abcdefghijk', 'https://www.youtube-nocookie.com/embed/abcdefghijk'],
    'vimeo' => ['https://vimeo.com/123456789', 'https://player.vimeo.com/video/123456789'],
    'other host' => ['https://example.com/videos/intro', null],
    'suspicious youtube id' => ['https://youtu.be/<script>', null],
    'empty' => [null, null],
]);
