<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificateTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'agn', 'course_id', 'bg_image', 'fields', 'active', 'created_by',
    ];

    protected $casts = [
        'fields' => 'array',
        'active' => 'boolean',
    ];

    /** Field keys a template can place on the certificate. */
    public const FIELD_KEYS = ['name', 'course', 'date', 'cert_no'];

    public static function defaultFields(): array
    {
        return [
            // y is the top of the text box; with THSarabunNew the glyphs sit roughly 1x font-size below it.
            'name'    => ['show' => true, 'x' => 50, 'y' => 25, 'size' => 44, 'align' => 'center', 'bold' => true],
            'course'  => ['show' => true, 'x' => 50, 'y' => 42, 'size' => 34, 'align' => 'center', 'bold' => true],
            // date and number share one row at the bottom: date ends at the middle, number starts after it
            'date'    => ['show' => true, 'x' => 48, 'y' => 91, 'size' => 16, 'align' => 'right', 'bold' => false],
            'cert_no' => ['show' => true, 'x' => 52, 'y' => 91, 'size' => 16, 'align' => 'left', 'bold' => false],
        ];
    }

    public function resolvedFields(): array
    {
        $out = self::defaultFields();
        foreach ((array) $this->fields as $key => $cfg) {
            if (isset($out[$key]) && is_array($cfg)) {
                $out[$key] = array_merge($out[$key], $cfg);
            }
        }
        return $out;
    }

    public function bgPath(): string
    {
        // Forward slashes: dompdf resolves local images more reliably that way on Windows.
        return str_replace('\\', '/', public_path('uploads/certificates/' . $this->bg_image));
    }

    /** Inline image for dompdf: avoids local-path/protocol quirks (e.g. "E:" read as a URL scheme on Windows). */
    public function bgDataUri(): string
    {
        $path = $this->bgPath();
        $mime = (@getimagesize($path)['mime'] ?? null) ?: 'image/jpeg';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    public function bgUrl(): string
    {
        return asset('uploads/certificates/' . $this->bg_image);
    }

    public function getCourse()
    {
        return $this->belongsTo(course::class, 'course_id');
    }

    public function getAgn()
    {
        return $this->belongsTo(agency::class, 'agn');
    }
}
