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
  });

  // Load the plugin that provides the "po2mo" task.
  grunt.loadNpmTasks('grunt-po2mo');

  // Default task(s).
  grunt.registerTask('default', ['po2mo']);

};