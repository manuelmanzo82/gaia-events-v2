<?php

declare(strict_types=1);

namespace Gaia\Admin;

use GdImage;
use RuntimeException;

/**
 * Upload immagini con crop e varianti.
 *
 * WebP viene generato sempre (GD lo supporta praticamente su ogni hosting
 * condiviso). AVIF solo se imageavif() esiste: su Ionos/Aruba il supporto
 * non è garantito e un upload non deve fallire per quello.
 *
 * Le varianti finiscono accanto all'originale con il nome
 * `{base}-{larghezza}.{formato}`: la stessa convenzione che il sito usa in
 * site/src/lib/images.ts per costruire il srcset.
 */
final class ImageService
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function avifSupported(): bool
    {
        return function_exists('imageavif');
    }

    /**
     * Salva un file caricato e ne genera le varianti.
     *
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file
     * @param array{x: int, y: int, width: int, height: int}|null $crop  ritaglio in pixel sull'originale
     * @return array<string, mixed>  riga pronta per la tabella `media`
     */
    public static function store(array $file, string $folder, ?array $crop = null): array
    {
        self::assertUploadOk($file);

        $mime = self::detectMime($file['tmp_name']);
        $source = self::openImage($file['tmp_name'], $mime);

        if ($crop !== null) {
            $source = self::crop($source, $crop);
        }

        $source = self::limitSize($source, (int) Config::get('images.max_original', 3000));

        $folder = trim(preg_replace('/[^a-z0-9\/\-]/', '', strtolower($folder)) ?? '', '/');
        $base = Validator::slug(pathinfo($file['name'], PATHINFO_FILENAME));
        if ($base === '') {
            $base = 'immagine';
        }

        $directory = rtrim((string) Config::get('uploads_dir'), '/') . '/' . $folder;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException("Impossibile creare la cartella {$folder} negli upload.");
        }

        // Evita di sovrascrivere un file esistente.
        $name = $base;
        $suffix = 1;
        while (glob($directory . '/' . $name . '.*') !== []) {
            $name = $base . '-' . ++$suffix;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // L'originale resta in JPEG o PNG: è il fallback del <picture>.
        $originalExtension = $mime === 'image/png' ? 'png' : 'jpg';
        $originalPath = $directory . '/' . $name . '.' . $originalExtension;

        if ($originalExtension === 'png') {
            imagepng($source, $originalPath, 8);
        } else {
            imagejpeg($source, $originalPath, 86);
        }

        $widths = self::variantWidths($width);
        $formats = ['webp' => false, 'avif' => false];

        foreach ($widths as $targetWidth) {
            $resized = self::resize($source, $targetWidth);

            $webp = sprintf('%s/%s-%d.webp', $directory, $name, $targetWidth);
            if (imagewebp($resized, $webp, (int) Config::get('images.webp_quality', 82))) {
                $formats['webp'] = true;
            }

            if (self::avifSupported()) {
                $avif = sprintf('%s/%s-%d.avif', $directory, $name, $targetWidth);
                if (@imageavif($resized, $avif, (int) Config::get('images.avif_quality', 60))) {
                    $formats['avif'] = true;
                }
            }

            imagedestroy($resized);
        }

        imagedestroy($source);

        return [
            'path' => ($folder !== '' ? $folder . '/' : '') . $name . '.' . $originalExtension,
            'width' => $width,
            'height' => $height,
            'bytes' => filesize($originalPath) ?: 0,
            'variant_widths' => json_encode(array_values($widths)),
            'has_webp' => $formats['webp'] ? 1 : 0,
            'has_avif' => $formats['avif'] ? 1 : 0,
            'focal_x' => 0.5,
            'focal_y' => 0.5,
            'created_at' => date('Y-m-d H:i:s'),
        ];
    }

    /** Cancella originale e varianti di un'immagine. */
    public static function delete(string $path): void
    {
        $root = rtrim((string) Config::get('uploads_dir'), '/');
        $full = $root . '/' . ltrim($path, '/');

        // Non uscire dalla cartella degli upload, qualunque cosa arrivi dal database.
        $realRoot = realpath($root);
        $realFile = realpath($full);
        if ($realRoot === false || $realFile === false || !str_starts_with($realFile, $realRoot)) {
            return;
        }

        $directory = dirname($realFile);
        $base = pathinfo($realFile, PATHINFO_FILENAME);

        @unlink($realFile);
        foreach (glob($directory . '/' . $base . '-*.{webp,avif}', GLOB_BRACE) ?: [] as $variant) {
            @unlink($variant);
        }
    }

    /** @return list<int> */
    private static function variantWidths(int $originalWidth): array
    {
        $configured = Config::get('images.widths', [480, 800, 1200, 1600, 2400]);
        $widths = array_values(array_filter(
            is_array($configured) ? $configured : [],
            static fn (mixed $w): bool => is_int($w) && $w <= $originalWidth,
        ));

        // Un'immagine piccola ha almeno la sua dimensione nativa.
        return $widths === [] ? [$originalWidth] : $widths;
    }

    /** @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file */
    private static function assertUploadOk(array $file): void
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Caricamento non riuscito (codice ' . $file['error'] . ').');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('File non valido.');
        }

        $max = (int) Config::get('images.max_upload_bytes', 12 * 1024 * 1024);
        if ($file['size'] > $max) {
            throw new RuntimeException(
                'Immagine troppo grande: massimo ' . round($max / 1048576) . ' MB.',
            );
        }
    }

    private static function detectMime(string $path): string
    {
        // Il tipo dichiarato dal browser non è attendibile: lo rileviamo noi.
        $info = @getimagesize($path);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';

        if (!isset(self::ALLOWED[$mime])) {
            throw new RuntimeException('Formato non ammesso: carica JPG, PNG o WebP.');
        }

        return $mime;
    }

    private static function openImage(string $path, string $mime): GdImage
    {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png' => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default => false,
        };

        if (!$image instanceof GdImage) {
            throw new RuntimeException('Immagine illeggibile o danneggiata.');
        }

        // Le foto dei fotografi arrivano spesso ruotate via EXIF.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $orientation = is_array($exif) ? (int) ($exif['Orientation'] ?? 0) : 0;

            $angle = match ($orientation) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };

            if ($angle !== 0) {
                $rotated = imagerotate($image, $angle, 0);
                if ($rotated instanceof GdImage) {
                    imagedestroy($image);
                    $image = $rotated;
                }
            }
        }

        return $image;
    }

    /** @param array{x: int, y: int, width: int, height: int} $crop */
    private static function crop(GdImage $source, array $crop): GdImage
    {
        $cropped = imagecrop($source, [
            'x' => max(0, $crop['x']),
            'y' => max(0, $crop['y']),
            'width' => max(1, $crop['width']),
            'height' => max(1, $crop['height']),
        ]);

        if (!$cropped instanceof GdImage) {
            return $source;
        }

        imagedestroy($source);

        return $cropped;
    }

    private static function limitSize(GdImage $image, int $maxSide): GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxSide && $height <= $maxSide) {
            return $image;
        }

        $targetWidth = $width >= $height
            ? $maxSide
            : (int) round($width * ($maxSide / $height));

        $resized = self::resize($image, $targetWidth);
        imagedestroy($image);

        return $resized;
    }

    private static function resize(GdImage $source, int $targetWidth): GdImage
    {
        $width = imagesx($source);
        $height = imagesy($source);
        $targetHeight = max(1, (int) round($height * ($targetWidth / $width)));

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        imagecopyresampled(
            $resized,
            $source,
            0,
            0,
            0,
            0,
            $targetWidth,
            $targetHeight,
            $width,
            $height,
        );

        return $resized;
    }
}
