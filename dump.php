<?php
header('Content-Type: text/html; charset=UTF-8');
require_once('util/Conexao.php');

$con = Conexao::getInstance()->getConexao();

date_default_timezone_set("America/Manaus");


if(!$_REQUEST){ $_REQUEST =  file_get_contents ( "php://input" ); }
$_REQUEST = json_decode ($_REQUEST, true);


switch ( $_REQUEST['metodo'] ) {
	case 'listarLogs':
		listarLogs( $con );
		break;
	case 'loadConfig':
		loadConfig( $con );
		break;
	case 'salvaConfig':
		salvaConfig( $con );
		break;
	case 'delete':
		delete( $con );
		break;
	case 'init':
		init( $con );
		break;
}


function init ( $con ) {
	$sql = "SELECT * FROM backupconf";
	$result = mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
	$response = "";
	if ( $row = mysqli_fetch_object( $result ) ) {
		if ( $row->ativo == "SIM" ) {
			$periodo = verificaIntervalo ( $con, $row );

			if ( $periodo ) { 
				$dump = geraDumpDB( $con );
				
				// $respmail = enviaEmail( $dump, $row->emails, $periodo );
				
				$dump = substr( $dump , 7);
				$periodo = utf8_encode( $periodo );
				$sql = "INSERT INTO backuplog(arquivo, emails, periodo) VALUES( '" .$dump. ".sql', '".$row->emails."', '".$periodo."' )";

				mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
				
				echo json_encode( array( "success"=>true, "msg"=>"O Backup foi realizado com sucesso!" ) );
			}else{
				echo json_encode( array( "success"=>false, "msg"=>"O ultimo backup ainda e valido segundo o intervalo configurado!" ) );
			}
		} else {
			echo json_encode( array( "success"=>false, "msg"=>"Sistema de Backup desativado!" ) );
		}
	}
}

function loadConfig ( $con ) {
	$sql = "SELECT * FROM backupconf";
	$result = mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
	$response = "";
	if ( $row = mysqli_fetch_object( $result ) ) {
		echo json_encode( $row );
	}
}

function salvaConfig ( $con ) {
	$data = $_REQUEST['data'];
	if ( empty($data['id']) ) {
		$sql = "INSERT INTO backupconf (intervalo, unidadeintervalo, emails, ativo) values(".$data['intervalo'].", '".$data['unidadeintervalo']."', '".$data['emails']."', '".$data['ativo']."')";
	}else{
		$sql = "UPDATE backupconf SET intervalo = ".$data['intervalo'].", unidadeintervalo = '".$data['unidadeintervalo']."', emails = '".$data['emails']."', ativo = '".$data['ativo']."'";
	}
	mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );

	echo json_encode( array( "success"=>false, "msg"=>"Atualizado com successo!" ) );
}

function delete ( $con ) {
	$data = $_REQUEST['data'];

	if ( unlink( 'backup/' . $data['arquivo'] ) ) {
		$sql = "DELETE FROM backuplog WHERE id = ".$data['id'];
		mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
		echo json_encode( array( "success"=>false, "msg"=>"Backup deletado com successo!" ) );
	}
}

function listarLogs ( $con ) {
	$sql = "SELECT * FROM backuplog";
	$result = mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
	$lista = array();
	while ( $row = mysqli_fetch_object( $result ) ) {
		array_push( $lista, $row );
	}
	echo json_encode( $lista );
}

function verificaIntervalo ( $con, $conf ) {
	$sql = "SELECT * FROM backuplog ORDER BY id DESC LIMIT 1";
	$result = mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
	$response = false;
	if ( $row = mysqli_fetch_object( $result ) ) {
		$now = new DateTime( date('Y-m-d H:i:s') );
		$databack = new DateTime( $row->datacadastro );
		$intervalo = $now->diff( $databack );

		switch ( $conf->unidadeintervalo ) {
			case 'SEGUNDO':
				if ( 	$intervalo->y > 0 || 
						$intervalo->m > 0 || 
						$intervalo->d > 0 || 
						$intervalo->h > 0 || 
						$intervalo->i > 0 || 
						$intervalo->s >= $conf->intervalo
					) $response = true;
				break;
			
			case 'MINUTO':
				if ( 	$intervalo->y > 0 || 
						$intervalo->m > 0 || 
						$intervalo->d > 0 || 
						$intervalo->h > 0 || 
						$intervalo->i >= $conf->intervalo 
					) $response = true;
				break;

			case 'HORA':
				if ( 	$intervalo->y > 0 || 
						$intervalo->m > 0 || 
						$intervalo->d > 0 || 
						$intervalo->h >= $conf->intervalo
					) $response = true;
				break;

			case 'DIA':
				if ( 	$intervalo->y > 0 || 
						$intervalo->m > 0 || 
						$intervalo->d >= $conf->intervalo
					) $response = true;
				break;

			case 'SEMANA':
				if ( 	$intervalo->y > 0 || 
						$intervalo->m > 0 || 
						($intervalo->d/7) >= $conf->intervalo
					) $response = true;
				break;

			case 'MES':
				if ( $intervalo->y > 0 || $intervalo->m >=  $conf->intervalo ) $response = true;
				break;

			case 'ANO':
				if ( $intervalo->y >=  $conf->intervalo ) $response = true;
				break;
		}
		if ( $response ) return "Período desde o último Backup: " . $intervalo->y . " ano(s) " . $intervalo->m . " mes(ses) " . ($intervalo->d/7) . " semana(s) " . $intervalo->d . " dia(s) " . $intervalo->h . " hora(s) " . $intervalo->i . " minuto(s) " . $intervalo->s . " segundo(s)";
	}else{
		return "Primeiro backup";
	}
	return $response;
}

function geraDumpDB ( $con ) {

	$dbname = "";
    if ($resultado = mysqli_query($con, "SELECT DATABASE()")) {
        $dbname = mysqli_fetch_row($resultado)[0];
    }
    
	// gerando um arquivo sql. Como?
	// a função fopen, abre um arquivo, que no meu caso, será chamado como: nomedobanco.sql
	// note que eu estou concatenando dinamicamente o nome do banco com a extensão .sql.
   	
   	
   	$dir = "backup"; // diretorio
   	if(!file_exists( $dir )) mkdir( $dir ); // cria caso não exista
   	$dump = $dir . "/" . $dbname . "_" . date('d-m-Y_H-i-s');
   	$back = fopen($dump . ".sql","w"); // abre cria arquivo

	$instrucao = "-- --------------------------------------------------------\n";
	// $instrucao .= "-- Servidor:             $host\n";
	$instrucao .= "-- PHP BackUp:             	 	1.0\n";
	$instrucao .= "-- Formato SGB: 					HeidSQL\n";
	$instrucao .= "-- --------------------------------------------------------\n\n\n";

	$instrucao .= "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
	$instrucao .= "/*!40101 SET NAMES utf8 */;\n";
	$instrucao .= "/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;\n";
	$instrucao .= "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;\n\n";


	$instrucao .= "-- Copiando estrutura do banco de dados para sgaf\n";
	$instrucao .= "CREATE DATABASE IF NOT EXISTS `$dbname` /*!40100 DEFAULT CHARACTER SET utf8 */;\n";
	$instrucao .= "USE `$dbname`;\n\n\n";

	fwrite( $back, $instrucao );

	// aqui, listo todas as tabelas daquele banco selecionado acima
	   // $res = mysql_list_tables($dbname) or die(mysql_error());
	$res = mysqli_query( $con, "SHOW TABLES" ) or die( mysqli_error( $con ) );

	//Em seguida, vamos, verificar quais são as tabelas daquela base, lista-las, e em um laço for, vamos mostrar cada uma delas, e resgatar as funções descriação da tabela, para serem gravadas no arquivo sql mais adiante.

	// resgato cada uma das tabelas, num loop
	while ($row = mysqli_fetch_row($res)) {
		$table = $row[0]; 
		
		// pegando o sql de criação da tabela direto do banco
		$res2 = mysqli_query( $con, "SHOW CREATE TABLE $table" ) or die( mysqli_error( $con ) );
		
		// montando create
	  	while ( $lin = mysqli_fetch_row($res2)) { 

	  		// ignorando Storeds e Procedures
			if ( strrpos( $lin[1], "ALGORITHM" ) ) {

			}else{
				
				// informações do arquivo
				fwrite($back,"\n#\n# Criação da Tabela : $table\n#\n\n");
				fwrite($back,"$lin[1] ;\n\n#\n# Dados a serem incluídos na tabela\n#\n\n");

				// cirando os inserts
				$res3 = mysqli_query( $con, "SELECT * FROM $table" ) or die( mysqli_error( $con ) );
			    while($r=mysqli_fetch_row($res3)){ 
					$sql="INSERT INTO $table VALUES (";

					// montandos os inserts com os dados do banco
				 	for($j=0; $j<mysqli_num_fields( $res3 );$j++) {
						if(!isset($r[$j]))
							$sql .= " '',";
						elseif($r[$j] != "")
						
						    $sql .= " '".addslashes(utf8_decode($r[$j]))."',";
						else
						  	$sql .= " '',";
					}
					$sql = @ereg_replace(",$", "", $sql);
					$sql .= ");\n";

				   	fwrite( $back, $sql) ;
			    }

			}
	   	}
	}

	$sql  = "\n\n\n";
	$sql .= "##########################################################################################\n";
	$sql .= "#\n";
	$sql .= "####											STORED PROCEDURES\n";
	$sql .= "#\n";
	$sql .= "##########################################################################################\n";
	$sql .= "\n\n\n";

	// gerando as Stored Procedures
	$sql .= GetCreateProcedure( $con );
	fwrite( $back, $sql);
	
	$sql  = "\n\n\n";
	$sql .= "##########################################################################################\n";
	$sql .= "#\n";
	$sql .= "####											VIEWS\n";
	$sql .= "#\n";
	$sql .= "##########################################################################################\n";
	$sql .= "\n\n\n";

	$sql .= getCreateViews( $con );

	fwrite( $back, $sql);


	$instrucao 	= "\n";
	$instrucao .= "/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;\n";
	$instrucao .= "/*!40014 SET FOREIGN_KEY_CHECKS=IF(@OLD_FOREIGN_KEY_CHECKS IS NULL, 1, @OLD_FOREIGN_KEY_CHECKS) */;\n";
	$instrucao .= "/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;\n";

	fwrite( $back, $instrucao ) ;

	// fechar o arquivo que foi gravado
	fclose( $back) ;

	return $dump;
}

function GetCreateProcedure ( $con ) {

	$dbname = "";
    if ($resultado = mysqli_query($con, "SELECT DATABASE()")) {
        $dbname = mysqli_fetch_row($resultado)[0];
    }

	$sql = "SHOW PROCEDURE STATUS";
	$result = mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
	$response = "";

	while($row=mysqli_fetch_row($result)){ 
		$sql = "SHOW CREATE PROCEDURE $row[1]";
		$result2 = mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
		if ( $row2 = mysqli_fetch_row( $result2 ) ) {

			$response .= "-- Copiando estrutura para procedure $dbname.$row2[0]\n";
			$response .= "DELIMITER //\n";
				
			$response .= $row2[2] . "//\n";

			$response .= "DELIMITER ;\n\n";	
		} 
	}
	return $response;
}

function getCreateViews ( $con ) {

	$dbname = "";
    if ($resultado = mysqli_query($con, "SELECT DATABASE()")) {
        $dbname = mysqli_fetch_row($resultado)[0];
    }
	
	$sql = "SELECT * FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = '$dbname'";
	$result = mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
	$response = "";

	while ( $row = mysqli_fetch_row( $result ) ) { 
		$sql = "SHOW CREATE VIEW $row[2]";
		$result2 = mysqli_query( $con, $sql ) or die( mysqli_error( $con ) );
		if ( $row2 = mysqli_fetch_row( $result2 ) ) {

			$response .= "-- Copiando estrutura para view $dbname.$row2[0]\n";
			$response .= $row2[1] . ";\n";
		
		} 
	}
	return $response;
} 

function enviaEmail( $dump, $emails, $periodo ) {

	require_once 'util/phpmailer/PHPMailerAutoload.php';

	if ( empty($emails) ) return false; // se não haver emails
	$emails = explode( ',', $emails ); // separando os emails

    $anexoSize = filesize( $dump. ".sql" );

    $dataAtual = date("d/m/Y H:i:s");// REBECEMOS O DIA ATUAL

	$usuario = 'sistema@agmtiservicos.com.br';

	$senha = 'agm@ti754';

	/*abaixo as veriaveis principais, que devem conter em seu formulario*/
	$nomeRemetente = 'Solverp Gerencial';
	$Subject = 'Backup ' . $dataAtual;
	$Message  = "Backup gerado em " . date('d/m/Y') . " às " . date('H:i:s'); // data de geração
	$Message .= "<br>Tamanho: " . $anexoSize . " bytes"; // tamanho
	$Message .= "<br><br>" . $periodo;
	
	if ( $anexoSize >= 10485760 ) { // caso exceda o limite 10MB
		$Message .= "<br><br><font color='red'>O Arquivo de Backup SQL excede o limite para ser enviado por e-mail.</font>";
	}

	$Host = 'smtp.'.substr(strstr($usuario, '@'), 1);
	$Username = $usuario;
	$Password = $senha;
	$Port = "587";

	unset($mail);
	$mail = new PHPMailer();
	$body = $Message;
	$mail->IsSMTP(); // telling the class to use SMTP
	$mail->Host = $Host; // SMTP server
	$mail->SMTPDebug = 0; // enables SMTP debug information (for testing)
	// 1 = errors and messages
	// 2 = messages only
	$mail->SMTPAuth = true; // enable SMTP authentication
	$mail->Port = $Port; // set the SMTP port for the service server
	$mail->Username = $Username; // account username
	$mail->Password = $Password; // account password

	$mail->SetFrom($usuario, $nomeRemetente);
	$mail->Subject = $Subject;
	$mail->MsgHTML($body);
	
	if ( $anexoSize < 10485760 ) { // caso não exceda o limite 10MB
		$mail->AddAttachment( $dump . ".sql" );
	}

	foreach ( $emails as $key ) {
		$mail->AddAddress( trim($key), "" );
	}

	if(!$mail->Send()) {
		$response = false;
	} else {
		$response = true;
	}

	return $response;

}

// echo init( $con );

?>
