module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    po2mo: {
      files: {
        src: 'languages/*.po',
        expand: true,
      },
    },
    pot: {
      options: {
        text_domain: 'rich-text-excerpts',
        dest: 'languages/',
        language: 'PHP',
        msgmerge: true,
        keywords: [
          '__:1',
          '_e:1',
          '_x:1,2c',
          'esc_html__:1',
          'esc_html_e:1',
          'esc_html_x:1,2c',
          'esc_attr__:1', 
          'esc_attr_e:1', 
          'esc_attr_x:1,2c', 
          '_ex:1,2c',
          '_n:1,2', 
          '_nx:1,2,4c',
          '_n_noop:1,2',
          '_nx_noop:1,2,3c'
        ],
      },
      files: {
        src: 'rich-text-excerpts.php'
      }
    },
    clean: {
    	svn: ['svn'],
    	trunk: ['svn/trunk/*']
    },
    mkdir: {
    	svn: {
    		options: {
    			create: ['svn']
    		}
    	}
    },
    copy: {
    	filestotrunk: {
    		files: [
	    		/* copy plugin files */
	    		{expand: true, cwd: './', src: 'rich-text-excerpts**', dest: 'svn/trunk/'},
	    		/* copy screenshots */
	    		{expand: true, cwd: './', src: 'screenshot**', dest: 'svn/trunk/'},
	    		/* copy readme */
	    		{src: 'readme.txt', dest: 'svn/trunk/readme.txt'},
	    		/* copy License */
	    		{src: 'LICENSE', dest: 'svn/trunk/LICENSE'},
	    		/* copy languages dir */
	    		{expand: true, cwd: 'languages', src: '**', dest: 'svn/trunk/languages/'}
	    	]
	    }
    },
    exec: {
        svncheckout: {
        	command: 'svn co https://plugins.svn.wordpress.org/rich-text-excerpts .',
        	cwd: 'svn'
        },
        svncheckin: {
        	command: 'svn ci',
        	cwd: 'svn'
        }
    }
  });

  // Load plugins
  grunt.loadNpmTasks( 'grunt-po2mo' );
  grunt.loadNpmTasks( 'grunt-pot' );
  grunt.loadNpmTasks( 'grunt-exec' );
  grunt.loadNpmTasks( 'grunt-mkdir' );
  grunt.loadNpmTasks( 'grunt-contrib-clean' );
  grunt.loadNpmTasks( 'grunt-contrib-copy' );

  // Default task(s).
  grunt.registerTask('buildsvn', ['clean:svn', 'mkdir:svn', 'exec:svncheckout']);
  grunt.registerTask('copytosvn', ['clean:trunk', 'copy:filestotrunk']);
  grunt.registerTask('default', ['clean:svn']);

};