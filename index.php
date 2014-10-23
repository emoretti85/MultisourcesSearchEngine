<?php
require_once("SearchCore/SearchConfig.php");
require_once("SearchCore/Search.class.php");
if(isset($_POST['searchKey']) && trim($_POST['searchKey'])!='' && preg_replace("/[^0-9a-z]/","",$_POST['searchKey'])!='')
		$out= Search::getDBSearch(preg_replace("/[^0-9a-z]/","",$_POST['searchKey']));
		//$out= Search::getXMLSearch(preg_replace("/[^0-9a-zA-Z]/","",$_POST['searchKey']));
		//$out= Search::getINISearch(preg_replace("/[^0-9a-z]/","",$_POST['searchKey']));
		//$out= Search::getFLATSearch(preg_replace("/[^0-9a-z]/","",$_POST['searchKey']));
	
?>
<html>
<body>

<form action="#" method="post">

<span>Search (stuff):</span>
<input type="text" name="searchKey" value="" placeholder="Search key here"/>
<input type="submit" name="sub" value"Search that" /> 
</form>


<div class="result_container">
<?php if(isset($out)){
//Now you can format the output as you wish :)
echo "<pre>";
print_r($out);
}?>
</div>
</body>
</html>