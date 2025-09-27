📖 README.md (تکمیلی)
# StaticAssets


A lightweight PHP library for combining and serving CSS/JS files with automatic cache-busting.


## ✨ Features
- ✅ Combine multiple CSS/JS files into one
- ✅ Automatic cache-busting using file hashes
- ✅ Defer & async support for JavaScript
- ✅ Works with raw PHP or frameworks (Laravel, Symfony, etc.)
- ✅ Safe directory handling and atomic writes


## 📦 Installation
```bash
composer require hadiabedzadeh/static-assets
🚀 Usage
use HadiAbedzadeh\StaticAssets\StaticAssets;


echo StaticAssets::combineFiles([
    'assets/src/style1.css',
    'assets/src/style2.css'
], 'css', 'my-segment');


echo StaticAssets::combineFiles([
    'assets/src/app.js',
    'assets/src/utils.js'
], 'js', 'my-segment', 'async');
With Laravel
// Example inside a Blade template
{!! HadiAbedzadeh\\StaticAssets\\StaticAssets::combineFiles([
    public_path('css/app.css'),
    public_path('css/theme.css')
], 'css', 'laravel', 'defer', public_path(), asset('')) !!}
With Symfony
// Example inside Twig template
{{ HadiAbedzadeh\\StaticAssets\\StaticAssets.combineFiles([
    'assets/css/app.css',
    'assets/css/theme.css'
], 'css', 'symfony', 'defer', project_root ~ '/public', asset(''))|raw }}
⚙️ Options

$files: Array of file paths

$type: 'css' or 'js'

$segment: Output folder name (defaults to first URL segment)

$jsLoad: '' | 'defer' | 'async'

$publicPath: Filesystem path to public folder (defaults to getcwd() . '/public')

$publicUrl: Base URL for assets (optional)

📤 Publishing to Packagist

Create a GitHub repo: github.com/hadiabedzadeh/static-assets

Push all files and create a release tag (e.g., v1.0.0)

Submit the repo to Packagist or enable GitHub auto-sync

📝 License

MIT License