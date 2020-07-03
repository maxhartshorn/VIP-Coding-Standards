<?php

namespace WordPressVIPMinimum;

class CLI_Output {

	private $block_red = "\033[41;1;97m";
	private $red = "\033[0;31m";

	private $block_orange = "\033[43;1;30m";
	private $orange = "\033[0;33m";

	private $block_green = "\033[42;1;30m";
	private $green = "\033[0;32m";

	private $block_white = "\033[107;1;30m";
	private $white = "\033[0m";

	/**
	 * Resets the terminal colours
	 */
	private function reset() : void {
		echo $this->white;
	}

	/**
	 * Resets the terminal colours and starts a new line
	 */
	private function end() : void {
		$this->reset();
		echo "\n";
	}

	public function error( string $message, $title='Error' ) : void {
		if ( !empty( $title ) ) {
			echo $this->block_red;
			echo ' '.$title.' ';
			echo $this->red.' ';
		} else {
			echo $this->red;
		}
		$this->output( $message );
	}

	public function warning( string $message, $title='Warning' ) : void {
		if ( !empty( $title ) ) {
			echo $this->block_orange;
			echo ' '.$title.' ';
			echo $this->orange.' ';
		} else {
			echo $this->orange;
		}
		$this->output( $message );
	}

	public function success( string $message, $title='Warning' ) : void {
		if ( !empty( $title ) ) {
			echo $this->block_green;
			echo ' '.$title.' ';
			echo $this->green.' ';
		} else {
			echo $this->green;
		}
		$this->output( $message );
	}

	public function info( string $message, $title='Info' ) : void {
		if ( !empty( $title ) ) {
			echo $this->block_white;
			echo ' '.$title.' ';
			echo $this->white;
		} else {
			echo $this->white;
		}
		$this->output( $message );
	}

	public function output( string $message ) : void {
		echo $message;
		$this->end();
	}
}
