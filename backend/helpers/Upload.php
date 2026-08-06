<?php

declare(strict_types=1);

namespace AfriSense\Backend\Helpers;

use RuntimeException;

class Upload
{
    /**
     * Validate and move an uploaded file into a destination directory.
     *
     * @throws RuntimeException When validation or moving fails.
     */
    public function moveUploadedFile(
        array $file,
        string $destinationDirectory,
        array $allowedMimeTypes = [],
        int $maxBytes = 5242880
    ): string {
        // Guard this block so it only runs when the required condition is met.
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('File upload failed.');
        }

        // Guard this block so it only runs when the required condition is met.
        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('Uploaded file is too large.');
        }

        $temporaryName = (string) ($file['tmp_name'] ?? '');
        $mimeType = (string) finfo_file(finfo_open(FILEINFO_MIME_TYPE), $temporaryName);

        // Guard this block so it only runs when the required condition is met.
        if ($allowedMimeTypes !== [] && !in_array($mimeType, $allowedMimeTypes, true)) {
            throw new RuntimeException('Uploaded file type is not allowed.');
        }

        // Guard this block so it only runs when the required condition is met.
        if (!is_dir($destinationDirectory) && !mkdir($destinationDirectory, 0755, true)) {
            throw new RuntimeException('Upload directory could not be created.');
        }

        $extension = pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(16)) . ($extension !== '' ? '.' . strtolower($extension) : '');
        $destination = rtrim($destinationDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        // Guard this block so it only runs when the required condition is met.
        if (!move_uploaded_file($temporaryName, $destination)) {
            throw new RuntimeException('Uploaded file could not be moved.');
        }

        return $destination;
    }

    /**
     * Delete an uploaded file when it exists.
     */
    public function delete(string $path): bool
    {
        return is_file($path) && unlink($path);
    }
}
