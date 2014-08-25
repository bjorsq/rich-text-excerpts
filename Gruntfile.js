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
    }
  });

  // Load the plugin that provides the "po2mo" task.
  grunt.loadNpmTasks( 'grunt-po2mo' );
  grunt.loadNpmTasks( 'grunt-pot' );

  // Default task(s).
  grunt.registerTask('default', ['po2mo']);

};