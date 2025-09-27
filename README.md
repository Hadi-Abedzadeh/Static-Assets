# Static Assets Combiner

A PHP package for combining and optimizing CSS/JS files with cache support and framework integration.

---

## 📦 Installation

```bash
composer require hadiabedzadeh/static-assets
```
### 🚀 Usage

```bash
use hadiabedzadeh\StaticAssets\StaticAssets;

echo StaticAssets::combineFiles([
    'assets/src/style1.css',
    'assets/src/style2.css'
], 'css', 'my-segment');

echo StaticAssets::combineFiles([
    'assets/src/app.js',
    'assets/src/utils.js'
], 'js', 'my-segment', 'async');
```

### ⚡ With Laravel

### Example inside a Blade template:
```bash
{!! hadiabedzadeh\StaticAssets\StaticAssets::combineFiles([
    public_path('css/app.css'),
    public_path('css/theme.css')
], 'css', 'laravel', 'defer', public_path(), asset('')) !!}
```
### 🏛️ With Symfony

### Example inside a Twig template:
```bash
{{ hadiabedzadeh\StaticAssets\StaticAssets.combineFiles([
    'assets/css/app.css',
    'assets/css/theme.css'
], 'css', 'symfony', 'defer', project_root ~ '/public', asset(''))|raw }}
```

### ⚙️ Options

* $files: Array of file paths

* $type: 'css' or 'js'

* $segment: Output folder name (defaults to first URL segment)

* $jsLoad: '' | 'defer' | 'async'

* $publicPath: Filesystem path to public folder (defaults to getcwd() . '/public')

* $publicUrl: Base URL for assets (optional)
