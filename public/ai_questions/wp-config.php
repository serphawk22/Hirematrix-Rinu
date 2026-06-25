<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'ai_job_portal' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', '' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'as!V(#20zL#-w_m6#GV2dw<}EtD!8:A!Fc|Vn03Khv1IjX1n{U0!)7-SBS}Z7Hg?' );
define( 'SECURE_AUTH_KEY',  '$:Jw[9-[ce$4eRY99iRAA(1lLHb1aP:Do8m6/K6j.if9@fuhy3a{!!*Sy5GE7$df' );
define( 'LOGGED_IN_KEY',    '?64c>}Lmg4lm Y-F>$~&oE}R6<3H4Xar3|}9Gn0R)wRq<GuFK&@?D*baf2d%v?d}' );
define( 'NONCE_KEY',        'zP1J_;p^+uI|Eh7s5x$j:NRNPh~g8tsgy9crd|m)uotX#NJGVkd.yx&1V`}|k^W(' );
define( 'AUTH_SALT',        'pOLW$a`]71ht#Z?NqkO|j~^?M:u|KUQak`mfx$<33bWcb/5QO<CT:J^7iCIMvAu*' );
define( 'SECURE_AUTH_SALT', 'fBV/@MUfDC<9Qzu0@01Kfk.VZhp<J[4DE7U[9MT~ZtWWjsUqfyJn}HVFWkj.[O33' );
define( 'LOGGED_IN_SALT',   '6;Vk&5*l(%y,Vg0WD*^l.4i|BS@ee)j^XltRC=%{mKUYZj)^fp wrO4`lQ{~m-Dz' );
define( 'NONCE_SALT',       'd={ Up?*oz{f{t8Czdg-I!h IP|b;~8QT:iRCCv 1)*!d,5}RTwSr;3`[D.}jr@Y' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'ai_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
