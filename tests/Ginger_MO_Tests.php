<?php
class Ginger_MO_Tests extends Ginger_MO_TestCase {

	function test_no_files_loaded_returns_false() {
		$instance = new Ginger_MO;
		$this->assertFalse( $instance->translate( "singular" ) );
		$this->assertFalse( $instance->translate_plural( array( "plural0", "plural1" ), 1 ) );
	}

	function test_unload_entire_textdomain() {
		$instance = new Ginger_MO;
		$this->assertFalse( $instance->is_loaded( 'unittest' ) );
		$this->assertTrue( $instance->load( GINGER_MO_TEST_DATA . 'example-simple.php', 'unittest' ) );
		$this->assertTrue( $instance->is_loaded( 'unittest' ) );

		$this->assertSame( 'translation', $instance->translate( 'original', null, 'unittest' ) );

		$this->assertTrue( $instance->unload( 'unittest' ) );
		$this->assertFalse( $instance->is_loaded( 'unittest' ) );
		$this->assertFalse( $instance->translate( 'original', null, 'unittest' ) );
	}

	/**
	 * @dataProvider dataprovider_invalid_files
	 */
	function test_invalid_files( $type, $file_contents, $expected_error = null ) {
		$file = $this->temp_file( $file_contents );

		$instance = Ginger_MO_Translation_File::create( $file, 'read', $type );

		// Not an error condition until it attempts to parse the file.
		$this->assertFalse( $instance->error() );

		// Trigger parsing.
		$instance->headers();

		$this->assertNotFalse( $instance->error() );

		if ( $expected_error ) {
			$this->assertSame( $expected_error, $instance->error() );
		}
	}

	function dataprovider_invalid_files() {
		return array(
			// filetype, file ( contents ) [, expected error string ]
			array( 'php', '' ),
			array( 'php', '<?php // This is a php file without a payload' ),
			array( 'json', '' ),
			array( 'json', 'Random data in a file' ),
			array( 'mo', '', "Invalid Data." ),
			array( 'mo', 'Random data in a file long enough to be a real header', "Magic Marker doesn't exist" ),
			array( 'mo', pack( 'V*', 0x950412de ), 'Invalid Data.' ),
			array( 'mo', pack( 'V*', 0x950412de ) . "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA", 'Unsupported Revision.' ),
			array( 'mo', pack( 'V*', 0x950412de, 0x0 ) . "AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA", 'Invalid Data.' ),
		);
	}

	function test_non_existent_file() {
		$instance = new Ginger_MO;
	
		$this->assertFalse( $instance->load( GINGER_MO_TEST_DATA . 'file-that-doesnt-exist.mo', 'unittest' ) );
		$this->assertFalse( $instance->is_loaded( 'unittest' ) );
	}

	/**
	 * @dataProvider dataprovider_simple_example_files
	 */
	function test_simple_translation_files( $file ) {
		$ginger_mo = new Ginger_MO;
		$this->assertTrue( $ginger_mo->load( GINGER_MO_TEST_DATA . $file, 'unittest' ) );

		$this->assertTrue( $ginger_mo->is_loaded( 'unittest' ) );
		$this->assertFalse( $ginger_mo->is_loaded( 'textdomain not loaded' ) );

		$this->assertFalse( $ginger_mo->translate( "string that doesn't exist", null, 'unittest' ) );
		$this->assertFalse( $ginger_mo->translate( 'original', null, 'textdomain not loaded' ) );

		$this->assertSame( 'translation', $ginger_mo->translate( 'original', null, 'unittest' ) );
		$this->assertSame( 'translation with context', $ginger_mo->translate( 'original with context', 'context', 'unittest' ) );

		$this->assertSame( 'translation1', $ginger_mo->translate_plural( array( 'plural0', 'plural1' ), 0, null, 'unittest' ) );
		$this->assertSame( 'translation0', $ginger_mo->translate_plural( array( 'plural0', 'plural1' ), 1, null, 'unittest' ) );
		$this->assertSame( 'translation1', $ginger_mo->translate_plural( array( 'plural0', 'plural1' ), 2, null, 'unittest' ) );

		$this->assertSame( 'translation1 with context', $ginger_mo->translate_plural( array( 'plural0 with context', 'plural1 with context' ), 0, 'context', 'unittest' ) );
		$this->assertSame( 'translation0 with context', $ginger_mo->translate_plural( array( 'plural0 with context', 'plural1 with context' ), 1, 'context', 'unittest' ) );
		$this->assertSame( 'translation1 with context', $ginger_mo->translate_plural( array( 'plural0 with context', 'plural1 with context' ), 2, 'context', 'unittest' ) );
	}

	function dataprovider_simple_example_files() {
		return array(
			array( 'example-simple.json' ),
			array( 'example-simple-jed.json' ),
			array( 'example-simple-po2json.json' ),
			array( 'example-simple.mo' ),
			array( 'example-simple.php' ),
		);
	}

	/**
	 * @dataProvider plural_form_function_pairs
	 */
	function test_plural_form_functions( $plural_form, $values ) {
		$instance = Testable_Ginger_MO_Translation_File::get_testable_instance();
		$plural_func = $instance->generate_plural_forms_function( $plural_form );
		$this->assertTrue( is_callable( $plural_func ) );

		foreach ( $values as $number => $expected ) {
			$form = $plural_func( $number );
			$this->assertSame( $expected, $form, print_r( compact( 'number', 'expected', 'form' ), true ) );
		}

	}

	function plural_form_function_pairs() {
		return array(
			// Bulgarian, etc.
			array( 'nplurals=2; plural=n != 1', array(
				0 => 1,
				1 => 0,
				2 => 1,
				3 => 1,
				10 => 1,
				11 => 1
			) ),
			// Japanese
			array( 'nplurals=2; plural=0', array(
				0 => 0,
				1 => 0,
				2 => 0,
				3 => 0,
				10 => 0,
				11 => 0
			) ),
			// French
			array( 'nplurals=2; plural=n > 1', array(
				0 => 0,
				1 => 0,
				2 => 1,
				3 => 1,
				10 => 1,
				11 => 1
			) ),
			/*
			 * Arabic: http://www.arabeyes.org/Plural_Forms
			 * 0: First form: for 0
			 * 1: Second form: for 1
			 * 2: Third form: for 2
			 * 3: Fourth form: for numbers that end with a number between 3 and 10 (like: 103, 1405, 23409).
			 * 4: Fifth form: for numbers that end with a number between 11 and 99 (like: 1099, 278).
			 * 5: Sixth form: for numbers above 100 ending with 0, 1 or 2 (like: 100, 232, 3001)
			*/
			array( 'nplurals=6; plural=(n==0 ? 0 : n==1 ? 1 : n==2 ? 2 : n%100>=3 && n%100<=10 ? 3 : n%100>=11 && n%100<=99 ? 4 : 5);', array(
				0 => 0,
				1 => 1,
				2 => 2,
				3 => 3,
				103 => 3,
				1405 => 3,
				23409 => 3,
				11 => 4,
				12 => 4,
				98 => 4,
				99 => 4,
				111 => 4,
				132 => 4,
				100 => 5,
				101 => 5,
				102 => 5,
				// 232 => 5, // This seems broken, according to the plural form function, this should be form 4.
				3001 => 5,
			) ),
			// Slovenian
			array( 'nplurals=4; plural=(n%100==1 ? 0 : n%100==2 ? 1 : n%100==3 || n%100==4 ? 2 : 3);', array(
				0 => 3,
				1 => 0,
				2 => 1,
				3 => 2,
				4 => 2,
				5 => 3,
				99 => 3,
				100 => 3,
				101 => 0,
				102 => 1,
				103 => 2,
				104 => 2,
				1405 => 3,
				23409 => 3,
			) ),
			// Icelandic
			array( 'nplurals=2; plural=(n % 100 != 1 && n % 100 != 21 && n % 100 != 31 && n % 100 != 41 && n % 100 != 51 && n % 100 != 61 && n % 100 != 71 && n % 100 != 81 && n % 100 != 91);', array(
				0 => 1,
				1 => 0,
				2 => 1,
				99 => 1,
				100 => 1,
				101 => 0,
				102 => 1,
				121 => 0,
				190 => 1,
				191 => 0,
				192 => 1,
			) ),
			/*
			 * Scottish Gaelic
			 * 0: Form 1 is for 1, 11
			 * 1: Form 2 is for 2, 12
			 * 2: Form 3 is for 3-10, 13-19
			 * 3: Form 4 is everything else: 20+
			 */
			array( 'nplurals=4; plural=(n==1 || n==11) ? 0 : (n==2 || n==12) ? 1 : (n > 2 && n < 20) ? 2 : 3;', array(
				0 => 3,
				1 => 0,
				2 => 1,
				3 => 2,
				5 => 2,
				10 => 2,
				11 => 0,
				12 => 1,
				21 => 3,
				22 => 3,
				31 => 3,
				32 => 3,
			) ),
		);
		/*
		 * Plural forms from GlotPress which aren't included here yet.
		 * (n%10==1 && n%100!=11 ? 0 : n != 0 ? 1 : 2)
		 * (n%10==1 && n%100!=11 ? 0 : n%10>=2 && (n%100<10 || n%100>=20) ? 1 : 2)
		 * (n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)
		 * (n==1 ? 0 : (n==0 || (n%100 > 0 && n%100 < 20)) ? 1 : 2)
		 * (n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2)
		 * n==1 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2
		 * (n==1 ? 0 : n%10>=2 && n%10<=4 && n%100==20 ? 1 : 2)
		 * n==1 ? 0 : n==2 ? 1 : n<7 ? 2 : n<11 ? 3 : 4
		 * (n==1) ? 0 : (n==2) ? 1 : (n != 8 && n != 11) ? 2 : 3
		 * (n==1) ? 0 : (n>=2 && n<=4) ? 1 : 2
		 * n==1 || n%10==1 ? 0 : 1
		 * (n==1 || n==11) ? 0 : (n==2 || n==12) ? 1 : (n > 2 && n < 20) ? 2 : 3
		*/
	}

}