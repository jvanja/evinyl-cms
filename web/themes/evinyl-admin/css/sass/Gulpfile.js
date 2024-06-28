'use strict';

var gulp = require('gulp');
// var sass = require('gulp-sass');
 const sass = require('gulp-sass')(require('sass'));

// sass.compiler = require('node-sass');

gulp.task('sass', function () {
  return gulp.src('./evinyl_admin.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('../'));
});

gulp.task('sass:watch', function () {
  gulp.watch('./**/*.scss', gulp.series('sass'));
});

gulp.task('default', gulp.series('sass:watch'));
