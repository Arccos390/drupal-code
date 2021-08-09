'use strict';

var gulp = require('gulp');
var sass = require('gulp-sass');
var spritesmith = require('gulp.spritesmith');
var sourcemaps = require('gulp-sourcemaps');
var autoprefixer = require('gulp-autoprefixer');
// doesnt work with import once var importer = require('node-sass-globbing');
// doesnt work with import once var sassGlob = require('gulp-sass-glob');
var importOnce = require('node-sass-import-once');
var plumber = require('gulp-plumber');
var cssmin = require('gulp-cssmin');
var uglify = require('gulp-uglify');
var rename = require('gulp-rename');
var pixrem = require('gulp-pixrem');
//var Gmsmith = require('gmsmith');
//var browserSync = require('browser-sync').create();

var babel = require("gulp-babel");
var concat = require("gulp-concat");





var sass_config = {
  importer: importOnce,
  includePaths: [
    'node_modules/breakpoint-sass/stylesheets/',
    'node_modules/susy/sass/',
   // 'node_modules/chroma-sass/sass',
  ]
};

gulp.task('uglify', function() {
    return gulp.src('libraries/**/*.js')
    .pipe(uglify())
    .pipe(rename({
      suffix: '.min'
    }))
    .pipe(gulp.dest('dist/uglify-exports'));
});


// babel task 
gulp.task('babelify', () =>
    gulp.src('./js/**/*.js')
        .pipe(sourcemaps.init())
        .pipe(babel({
            presets: ['env']
        }))
        .pipe(concat('app.js'))
        .pipe(sourcemaps.write('.'))
        .pipe(gulp.dest('dist'))
);


gulp.task('sass-compile', function() {
//    browserSync.init({
//    injectChanges: true,
//      proxy: "",
//    open: false,
//      online: false,
      // ui: false,
      // logConnections: true,
      // reloadDelay: 2000
//    });
    gulp.watch("./scss/**/*.scss", ['sass']);
//    gulp.watch("./css/*.styles.css").on('change',browserSync.reload);
});

gulp.task('sprite',function() {
 var spriteData = gulp.src('./images/sprites/*.png')
  .pipe(spritesmith({
    imgName: 'sprites.png',
    imgPath: '/themes/custom/shipyard/images/sprites/build/sprites.png',
    cssName: '_sprites.scss',
    padding: 2,
  //  engine: Gmsmith,
    cssVarMap: function(sprite) {
  sprite.name = "sprite_" + sprite.name;
    }
  }));

  spriteData.img.pipe(gulp.dest('./images/sprites/build/'));
  spriteData.css.pipe(gulp.dest('./scss/variables/'));
});

gulp.task('sass', function () {

// delay execution of sass because we have problem we SFTP sync from local dev
 setTimeout(delaySFTP, 260);

function delaySFTP() {
  gulp.src('./scss/**/*.scss')
    .pipe(plumber())
    .pipe(sourcemaps.init())
    .pipe(sass(sass_config).on('error', sass.logError))
    .pipe(autoprefixer({
      browsers: ['last 2 version']
    }))
//    .pipe(cssmin())
    .pipe(pixrem({
     rootValue: '16px',
     unitPrecision: 5
    }))
    .pipe(sourcemaps.write('.'))
    .pipe(gulp.dest('./css'))
//    .pipe(browserSync.stream());
// avoid sourcemaps!!! .pipe(browserSync.stream({match: '**/*.css'}));
 }
});

gulp.task('default', ['sass-compile','babelify']);
