<?php
	
	include 'CBible.php';
	$x = new CBible();

	// Read home dir with exec... maybe there is better way?
	$path = exec( 'echo $HOME' );

	// Check command line arguments.
	for( $i=0; $i < $argc; $i++ )
	{
		switch( $argv[$i] )
		{
			case '--language':
				if( isset( $argv[$i+1] ) )
					$x->set_language( $argv[$i+1] );
				break;

			// User wanted the whole chapter
			case '--whole_chapter':
				$x->whole_chapter( true );
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
