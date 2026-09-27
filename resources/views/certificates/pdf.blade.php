<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sertifikat {{ $certificate->code }}</title>
    <style>
        @page { margin: 0; size: A4 landscape; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #1f2937; }
        .page { position: relative; width: 297mm; height: 210mm; background: #ffffff; }
        .frame { position: absolute; top: 9mm; left: 9mm; right: 9mm; bottom: 9mm; border: 1.2mm solid {{ $surface }}; }
        .frame-inner { position: absolute; top: 12.5mm; left: 12.5mm; right: 12.5mm; bottom: 12.5mm; border: 0.4mm solid {{ $primary }}; }
        .band { position: absolute; top: 9mm; left: 9mm; width: 16mm; bottom: 9mm; background: {{ $surface }}; }
        .band-accent { position: absolute; top: 9mm; left: 25mm; width: 2mm; bottom: 9mm; background: {{ $primary }}; }
        .content { position: absolute; top: 24mm; left: 42mm; right: 26mm; }
        .brand td { vertical-align: middle; }
        .brand-name { font-size: 13pt; font-weight: bold; color: {{ $surface }}; padding-left: 3mm; }
        .eyebrow { margin-top: 13mm; font-size: 10pt; letter-spacing: 3pt; color: {{ $primary }}; font-weight: bold; text-transform: uppercase; }
        .title { margin-top: 1mm; font-family: 'DejaVu Serif', serif; font-size: 34pt; font-weight: bold; color: {{ $surface }}; }
        .label { margin-top: 8mm; font-size: 10.5pt; color: #6b7280; }
        .name { margin-top: 2mm; font-family: 'DejaVu Serif', serif; font-size: 27pt; font-weight: bold; color: #111827; border-bottom: 0.4mm solid #d1d5db; padding-bottom: 2mm; width: 190mm; }
        .course { margin-top: 2mm; font-size: 17pt; font-weight: bold; color: {{ $primary }}; width: 190mm; }
        .grade { margin-top: 3mm; font-size: 10.5pt; color: #374151; }
        .footer { position: absolute; left: 42mm; right: 26mm; bottom: 24mm; }
        .footer td { vertical-align: bottom; font-size: 9.5pt; color: #374151; }
        .sign-line { border-top: 0.3mm solid #9ca3af; width: 62mm; padding-top: 1.5mm; }
        .muted { color: #6b7280; font-size: 8.5pt; }
        .code { font-family: 'DejaVu Sans Mono', monospace; font-size: 10pt; font-weight: bold; color: #111827; }
    </style>
</head>
<body>
<div class="page">
    <div class="frame"></div>
    <div class="frame-inner"></div>
    <div class="band"></div>
    <div class="band-accent"></div>

    <div class="content">
        <table class="brand" cellspacing="0" cellpadding="0">
            <tr>
                <td><img src="{{ $logo }}" style="height: 11mm;" alt=""></td>
                <td class="brand-name">{{ $siteName }}</td>
            </tr>
        </table>

        <div class="eyebrow">Sertifikat Kelulusan</div>
        <div class="title">Sertifikat</div>

        <div class="label">Diberikan kepada</div>
        <div class="name">{{ $certificate->student_name }}</div>

        <div class="label">atas keberhasilannya menyelesaikan kursus</div>
        <div class="course">{{ $certificate->course_title }}</div>

        @if ($certificate->final_percent !== null)
            <div class="grade">dengan nilai akhir <strong>{{ $certificate->final_percent }}</strong> (predikat <strong>{{ $certificate->letter }}</strong>)</div>
        @endif
    </div>

    <div class="footer">
        <table width="100%" cellspacing="0" cellpadding="0">
            <tr>
                <td style="width: 34%;">
                    <div class="muted">Diterbitkan</div>
                    <div style="margin-top: 1mm; font-weight: bold;">{{ $issuedAt }}</div>
                </td>
                <td style="width: 38%;">
                    @if ($certificate->instructor_name)
                        <div class="sign-line">
                            <strong>{{ $certificate->instructor_name }}</strong><br>
                            <span class="muted">Instruktur</span>
                        </div>
                    @endif
                </td>
                <td style="width: 28%; text-align: right;">
                    <table cellspacing="0" cellpadding="0" style="margin-left: auto;">
                        <tr>
                            <td style="text-align: right; padding-right: 3mm;">
                                <div class="muted">Nomor sertifikat</div>
                                <div class="code">{{ $certificate->code }}</div>
                                <div class="muted" style="margin-top: 1mm; white-space: nowrap;">Pindai untuk cek keaslian</div>
                            </td>
                            <td><img src="{{ $qr }}" style="width: 24mm; height: 24mm;" alt=""></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
