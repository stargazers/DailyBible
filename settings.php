<?php

	// User home directory
	$settings['home'] = exec( 'echo $HOME' );

	// Application files. Here we save temporary files and
	// keep caches and so on.
	$settings['app_files'] = $settings['home'] . '/.dailybible';
?>

