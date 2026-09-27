<?php

namespace App\Actions;

use App\Models\Certificate;
use App\Models\SiteSetting;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdf;
use Illuminate\Support\Facades\Storage;

class RenderCertificatePdf
{
    /**
     * The built-in brand colour, used until an admin picks another.
     */
    private const string DEFAULT_PRIMARY = '#cb450b';

    /**
     * The built-in dark surface colour (hero and footer).
     */
    private const string DEFAULT_SURFACE = '#0b1123';

    /**
     * Lay the certificate out as an A4 landscape PDF in the site's colours,
     * with a QR code that opens its public verification page.
     */
    public function __invoke(Certificate $certificate): DomPdf
    {
        $primary = SiteSetting::get(SiteSetting::PRIMARY_COLOR) ?? self::DEFAULT_PRIMARY;

        return Pdf::loadView('certificates.pdf', [
            'certificate' => $certificate,
            'primary' => $primary,
            'surface' => SiteSetting::get(SiteSetting::SURFACE_COLOR) ?? self::DEFAULT_SURFACE,
            'siteName' => config('app.name'),
            'logo' => $this->logo($primary),
            'qr' => $this->qrCode(route('certificates.show', $certificate)),
            'issuedAt' => $certificate->issued_at->copy()->settings(['locale' => 'id'])->translatedFormat('j F Y'),
        ])->setPaper('a4', 'landscape');
    }

    /**
     * Get the uploaded logo, or the built-in mark in the brand colour, as a data URI.
     */
    private function logo(string $primary): string
    {
        $path = SiteSetting::get(SiteSetting::LOGO);
        $disk = Storage::disk(SiteSetting::LOGO_DISK);

        if ($path !== null && $disk->exists($path)) {
            return 'data:'.$disk->mimeType($path).';base64,'.base64_encode((string) $disk->get($path));
        }

        $svg = str_replace('#cb450b', $primary, (string) file_get_contents(public_path('favicon.svg')));

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Draw a QR code for the URL as an SVG data URI.
     */
    private function qrCode(string $url): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle(240, 1), new SvgImageBackEnd));

        return 'data:image/svg+xml;base64,'.base64_encode($writer->writeString($url));
    }
}
