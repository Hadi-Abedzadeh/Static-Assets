<?php
namespace hadiabedzadeh\StaticAssets;


use InvalidArgumentException;


class StaticAssets
{
/**
* Combine CSS/JS files and return an HTML tag with cache-busting.
*
* @param array $files Array of file paths (absolute or relative)
* @param string $type 'css' or 'js'
* @param string $segment Folder segment, default = first segment of REQUEST_URI
* @param string $jsLoad JS attribute: 'defer', 'async', or ''
* @param string $publicPath Filesystem path to public dir (default = getcwd() . '/public')
* @param string $publicUrl Base URL for assets (optional)
* @return string HTML tag
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


if ($segment === null || $segment === 'segment') {
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$first = explode('/', trim(parse_url($uri, PHP_URL_PATH), '/'));
$segment = $first[0] ?? '';
}


$segment = preg_replace('#[^a-zA-Z0-9_-]#', '_', trim($segment));
if ($segment === '') {
$segment = 'root';
}


$dir = $type;
$assetsDir = $publicPath . DIRECTORY_SEPARATOR . $segment . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $dir;


if (!is_dir($assetsDir)) {
if (!mkdir($assetsDir, 0755, true) && !is_dir($assetsDir)) {
throw new \RuntimeException("Unable to create assets directory: $assetsDir");
}
}


$outputFileName = 'combined.' . $dir;
$outputFilePath = $assetsDir . DIRECTORY_SEPARATOR . $outputFileName;
$hashFilePath = $assetsDir . DIRECTORY_SEPARATOR . 'combined.hash';


$currentHashData = '';
foreach ($files as $file) {
$filePath = self::resolveFilePath($file, getcwd());
if (!is_readable($filePath)) continue;
$currentHashData .= md5_file($filePath);
}
$currentHash = md5($currentHashData);


if (is_file($hashFilePath)) {
$savedHash = @file_get_contents($hashFilePath);
if ($savedHash === $currentHash && is_file($outputFilePath)) {
return self::generateHtmlTag(
self::buildUrl($publicUrl, $segment, $dir, $outputFileName),
$type,
$jsLoad,
$currentHash
);
}
}


$combined = '';
foreach ($files as $file) {
$filePath = self::resolveFilePath($file, getcwd());
if (!is_readable($filePath)) continue;
$combined .= file_get_contents($filePath) . PHP_EOL;
}
}