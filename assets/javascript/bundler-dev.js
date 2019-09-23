/**
 * This is the Parcel bundler for the DEVELOPMENT version
 */

const Bundler = require('parcel-bundler');
const Path = require('path');

const file = Path.join(__dirname, './src/script.js');

const options = {
	outDir: Path.join(__dirname, './dist'),
	outFile: 'bundle.js',
	watch: true,
	cache: false,
	minify: false,
	hmr: false,
	sourceMaps: false
};

const bundler = new Bundler(file, options);

bundler.bundle();
