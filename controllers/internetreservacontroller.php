<?php
#--------------------------------------------------------------------------
# internetreservacontroller.php
#--------------------------------------------------------------------------
#
#	@author: Yuri Fialho - 2� TEN FIALHO
#	@since: 03/02/2016
#	@contact: yurirfialho@gmail.com
#
#--------------------------------------------------------------------------
# Recebe as requisicoes da view e trata.
#-------------------------------------------------------------------------- 
	require_once "../includes/database.config.php";
	require_once "../helpers/mail_helper.php";
	require_once "../helpers/application_helper.php";
	require_once "../helpers/route_helper.php";
	include 	 "../libs/recaptcha/autoload.php";

	session_start();
	
	if(isset($_SESSION['idusuario'])) {
		$usuarioid = $_SESSION['idusuario'];
	}

	$router = new RouteHelper("../views/internet/internet_reserva_lista.php");
	
	function validateRecaptch() {
		$siteKey = '6Lc1wx8TAAAAABU2y3ysPDDt7B-AmCRFJh-1cJhS';
		$secret = '6Lc1wx8TAAAAAPElHWw9hGSbz52VhjR6V0gl3cfm';
		$recaptcha = new \ReCaptcha\ReCaptcha($secret);
		$resp = $recaptcha->verify($_POST['g-recaptcha-response'], $_SERVER['REMOTE_ADDR']);

		if($resp->isSuccess()) {
			return 1;
		} else {
			return 0;
		}
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    	$action     = $_POST['action'];
		$id 	    = isset($_POST['id']) ? $_POST['id'] : NULL;
		$disponibilidade_id = isset($_POST['disponibilidade_id']) ? $_POST['disponibilidade_id'] : NULL;
		$entidade   = isset($_POST['entidade']) ? $_POST['entidade'] : NULL;
		$nome       = isset($_POST['nome']) ? $_POST['nome'] : NULL;
		$telefone   = isset($_POST['telefone']) ? $_POST['telefone'] : NULL;
		$celular    = isset($_POST['celular']) ? $_POST['celular'] : NULL;
		$email      = isset($_POST['email']) ? $_POST['email'] : NULL;
		$quantidade = isset($_POST['quantidade']) ? $_POST['quantidade'] : NULL;
		$ano        = isset($_POST['ano']) ? $_POST['ano'] : NULL;
		$mes        = isset($_POST['mes']) ? $_POST['mes'] : NULL;
		$transp		= isset($_POST['transportetipo']) ? $_POST['transportetipo'] : NULL;
		$nrtransp	= isset($_POST['nrtransp']) ? $_POST['nrtransp'] : NULL;
		$escolaridade = isset($_POST['escolaridadetipo']) ? $_POST['escolaridadetipo'] : NULL;
	} else {
		$action    = $_GET['action'];
		$id 	   = $_GET['id'];
		$disponibilidade_id = isset($_GET['disponibilidade_id']) ? $_GET['disponibilidade_id'] : NULL;
	}
	
	if($action == "index") {
		#validar os paramentros informados
		if($ano != NULL && isNumeric($ano)){
			$router->addParam("ano", $ano);
		} else {
			$router->addMsgErro("Ano informado inv�lido.");
		}
		if($mes != NULL && isNumeric($mes)){
			$router->addParam("mes", $mes);
		} else {
			$router->addMsgErro("M�s informado inv�lido.");
		}
		$router->redirect();
		return;
	} elseif($action == "agendar") {
		if(!validateRecaptch()) {
			$router->addMsgErro("C�digo n�o confere com a imagem!");
			$router->redirect(); return;
		}

		#quantidade de visitante tem que ser maior que 10.
		if(isset($quantidade) && $quantidade <= 10) {
			$router->addMsgErro("Quantidade de pessoas tem que ser maior que 10!");
			$router->redirect(); return;
		}
		
		$dispo = Disponibilidade::find($disponibilidade_id);

		if($dispo != null && $dispo->reserva != null) {
			$router->addMsgErro("Essa data j� encontra-se reservada por outra pessoa, por favor tente agendar em outra data!");
			$router->redirect(); return;
		}
				
		$reserva = new Reserva();
		$reserva->entidade = $entidade;
		$reserva->nome = $nome;
		$reserva->telefone = $telefone;
		$reserva->celular = $celular;
		$reserva->email = $email;
		$reserva->quantidade = $quantidade;
		$reserva->disponibilidade_id = $disponibilidade_id;
		$reserva->reserva_situacao_id = 3; #Aguardando Confirmacao
		$reserva->transporte_tipo_id = $transp;
		$reserva->transporte_numero = $nrtransp;
		$reserva->escolaridade_tipo_id = $escolaridade;
			
		if($reserva->save()){
			$router->addMsg("Objeto salvo com sucesso! A reserva encontra-se em an�lise para aprova��o.");

			$mail = new MailHelper();
			$mail->sendAgendamento($email, $entidade, $reserva->disponibilidade->data,
					$reserva->disponibilidade->hora);

		} else {
			$router->addMsgErro("Nao foi possivel salvar objeto!");
		}
					
		$router->redirect(); return;
	} else {
		$router->addMsgErro("Opera��o n�o suportada!");
		$router->redirect(); return;
	}  		
?>