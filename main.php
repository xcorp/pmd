<?php


function checkIfMovieExists($id) {
	global $mysqli;
	$query = 'SELECT id FROM movies WHERE id="'.$id.'";';
	$result = $mysqli->query($query);
	return $result->num_rows;
}

function escapeSpecialChars($string) {
	$string = str_replace("'", "\'", $string);
	return str_replace(' ', '\ ', $string);
}

function getMovie($id) {
	global $mysqli;
	$query = 'SELECT * FROM movies WHERE id="'.$id.'";';
	$result = $mysqli->query($query);
	while ($res = $result->fetch_object()) {
		$movie = $res;
	}
	$query = 'SELECT actors.actor FROM actors LEFT JOIN `actor-movie` ON actors.id=`actor-movie`.actor WHERE movie='.$id.';';
	$result = $mysqli->query($query);
	while ($actor = $result->fetch_object()) {
		$movie->actors[] = $actor->actor;
	}
	
	$query = 'SELECT genres.genre FROM genres LEFT JOIN `genre-movie` ON genres.id=`genre-movie`.genre WHERE movie='.$id.';';
	$result = $mysqli->query($query);
	while ($genre = $result->fetch_object()) {
		$movie->genres[] = $genre->genre;
	}
	
	$query = 'SELECT writers.writer FROM writers LEFT JOIN `writer-movie` ON writers.id=`writer-movie`.writer WHERE movie='.$id.';';
	$result = $mysqli->query($query);
	while ($writer = $result->fetch_object()) {
		$movie->writers[] = $writer->writer;
	}
	
	$query = 'SELECT directors.director FROM directors LEFT JOIN `director-movie` ON directors.id=`director-movie`.director WHERE movie='.$id.';';
	$result = $mysqli->query($query);
	while ($director = $result->fetch_object()) {
		$movie->directors[] = $director->director;
	}
	
	return $movie;
}

function getMovieInfo($type, $value) {
	//$name = str_replace(' ', '%20', $value);
	$url = 'http://www.imdbapi.com/?plot=full&'.$type.'='.$value;
	$json = file_get_contents($url);
	$movie = (json_decode($json));
	$movie->ID = preg_replace('/[^0-9]*/','', $movie->ID);
	$movie->Genre = explode(",", $movie->Genre);
	$movie->Director = explode(",", $movie->Director);
	$movie->Writer = explode(",", $movie->Writer);
	$movie->Actors = explode(",", $movie->Actors);
	return $movie;
}

function insertMovie($movie) {
	global $mysqli;
	if(checkIfMovieExists($movie->ID)) {
		echo  $movie->Title.' already exists';
	}
	else {
		$query = 'INSERT INTO movies VALUES (
			"'.$movie->ID.'",
			"'.$movie->Title.'",
			"'.$movie->Rated.'",
			"'.$movie->Year.'",
			"'.$movie->Released.'",
			"'.$movie->Poster.'",
			"'.$movie->Runtime.'",
			"'.$movie->Plot.'");';
		foreach($movie->Genre as $genre) {
			$query .= 'INSERT INTO genres (genre) VALUES ("'.$genre.'")
 						on duplicate key update id=id;
 						INSERT INTO `genre-movie` SELECT id, "'.$movie->ID.'" FROM genres  WHERE genre="'.$genre.'";';
		}
		foreach($movie->Director as $director) {
			$query .= 'INSERT INTO directors (director) VALUES ("'.$director.'")
 						on duplicate key update id=id;
 						INSERT INTO `director-movie` SELECT id, "'.$movie->ID.'" FROM directors  WHERE director="'.$director.'";';
		}
		foreach($movie->Writer as $writer) {
			$query .= 'INSERT INTO writers (writer) VALUES ("'.$writer.'")
 						on duplicate key update id=id;
 						INSERT INTO `writer-movie` SELECT id, "'.$movie->ID.'" FROM writers  WHERE writer="'.$writer.'";';
		}
		foreach($movie->Actors as $actor) {
			$query .= 'INSERT INTO actors (actor) VALUES ("'.$actor.'")
 						on duplicate key update id=id;
 						INSERT INTO `actor-movie` SELECT id, "'.$movie->ID.'" FROM actors  WHERE actor="'.$actor.'";';
		}
		if($mysqli->multi_query($query)) {
			echo $movie->Title.' added to database';
		}
		else {
			echo "Something went wrong, try again";
		}		
	}	
}

function parseUploadedFile() {
	move_uploaded_file($_FILES["file"]["tmp_name"], "tmp/" . $_FILES["file"]["name"]);
	$imdbLink = exec('grep imdb /var/www/pmd/tmp/'.escapeSpecialChars($_FILES["file"]["name"]));
	$imdbId = 'tt'.preg_replace('/[^0-9]*/','', $imdbLink);
	insertMovie((getMovieInfo('i', $imdbId)));
	exec('rm /var/www/pmd/tmp/'.$_FILES["file"]["name"]);	
}

function printActor($id) {
	global $mysqli;
	$query = 'SELECT title, id FROM movies WHERE id=(SELECT movie FROM actors LEFT JOIN `actor-movie` ON(actors.id=`actor-movie`.actor) WHERE id='.$id.' );';
	echo $query;
	$result=$mysqli->query($query);
	while ($res = $result->fetch_object()) {
		
		echo '<a href="?movie='.getMovie($res->id)->id.'">'.$res->title.'</a>';
	}
}

function printMovie($movie) {
	echo '<H1>'.$movie->title.' ('.$movie->year.')</H1>
	Released: '.$movie->released.'<br />
	<img src="'.$movie->poster.'" /><br />
	Runtime: '.$movie->runtime.'
	<h2>Plot:</h2>
	<p>'.$movie->plot.'</p>
	Stars:<br />';
	foreach ($movie->actors as $actor) {
		echo $actor.'<br />';
		;
	}
}

function printSearchForm() {
	echo '<form action="?" method="GET">
		<input name="search_string"> <br />
		Movie: <input type="radio" name="search_type" value="movie" checked> <br />
		Actor: <input type="radio" name="search_type" value="actor"> <br />
		Director: <input type="radio" name="search_type" value="director"> <br />
		Writer: <input type="radio" name="search_type" value="writer"> <br />
		Genre: <input type="radio" name="search_type" value="genre"> <br />
		<input type="submit" value="Search">
		</form>';
}

function printSearchResult($search_type, $result) {
	while ($res = $result->fetch_object()) {
		$type=$search_type;
		if ($search_type == "movie") {
			$type="title";
		}
		 echo '<a href="?'.$search_type.'='.$res->id.'">'.$res->$type.'</a><br />';
	}
}

function printUploadForm() {
	echo '<form action="?uploaded" method="post"
	enctype="multipart/form-data">
	<label for="file">Filename:</label>
	<input type="file" name="file" id="file" />
	<br />
	<input type="submit" value="Upload" />
	</form>';
}

function searchMovie($type, $string) {
	global $mysqli;
	$query = 'SELECT * FROM movies WHERE title LIKE "%'.$string.'%" ORDER BY title ASC;';
	if ($type != "movie") {
		$query = 'SELECT * FROM '.$type.'s WHERE '.$type.' LIKE "%'.$string.'%" ORDER BY '.$type.' ASC;';
	}
	return $mysqli->query($query);
	
}


?>