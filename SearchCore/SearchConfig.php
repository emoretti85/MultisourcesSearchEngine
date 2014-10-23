<?php
/**
 * This class exposes the configurations for the use of the class Search ()
 *
 *
 *[IT]
 * Tutte le varie configurazioni sono descritte da dei commenti.
 * P.S. le main configuration sono relative a tutte le fonti ad eccezione di mysql, 
 * per quest'ultimo sono state utilizzate le funzioni native del db o delle funzioni appositamente create. v
 * (vedi il file levenshtein_function.sql). 
 *
 * 
 *[EN - translated with google]
 * All of the various configurations are described by the comments. 
 * P.S. the main configuration are relative to all sources except mysql, 
 * for the latter have been used native functions of the db or functions specially created 
 * (you see the file levenshtein_function.sql).
 *
 * @category   SearchEngine
 * @author     Ettore Moretti <ettoremoretti27@gmail.com>
 * @copyright  2014 Ettore Moretti
 * @license    MIT License (MIT)
 * @version    Release: 1.0
 */
class Conf {
	/**
	 * Main configuration
	 */
		//If set to true this will be the first search strategy, 
		//if it does not recover anything, the cascade will be questioned later (if set to true)					  
		static $SEARCH_WHIT_LIKE = true;
		//Second search strategy if the precedent does not recover anything
		static $SEARCH_WHIT_SOUNDEX = true;
		//Third search strategy if the precedent does not recover anything
		static $SEARCH_WHIT_LEVENSHTEIN = true;
		static $MAX_LEVENSHTEIN_DISTANCE = 3;
		
	/**
	 * These configurations are related to a search in mysql db
	 */
		static $DB_HOST = "localhost";
		static $DB_PORT = "";
		static $DB_USERNAME = "root";
		static $DB_PASSWORD = "";
		static $DB_NAME = "searchdb";
		//You can search in multiple tables and columns in several of these
		static $DB_SEARCH_TABLES = array (
										array ("T_name" => "table1", 
												"T_search_col" => array (0 => "Searchable", 
																		 1 => "Other_Searchable" ) ), 
										array ("T_name" => "table2", 
												"T_search_col" => array (0 => "Searchable", 
																		 1 => "Other_Searchable" ) ) );
	
	/**
	 * These configurations are related to a search in xml file
	 */
	//If the xml file is not on the same server it will recover by the CURL functions
		static $XML_IN_SERVER = true;
		//if not in server populate this conf
		static $XML_URL = "https://<your_repo>/<file_name>";
		//if in server populate this conf
		static $XML_LOCATION = "FILE/";
		static $XML_FILENAME = "example.xml";
		//name of the element 
		static $XML_ELEMENT_NAME = "item";
	
	/**
	 * This is the configuration for search in .ini "database"
	 */
	//If the ini file are in the same server insert true, else false
		static $INI_IN_SERVER = true;
		//if not in server populate this conf
		static $INI_URL = "https://<your_repo>/<file_name>";
		//if in server populate this conf
		static $INI_LOCATION = "FILE/";
		static $INI_FILENAME = "example.ini";
	
	/**
	 * This is the configuration for search in a flat file dictionary
	 */
	//If the flat file are in the same server insert true, else false
		static $FLAT_IN_SERVER = true;
		//if not in server populate this conf
		static $FLAT_URL = "https://<your_repo>/<file_name>";
		//if in server populate this conf
		static $FLAT_LOCATION = "FILE/";
		static $FLAT_FILENAME = "example.txt";
		//The separator data used
		static $FLAT_SEPARATOR = "~";
}
 
