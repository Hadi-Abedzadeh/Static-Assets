<?php
namespace hadiabedzadeh\StaticAssets;

use InvalidArgumentException;
use RuntimeException;

class StaticAssets
{
    /**
     * Combine CSS/JS files into a single file and return the proper HTML tag.
     *
     * @param array $files
     * @param string $type 'css' or 'js'
     * @param string|null $segment optional segment (folder under public). If null, tries to detect from REQUEST_URI.
     * @param string $jsLoad 'defer'|'async'|'' for script tag
     * @param string|null $publicPath filesystem path to public directory (default: getcwd() . '/public')
     * @param string|null $publicUrl base public URL (optional) used to build final URL
     * @return string HTML tag
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public static function combineFiles(
        array $files,
        string $type = 'css',
        string $segment = null,
        string $jsLoad = 'defer',
        string $publicPath = null,
        string $publicUrl = null
    ): string {
        $type = strtolower($type);
        if (!in_array($type, ['css', 'js'])) {
            throw new InvalidArgumentException("Invalid type: $type. Use 'css' or 'js'.");
        }

        $publicPath = $publicPath ?: getcwd() . DIRECTORY_SEPARATOR . 'public';
        $publicPath = rtrim($publicPath, DIRECTORY_SEPARATOR);

        // determine segment if not provided
        if ($segment === null) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $path = trim(parse_url($uri, PHP_URL_PATH) ?? '/', '/');
            $parts = $path === '' ? [] : explode('/', $path);
            $first = $parts[0] ?? '';
            $segment = $first !== '' ? 'public/'.$first : '';
        }

        // sanitize segment
//        $segment = preg_replace('#[^a-zA-Z0-9_-]#', '_', trim((string)$segment));
        if ($segment === '') {
            $segment = 'root';
        }

        $dir = $type;
        $assetsDir = $publicPath . DIRECTORY_SEPARATOR . $segment . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $dir;

        if (!is_dir($assetsDir)) {
            if (!mkdir($assetsDir, 0755, true)) {
                throw new RuntimeException("Unable to create assets directory: $assetsDir");
            }
        }

        $outputFileName = 'combined.' . $type;
        $outputFilePath = $assetsDir . DIRECTORY_SEPARATOR . $outputFileName;
        $hashFilePath = $assetsDir . DIRECTORY_SEPARATOR . 'combined.hash';

        // compute current md5 hash (based on each source file's md5 in order)
        $currentHashData = [];

        foreach ($files as $file) {
            // skip remote URLs when computing (they can't be combined)
            if (self::isRemoteUrl($file)) {
                // remote files are ignored for the combined file's hash
                continue;
            }
            $filePath = self::resolveFilePath($file, getcwd());
            if (!is_readable($filePath)) {
                continue;
            }
            $currentHashData[] = md5_file($filePath);
        }
        $currentHash = md5(implode('', $currentHashData));

        // if cache exists and hash matches, return existing url + integrity
        if (file_exists($hashFilePath) && file_exists($outputFilePath)) {
            $savedHash = @file_get_contents($hashFilePath);
            if ($savedHash === $currentHash) {
                // compute sha256 integrity of combined file (base64)
                $integrity = base64_encode(hash_file('sha256', $outputFilePath, true));
                $url = self::buildUrl($publicUrl, $segment, $dir, $outputFileName) . '?v=' . substr($currentHash, 0, 8);
                return self::generateHtmlTag($url, $type, $jsLoad, $integrity);
            }
        }

        // combine files
        $combined = '';

        foreach ($files as $file) {
            // if remote URL, skip combining but we could optionally include remote separately.
            if (self::isRemoteUrl($file)) {
                // skip remote files for combination; continue
                continue;
            }

            $filePath = self::resolveFilePath($file, getcwd());
            if (!is_readable($filePath)) {
                continue;
            }

            $content = file_get_contents($filePath);

            if ($type === 'css') {
                $content = self::fixCssPaths($content, dirname($filePath));
            }
            $combined .= $content . PHP_EOL;
        }

        // save combined
        if (file_put_contents($outputFilePath, $combined) === false) {
            throw new RuntimeException("Unable to write combined file: $outputFilePath");
        }

        // save hash
        if (file_put_contents($hashFilePath, $currentHash) === false) {
            throw new RuntimeException("Unable to write hash file: $hashFilePath");
        }

        // compute sha256 integrity of the newly written file
        $integrity = base64_encode(hash_file('sha256', $outputFilePath, true));
        $url = self::buildUrl($publicUrl, $segment, $dir, $outputFileName) . '?v=' . substr($currentHash, 0, 8);

        return self::generateHtmlTag($url, $type, $jsLoad, $integrity);
    }

    /**
     * Resolve a relative or absolute filesystem path.
     * Returns the input as-is if it's an absolute Unix path, Windows path, or stream.
     */
    private static function resolveFilePath(string $file, string $basePath): string
    {
        // remote URL -> return as-is (we won't combine remote files)
        if (self::isRemoteUrl($file)) {
            return $file;
        }

        // absolute unix path
        if (str_starts_with($file, DIRECTORY_SEPARATOR)) {
            return $file;
        }

        // Windows absolute path (like C:\...)
        if (preg_match('#^[A-Za-z]:[\\\\/]#', $file)) {
            return $file;
        }


        // otherwise it's relative to basePath
        return rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
    }

    private static function isRemoteUrl(string $s): bool
    {
        return (bool)preg_match('#^(https?:)?//#i', $s) || (bool)preg_match('#^https?://#i', $s);
    }

    /**
     * Generate HTML tag for the combined asset.
     *
     * @param string $url
     * @param string $type
     * @param string $jsLoad
     * @param string|null $integrity base64 sha256 binary (not prefixed) - will be added as integrity="sha256-..."
     * @return string
     */
    private static function generateHtmlTag(string $url, string $type, string $jsLoad = '', ?string $integrity = null): string
    {
        $escaped = htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
//        $integrityAttr = $integrity ? ' integrity="sha256-' . $integrity . '"' : '';

        if ($type === 'css') {
            return sprintf('<link rel="stylesheet" href="%s">', $escaped);
        }

//        $attributes = $integrityAttr;
//        if ($jsLoad === 'defer') {
//            $attributes .= ' defer';
//        } elseif ($jsLoad === 'async') {
//            $attributes .= ' async';
//        }

        return sprintf('<script src="%s"></script>', $escaped);
    }

    /**
     * Build a public-facing URL for the asset.
     */
    private static function buildUrl(?string $publicUrl, string $segment, string $dir, string $fileName): string
    {
        $segment = trim($segment, '/');
        if ($publicUrl === null) {
            // relative URL (from current request)
            return $segment . '/assets/' . $dir . '/' . $fileName;
        }
        return rtrim($publicUrl, '/') . '/' . $segment . '/assets/' . $dir . '/' . $fileName;
    }

    /**
     * Fix relative CSS url(...) paths by attempting to resolve to an absolute web path.
     * This implementation tries to resolve the referenced file on disk via realpath.
     * If the file exists, it converts to a web path relative to the document root (getcwd()).
     *
     * Note: depending on your deployment you might prefer to convert to $publicUrl-based absolute URLs.
     */
    private static function fixCssPaths(string $css, string $cssDir): string
    {
        return preg_replace_callback(
            '/url\s*\(\s*[\'"]?(?!data:)([^\'"\)]+)[\'"]?\s*\)/i',
            function ($matches) use ($cssDir) {
                $path = $matches[1];

                // if absolute URL or root-relative path or data URI, leave as-is
                if (preg_match('#^(data:|https?:|//|/).*#i', $path)) {
                    return $matches[0];
                }

                // try to resolve to filesystem absolute path
                $candidate = $cssDir . DIRECTORY_SEPARATOR . $path;
                $real = realpath($candidate);

                if ($real !== false) {
                    // create web path relative to project root (getcwd())
                    $cwd = rtrim(getcwd(), DIRECTORY_SEPARATOR);
                    if (str_starts_with($real, $cwd)) {
                        $web = substr($real, strlen($cwd));
                        $web = str_replace(DIRECTORY_SEPARATOR, '/', $web);
                        // ensure it starts with '/'
                        if ($web === '' || $web[0] !== '/') {
                            $web = '/' . $web;
                        }
                        return 'url("' . $web . '")';
                    }
                    // otherwise return original (can't map to web)
                    return 'url("' . $path . '")';
                }

                // fallback: return original relative path
                return 'url("' . $path . '")';
            },
            $css
        );
    }
}
