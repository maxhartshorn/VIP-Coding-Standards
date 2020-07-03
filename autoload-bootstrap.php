<?php
/**
 * Autoload the autoload files of the individual standards.
 *
 * Adapted from https://github.com/jrfnl/QA-WP-Projects by Juliette Reinders Folmer.
 *
 * @package  VIPCS\WordPressVIPMinimum
 */

/**
 * Register an autoloader to be able to load the custom report based
 * on a Fully Qualified (Class)Name.
 *
 * @param string $class Class being requested.
 */
spl_autoload_register(
	function ( $class ) {
		// Only try & load our own classes.
		if ( stripos( $class, 'WordPressVIPMinimum' ) !== 0 ) {
			return;
		}

		// The only class(es) this standard has, are in the Reports directory.
		$class = str_replace( 'WordPressVIPMinimum\\', 'Reports\\', $class );
		$file  = realpath( __DIR__ ) . DIRECTORY_SEPARATOR . strtr( $class, '\\', DIRECTORY_SEPARATOR ) . '.php';

		if ( file_exists( $file ) ) {
			include_once $file;
		}
	},
	true
);
