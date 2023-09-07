<?php 
require_once("../define.php");
require_once("db.php");
require_once("commonlib.php");

class QueryManager
{

public function makeHtml($array)	// 배열로 테이블을 만들어준다.
{
	if(!is_array($array)) return;
	$html .= "
			<html>
			<head>
			<title></title>
			<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">
			<meta name=\"robots\" content=\"noindex, nofollow\">
			</head>
			<body>";
	$htmlz .= "
			ALKI's All Posted Data Magement Manager. <p>
			This page lists all data posted by the form. <BR>
			꼭!!! FieldName을 DB상의 filed명과 같게 마쳐서 post할것 !!! (아니면 처리안됨.)
			<hr>";
	$html .= "<table border=\"1\" cellspacing=\"0\">
					<tr>
						<th> Field Name </th>
						<th> Value </th>
					</tr>";
	
	foreach ( $array as $key => $value ){
		if ( get_magic_quotes_gpc() ){
			$value = htmlspecialchars( stripslashes( $value ) ) ;
		}else{
			$value = htmlspecialchars( $value ) ;
		}
		$html .= "	
				<tr>
					<td> " . $key . " </td>
					<td> " . $value . " </td>
				</tr>";
	}
	$html .= "</table>
				</body>
			</html>";
	
	return $html;
}


public function checkTableValidate($tbl,$array,$qrytype)	//포스트된 값들이 DB.TABLE 의 NOTNULL 필드와 모두 매치가 되는지 확인한다.
{
	if(!is_array($array)) return;
	$db = new Query;
	$qry = "DESCRIBE $tbl";
	$recordsets = $db->runQuery2Str($qry);		# 레코드셋을 2차원배열로 받기

	foreach($array AS $key => $value){
		$isfldname = false;
		for($i=0 ; $i<COUNT($recordsets) ; $i++){
			if($recordsets[$i][Field] == $key){
				$isfldname = true;
				break;
			}
		}
		if(!$isfldname)	unset($array[$key]);		# 테이블의 필드명과 같지 않는 것들 삭제.
	}
		
	if($qrytype=="insert"){
		for($i=0 ; $i<COUNT($recordsets) ; $i++){
				if($recordsets[$i]['Null']=='NO' && $recordsets[$i]['Default']==NULL && $recordsets[$i]['Extra']!='auto_increment'){
					
					//if(!array_key_exists($recordsets[$i]['Field'],$array)){
					if($array[$recordsets[$i]['Field']]==""){
						// 포스트된값중 필수 입력 사항을 안넣었을때 처리하는 부분
						echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><script>alert('" . $recordsets[$i]['Field'] . " 는 필수 입력사항입니다.');</script>";
						break;
					}
				}
		}
	}
	return $array;
}

public function runQry($array)
{
	if(!is_array($array)) return;
	$db = new Query;
	$cm = new CommonLib;

	$qrytype = $array[qrytype];
	$table = $array[table];

	for($i=0 ; $i<count($array[where]) ; $i++){
		if($array[where][$i]==NULL){
			$array[where][$i] = 0 ;
		}
	}
	$where = is_array($array[where]) ? implode(" OR ",$array[where]) : $array[where] ;
	$where = stripslashes($where);

	// 전화번호,팩스,이메일 합치기
	foreach ( $array as $key => $value )
	{
		if(is_array($array[$key])){
			if(!(strpos($key,"phone")===FALSE) OR !(strpos($key,"fax")===FALSE)){
				$array[$key] = implode("-",$array[$key]);
			}elseif(!(strpos($key,"email")===FALSE)){
				$array[$key] = implode("@",$array[$key]);
			}
		}
	}

	// 고유 아이디 테이블에 따라 자동 만들기
	// 필드이름='makeid', 값='p_id' 등으로 오면 p1234567890123456 의 고유 아이디를 자동생성후
	// 필드이름='p_id', 값='p1234567890123456' 으로 변경한다.
	// 필드이름='makesession', 값='p_id' 등으로 오면 $_SESSION[p_id] = 'p1234567890123456' 의 세션생성
	if(array_key_exists("makeid",$array)){
		$fld = $array[makeid];
		$temp = explode("_",$fld);
		$timeid = microtime();
		$id = $temp[0] . substr($timeid,11,10) . substr($timeid,2,6);
		$array[$fld] = $id;

		if(array_key_exists("makesession",$array)){
			if($array[makesession]!=""){
				$fld = $array[makesession];
				$_SESSION[$fld] = $id ;
			}
		}
	}
	
	//암호화 비밀번호 저장
	if(array_key_exists("m_pw",$array)){
		if($qrytype=="insert"){
			// 새로운 해시된 패스워드 생성
			$new_hashed_password = password_hash($array["m_pw"], PASSWORD_DEFAULT);
			$array['hashed_password'] = $new_hashed_password;
		}
	}
	
	//원고료 , 지우기
	if(array_key_exists("p_cost",$array)){
		$array['p_cost'] = str_replace(',', '', $array["p_cost"]);
	}
	
	if(array_key_exists("makedate",$array)){
		$fld = $array[makedate];
		$array[$fld] = date("Y-m-d H:i:s");
	}
	
	if(array_key_exists("maketimestamp",$array)){
		$fld = $array[maketimestamp];
		$array[$fld] = time();
	}
	if(array_key_exists("concat",$array)){
		$concat = $array[concat];
	}
	if(array_key_exists("fileupload",$array)){
		$fld = $array["fileupload"] ;
		$save_dir = $array["filedir"] ;
		//echo $fld ;
		//echo $_FILES[$fld]["tmp_name"] ;
		$imgfile = $_FILES[$fld]["tmp_name"] ;
		
		if( is_uploaded_file($imgfile) ) {
			if( !file_exists($save_dir) ) {
				mkdir( $save_dir, 0777 ) ;
			}

			echo $array["filename"] . "<BR>" ; 
			
			if( $array["filename"]==$array["makeid"] ) {
				$temp = $array["filename"] ;
				$filename_left = $array[$temp] ;
			}else {
				$temp = explode( "_" , $array["filename"] );
				$timeid = microtime();
				$filename_left = $temp[0] . substr($timeid,11,10) . substr($timeid,2,6);
				//$filename = $_FILES[$fld]["name"] ;
			}
			$temp = explode( "." , $_FILES[$fld]["name"] ) ;
			$temp_cnt = count( $temp ) - 1 ;
			$filename_right = $temp[$temp_cnt] ;

			$filename = $filename_left . "." . $filename_right ;

			echo $filename . "<BR>" ; 

			$destination = $save_dir . "/" . $filename ;
			//$destination = iconv( "utf-8","euc-kr",$destination ) ;
			if( file_exists($destination) ) {
				$org_destination = $destination ;
				$temp = explode( "." , $org_destination ) ;
				$temp_cnt = count( $temp ) - 1 ;
				$rfn = $temp[$temp_cnt] ;
				unset( $temp[$temp_cnt] ) ;
				$lfn = implode( "." , $temp ) ;

				for( $i=1 ; file_exists($destination) ; $i++ ) {
					$destination = $lfn . "[" . $i . "]." . $rfn ;
				}
			}

			move_uploaded_file( $imgfile, $destination ) ;
			//$destination = iconv( "euc-kr","utf-8",$destination ) ;
			$array[$fld] =  $destination ;	// 저장될 파일경로/파일이름
		
			if(array_key_exists("fileresizefld",$array)){
				$fld = $array["fileresizefld"] ;
				$maxsize = $array["fileresizeto"] ;

				$temp = explode( "." , $destination ) ;
				$temp_cnt = count( $temp ) - 1 ;
				$rfn = $temp[$temp_cnt] ;
				unset( $temp[$temp_cnt] ) ;
				$lfn = implode( "." , $temp ) ;

				$resizefilename = $lfn . "_" . strval($maxsize) . "." . $rfn ;
				$cm->createThumb( $destination , $resizefilename , $maxsize ) ;
				$array[$fld] = $resizefilename ;
			}		
		}
	}

	
	$array = $this->checkTableValidate($table,$array,$qrytype);

	echo $this->makeHtml($array);

	// DB 처리부분
	if($qrytype=="update"){
		$db->update($array, $table, $where);
	}elseif($qrytype=="update_concat"){
		$db->update_concat($array, $table, $where, $concat);
	}elseif($qrytype=="insert"){
		$db->insert($array, $table);
	}elseif($qrytype=="delete"){
		$db->delete($table, $where);
	}

	return true;
}

}	// end of class
?>
