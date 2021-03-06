/* global process */
/* eslint arrow-body-style: 0 */

const gulp = require( 'gulp' );

const autoprefixer = require( 'autoprefixer' );
const babelify = require( 'babelify' );
const browserify = require( 'browserify' );
const buffer = require( 'gulp-buffer' );
const childProcess = require( 'child_process' );
const cssnano = require( 'cssnano' );
const del = require( 'del' );
const eslint = require( 'gulp-eslint' );
const exec = require( 'gulp-exec' );
const imagemin = require( 'gulp-imagemin' );
const jsonlint = require( 'gulp-jsonlint' );
const newer = require( 'gulp-newer' );
const phplint = require( 'phplint' ).lint;
const postcss = require( 'gulp-postcss' );
const rename = require( 'gulp-rename' );
const runSequence = require( 'run-sequence' );
const sass = require( 'gulp-sass' );
const tap = require( 'gulp-tap' );
const uglify = require( 'gulp-uglify' );
const util = require( 'gulp-util' );
const zip = require( 'gulp-zip' );

const config = {
	assets: {
		src: 'resources/assets/',
		dest: 'svn-assets/'
	},

	images: {
		src: 'resources/images/',
		dest: 'assets/images/'
	},

	name: 'MultilingualPress',

	scripts: {
		src: 'resources/js/',
		dest: 'assets/js/'
	},

	slug: 'multilingualpress',

	src: 'src/',

	styles: {
		src: 'resources/scss/',
		dest: 'assets/css/'
	},

	tests: {
		js: 'tests/js/',
		php: 'tests/php/'
	}
};

gulp.task( 'assets', () => {
	const dest = config.assets.dest;

	return gulp
		.src( `${config.assets.src}*.{gif,jpeg,jpg,png}` )
		.pipe( newer( dest ) )
		.pipe( imagemin( {
			optimizationLevel: 7
		} ) )
		.pipe( gulp.dest( dest ) );
} );

gulp.task( 'clean', () => {
	return del( [
		config.assets.dest,
		config.images.dest,
		config.scripts.dest,
		config.styles.dest,
	] );
} );

gulp.task( 'lint-configs', () => {
	return gulp
		.src( [
			'*.json',
			'.*rc',
		] )
		.pipe( newer( {
			dest: '*.json',
			extra: '.*rc'
		} ) )
		.pipe( jsonlint() )
		.pipe( jsonlint.reporter() );
} );

gulp.task( 'lint-javascript-tests', [
	'lint-configs',
], () => {
	const src = `${config.tests.js}**/*.js`;

	return gulp
		.src( src )
		.pipe( newer( {
			dest: src,
			extra: '.eslintrc'
		} ) )
		.pipe( eslint( {
			rules: {
				'no-native-reassign': 0
			}
		} ) )
		.pipe( eslint.format() );
} );

gulp.task( 'lint-php', ( cb ) => {
	const src = [
		'*.php',
		`${config.src}**/*.php`,
		`${config.tests.php}**/*.php`,
	];

	phplint( src, { limit: 10 }, ( err ) => {
		cb( err );
		if ( err ) {
			process.exit( 1 );
		}
	} );
} );

gulp.task( 'lint-scripts', [
	'lint-configs',
], () => {
	const src = `${config.scripts.src}*.js`;

	return gulp
		.src( src )
		.pipe( newer( {
			dest: src,
			extra: '.eslintrc'
		} ) )
		.pipe( eslint() )
		.pipe( eslint.format() );
} );

gulp.task( 'images', () => {
	const dest = config.images.dest;

	return gulp
		.src( `${config.images.src}**/*.{gif,jpeg,jpg,png}` )
		.pipe( newer( dest ) )
		.pipe( imagemin( {
			optimizationLevel: 7
		} ) )
		.pipe( gulp.dest( dest ) );
} );

gulp.task( 'phpunit', [
	'lint-php',
], ( cb ) => {
	childProcess.exec( '"./vendor/bin/phpunit"', ( err, stdout, sterr ) => {
		if ( stdout ) {
			util.log( stdout );
		}
		if ( sterr ) {
			util.log( sterr );
		}
		cb( err );
	} );
} );

gulp.task( 'scripts', [
	'lint-configs',
	'lint-scripts',
], () => {
	const dest = config.scripts.dest;
	const browserifyOptions = {
		debug: true,
		transform: [
			babelify,
		]
	};

	return gulp
		.src( `${config.scripts.src}*.js`, {
			read: false
		} )
		.pipe( tap( ( file ) => {
			file.contents = browserify( file.path, browserifyOptions ).bundle();
		} ) )
		.pipe( buffer() )
		.pipe( gulp.dest( dest ) )
		.pipe( rename( {
			extname: '.min.js'
		} ) )
		.pipe( uglify( {
			output: {
				ascii_only: true
			}
		} ) )
		.pipe( gulp.dest( dest ) );
} );

gulp.task( 'styles', () => {
	const dest = config.styles.dest;

	return gulp
		.src( `${config.styles.src}**/*.scss` )
		.pipe( newer( {
			dest,
			ext: '.css'
		} ) )
		.pipe( sass( {
			indentType: 'tab',
			indentWidth: 1,
			outputStyle: 'expanded'
		} ).on( 'error', sass.logError ) )
		.pipe( postcss( [
			autoprefixer( {
				cascade: false
			} ),
		] ) )
		.pipe( gulp.dest( dest ) )
		.pipe( rename( {
			extname: '.min.css'
		} ) )
		.pipe( postcss( [
			cssnano(),
		] ) )
		.pipe( gulp.dest( dest ) );
} );

gulp.task( 'tape', [
	'lint-configs',
	'lint-javascript-tests',
	'lint-scripts',
], () => {
	return gulp
		.src( `${config.tests.js}**/*Test.js`, {
			read: false
		} )
		.pipe( exec(
			'"./node_modules/.bin/babel-node" --plugins rewire <%= file.path %> | "./node_modules/.bin/faucet"'
		) )
		.pipe( exec.reporter() );
} );

gulp.task( 'zip', () => {
	return gulp
		.src( [
			'*.{php,txt}',
			`${config.images.dest}**/*.{gif,jpeg,jpg,png}`,
			`${config.scripts.dest}*.js`,
			`${config.styles.dest}*.css`,
			`${config.src}**/*.php`,
		], {
			base: '.'
		} )
		.pipe( rename( ( path ) => {
			path.dirname = `${path.slug}/${path.dirname}`;
		} ) )
		.pipe( zip( `${config.name}.zip` ) )
		.pipe( gulp.dest( '.' ) );
} );

gulp.task( 'common', [
	'lint-configs',
	'lint-javascript-tests',
	'lint-php',
	'lint-scripts',
] );

gulp.task( 'test', [
	'common',
	'phpunit',
	'tape',
] );

gulp.task( 'develop', [
	'common',
	'images',
	'scripts',
	'styles',
] );

gulp.task( 'pre-commit', ( cb ) => {
	runSequence(
		'clean',
		[
			'test',
			'assets',
			'images',
			'scripts',
			'styles',
		],
		cb
	);
} );

gulp.task( 'release', ( cb ) => {
	runSequence(
		'pre-commit',
		'zip',
		cb
	);
} );

gulp.task( 'default', [ 'develop' ] );
