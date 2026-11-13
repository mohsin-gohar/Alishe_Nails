<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Folders that tests may write uploaded media into.
     *
     * @var array<int, string>
     */
    private array $publicMediaFolders = [
        'images/products',
        'images/categories',
    ];

    /**
     * Files that existed before the test started, so the teardown only
     * removes what the test itself created.
     *
     * @var array<string, array<string, true>>
     */
    private array $mediaSnapshot = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->snapshotPublicMedia();
    }

    protected function tearDown(): void
    {
        $this->removeNewPublicMedia();

        parent::tearDown();
    }

    /**
     * Records the files currently present in the public media folders.
     */
    private function snapshotPublicMedia(): void
    {
        foreach ($this->publicMediaFolders as $folder) {
            $path = public_path($folder);

            $this->mediaSnapshot[$folder] = is_dir($path)
                ? array_fill_keys(array_map('basename', glob($path.'/*') ?: []), true)
                : [];
        }
    }

    /**
     * Deletes anything the test added to the public folder.
     *
     * Uploading through the admin panel writes real files to
     * public/images/products. Without this cleanup the repository slowly
     * fills up with orphaned test uploads.
     */
    private function removeNewPublicMedia(): void
    {
        foreach ($this->publicMediaFolders as $folder) {
            $path = public_path($folder);

            if (! is_dir($path)) {
                continue;
            }

            $before = $this->mediaSnapshot[$folder] ?? [];

            foreach (glob($path.'/*') ?: [] as $file) {
                $name = basename($file);

                if (isset($before[$name])) {
                    continue;
                }

                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }
}
