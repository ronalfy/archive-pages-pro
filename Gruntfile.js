module.exports = function (grunt) {
	grunt.initConfig({
		compress: {
			main: {
			  options: {
				archive: 'archive-pages-pro.zip'
			  },
			  files: [
				{src: ['archive-pages-pro.php'], dest: '/', filter: 'isFile'}, // includes files in path
				{src: ['readme.txt'], dest: '/', filter: 'isFile'}, // includes files in path
				{src: ['build/**'], dest: '/'}, // includes files in path and its subdirs
				{src: ['dist/**'], dest: '/'}, // includes files in path and its subdirs
				{src: ['lib/**'], dest: '/'}, // includes files in path and its subdirs
				{src: ['php/**'], dest: '/'}, // includes files in path and its subdirs
			  ]
			}
		  }
	  });
	  grunt.registerTask('default', ["compress"]);

 
 
	grunt.loadNpmTasks( 'grunt-contrib-compress' );
   
 };
