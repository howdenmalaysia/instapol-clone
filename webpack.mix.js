const mix = require('laravel-mix');
const Minimizer = require('image-minimizer-webpack-plugin')
require('@chiiya/laravel-mix-image-minimizer');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.setPublicPath('public')
    .setResourceRoot('../')
    .sass('resources/sass/app.scss', 'css/frontend.css')
    // .sass('resources/sass/dashboard/app.scss', 'css/bo.css')
    .options({
        processCssUrl: false,
    })
    .js('resources/js/app.js', 'app.js')
    .extract([
        'jquery',
        'popper.js',
        'bootstrap',
        'axios',
        'sweetalert2',
        'loadash'
    ])
    .images({
        implementation: Minimizer.imageminMinify,
        patterns: [{
            from: '**/*',
            to: 'images',
            context: 'resources/images'
        }],
        options: {
            plugins: [
                ['imagemin-mozjpeg', {
                    quality: 100,
                    progressive: true,
                }]
            ]
        }
    })
    .sourceMaps();

if(mix.inProduction()) {
    mix.version();
} else {
    mix.webpackConfig({
        devtool: 'inline-source-map'
    });
}