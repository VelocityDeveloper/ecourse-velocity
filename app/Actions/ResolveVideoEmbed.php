<?php

namespace App\Actions;

class ResolveVideoEmbed
{
    /**
     * Turn a YouTube or Vimeo link into an embeddable player URL.
     *
     * Returns null for any other host, in which case the lesson shows a plain
     * link to the video instead of an embedded player.
     */
    public function __invoke(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $host = preg_replace('/^(www\.|m\.)/', '', $host) ?? $host;
        $path = (string) parse_url($url, PHP_URL_PATH);

        if ($host === 'youtu.be') {
            return $this->youtube(ltrim($path, '/'));
        }

        if (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            if (isset($query['v']) && is_string($query['v'])) {
                return $this->youtube($query['v']);
            }

            if (preg_match('#^/(embed|shorts|live)/([^/?]+)#', $path, $matches) === 1) {
                return $this->youtube($matches[2]);
            }

            return null;
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)
            && preg_match('#/(?:video/)?(\d+)#', $path, $matches) === 1) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        return null;
    }

    /**
     * Build the privacy-friendly YouTube player URL for a video id.
     */
    private function youtube(string $videoId): ?string
    {
        return preg_match('/^[A-Za-z0-9_-]{6,20}$/', $videoId) === 1
            ? 'https://www.youtube-nocookie.com/embed/'.$videoId
            : null;
    }
}
