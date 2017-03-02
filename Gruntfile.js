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
                reporterOutput: '',
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

        bower_concat: {
            all: {
                dest: 'assets/javascript/source/bower.js',
                cssDest: 'assets/styles/bower.css',
                exclude: [
                    'jquery'
                ],
                include: [
                    'spin.js'
                ]
            }
        },

        concat: {
            options: {
                separator: ''
            },
            dist: {
                src: [
                	'assets/javascript/source/bower.js',
                    'assets/javascript/source/searchwp-live-search.js'
				],
                dest: 'assets/javascript/build/searchwp-live-search.js'
            }
        },

		uglify: {
			dist: {
				files: {
					'assets/javascript/build/searchwp-live-search.min.js': 'assets/javascript/build/searchwp-live-search.js'
				}
			}
		},

		watch: {
			js: {
				files: [
					'<%= jshint.all %>'
				],
				tasks: [ 'jshint', 'bower_concat', 'concat', 'uglify' ]
			}
		}
	});

	// Load tasks
	grunt.loadNpmTasks('grunt-import');
	grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-bower-concat');
    grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');

	// Register tasks
	grunt.registerTask('default', [
		'jshint',
		'bower_concat',
		'concat',
		'uglify',
		'watch'
	]);

};
