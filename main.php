<?php


function checkIfMovieExists($id) {
	global $mysqli;
	$query = 'SELECT id FROM movies WHERE id="'.$id.'";';
	$result = $mysqli->query($query);
	return $result->num_rows;
}

function escapeSpaces($string) {
	return str_replace(' ', '\ ', $string);
}


function insertMovie($data) {
	global $mysqli;
	if(checkIfMovieExists($data->ID)) {
		echo "Movie already exists";
	}
	else {
		$query = 'INSERT INTO movies VALUES (
			"'.$data->ID.'",
			"'.$data->Title.'",
			"'.$data->Rated.'",
			"'.$data->Year.'",
			"'.$data->Released.'",
			"'.$data->Poster.'",
			"'.$data->Runtime.'",
			"'.$data->Plot.'");';
		foreach($data->Genre as $genre) {
			$query .= 'INSERT INTO genres (genre) VALUES ("'.$genre.'")
 						on duplicate key update id=id; ';
		}
		foreach($data->Director as $director) {
			$query .= 'INSERT INTO directors (director) VALUES ("'.$director.'")
 						on duplicate key update id=id;
 						INSERT INTO director-movie VALUES ((SELECT id FROM director WHERE director="'.$director.'"), '.$data->ID.');';
		}
		foreach($data->Writer as $writer) {
			$query .= 'INSERT INTO writers (writer) VALUES ("'.$writer.'")
 						on duplicate key update id=id; ';
		}
		foreach($data->Actors as $actor) {
			$query .= 'INSERT INTO actors (actor) VALUES ("'.$actor.'")
 						on duplicate key update id=id; ';
		}
		
		echo $query;
		
	}
	
}

function parseUploadedFile() {
	move_uploaded_file($_FILES["file"]["tmp_name"], "tmp/" . $_FILES["file"]["name"]);
	$imdbLink = exec('grep imdb /var/www/pmd/tmp/'.escapeSpaces($_FILES["file"]["name"]));
	$imdbId = 'tt'.preg_replace('/[^0-9]*/','', $imdbLink);
	insertMovie((getMovieInfo('i', $imdbId)));
	exec('rm /var/www/pmd/tmp/'.$_FILES["file"]["name"]);

	
}

function printUploadForm() {
	echo '<form action="index.php?uploaded" method="post"
	enctype="multipart/form-data">
	<label for="file">Filename:</label>
	<input type="file" name="file" id="file" />
	<br />
	<input type="submit" name="submit" value="Submit" />
	</form>';
}

function getMovieInfo($type, $value) {
	$name = str_replace(' ', '%20', $value);
	$url = 'http://www.imdbapi.com/?plot=full&'.$type.'='.$value;
	$json = file_get_contents($url);
	$movie = (json_decode($json));
	$movie->Genre = explode(",", $movie->Genre);
	$movie->Director = explode(",", $movie->Director);
	$movie->Writer = explode(",", $movie->Writer);
	$movie->Actors = explode(",", $movie->Actors);
	return $movie;

}


?>