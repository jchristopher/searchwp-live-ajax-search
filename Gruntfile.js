'use strict';
module.exports = function(grunt) {

	grunt.initConfig({

		jshint: {
			options: {
				"bitwise": true,
				"browser": true,
				"curly": true,
				"eqeqeq": true,
				"eqnull": true,
				"es5": false,
				"esnext": true,
				"immed": true,
				"jquery": true,
				"latedef": true,
				"newcap": true,
				"noarg": true,
				"node": true,
				"strict": false,
				"trailing": false,
				"undef": true,
				"globals": {
					"jQuery": true,
					"Spinner": true,
					"searchwp_live_search_params": true,
					"alert": true
				}
			},
			all: [
				'assets/javascript/source/searchwp-live-search.js'
			]
		},

		uglify: {
			dist: {
				files: {
					'assets/javascript/searchwp-live-search.min.js': [
						'assets/javascript/source/searchwp-live-search-staged.js'
					]
				}
			}
		},

		import: {
			options: {},
			dist: {
				src: 'assets/javascript/source/wrapper.js',
				dest: 'assets/javascript/source/searchwp-live-search-staged.js'
			},
			tasks: ['jshint','uglify']
		},

		watch: {
			js: {
				files: [
					'<%= jshint.all %>'
				],
				tasks: ['import','jshint','uglify']
			}
		}
	});

	// Load tasks
	grunt.loadNpmTasks('grunt-import');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');

	// Register tasks
	grunt.registerTask('default', [
		'import',
		'jshint',
		'uglify',
		'watch'
	]);

};
