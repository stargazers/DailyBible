<?php
	
	include 'CBible.php';
	$x = new CBible();

	if( $argc > 1 )
	{
		if( $argv[1] == '--whole_chapter' )
			$x->whole_chapter( true );
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

	//print_r( $ret );
?>
