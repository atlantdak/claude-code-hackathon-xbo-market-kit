<?php
declare(strict_types=1);

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define WordPress constants used by plugin.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}
if ( ! defined( 'XBO_MARKET_KIT_VERSION' ) ) {
	define( 'XBO_MARKET_KIT_VERSION', '0.1.0' );
}
if ( ! defined( 'XBO_MARKET_KIT_FILE' ) ) {
	define( 'XBO_MARKET_KIT_FILE', dirname( __DIR__ ) . '/xbo-market-kit.php' );
}
if ( ! defined( 'XBO_MARKET_KIT_DIR' ) ) {
	define( 'XBO_MARKET_KIT_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'XBO_MARKET_KIT_URL' ) ) {
	define( 'XBO_MARKET_KIT_URL', 'http://example.com/wp-content/plugins/xbo-market-kit/' );
}
