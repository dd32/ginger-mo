<?php

class Ginger_MO_Translation_File_PHP extends Ginger_MO_Translation_File {
	protected function parse_file() {
		$result = include( $this->file );
		if ( ! $result || ! is_array( $result ) ) {
			$this->error = true;
			return;
		}

		foreach ( array( 'headers', 'entries', 'plural_form_function' ) as $field ) {
			if ( isset( $result[ $field ] ) ) {
				$this->$field = $result[ $field ];
			}
		}

		$this->headers = array_change_key_case( $this->headers, CASE_LOWER );
		$this->parsed = true;
	}

	protected function create_file( $headers, $entries ) {
		$file_contents = '<' . "?php\n";
		if ( ! empty( $headers['x-converter'] ) ) {
			$file_contents .= "// {$headers['x-converter']}.\n";
		}

		$plural_func = false;
		if ( isset( $headers['plural-forms'] ) ) {
			$plural_func_contents = $this->generate_plural_forms_function_content( $headers['plural-forms'] );
			if ( $plural_func_contents ) {
				$plural_form_function = 'plural_forms_' . preg_replace( '![^0-9a-z_]!i', '_', basename( $this->file ) ) . '_' . sha1( uniqid( rand(), true ) );
				$plural_func = "if ( ! function_exists( '{$plural_form_function}' ) ) { function {$plural_form_function}( \$n ) { $plural_func_contents } }";
			}
		}

		if ( $plural_func ) {
			$file_contents .= $plural_func . "\n";
		}
		$file_contents .= 'return ' . var_export( compact( 'plural_form_function', 'headers', 'entries' ), true ) . ';';

		return (bool) file_put_contents( $this->file, $file_contents );
	}
}
