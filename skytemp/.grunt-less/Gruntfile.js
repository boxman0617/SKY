module.exports = function(grunt) {
  grunt.loadNpmTasks('grunt-contrib-watch');
  grunt.loadNpmTasks('grunt-contrib-less');

  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),

    watch: {
      scripts: {
        files: ['dev/**/*.less'],
        tasks: ['compileLess'],
        options: {
          spawn: false,
        },
      },
    },

    less: {
      dev: {
        options: {
          compress: true,
          cleancss: true
        },
        files: [{
          src: 'dev/less/main.less',
          dest: 'public/stylesheet/main.css'
        }]
      }
    }
  });

  grunt.registerTask('compileLess', ['less:dev']);
};
