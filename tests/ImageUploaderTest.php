<?php

declare(strict_types=1);

namespace Tests;

use App\Security\ImageUploader;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ImageUploaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/sihos-uploads-' . bin2hex(random_bytes(4));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        rmdir($this->directory);
    }

    public function testAcceptsPngAndRejectsPlainText(): void
    {
        $uploader = new ImageUploader($this->directory);
        $png = $this->directory . '/pixel.png';
        $text = $this->directory . '/nota.txt';
        file_put_contents($png, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        ));
        file_put_contents($text, 'no es una imagen');

        self::assertSame('png', $uploader->extensionForPath($png));

        $this->expectException(RuntimeException::class);
        $uploader->extensionForPath($text);
    }

    public function testStoreRejectsFilesThatWereNotUploaded(): void
    {
        $uploader = new ImageUploader($this->directory);
        $png = $this->directory . '/pixel.png';
        file_put_contents($png, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true
        ));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no es válida');
        $uploader->store([
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($png),
            'tmp_name' => $png,
        ]);
    }

    public function testPublicUrlAcceptsOnlyGeneratedNames(): void
    {
        self::assertNull(ImageUploader::publicUrl(null));
        self::assertNull(ImageUploader::publicUrl('../.htaccess'));
        self::assertSame(
            '/uploads/' . str_repeat('ab', 16) . '.png',
            ImageUploader::publicUrl(str_repeat('ab', 16) . '.png'),
        );
    }
}
