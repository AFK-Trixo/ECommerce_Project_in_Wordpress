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
define( 'DB_NAME', 'wordpress_db' );

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
define( 'AUTH_KEY',         '+Tr%FrgscK1GAW 9pi)y8rI|I#! =GBoU^y0&3g&=7Ok/9anq^el6!$1jAT%rhjm' );
define( 'SECURE_AUTH_KEY',  'I@h+%U8dSR=%L1!L_Plmf|[TR#aRk=)_v66#7^$cD)fjJI[blA`2G|6K(Y2U8jUy' );
define( 'LOGGED_IN_KEY',    'v%=kfxLK#@4/S#Uu{z~fQt6Xgf1u}AeJTekqK%Uv{J&<1<4!6*[|C@EAEAm=_CsV' );
define( 'NONCE_KEY',        'Y#?_>D;-wTn%PMj+ddPFh.(@Cq`k ip^O?Id/5Krq*(a!l)0 4|gG2ot^Lyst-mO' );
define( 'AUTH_SALT',        'cl%K%L:vZV@}dny39>:0sF>[*t}(*xvzc;I[Leb}@1=X;+18C! cG2`1g^r>V7{K' );
define( 'SECURE_AUTH_SALT', '[RX?m4R3qdLpS 5y$TI6/LL&Cr(x(*U<JRch0lSwTj::Vq2/&7>`.9zOwp)fUSM3' );
define( 'LOGGED_IN_SALT',   'RI%fEh@Z,9_%8vu{m827A`o_(_(B$Y|CGf O1fU&+5Xpp*RP;xnklp!m9?tqo-$E' );
define( 'NONCE_SALT',       '@HfV=h2RF>M4Z3};0}Myk8MTYkvJ!VLscZ;F<-+9povF6U{?l_RW_d?|G+fAh(r>' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

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
