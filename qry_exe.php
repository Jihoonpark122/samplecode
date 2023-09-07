<?php 
require_once("../define.php");
require_once("QueryManager.php");
header("Content-Type:text/html; charset=utf-8");
?>

<?php
if(isset($_REQUEST)){
	$qm = new QueryManager();
	$postedArray = $_REQUEST;

	if(array_key_exists("alert",$postedArray)){
		$alert = $postedArray[alert];
		unset($postedArray[alert]);
		$alert = trim(stripslashes($alert));
		$alert = ( $alert ) ? "alert('$alert');" : NULL ;
	}
	if(array_key_exists("movepage",$postedArray)){
		$movepage = trim( $postedArray[movepage] );
		unset($postedArray[movepage]);
		switch( $movepage ) {
			case "":
				$movepage = NULL ;
				break;
			case "self":
				$movepage = "top.location.reload(true);" ;
				break;
			case "close":
				$movepage = "top.window.close();" ;
				break;
			default:
				$movepage = "top.location.href='$movepage';" ;
				break;
		}
	}

	if($postedArray[multirec] == "1"){
		unset($postedArray[multirec]);
		unset($postedArray[x]);
		unset($postedArray[y]);
		unset($postedArray[PHPSESSID]);
		foreach($postedArray AS $key => $array){
			unset($postedArray[list_check]);
			$result_ok = $qm->runQry($array);
			unset($postedArray[$key]);
		}
	}else{
		$array = &$postedArray ;
		$result_ok = $qm->runQry($array);
	}
	
	if($result_ok){
		echo "<SCRIPT LANGUAGE=\"JavaScript\">" . $alert .  $movepage . "</SCRIPT>";
	}else{
		echo "<SCRIPT LANGUAGE=\"JavaScript\">
					//alert('장애로 인해 정상처리 되지 못했습니다.');
				</SCRIPT>";
	}
}
?>
