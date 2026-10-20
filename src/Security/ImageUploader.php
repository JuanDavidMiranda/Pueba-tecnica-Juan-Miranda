<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

final class ImageUploader
{
    public const NAME_PATTERN = '/^[a-f0-9]{32}\.(jpg|png|webp)$/';

    private const MAX_BYTES = 2_097_152;

    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(private readonly string $directory)
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException('No se pudo preparar el directorio de fotografías.');
        }
    }

    /**
     * @param array<string, mixed>|null $file
     */
    public function store(?array $file): ?string
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo cargar la fotografía.');
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size < 1 || $size > self::MAX_BYTES) {
            throw new RuntimeException('La fotografía no puede superar 2 MB.');
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException('La fotografía no es válida.');
        }

        $extension = $this->extensionForPath($tmp);
        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $destination = $this->directory . DIRECTORY_SEPARATOR . $name;

        if (!move_uploaded_file($tmp, $destination)) {
            throw new RuntimeException('No se pudo guardar la fotografía.');
        }

        return $name;
    }

    public static function publicUrl(?string $filename): ?string
    {
        if ($filename === null || preg_match(self::NAME_PATTERN, $filename) !== 1) {
            return null;
        }

        return '/uploads/' . $filename;
    }

    public function extensionForPath(string $path): string
    {
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
        $extension = self::MIME_EXTENSIONS[$mime] ?? null;
        if ($extension === null) {
            throw new RuntimeException('La fotografía debe ser JPG, PNG o WEBP.');
        }

        return $extension;
    }

    public function delete(?string $filename): void
    {
        if (self::publicUrl($filename) === null) {
            return;
        }

        $path = $this->directory . DIRECTORY_SEPARATOR . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
