<?php

require __DIR__ . '/../src/classes/Conjunto.class.php';
require __DIR__ . '/../src/classes/Linha.class.php';
require __DIR__ . '/../src/classes/MemoriaCache.class.php';
require __DIR__ . '/../src/classes/MemoriaPrincipal.class.php';
require __DIR__ . '/../src/classes/Waiter.class.php';

$app->get('/[{name}]', function ($request, $response, $args){
	$args['message'] = $this->flash->getMessages();

	return $this->renderer->render($response, 'index.phtml', $args);
});

$app->post('/process', function($request, $response, $args){
	$waiter = new Waiter;

	//Valida formulário
	$waiter->validate($request);
	
	$params['politica_escrita'] = $request->getParam('politica_escrita');
	$params['politica_substituicao'] = $request->getParam('politica_substituicao');
	$params['numero_linhas'] = $request->getParam('numero_linhas');
	$params['linhas_conjunto'] = $request->getParam('linhas_conjunto');
	$params['tamanho_linha'] = $request->getParam('tamanho_linha');
	$params['tempo_cache'] = $request->getParam('tempo_cache');
	$params['tempo_memoria_principal'] = $request->getParam('tempo_memoria_principal');

	//Tamanho total da Cache (capacidade)
    $tamanho_cache = $params['numero_linhas'] * $params['tamanho_linha'];

	//Quantidade de Conjuntos
    $quantidade_conjuntos = ceil($params['numero_linhas'] / $params['linhas_conjunto']);

    //Tamanho de cada parte do endereco
    $aux = $quantidade_conjuntos;
    $tamanho_conjunto = 0;
    while($aux != 1){
        $aux = $aux / 2;
        $tamanho_conjunto++;
    }

    $aux = $params['tamanho_linha'];
    $tamanho_palavra = 0;
    while($aux != 1){
        $aux = $aux / 2;
        $tamanho_palavra++;
    }

    //Calcula o rótulo
	$tamanho_rotulo = 32 - ($tamanho_palavra + $tamanho_conjunto);

	$memoriaPrincipal = new MemoriaPrincipal;
	$memoriaCache = new MemoriaCache($quantidade_conjuntos, $params['linhas_conjunto']);

	$leituras = 0;
    $escritas = 0;
    $leiturasNaCache = 0;
    $leiturasNaMP = 0;
    $escritasNaCache = 0;
    $escritasNaMP = 0;
    $naoEncontrouNaCacheLeitura = 0;
    $encontrouNaCacheLeitura = 0;
    $naoEncontrouNaCacheEscrita = 0;
    $encontrouNaCacheEscrita = 0;
    $naoEncontrouNaMPLeitura = 0;
    $encontrouNaMPLeitura = 0;
    $naoEncontrouNaMPEscrita = 0;
    $encontrouNaMPEscrita = 0;

	$address = $waiter->readFile('teste.cache');

	foreach($address as $key => $options){
		$endereco = $options['address'];
        $operacao = $options['operation'];

        //preenche com zeros na frente para ficar com 32 digitos
        $endereco = str_pad($endereco, 32, "0", STR_PAD_LEFT);

        //Busca os endereços
        $rotuloEndereco = substr($endereco, 0, $tamanho_rotulo);
        $enderecoConjunto = substr($endereco, $tamanho_rotulo + 1, $tamanho_conjunto);

        if($operacao == "R"){
            $leituras++;
            
            //procura na cache o endereço do conjunto
            $conjunto = $memoriaCache->procuraConjunto($enderecoConjunto);

            //Contador de leituras
            $leiturasNaCache++;

            if($conjunto != null){
                //Procura rotulo pelo conjunto encontrado
                $retornoRotulo = $conjunto->procuraRotulo($rotuloEndereco);
                if(!is_null($retornoRotulo)){
                    //Se encontrou o rótulo ocorre hit
                    $encontrouNaCacheLeitura++;
                }else{
                    //caso não encontrou o rótulo da erro
                    $naoEncontrouNaCacheLeitura++;
                    $conjunto->gravaRotulo($rotuloEndereco, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);

                    $leiturasNaMP++;
                    $encontrouNaMPLeitura++;
                }
            } else {
                $naoEncontrouNaCacheLeitura++;
                $conjunto = $memoriaCache->gravaConjunto($enderecoConjunto);
                if(!is_null($conjunto)){
	                $conjunto->gravaRotulo($rotuloEndereco, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
	        	}

                $leiturasNaMP++;
                $encontrouNaMPLeitura++;
            }

        } else {
            $escritas++;
            if($params['politica_escrita'] == 0){ //Write Through
                $conjunto = $memoriaCache->procuraConjunto($enderecoConjunto);
                $escritasNaCache++;
                if(is_null($conjunto)){
                    $conjunto = $memoriaCache->gravaConjunto($enderecoConjunto);
                    if(!is_null($conjunto)){
                    	$conjunto->gravaRotulo($rotuloEndereco, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                	}
                    $naoEncontrouNaCacheEscrita++;
                }else{
                    $retornoRotulo = $conjunto->procuraRotulo($rotuloEndereco);
                    if(!$retornoRotulo){
                        $conjunto->gravaRotulo($rotuloEndereco, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                        $naoEncontrouNaCacheEscrita++;
                    }else{
                        $encontrouNaCacheEscrita++;
                    }
                }
                $escritasNaMP++;
                $encontrouNaMPEscrita++;
            }else{ //Write Back
                $conjunto = $memoriaCache->procuraConjunto($enderecoConjunto);
                $escritasNaCache++;
                if(is_null($conjunto)){
                    $conjunto = $memoriaCache->gravaConjunto($enderecoConjunto); 
                    if(!is_null($conjunto)){
                    	$conjunto->gravaRotulo($rotuloEndereco, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                	}
                    $naoEncontrouNaCacheEscrita++;
                }else{
                    $retornoRotulo = $conjunto->procuraRotulo($rotuloEndereco);
                    if(!$retornoRotulo){
                        $conjunto->gravaRotulo($rotuloEndereco, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                        $naoEncontrouNaCacheEscrita++;
                    }else{
                        $encontrouNaCacheEscrita++;
                    }
                }
            }
        }
	}

	$totalDeRegistros = $leituras + $escritas;
    
    var_dump("Total de Escritas: ".$escritas);
    var_dump("Total de Leituras: ".$leituras);
    var_dump("Total de Registros: ".$totalDeRegistros);
    
    var_dump("Total de Leituras na Cache: ".$leiturasNaCache);
	var_dump("Total de Escritas na Cache:" .$escritasNaCache);
	
	var_dump("Total de Leituras na MP:" .$leiturasNaMP);
    var_dump("Total de Escritas na MP:" .$escritasNaMP);

    var_dump("Total de Operacoes na Cache: ".($leiturasNaCache+$escritasNaCache));
	var_dump("Total de Operacoes na MP: ".($leiturasNaMP+$escritasNaMP));

    //Acerto Leitura Cache
    $taxaDeAcertoCacheLeitura = ($encontrouNaCacheLeitura*100)/$leiturasNaCache;
    var_dump("Taxa de Acerto na Cache (Leitura): ".($encontrouNaCacheLeitura." - ".($taxaDeAcertoCacheLeitura))."%");

    //Erro Leitura Cache 
    $taxaDeErroCacheLeitura = ($naoEncontrouNaCacheLeitura*100)/$leiturasNaCache;
    var_dump("Taxa de Erro na Cache (Leitura): ".($naoEncontrouNaCacheLeitura." - ".($taxaDeErroCacheLeitura))."%");

    //Erro Escrita Cache
    $taxaDeErroCacheEscrita = ($naoEncontrouNaCacheEscrita*100)/$escritasNaCache;
    var_dump("Taxa de Erro na Cache (Escrita): ".($naoEncontrouNaCacheEscrita." - ".($taxaDeErroCacheEscrita))."%");
    
    //Acerto Escrita Cache
    $taxaDeAcertoCacheEscrita = ($encontrouNaCacheEscrita*100)/$escritasNaCache;
    var_dump("Taxa de Acerto na Cache (Escrita): ".($encontrouNaCacheEscrita." - ".($taxaDeAcertoCacheEscrita))."%");
    
    $taxaDeAcertoMPLeitura = ($encontrouNaMPLeitura*100)/$leiturasNaMP;
    var_dump("Taxa de Acerto na MP (Leitura): ".($encontrouNaMPLeitura." - ".($taxaDeAcertoMPLeitura))."%");
    
    //Erro Leitura MP
    $taxaDeErroMPLeitura = ($naoEncontrouNaMPLeitura*100)/$leiturasNaMP;
    var_dump("Taxa de Erro na MP (Leitura): ".($naoEncontrouNaMPLeitura." - ".($taxaDeErroMPLeitura))."%");
    
    $tempoMedio = ((($taxaDeAcertoCacheLeitura/100) * $params['tempo_cache']) +
            ((1 - ($taxaDeAcertoCacheLeitura/100)) * ($params['tempo_cache'] + $params['tempo_memoria_principal'])));
    
    var_dump("Tempo médio de acesso: ".$tempoMedio);
});
