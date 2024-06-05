const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
module.exports = ( env ) => {
	return [
		{
			...defaultConfig,
			module: {
				...defaultConfig.module,
				rules: [ ...defaultConfig.module.rules ],
			},
			mode: env.mode,
			devtool: 'production' === env.mode ? false : 'source-map',
			entry: {
				'app-settings-reading': [ './src/react/views/settings-reading/index.js', './src/react/views/settings-reading/styles.scss' ],
				'app-term-edit': './src/react/views/term-edit/index.js',
				'app-profile-edit': './src/react/views/profile-edit/index.js',
			},
		},
	];
};
