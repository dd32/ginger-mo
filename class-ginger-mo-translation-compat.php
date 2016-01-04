<?php

class Ginger_MO_Translation_Compat implements ArrayAccess {

	function __construct() {
		Ginger_MO::instance()->set_locale( get_locale() );
	}

	function offsetExists( $domain ) {
		return Ginger_MO::instance()->is_loaded( $domain );
	}

	function offsetGet( $domain ) {
		return new Ginger_MO_Translation_Compat_Provider( $domain );
	}

	function offsetSet( $domain, $value ) {
		// Not supported
		return false;
	}

	function offsetUnset( $domain ) {
		return Ginger_MO::instance()->unload( $domain );
	}

	function load_textdomain( $return, $domain, $mofile ) {
		do_action( 'load_textdomain', $domain, $mofile );
		$mofile = apply_filters( 'load_textdomain_mofile', $mofile, $domain );

		Ginger_MO::instance()->load( $mofile, $domain );

		return true;
	}

	function unload_textdomain( $return, $domain ) {
		do_action( 'unload_textdomain', $domain );

		Ginger_MO::instance()->unload( $domain );

		return true;
	}

	static function overwrite_wordpress() {
		global $l10n;

		$l10n = new Ginger_MO_Translation_Compat();

		add_filter( 'override_unload_textdomain', array( $l10n, 'unload_textdomain' ), 10, 2 );
		add_filter( 'override_load_textdomain',   array( $l10n, 'load_textdomain'   ), 10, 3 );
	}
}

class Ginger_MO_Translation_Compat_Provider {
	private $textdomain = 'default';
	function __construct( $textdomain = 'default' ) {
		$this->textdomain = $textdomain;
	}

	function translate_plural( $single, $plural, $number = 1, $context = '' ) {
		return Ginger_MO::instance()->translate_plural( array( $single, $plural ), $number, $context, $this->textdomain );
	}

	function translate( $text, $context = '' ) {
		return Ginger_MO::instance()->translate( $text, $context, $this->textdomain );
	}
}
