import commonjs from '@rollup/plugin-commonjs';
import replace from '@rollup/plugin-replace';
import resolve from '@rollup/plugin-node-resolve';
import css from 'rollup-plugin-css-only';
import { terser } from 'rollup-plugin-terser';

let getConfig = function(handle, output, replace, terser) {
	return {
		input: 'assets/javascript/src/' + handle + '.js',
		output: {
			file: 'assets/javascript/dist/' + output + '.js',
			format: 'iife'
		},
		plugins: [
			commonjs({
				include: /node_modules/,
				// namedExports: {
				// 	'node_modules/lodash.clonedeep/index.js': ['cloneDeep'],
				// 	'node_modules/lodash.isequal/index.js': ['isEqual']
				// }
			}),
			resolve(),
			replace,
			css(),
			terser
		]
	};
};

export default ['script'].map(function(bundle){
	return [
		// Development version.
		getConfig(bundle, bundle, replace({
			'process.env.NODE_ENV': JSON.stringify('development')
		}), null),
		// Production version.
		getConfig(bundle, bundle + '.min', replace({
			'process.env.NODE_ENV': JSON.stringify('production')
		}), terser())
	];
}).reduce((a, b) => a.concat(b), []);
