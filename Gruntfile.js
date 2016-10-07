/* 
 * Package changes for release and trigger automatic update
 */

module.exports = function (grunt) {
    
    //setup file list for copying/ not copying for the final zip file
    files_list=[
        '**',
        '!node_modules/**',
        '!.git/**',
        '!README.md',
        '!Gruntfile.js',
        '!package.json',
        '!.gitignore',
        '!.gitmodules',
        '!.composer.*',
        '!composer.*',
        //- skip any existing zip files
        '!*.zip'
    ];

    grunt.initConfig({
        pkg: grunt.file.readJSON( 'package.json' ),
        clean: {
            //- deletes the release folder
            release: [
                'release/'
            ]
        },
        copy: {
            release: {
                options: {
                    mode: 0777 //- copies existing permissions. could also set the mode, ie: 777
                },
                src: files_list,
                dest: 'release/<%= pkg.name %>/'
            }
        },
        compress: {
            release: {
                options:{
                    archive: "<%= pkg.name %>.zip",
                    mode: 'zip'
                    },
                files: [{ 
                        cwd: 'release/', 
                        expand: true,
                        src: ['**/*']
                    } ]    
            }
        },
        chmod: {
            options: {
                    mode: '777'
                },
            release:{
                files: {
                    src: ["release/<%= pkg.name %>.zip"]
                }
            }
        },
        gitcheckout:{
            release:{
                options:{
                    branch: 'master'
                }
            }
        },
        gitadd:{
            task:{
                options:{
                  verbose: true,
                  force: true,
                  all: true,
                  cwd: './'
              }
          }
        },
        gitcommit: {
            commit: {
                options: {
                    message: 'Repository updated on ' + grunt.template.today(),
                    noVerify: true,
                    noStatus: false,
                    allowEmpty: true
                },
                files: {
                    src: './'
                }
            }
        },
        gitpush: {
            release:{
                options:{
                    tags:false,
                    remote: 'origin',
                    branch: 'master'
                }
            }
        },
        replace: {
				plugin_php: {
					src: ['all-in-one-event-calendar-export.php'],
					overwrite: true,
					replacements: [{
							from: /Version:\s*(.*)/,
							to: "Version: <%= pkg.version %>"
						},
						{
							from: /PLUGIN_VERSION = \'\s*(.*)\'/,
							to: "PLUGIN_VERSION = '<%= pkg.version %>'"
						}]
				},
        },
        cssmin:{
            release: {
               files: [{
                   expand: true,
                   report: 'min',
                   cwd: 'css/',
                   src: ['*.css', '!*.min.css'],
                   dest: 'css/',
                   ext: '.min.css',
                   extDot: 'last'
               }]
           }
        },
        uglify:{
            release:{
                files:[{
                    expand: true,
                    cwd: 'js/',
                    src: '**/*[!min].js',
                    dest: 'js/',
                    ext: '.min.js',
                    extDot: 'last'
                }]
            }
        },
       
    });

    //- load modules
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.loadNpmTasks('grunt-contrib-compress');
    grunt.loadNpmTasks('grunt-chmod');
    //- https://github.com/gruntjs/grunt-contrib-cssmin
    grunt.loadNpmTasks('grunt-contrib-cssmin');
    //- https://github.com/rubenv/grunt-git
    grunt.loadNpmTasks('grunt-git');         
	//- https://github.com/yoniholmes/grunt-text-replace
    grunt.loadNpmTasks('grunt-text-replace');
    //- https://github.com/gruntjs/grunt-contrib-uglify
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-remove');
    
    //- release tasks
    grunt.registerTask( 'productionRelease', [ 'clean:release', 'version_number', 'cssmin:release', 'uglify:release', 'releaseGit', 'copy:release', 'compress:release' ] );

    grunt.registerTask( 'releaseGit', [ 'gitadd', 'gitcommit',  'gitpush:release' ] );
	
	//- update version number in various files for production release
    grunt.registerTask( 'version_number', [ 'replace:plugin_php' ] );

};
