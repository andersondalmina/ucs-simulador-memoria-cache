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
	
    //Recebe parametros para a simulação
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

    //Calcula o tamanho do conjunto
    $tamanho_conjunto = $waiter->calcularParteEndereco($quantidade_conjuntos);

    //Calcula o tamanho da palavra
    $tamanho_palavra = $waiter->calcularParteEndereco($params['tamanho_linha']);
    
    //Calcula o tamanho do rótulo
	$tamanho_rotulo = 32 - ($tamanho_palavra + $tamanho_conjunto);

	$memoriaPrincipal = new MemoriaPrincipal;
	$memoriaCache = new MemoriaCache($quantidade_conjuntos, $params['linhas_conjunto']);

    $resultados = [
        'params' => $params,
        'leituras' => 0,
        'escritas' => 0,
        'total_operacoes' =>  0,
        'cache_leituras' => 0,
        'cache_leituras_acertos' => 0,
        'cache_leituras_acertos_taxa' => 0,
        'cache_escritas' => 0,
        'cache_escritas_acertos' => 0,
        'cache_escritas_acertos_taxa' => 0,
        'cache_total_operacoes' => 0,
        'cache_taxa_acerto_total' => 0,
        'memoria_principal_leituras' => 0,
        'memoria_principal_leituras_acertos' => 0,
        'memoria_principal_escritas' => 0,
        'memoria_principal_escritas_acertos' => 0,
        'memoria_principal_total_operacoes' => 0,
        'tempo_medio' => 0
    ];

	$address = $waiter->readFile('teste.cache');

	foreach($address as $key => $options){
		$endereco = $options['address'];
        $operacao = $options['operation'];

        //preenche com zeros na frente para ficar com 32 digitos
        $endereco = str_pad($endereco, 32, "0", STR_PAD_LEFT);

        //Busca os endereços
        $endereco_rotulo = substr($endereco, 0, $tamanho_rotulo);
        $endereco_conjunto = substr($endereco, $tamanho_rotulo + 1, $tamanho_conjunto);

        if($operacao == "R"){
            $resultados['leituras']++;
            
            //procura na cache o endereço do conjunto
            $conjunto = $memoriaCache->procuraConjunto($endereco_conjunto);

            //Soma um na leitura na cache
            $resultados['cache_leituras']++;

            //Se conjunto existe na cache
            if($conjunto != null){
                //Procura o rotulo do conjunto
                $conjunto_rotulo = $conjunto->procuraRotulo($endereco_rotulo);
                
                //Se rótulo existe
                if(!is_null($conjunto_rotulo)){
                    //Soma um no acertos de leitura da cache
                    $resultados['cache_leituras_acertos']++;
                }else{
                    //Se rótulo não existe grava
                    $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);

                    //Soma um na leitura da memória principal
                    $resultados['memoria_principal_leituras']++;
                    $resultados['memoria_principal_leituras_acertos']++;
                }
            } else {
                //Se conjunto não existe grava
                $conjunto = $memoriaCache->gravaConjunto($endereco_conjunto);

                if(!is_null($conjunto)){
	                $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
	        	}

                //Soma um na leitura da memória principal
                $resultados['memoria_principal_leituras']++;
                $resultados['memoria_principal_leituras_acertos']++;
            }

        } else {
            $resultados['escritas']++;
            
            if($params['politica_escrita'] == 0){ //Write Through
                $conjunto = $memoriaCache->procuraConjunto($endereco_conjunto);

                $resultados['cache_escritas']++;

                if(is_null($conjunto)){
                    $conjunto = $memoriaCache->gravaConjunto($endereco_conjunto);
                    if(!is_null($conjunto)){
                    	$conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                	}
                    
                }else{
                    $retornoRotulo = $conjunto->procuraRotulo($endereco_rotulo);
                    if(!$retornoRotulo){
                        $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                        
                    }else{
                        $resultados['cache_escritas_acertos']++;
                    }
                }
                $resultados['memoria_principal_escritas']++;
                $resultados['memoria_principal_escritas_acertos']++;
            }else{ //Write Back
                $conjunto = $memoriaCache->procuraConjunto($endereco_conjunto);

                $resultados['cache_escritas']++;
                
                if(is_null($conjunto)){
                    $conjunto = $memoriaCache->gravaConjunto($endereco_conjunto); 
                    if(!is_null($conjunto)){
                    	$conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                	}
                    
                }else{
                    $retornoRotulo = $conjunto->procuraRotulo($endereco_rotulo);
                    if(!$retornoRotulo){
                        $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                        
                    }else{
                        $resultados['cache_escritas_acertos']++;
                    }
                }
            }
        }
	} 


    //RESULTADOS
    $resultados['total_operacoes'] = $resultados['leituras'] + $resultados['escritas'];

    $resultados['cache_leituras_acertos_taxa'] = number_format(($resultados['cache_leituras_acertos']*100)/$resultados['cache_leituras'], 4, '.', '');

    //Operações de escrita na cache
    $resultados['cache_escritas_acertos_taxa'] = number_format(($resultados['cache_escritas_acertos']*100)/$resultados['cache_escritas'], 4, '.', '');

    $resultados['total_operacoes_cache'] = $resultados['cache_leituras'] + $resultados['cache_escritas'];

    $cache_taxa_acerto_total = (($resultados['cache_leituras_acertos'] + $resultados['cache_escritas_acertos']) * 100) /$resultados['total_operacoes_cache'];

    $resultados['cache_taxa_acerto_total'] = number_format($cache_taxa_acerto_total, 4, '.', '');


    //Operações de leitura na memória principal
    $resultados['memoria_principal_leitura_acertos_taxa'] = number_format(($resultados['memoria_principal_leituras_acertos']*100)/$resultados['memoria_principal_leituras'], 4, '.', '');


    //Operações de escrita na memória principal
    $resultados['memoria_principal_escrita_acertos_taxa'] = number_format(($resultados['memoria_principal_escritas_acertos']*100)/$resultados['memoria_principal_escritas'], 4, '.', '');

    $resultados['total_operacoes_memoria_principal'] = $resultados['memoria_principal_leituras'] + $resultados['memoria_principal_escritas'];

    
    //Calculo do tempo médio de acesso
    $tempoMedio = ((($resultados['cache_leituras_acertos_taxa']/100) * $params['tempo_cache']) +
            ((1 - ($resultados['cache_leituras_acertos_taxa']/100)) * ($params['tempo_cache'] + $params['tempo_memoria_principal'])));

    $resultados['tempoMedio'] = $tempoMedio;

    return $this->renderer->render($response, 'result.phtml', $resultados);
});
