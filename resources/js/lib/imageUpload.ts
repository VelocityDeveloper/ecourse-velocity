/**
 * Check a picked image against the upload limit before the form is sent, so an
 * oversized file gets a clear message instead of being dropped by PHP on the way.
 * Returns an error message, or null when the file is fine.
 */
export function oversizedImageError(file: File, maxMb: number): string | null {
    return file.size > maxMb * 1024 * 1024
        ? `Ukuran gambar ${(file.size / 1024 / 1024).toFixed(1)} MB. Pilih gambar yang ukurannya tidak lebih dari ${maxMb} MB.`
        : null;
}
