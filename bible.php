#!/usr/bin/php
<?php
	
	include 'settings.php';
	include 'CBible.php';

	// By default user did not gave param --list_verses
	$list_verses = false;

	// If user wanted to list old verses, then this keep info
	// if we must print also the text, not only the chapter and 
	// verse number.
	$text_too = false;


	// *********************************************
	//	list_verses
	//
	//	@brief List old verses
	//
	//	@param $settings Application settings
	//
	//	@param $text_too Should we print the text too?
	//
	// *********************************************
	function list_verses( $settings, $text_too )
	{
		$tmp = $settings['app_files'] . '/daily_verses.txt';

		// If history file is not found
		if(! file_exists( $tmp ) )
		{
			echo "No verse history found, yet.\n\n";
			return;
		}

		$data = file( $tmp );

		foreach( $data as $cur )
		{
			// Values are separated with | char in file
			$arr = explode( '|', $cur );

			// Bible text is in array what is serialized
			$data_array = unserialize( $arr[1] );

			// Day when this was the verse of the Day
			$day = $arr[0];

			// In file the verse number MUST be written in the text,
			// even when using --without_numbers, so now we can get the
			// first verse number
			$tmp_arr = explode( ' ', $data_array[0] );
			$verse_num = $tmp_arr[0];
			unset( $tmp_arr );

			// How many verses was on the Verse of the day?
			// Remove 1 because one is reserved for chapter name.
			$num_verses = sizeof( $data_array ) -1;

			// Show day and the name of the chapter
			echo "\t" . $day . "\t" . $data_array['chapter'];

			// Show verse number
			echo ":" . $verse_num;

			// Show end verse too, eg. show range.
			if( $num_verses > 1 )
				echo '-' . ( $verse_num + ( $num_verses -1 ) );

			echo  "\n";

			// User wanted to see text too
			if( $text_too )
			{
				echo "\n";

				// List all verses
				for( $i=0; $i < $num_verses; $i++ )
					echo wordwrap( $data_array[$i] ) . "\n";
				
				echo "\n";
			}

		}
		echo "\n";
	}

	function write_daily_verse( $settings, $data )
	{
		// File where verses of the Day are stored
		$tmp = $settings['app_files'] . '/daily_verses.txt';

		// If that file exists, then check if we have already
		// written this day verse in the file.
		if( file_exists( $tmp ) )
		{
			// Read file to array
			$arr = file( $tmp );

			// Loop line by line. If this day is found in file, then
			// we have to return without re-writing data.
			foreach( $arr as $cur )
			{
				// We have wrote verse already. Just skip this now.
				if( strstr( $cur, date( 'Y-m-d' ) ) != false )
					return;
			}
		}

		// Add verse of the day to file.
		$fh = fopen( $tmp, 'a+' );
		$line = date( 'Y-m-d' ) . '|' . serialize( $data ) . "\n";
		fwrite( $fh, $line );
		fclose( $fh );
	}

	function show_help( $argv )
	{
		echo "DailyBible\n\n";
		echo "Näyttää päivän Raamattulauseen käyttäen sivuston\n"
			. "http://raamattu.uskonkirjat.net sisältöä\n\n";
		echo "Käyttö: " . basename( $argv[0] ) . " [PARAMS]\n\n";
		echo "Mahdolliset parametrit:\n";
		echo " --whole_chapter\n";
		echo "   Näyttää koko luvun\n";
		echo " --language english\n";
		echo "   Näyttää Päivän Sanan englanniksi. Muut vaihtoehdot ovat\n";
		echo "   finnish (oletus) ja old_finnish\n";
		echo " --only_once\n";
		echo "   Näyttää vain kerran päivässä Päivän Sanan. Tämä on\n";
		echo "   tarkoitettu jos halutaan lisätä ohjelma .bashrc tai\n";
		echo "   vastaavan loppuun.\n";
		echo " --verse 'Joh. 3:16'\n";
		echo "   Näyttää halutun kappaleen, esim. Johannes 3:16\n";
		echo "   Tähän voidaan antaa myös alue, esim. 'Joh 3:2-12'\n";
		echo " --without_numbers\n";
		echo "   Poistaa rivin alusta kappaleiden numerot.\n";
		echo " --list_verses\n";
		echo "   Listaa aikaisemmat päivän Sanat mitä cachessa on.\n";
		echo " --text_too\n";
		echo "   Mikäli käytetään --list_verses parametriä, silloin\n";
		echo "   tällä parametrillä saadaan kerrottua että haluamme\n";
		echo "   nähdä myös jakeiden tekstit\n";
		echo " --help\n";
		echo "   Näyttää tämän ohjeen.\n";
		echo "\n";
		echo "Ohjelmoinut: Aleksi Räsänen <aleksi.rasanen@runosydan.net>\n";
		die();
	}

	function check_folders( $settings )
	{
		// Create folder for settings and cache etc. if not exists
		if(! file_exists( $settings['app_files'] ) )
			mkdir( $settings['app_files'], 0755 );
	}

	// Check if folders for cache and temporary files exists
	check_folders( $settings );

	$x = new CBible();

	// Check command line arguments.
	for( $i=0; $i < $argc; $i++ )
	{
		switch( $argv[$i] )
		{
			// User needs to get params
			case '--help':
				show_help( $argv );
				break;

			// User wants to use another language
			case '--language':
				if( isset( $argv[$i+1] ) )
					$x->set_language( $argv[$i+1] );
				break;

			// User wants text without chapter numbers
			case '--without_numbers':
				$x->without_numbers( true );
				break;

			// User wanted the whole chapter
			case '--whole_chapter':
				$x->whole_chapter( true );
				break;

			// User wanted to search some chapter and verse,
			// eg. Joh 3:16
			case '--verse':
				// --verse requires parameter what chapter and verse
				// we want to search, so check it.
				if( isset( $argv[$i+1] ) )
				{
					$x->verse( $argv[$i+1] );
				}
				// Not enough parameters, show some samples how to
				// use that parameter --verse correctly.
				else
				{
					echo 'If you use --verse, you must give a verse '
						. 'as an parameter!' . "\n";
					echo 'Example: ' . $argv[0] . ' --verse "Saarn. 1:2"'
						. "\n\n";
					echo 'You can set ranges too: ' . "\n";
					echo 'Example: ' . $argv[0] 
						. ' --verse "Ilm. 13:16-18"' . "\n";
					die();
				}	
				break;
				
			// List old verses
			case '--list_verses':
				$list_verses = true;
				break;

			// If user wants the bible text too when using --list_verses
			case '--text_too':
				$text_too = true;
				break;

			// User wants to see Daily Verse only once in a day.
			case '--only_once':
				$only_once = true;

				// Temporary file
				$tmp = $settings['app_files'] . '/bible.txt';

				// Is there temporary file? If so, then
				// just check if there is this day in that temporary
				// file. If there is, just die then, because Daily Verse
				// is already shown today.
				if( file_exists( $tmp ) )
				{
					$data = file( $tmp );
					if( isset( $data[0] ) )
					{
						if( $data[0] == date('Y-m-d' ) )
							die();
					}
				}
				break;
		}
	}

	// User wanted to list old verses
	if( $list_verses )
	{
		list_verses( $settings, $text_too );
		die();
	}

	$ret = $x->get_verse();

	echo $ret['chapter'] . "\n";
	echo str_pad( '-', strlen( $ret['chapter'] ), '-', STR_PAD_LEFT );
	echo "\n\n";

	// Print verses
	foreach( $ret as $key => $val )
	{
		if( is_numeric( $key ) )
			echo wordwrap( $val ) . "\n";
	}

	// If user wants Daily Verse only once in a day, then we must
	// create temporary file so next time this script is called we
	// don't show Daily Verse again.
	if( $only_once )
	{
		$fh = fopen( $settings['app_files'] . '/bible.txt', 'w' );
		fwrite( $fh, date( 'Y-m-d' ) );
		fclose( $fh );
	}

	// If user has not given any arguments, then we can check
	// if we must write this Daily Verse to cache file too, eg.
	// later user are able to use --list-verses to see daily verses
	// NOTE! We still must check if --only_once is given, because if
	// is is given, then we still need to write this to file...
	if( ( $argc < 2 ) || in_array( '--only_once', $argv ) )
		write_daily_verse( $settings, $ret );
	
?>
