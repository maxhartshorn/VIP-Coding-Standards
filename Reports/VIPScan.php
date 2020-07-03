<?php

namespace WordPressVIPMinimum;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Reports\Report;

/**
 * Custom VIPScan report.
 *
 * @package VIPCS\WordPressVIPMinimum
 */
class VIPScan implements Report {

	// Hack
	private $output;

	// Hackety-hack
	public function __construct() {
		require __DIR__ . '/CLI_Output.php';
		$this->output = new CLI_Output;
	}

	/**
	 * Generate a partial report for a single processed file.
	 *
	 * Function should return TRUE if it printed or stored data about the file
	 * and FALSE if it ignored the file. Returning TRUE indicates that the file and
	 * its data should be counted in the grand totals.
	 *
	 * @param array $report      Prepared report data.
	 * @param File  $phpcsFile   The file being reported on.
	 * @param bool  $showSources Show sources?
	 * @param int   $width       Maximum allowed line width.
	 *
	 * @return bool
	 */
	public function generateFileReport( $report, File $phpcsFile, $showSources = false, $width = 80 ) {
		$filename = str_replace('\\', '\\\\', $report['filename']);
		$filename = str_replace('"', '\"', $filename);
		$filename = str_replace('/', '\/', $filename);
		echo '"'.$filename.'":{';
		echo '"errors":'.$report['errors'].',"warnings":'.$report['warnings'].',"messages":[';

		$messages = '';
		foreach ($report['messages'] as $line => $lineErrors) {
			foreach ($lineErrors as $column => $colErrors) {
				foreach ($colErrors as $error) {
					$error['message'] = str_replace("\n", '\n', $error['message']);
					$error['message'] = str_replace("\r", '\r', $error['message']);
					$error['message'] = str_replace("\t", '\t', $error['message']);

					$fixable = false;
					if ($error['fixable'] === true) {
						$fixable = true;
					}

					$messagesObject          = (object) $error;
					$messagesObject->line    = $line;
					$messagesObject->column  = $column;
					$messagesObject->fixable = $fixable;

					$messages .= json_encode($messagesObject).",";
				}
			}
		}//end foreach

		echo rtrim($messages, ',');
		echo ']},';

		return true;
	}

	/**
	 * Generate the actual report.
	 *
	 * @param string $cachedData    Any partial report data that was returned from
	 *                              generateFileReport during the run.
	 * @param int    $totalFiles    Total number of files processed during the run.
	 * @param int    $totalErrors   Total number of errors found during the run.
	 * @param int    $totalWarnings Total number of warnings found during the run.
	 * @param int    $totalFixable  Total number of problems that can be fixed.
	 * @param bool   $showSources   Show sources?
	 * @param int    $width         Maximum allowed line width.
	 * @param bool   $interactive   Are we running in interactive mode?
	 * @param bool   $toScreen      Is the report being printed to screen?
	 *
	 * @return void
	 */
	public function generate( $cachedData, $totalFiles, $totalErrors, $totalWarnings, $totalFixable, $showSources = false, $width = 80, $interactive = false, $toScreen = true ) {
		$json = '{"totals":{"errors":'.$totalErrors.',"warnings":'.$totalWarnings.',"fixable":'.$totalFixable.'},"files":{';
		$json .= rtrim($cachedData, ',');
		$json .= "}}".PHP_EOL;

//		echo $this->process_json( $json );
		$this->process_json( $json );
	}

	/**
	 * Takes JSON report string as input containing PHPCS results, and processes it.
	 *
	 * @param  string $json JSON report contents.
	 * @return string Output.
	 */
	function process_json( $json ) {
		$data = json_decode( $json, true, 1024 );
		$files = $data['files'];
		unset( $data );

		$final_data = [];
		foreach ( $files as $name => $f ) {
			$messages = $f['messages'];
			foreach ( $messages as $m ) {
				$m['file'] = $name;
				$final_data[] = $m;
			}
		}
		unset( $files );
		usort($final_data, function( $a, $b ) {
			// make sure the labels stay at the top
			if ( 'ERROR' === $b['type'] ) {
				if ( $a['type'] !== 'ERROR' ) {
					return 1;
				}
			} else {
				if ( $a['type'] === 'ERROR' ) {
					return -1;
				}
			}
			if ( $a['severity'] < $b['severity']) {
				return 1;
			}if ( $a['severity'] > $b['severity']) {
				return -1;
			}
			if ( $a['file'] != $b['file'] ) {
				return strcmp( $a['file'], $b['file'] );
			}
			if ( $a['line'] === $b['line'] ) {
				return 0;
			}
			return ($a['line'] < $b['line']) ? -1 : 1;
		});
		$lines = "";
		$md = "";
		$errors_needing_dev = 0;
		$errors_needing_fix = 0;
		$warnings = 0;
		foreach ( $final_data as $line ) {
			if ( empty( $line ) ) {
				continue;
			}
			if ( empty( $line['file'] ) ) {
				echo " * PHP: Empty file value found on CSV line, full line:\n";
				echo json_encode( $line );
				echo "* skipping line \n";
				continue;
			}
			if ( $line['type'] == 'ERROR' ) {
				$errors_needing_dev += 1;
			} else if ( ( $line['severity'] >= 5 ) && ( $line['type'] == 'WARNING' ) ) {
				$errors_needing_fix += 1;
			} else {
				$warnings += 1;
			}
			$lines .= '"'.$line['file'].'",'.intval( $line['line'] ).',"'.$line['severity'].'","'.$line['type'].'","'.addslashes( $line['message'] ).'","'.$line['source']."\"\n";
			$md .= '| '.$line['file'].' | '.intval( $line['line'] ).' | '.$line['severity'].' | '.$line['type'].' | '. $line['message'].' | '.$line['source']." |\n";
		}
		echo "\n";
		$this->output->success( '', 'Results' );
		/*if ( $php_errors > 0 ) {
			$this->output->error( 'These will break the site, guaranteed, and are the highest priority, and are a major red flag', "🚨 Files With PHP Syntax Errors: ${php_errors}" );
		}*/
		if ( $errors_needing_dev > 0 ) {
			$this->output->error( 'These indicate platform incompatibilities, and potential performance/security issues. They might actually be ok, but the client should aim to take care of these', 'PHPCS Errors');
		}

		if ( $errors_needing_fix > 0 ) {
			$this->output->error( 'These are warnings at severity 6 or above', 'Problem Warnings');
		}

		if ( $warnings > 0 ) {
			$this->output->warning( 'These are warnings below severity 6, and indicate logic bugs or sources of PHP warnings and notices. It would be best practice to resolve these, but not a high priority', 'Warnings');
		}
		if ( ( $warnings + $errors_needing_fix + $errors_needing_dev )  === 0 ) {
			$this->output->success( "The total number of warnings and errors is 0, the target has passed \n", 'Success' );
		}
		if ( !empty( $lines ) ) {
			//$this->write_csv( $output_csv, $lines );
			//$this->write_markdown( $output_markdown, $md, count( $final_data ) );
			$this->echo_csv( $lines );
		}

		return [
			'errors_needing_dev' => $errors_needing_dev,
			'errors_needing_fix' => $errors_needing_fix,
			'warnings' => $warnings
		];
	}

	private function write_csv( $file, $lines ) {
		$header = "\"File\",\"Line\",\"Severity\",\"Type\",\"Message\",\"Source\"\n";
		file_put_contents( $file, $header.$lines );
	}

	private function echo_csv( $lines ) {
		$header = "\"File\",\"Line\",\"Severity\",\"Type\",\"Message\",\"Source\"\n";
		echo $header.$lines;
	}

	private function write_markdown( $file, $md, $issue_count ) {
		$preamble = "\`".$issue_count."\` issues were found during standard review. All errors, and warnings 6 and above should be fixed. For warnings below 6 but you may use your own discretion. Items marked as \`error\` represent major security and performance issues, or functionality that will not work, e.g. filesystem writes or user agent checks.\n\n\n";
		$preamble .= "| File | Line | Severity | Type | Message | Source |\n";
		$preamble .= "| ---- | ---- | -------- | ---- | ------- | ------ |\n";
		file_put_contents( $file, $preamble.$md );
	}
}
