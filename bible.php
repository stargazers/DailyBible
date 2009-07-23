#!/usr/bin/php
<?php
	
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
		echo " --help\n";
		echo "   Näyttää tämän ohjeen.\n";
		echo "\n";
		echo "Ohjelmoinut: Aleksi Räsänen <aleksi.rasanen@runosydan.net>\n";
		die();
	}

	include 'CBible.php';
	$x = new CBible();

	// Read home dir with exec... maybe there is better way?
	$path = exec( 'echo $HOME' );

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
				
			// User wants to see Daily Verse only once in a day.
			case '--only_once':
				$only_once = true;

				// Temporary file
				$tmp = $path . '/.bible.txt';

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

	$ret = $x->get_verse();

	echo $ret['chapter'] . "\n";
	echo str_pad( '-', strlen( $ret['chapter'] ), '-', STR_PAD_LEFT );
	echo "\n\n";

	foreach( $ret as $key => $val )
	{
		if( is_numeric( $key ) )
			echo $val . "\n";
	}

	// If user wants Daily Verse only once in a day, then we must
	// create temporary file so next time this script is called we
	// don't show Daily Verse again.
	if( $only_once )
	{
		$fh = fopen( $path . '/.bible.txt', 'w' );
		fwrite( $fh, date( 'Y-m-d' ) );
		fclose( $fh );
	}

?>
