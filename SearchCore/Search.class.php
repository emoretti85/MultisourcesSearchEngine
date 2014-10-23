<?php
/**
 * The Search class allows you to perform searches on a variety of sources.
 *
 * [IT]
 * La Classe Search permette di effettuare delle ricerche su diverse fonti.
 * Attraverso i metodi statici che vengono esposti, si ha la possibilità di effettuare le ricerche utilizzando fino a tre strategie di ricerca a cascata (Like,Soundex,Levenshtein).
 * La classe è fortemente configurabile per diversi tipi di uso, tutte le configurazioni sono descritte nella Classe Conf() (file SearchConf.php).
 * Tutti i metodi esposti sono statici, in modo da poter essere richiamati facilmente anche via AJAX.
 * Le possibili fonti sui quali è possibile lavorare sono:
 * DB-MYSQL ; XML ; INI ; FLAT FILE
 * I file possono risiedere sia sullo stesso server dove risiede l'application server, sia su server esterni.
 * 
 *  
 *  L'output è composto da un array principale, suddiviso in 3 aree, tante quante sono le strategie di ricerca.
 *  Al loro interno, saranno presentati i dati trovati e il numero di risultati (num_of_total_rows), 
 *  suddivisi a loro volta per:
 *  ° tabella nel caso di ricerca su mysql
 *  ° DOMElement Object nel caso di ricerca in file xml
 *  ° Stringa in caso di INI e FLATFILE
 * 
 * 
 * 
 * [EN - translated with google]
 * The Class Search allows you to perform searches on a variety of sources. 
 * Through static methods that are exposed, you have the ability to perform searches using up to three search strategies cascade (Like, Soundex, Levenshtein). 
 * The class is highly configurable for different types of use, all configurations are described in the Class Pack () (file SearchConf.php). 
 * All the exposed methods are static, so as to be easily recalled also via AJAX. 
 * Possible sources on which it is possible to work are: 
 * MYSQL-DB; XML; INI; FLAT FILE 
 * The file can reside either on the same server where the application server, both on external servers.
 *
 *
 * The output is composed of a main array, divided into 3 areas, as many as the search strategies. 
 * They will be presented the data found and the number of results (num_of_total_rows) 
 * In turn subdivided for: 
 * ° Table in the case of research on mysql 
 * ° DOMElement Object in the case of search in xml file 
 * ° String  in the case of INI and FlatFile
 *
 *
 *
 * @category   SearchEngine
 * @author     Ettore Moretti <ettoremoretti27@gmail.com>
 * @copyright  2014 Ettore Moretti
 * @license    MIT License (MIT)
 * @version    Release: 1.0
 */
class Search {
	
	/**
	 * Public method for searching in mysql db
	 * 
	 * (The connection to the db, is via pdo classes, so if necessary, 
	 * you can configure the connection to other types of DB modificado 
	 * in an appropriate manner the function for the connection)
	 */
	public static function getDBSearch($keySearch) {
		$DB = self::getDBConnection ();
		return self::getDBSearchResult ( $DB, $keySearch );
	}
	
	public static function getXMLSearch($keySearch) {
		if (! Conf::$XML_IN_SERVER)
			$XML = self::getRemoteXmlFile ();
		else
			$XML = self::getXMLFile ();
		return self::getXMLSearchResult ( $keySearch, $XML );
	}
	
	public static function getINISearch($keySearch) {
		if (! Conf::$INI_IN_SERVER)
			$INI = self::getRemoteFile ( Conf::$INI_URL );
		else
			$INI = self::getFile ( Conf::$INI_LOCATION . Conf::$INI_FILENAME );
		return self::getIniSearchResult ( $keySearch, $INI );
	}
	
	public static function getFLATSearch($keySearch) {
		if (! Conf::$FLAT_IN_SERVER)
			$FLAT = self::getRemoteFile ( Conf::$FLAT_URL );
		else
			$FLAT = self::getFile ( Conf::$FLAT_LOCATION . Conf::$FLAT_FILENAME );
		return self::getFlatSearchResult ( $keySearch, $FLAT );
	}
	
	/**
	 * This method handles the connection to the db via the PDO class.
	 */
	protected static function getDBConnection() {
		$Conn = new PDO ( 'mysql:host=' . Conf::$DB_HOST . ';dbname=' . Conf::$DB_NAME, Conf::$DB_USERNAME, Conf::$DB_PASSWORD );
		return $Conn;
	}
	
	/**
	 * This method deals with the logic of research in the mysql db based on the configurations set.
	 * @param PDO $dbConn
	 * @param string $searchKey
	 * 
	 * P.S. once finished writing the method, I noticed that it's a bit redundant, 
	 * i can improve the readability of the code and therefore the performance. 
	 * will be the subject of future developments. ;)
	 */
	protected static function getDBSearchResult($dbConn, $searchKey) {
		$RESULT = null;
		//First query step
		if (Conf::$SEARCH_WHIT_LIKE) {
			//i need an array of table and column\s
			if (is_array ( Conf::$DB_SEARCH_TABLES )) {
				$bind = array ();
				$cnt = 0;
				$resNum = 0;
				//Creation of prepared query string and realtive bind array
				foreach ( Conf::$DB_SEARCH_TABLES as $table ) {
					$q [$cnt] = "SELECT * FROM {$table['T_name']} where";
					//Cicling for columns
					foreach ( $table ['T_search_col'] as $column ) {
						$q [$cnt] .= " $column LIKE :$column";
						$bind [$cnt] [':' . $column] = "%" . $searchKey . "%";
						if ($column !== end ( $table ['T_search_col'] ))
							$q [$cnt] .= " OR ";
					}
					$cnt ++;
				}
				//Run all the query prepared
				foreach ( $q as $key => $query ) {
					$dbConn->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
					$sth = $dbConn->prepare ( $query );
					$sth->execute ( $bind [$key] );
					$resNum += $sth->rowCount ();
					$res [Conf::$DB_SEARCH_TABLES [$key] ['T_name']] = $sth->fetchAll ( PDO::FETCH_ASSOC );
				}
			}
			//Populate the result array
			$RESULT ['OUT_LIKE'] = $res;
			$RESULT ['OUT_LIKE'] ['num_of_total_rows'] = $resNum;
		} // END SEARCH WITH LIKE
		

		//Second query that will be executed when the first step did not return
		if (Conf::$SEARCH_WHIT_SOUNDEX && $RESULT ['OUT_LIKE'] ['num_of_total_rows'] <= 0) {
			if (is_array ( Conf::$DB_SEARCH_TABLES )) {
				$bind = array ();
				$cnt = 0;
				$resNum = 0;
				//Creation of prepared query string and realtive bind array
				foreach ( Conf::$DB_SEARCH_TABLES as $table ) {
					$q [$cnt] = "SELECT * FROM {$table['T_name']} where";
					//Cicling for columns
					foreach ( $table ['T_search_col'] as $column ) {
						$q [$cnt] .= " $column SOUNDS LIKE :$column";
						$bind [$cnt] [':' . $column] = "%" . $searchKey . "%";
						if ($column !== end ( $table ['T_search_col'] ))
							$q [$cnt] .= " OR ";
					}
					$cnt ++;
				}
				//Run all the query prepared
				foreach ( $q as $key => $query ) {
					$dbConn->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
					$sth = $dbConn->prepare ( $query );
					$sth->execute ( $bind [$key] );
					$resNum += $sth->rowCount ();
					$res [Conf::$DB_SEARCH_TABLES [$key] ['T_name']] = $sth->fetchAll ( PDO::FETCH_ASSOC );
				}
			}
			//Populate the result array
			$RESULT ['OUT_SOUNDEX'] = $res;
			$RESULT ['OUT_SOUNDEX'] ['num_of_total_rows'] = $resNum;
		
		} // END SEARCH WITH SOUNDEX
		

		//Third query that will be executed when the first and/or second step did not return
		if (Conf::$SEARCH_WHIT_LEVENSHTEIN && $RESULT ['OUT_LIKE'] ['num_of_total_rows'] <= 0 && $RESULT ['OUT_SOUNDEX'] ['num_of_total_rows'] <= 0) {
			if (is_array ( Conf::$DB_SEARCH_TABLES )) {
				$bind = array ();
				$cnt = 0;
				$resNum = 0;
				//Creation of prepared query string and realtive bind array
				foreach ( Conf::$DB_SEARCH_TABLES as $table ) {
					$q [$cnt] = "SELECT * FROM {$table['T_name']} where";
					//Cicling for columns
					foreach ( $table ['T_search_col'] as $column ) {
						$q [$cnt] .= " levenshtein(:$column, $column) BETWEEN 0 AND " . Conf::$MAX_LEVENSHTEIN_DISTANCE . " ";
						$bind [$cnt] [':' . $column] = "%" . $searchKey . "%";
						if ($column !== end ( $table ['T_search_col'] ))
							$q [$cnt] .= " OR ";
					}
					$cnt ++;
				}
				//Run all the query prepared
				foreach ( $q as $key => $query ) {
					$dbConn->setAttribute ( PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING );
					$sth = $dbConn->prepare ( $query );
					$sth->execute ( $bind [$key] );
					$resNum += $sth->rowCount ();
					$res [Conf::$DB_SEARCH_TABLES [$key] ['T_name']] = $sth->fetchAll ( PDO::FETCH_ASSOC );
				}
			}
			//Populate the result array
			$RESULT ['OUT_LEVENSHTEIN'] = $res;
			$RESULT ['OUT_LEVENSHTEIN'] ['num_of_total_rows'] = $resNum;
		
		} // END SEARCH WITH LEVENSHTEIN
		return $RESULT;
	}
	
	protected static function getRemoteXmlFile() {
		$url = Conf::$XML_URL;
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		$data = curl_exec ( $ch );
		curl_close ( $ch );
		
		$xml = simplexml_load_string ( $data );
		return $xml->asXML ();
	}
	
	protected static function getXMLFile() {
		$url = Conf::$XML_LOCATION . Conf::$XML_FILENAME;
		$xml = simplexml_load_file ( $url );
		return $xml->asXML ();
	}
	
	protected static function getXMLSearchResult($keySearch, $XML) {
		$RESULT = array ();
		$nResult = 0;
		
		$doc = new DOMDocument ();
		$doc->loadXML ( $XML );
		$elements = $doc->getElementsByTagName ( Conf::$XML_ELEMENT_NAME );
		
		foreach ( $elements as $element ) {
			if (Conf::$SEARCH_WHIT_LIKE) {
				if (self::isLikeThat ( strtolower ( $element->nodeValue ), strtolower ( trim ( $keySearch ) ) )) {
					$nResult ++;
					$RESULT ['OUT_LIKE'] [] = $element;
					$RESULT ['OUT_LIKE'] ['num_of_total_rows'] = $nResult;
				}
			}
			if (Conf::$SEARCH_WHIT_SOUNDEX && ! isset ( $RESULT ['OUT_LIKE'] ['num_of_total_rows'] )) {
				if (self::isSoundLikeThat ( strtolower ( $element->nodeValue ), strtolower ( trim ( $keySearch ) ) )) {
					$nResult ++;
					$RESULT ['OUT_SOUNDEX'] [] = $element;
					$RESULT ['OUT_SOUNDEX'] ['num_of_total_rows'] = $nResult;
				}
			}
			if (Conf::$SEARCH_WHIT_LEVENSHTEIN && ! isset ( $RESULT ['OUT_LIKE'] ['num_of_total_rows'] ) && ! isset ( $RESULT ['OUT_SOUNDEX'] ['num_of_total_rows'] )) {
				if (self::isLevenshteinLikeThat ( strtolower ( $element->nodeValue ), strtolower ( trim ( $keySearch ) ) ) <= Conf::$MAX_LEVENSHTEIN_DISTANCE) {
					$nResult ++;
					$RESULT ['OUT_LEVENSHTEIN'] [] = $element;
					$RESULT ['OUT_LEVENSHTEIN'] ['num_of_total_rows'] = $nResult;
				}
			}
		}
		return $RESULT;
	}
	
	protected static function getRemoteFile($url) {
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		$data = curl_exec ( $ch );
		curl_close ( $ch );
		return $data;
	}
	
	protected static function getFile($path) {
		return file_get_contents ( $path );
	}
	
	protected static function getIniSearchResult($keySearch, $INI) {
		$arrayData = parse_ini_string ( $INI );
		$RESULT = array ();
		$nResult = 0;
		
		foreach ( $arrayData as $value ) {
			if (Conf::$SEARCH_WHIT_LIKE) {
				if (self::isLikeThat ( strtolower ( $value ), strtolower ( trim ( $keySearch ) ) )) {
					$nResult ++;
					$RESULT ['OUT_LIKE'] [] = $value;
					$RESULT ['OUT_LIKE'] ['num_of_total_rows'] = $nResult;
				}
			}
			if (Conf::$SEARCH_WHIT_SOUNDEX && ! isset ( $RESULT ['OUT_LIKE'] ['num_of_total_rows'] )) {
				if (self::isSoundLikeThat ( strtolower ( $value ), strtolower ( trim ( $keySearch ) ) )) {
					$nResult ++;
					$RESULT ['OUT_SOUNDEX'] [] = $value;
					$RESULT ['OUT_SOUNDEX'] ['num_of_total_rows'] = $nResult;
				}
			}
			if (Conf::$SEARCH_WHIT_LEVENSHTEIN && ! isset ( $RESULT ['OUT_LIKE'] ['num_of_total_rows'] ) && ! isset ( $RESULT ['OUT_SOUNDEX'] ['num_of_total_rows'] )) {
				if (self::isLevenshteinLikeThat ( strtolower ( $value ), strtolower ( trim ( $keySearch ) ) ) <= Conf::$MAX_LEVENSHTEIN_DISTANCE) {
					$nResult ++;
					$RESULT ['OUT_LEVENSHTEIN'] [] = $value;
					$RESULT ['OUT_LEVENSHTEIN'] ['num_of_total_rows'] = $nResult;
				}
			}
		}
		return $RESULT;
	}
	
	protected static function getFlatSearchResult($keySearch, $FLAT) {
		$arrayData = explode ( Conf::$FLAT_SEPARATOR, $FLAT );
		
		$RESULT = array ();
		$nResult = 0;
		
		foreach ( $arrayData as $value ) {
			if (Conf::$SEARCH_WHIT_LIKE) {
				if (self::isLikeThat ( strtolower ( $value ), strtolower ( trim ( $keySearch ) ) )) {
					$nResult ++;
					$RESULT ['OUT_LIKE'] [] = $value;
					$RESULT ['OUT_LIKE'] ['num_of_total_rows'] = $nResult;
				}
			}
			if (Conf::$SEARCH_WHIT_SOUNDEX && ! isset ( $RESULT ['OUT_LIKE'] ['num_of_total_rows'] )) {
				if (self::isSoundLikeThat ( strtolower ( $value ), strtolower ( trim ( $keySearch ) ) )) {
					$nResult ++;
					$RESULT ['OUT_SOUNDEX'] [] = $value;
					$RESULT ['OUT_SOUNDEX'] ['num_of_total_rows'] = $nResult;
				}
			}
			if (Conf::$SEARCH_WHIT_LEVENSHTEIN && ! isset ( $RESULT ['OUT_LIKE'] ['num_of_total_rows'] ) && ! isset ( $RESULT ['OUT_SOUNDEX'] ['num_of_total_rows'] )) {
				if (self::isLevenshteinLikeThat ( strtolower ( $value ), strtolower ( trim ( $keySearch ) ) ) <= Conf::$MAX_LEVENSHTEIN_DISTANCE) {
					$nResult ++;
					$RESULT ['OUT_LEVENSHTEIN'] [] = $value;
					$RESULT ['OUT_LEVENSHTEIN'] ['num_of_total_rows'] = $nResult;
				}
			}
		}
		return $RESULT;
	}
	
	protected static function isLikeThat($string, $search) {
		return strpos ( $string, $search ) !== false;
	}
	protected static function isSoundLikeThat($string, $search) {
		return soundex ( $string ) == soundex ( $search );
	}
	protected static function isLevenshteinLikeThat($string, $search) {
		return levenshtein ( $string, $search );
	}
}