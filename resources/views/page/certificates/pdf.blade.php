<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificate</title>
    @php
        // Forward slashes: backslashes in a CSS url() break the Windows path for dompdf.
        $fontUrl = fn ($file) => str_replace('\\', '/', public_path('fonts/' . $file));
    @endphp
    <style>
        @font-face {
            font-family: 'THSarabunNew';
            font-style: normal;
            font-weight: normal;
            src: url("{{ $fontUrl('THSarabunNew.ttf') }}") format('truetype');
        }
        @font-face {
            font-family: 'THSarabunNew';
            font-style: normal;
            font-weight: bold;
            src: url("{{ $fontUrl('THSarabunNew Bold.ttf') }}") format('truetype');
        }
        @page { margin: 0; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'THSarabunNew', sans-serif; color: #111; }
        .bg { position: absolute; left: 0; top: 0; width: {{ $pageW }}pt; height: {{ $pageH }}pt; }
        .f { position: absolute; white-space: nowrap; line-height: 1.1; }
    </style>
</head>
<body>
    <img class="bg" src="{{ $template->bgDataUri() }}">

    @foreach ($template->resolvedFields() as $key => $cfg)
        @continue(empty($cfg['show']) || !isset($values[$key]) || $values[$key] === '')
        @php
            $x = max(0, min(100, (float) $cfg['x'])) / 100 * $pageW;
            $y = max(0, min(100, (float) $cfg['y'])) / 100 * $pageH;
            $size = max(6, (float) $cfg['size']);
            $align = in_array($cfg['align'], ['left', 'center', 'right']) ? $cfg['align'] : 'center';

            // Text box is anchored on x: centered on it, ending at it (right) or starting from it (left).
            if ($align === 'center') {
                $w = 2 * min($x, $pageW - $x);
                $left = $x - $w / 2;
            } elseif ($align === 'right') {
                $w = $x;
                $left = 0;
            } else {
                $w = $pageW - $x;
                $left = $x;
            }
        @endphp
        <div class="f" style="left: {{ round($left, 2) }}pt; top: {{ round($y, 2) }}pt; width: {{ round($w, 2) }}pt; text-align: {{ $align }}; font-size: {{ $size }}pt; font-weight: {{ !empty($cfg['bold']) ? 'bold' : 'normal' }};">{{ $values[$key] }}</div>
    @endforeach
</body>
</html>
