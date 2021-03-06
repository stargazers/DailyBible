<?php
	/* 
	Biblesite handling class
	This file is part of DailyBible
	Copyright (C) 2009 Aleksi Räsänen <aleksi.rasanen@runosydan.net>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	*/
	class CBible
	{
		//! Should we get whole chapter?
		private $whole_chapter = false;

		// Default language
		private $language = 'FinPR';

		//! Here we download temporary page
		private $file = '/tmp/bible.txt';

		//! Verse to search
		private $search_verse = '';

		//! If we don't want chapter numbers
		private $without_numbers = false;

		// *********************************************
		//	set_language
		//
		//	@brief Set language to use
		//
		//	@param $val Language. Valid values are:
		//		'finnish', 'old_finnish', 'english'
		//
		// *********************************************
		public function set_language( $val )
		{
			switch( strtolower( $val ) )
			{
				// Raamattu 1933/38
				default:
				case 'finnish':
					$this->language = 'FinPR';
					break;

				// Biblia 1776
				case 'old_finnish':
					$this->language = 'FinBiblia';
					break;

				// Young's literal
				case 'english':
					$this->language = 'YLT';
					break;
			}
		}

		// *********************************************
		//	without_numbers
		//
		//	@brief Sets if user wants text wihtout chapter
		//		numbers on it.
		//
		//	@param $val True or False.
		//
		// *********************************************
		public function without_numbers( $val )
		{
			$this->without_numbers = $val;
		}

		// *********************************************
		//	verse
		//
		//	@brief Set verse to show
		//
		//	@param $verse Exact verse or a range.
		//		For example "Joh. 3:16"
		//
		// *********************************************
		public function verse( $verse )
		{
			$this->search_verse = $verse;
		}

		// *********************************************
		//	whole_chapter
		//
		//	@brief Sets is we should get whole chapter
		//
		//	@param $val True or false
		//
		// *********************************************
		public function whole_chapter( $val )
		{
			$this->whole_chapter = $val;
		}

		// *********************************************
		//	get_whole_chapter_url
		//
		//	@brief Read temporary file $this->file and
		//		get the URL of whole chapter. Note!
		//		This returns only part after ? from URL!
		//
		//	@return End of the URL.
		//
		// *********************************************
		private function get_whole_chapter_url()
		{
			// Read temporary file to array
			$data = file( $this->file, FILE_IGNORE_NEW_LINES );

			// Loop through lines and search correct value
			foreach( $data as $cur )
			{
				// If there is href-tag, then we must check this line
				if( strstr( $cur, 'href' ) != false )
				{
					$tmp = explode( '>', $cur );

					// If there is at least 9 items in our array, then
					// we have found the right array.
					if( isset( $tmp[9] ) )
					{
						// Split 9th item
						$tmp = explode( '<', $tmp[9] );

						// So, if we have no in first item text what
						// is not empty, then that is the URL of the
						// whole chapter.
						if( isset( $tmp[1] ) && $tmp[1] != '' )
						{
							$tmp2 = explode( '?', $tmp[1] );
							return $tmp2[1];
						}
					}
				}
			}
		}

		// *********************************************
		//	download_page
		//
		//	@brief Downloads page. This downloads the
		//		main page or the whole page, depending
		//		on $this->whole_chapter value.
		//
		// *********************************************
		private function download_page( )
		{
		 	$base_url = 'http://raamattu.uskonkirjat.net/'
				. 'servlet/biblesite.Bible';

			// Always get the main page
			$this->download( $base_url );

			// Do we need the whole chapter?
			// If so, then we must get the whole chapter URL
			// from downloaded mainpage.
			if( $this->whole_chapter )
			{
				$ch_url = $this->get_whole_chapter_url();
				$url = $base_url . '?' . $ch_url;
				$this->download( $url );
			}
		}

		// *********************************************
		//	download
		//
		//	@brief Downloads given URL to $this->file
		//
		//	@param $url URL to download
		//
		// *********************************************
		private function download( $url )
		{
			$file = fopen( $this->file, 'w' );

			// Download temporary file
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_POST, 1 );

			// Language to use
			$post_values = 'mod1=' . $this->language;

			// If user has searched some chapter, then we just
			// put that chapter here in post values.
			if( $this->search_verse != "" )
				$post_values .= '&ref=' . $this->search_verse;

			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post_values );

			curl_setopt( $ch, CURLOPT_FILE, $file );
			curl_setopt( $ch, CURLOPT_URL, $url );
			curl_exec( $ch );
			curl_close( $ch );
			fclose( $file );
		}

		// *********************************************
		//	get_correct_verse
		//
		//	@brief Get correct verse or verses from 
		//		temporary file $this->file
		//
		//	@return Array
		//
		// *********************************************
		private function get_correct_verse()
		{
			// Read temporary file to array
			$data = file( $this->file, FILE_IGNORE_NEW_LINES );

			// We have not found correct div yet.
			$found = false;

			// Return value
			$verses = array();

			foreach( $data as $cur )
			{
				// If there is text "Näytä koko luku" in this line, then
				// we just get the correct chapter name.
				if(! $this->whole_chapter )
				{
					if( strstr( $cur, 'koko&nbsp;luku' ) != false )
					{
						$ch = explode( '>', $cur );
						$ch = explode( '<', $ch[13] );
						$verses['chapter'] = $ch[0];
					}
				}
				else
				{
					if( strstr( $cur, 'align=center><p><b>' ) != false )
					{
						$ch = explode( '>', $cur );
						$ch = explode( '<', $ch[10] );
						$verses['chapter'] = $ch[0];
					}
				}

				// So, if there is class="text" in this string,
				// then we have found the correct div where we can
				// read the Bible verse/verses.
				if( strstr( $cur, 'class="text"' ) != false )
					$found = true;

				if( $found )
				{
					$tmp = explode( '>', $cur );

					// If there is text "font" in this line too,
					// then we need to get 5th part. Otherwise part
					// what we need is third.
					if( strstr( $cur, '<font' ) != false )
						$pos = 5;
					else
						$pos = 3;

					if( isset( $tmp[$pos] ) )
					{
						// By default we do not need this, because
						// it is in the text already.
						$verse_num = -1;

						// If there was text font in this line, 
						// then we need to get correct verse number 
						// using different way.
						if( strstr( $cur, '<font' ) != false )
						{
							$nums = explode( '<', $tmp[3] );
							$verse_num = $nums[0];
						}

						$tmp = explode( '<', $tmp[$pos] );
						$tmp = html_entity_decode( $tmp[0] );

						// Do not add empty lines!
						if( strlen( $tmp ) > 1 )
						{
							// If we have get manually verse number,
							// then we must add it before the line.
							if( $verse_num != -1 )
								$tmp = $verse_num . $tmp;

							// If user did not want chapter numbers
							// on text, then we remove them here.
							if( $this->without_numbers )
							{
								// Get the verse number
								$arr = explode( ' ', $tmp );
								$num = $arr[0];

								// Remove verse number from text.
								$tmp = trim( preg_replace( '/^[0-9]*/', '', 
									$tmp ) );

								// Verse number must be used as a key 
								// numberif user wanted text 
								// without verse numbers
								$verses[$num] = $tmp;
							}
							// Just add text with verse numbers.
							else
							{
								$verses[] = $tmp;
							}
						}
					}
				}
			}

			return $verses;
		}

		// *********************************************
		//	get_verse
		//
		//	@brief Get daily verse. If $this->whole_chapter
		//		is true, we return the whole chapter, not
		//		only daily verse.
		//
		//	@return Array
		//
		// *********************************************
		public function get_verse()
		{
			// First we need to download correct page.
			$this->download_page();

			// Then we must get the verse or verses.
			return $this->get_correct_verse();
		}
	}
?>
