<?php

namespace App\Models;

use Database\Factories\CertificateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A certificate of completion, issued once per student and course.
 *
 * The names, title and grade are copied in when it is issued so the certificate
 * keeps saying the same thing after the course or the student is renamed.
 *
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property string $code
 * @property string $student_name
 * @property string $course_title
 * @property string|null $instructor_name
 * @property int|null $final_percent
 * @property string|null $letter
 * @property Carbon $issued_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'course_id', 'code', 'student_name', 'course_title', 'instructor_name', 'final_percent', 'letter', 'issued_at'])]
class Certificate extends Model
{
    /** @use HasFactory<CertificateFactory> */
    use HasFactory;

    /**
     * Characters used in certificate codes; look-alikes such as 0/O and 1/I are left out.
     */
    private const string CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'course_id' => 'integer',
            'final_percent' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    /**
     * Look certificates up by their public code in URLs.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }

    /**
     * Make a new, unused certificate code such as "EV-2026-7K3P9QXM".
     */
    public static function newCode(): string
    {
        do {
            $random = '';

            for ($i = 0; $i < 8; $i++) {
                $random .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }

            $code = 'EV-'.now()->format('Y').'-'.$random;
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    /**
     * Get the student the certificate was issued to.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the course the certificate is for.
     *
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
