<?php
/*---------------------------------------------------
    Autor: Ânderson Zorrer Dalmina
*/

//Importa as classes criadas
require __DIR__ . '/../src/classes/Conjunto.class.php';
require __DIR__ . '/../src/classes/Linha.class.php';
require __DIR__ . '/../src/classes/MemoriaCache.class.php';
require __DIR__ . '/../src/classes/MemoriaPrincipal.class.php';
require __DIR__ . '/../src/classes/Waiter.class.php';

//Rota para a página inicial
$app->get('/[{name}]', function ($request, $response, $args){
	$args['message'] = $this->flash->getMessages();

	return $this->renderer->render($response, 'index.phtml', $args);
});

//Rota que realiza o processamento
$app->post('/process', function($request, $response, $args) use ($app){
	$waiter = new Waiter;

	//Valida formulário
	$validou = $waiter->validate($request);
    if($validou == false){
        $this->flash->addMessage('message', 'Informações inválidas. Verifique se foi preenchido todo o formulário e se os campos (Tamanho da Linha, Número de Linhas, Linhas por Conjunto) são potências de 2.');

        return $response->withHeader('Location', '/');
    }
	
    //Recebe parametros para a simulação
	$params['politica_escrita'] = (int) $request->getParam('politica_escrita');
	$params['politica_substituicao'] = (int) $request->getParam('politica_substituicao');
	$params['numero_linhas'] = $request->getParam('numero_linhas');
	$params['linhas_conjunto'] = $request->getParam('linhas_conjunto');
	$params['tamanho_linha'] = $request->getParam('tamanho_linha');
	$params['tempo_cache'] = $request->getParam('tempo_cache');
	$params['tempo_memoria_principal'] = $request->getParam('tempo_memoria_principal');
    $params['linhas_arquivo'] = 0;
    $params['linhas_leituras_arquivo'] = 0;
    $params['linhas_escritas_arquivo'] = 0;

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

	$address = $waiter->readFile('oficial.cache');
    $params['linhas_arquivo'] = count($address);

	foreach($address as $key => $options){
		$endereco = $options['address'];
        $operacao = $options['operation'];

        //preenche com zeros na frente para ficar com 32 digitos
        $endereco = str_pad($endereco, 32, "0", STR_PAD_LEFT);

        //define parte do endereco de rotulo
        $endereco_rotulo = substr($endereco, 0, $tamanho_rotulo);

        //define parte do endereco do conjunto
        $endereco_conjunto = substr($endereco, $tamanho_rotulo + 1, $tamanho_conjunto);

        if($operacao == "R"){
            $params['linhas_leituras_arquivo']++;
            $resultados['leituras']++;
            
            //procura na cache o endereço do conjunto
            $conjunto = $memoriaCache->procuraConjunto($endereco_conjunto);

            //Soma um na leitura na cache
            $resultados['cache_leituras']++;

            //Se conjunto existe na cache
            if(!is_null($conjunto)){
                //Procura o rotulo das linhas do conjunto
                $conjunto_rotulo = $conjunto->procuraRotulo($endereco_rotulo);

                //Se rótulo existe
                if($conjunto_rotulo == true){
                    //Soma um no acertos de leitura da cache
                    $resultados['cache_leituras_acertos']++;
                } else {
                    $encontrou_mp = $memoriaPrincipal->search($endereco);
                    //Soma um na leitura da memória principal
                    $resultados['memoria_principal_leituras']++;

                    if($encontrou_mp == true){
                        $resultados['memoria_principal_leituras_acertos']++;    
                    } else {
                        $memoriaPrincipal->add($endereco);
                    }

                    //Se rótulo não existe grava
                    $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                }
            } else {
                //procura na memória principal
                $encontrou_mp = $memoriaPrincipal->search($endereco);

                $resultados['memoria_principal_leituras']++;

                if($encontrou_mp == true){
                    $resultados['memoria_principal_leituras_acertos']++;    

                } else {
                    $memoriaPrincipal->add($endereco);
                }

                //Se conjunto não existe grava na cache
                $conjunto = $memoriaCache->gravaConjunto($endereco_conjunto);

                //salva rotulo no conjunto
                $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
            }

        } else {
            $params['linhas_escritas_arquivo']++;
            $resultados['escritas']++;
            
            //procura conjunto na cache
            $conjunto = $memoriaCache->procuraConjunto($endereco_conjunto);

            $resultados['cache_escritas']++;

            if($params['politica_escrita'] == 0){ //Write Through
                if($conjunto == false){
                    $conjunto = $memoriaCache->gravaConjunto($endereco_conjunto);
                    $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);

                } else {
                    $encontrou_rotulo = $conjunto->procuraRotulo($endereco_rotulo);
                    
                    if($encontrou_rotulo == true){
                        $resultados['cache_escritas_acertos']++;
                        
                    } else {
                        $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
                    }
                }

                $resultados['memoria_principal_escritas']++;
                $resultados['memoria_principal_escritas_acertos']++;
            } else { //Write Back
                if($conjunto == false){
                    $conjunto = $memoriaCache->gravaConjunto($endereco_conjunto); 
                	$dirty_bit = $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);

                    if($dirty_bit == 1){
                        $resultados['memoria_principal_escritas']++;
                    }
                } else {
                    $encontrou_rotulo = $conjunto->procuraRotulo($endereco_rotulo);

                    if($encontrou_rotulo == true){
                        $resultados['cache_escritas_acertos']++;
                        
                    } else {
                        $conjunto->gravaRotulo($endereco_rotulo, $params['politica_substituicao'], $params['politica_escrita'], $memoriaPrincipal, $endereco);
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

    $resultados['total_operacoes_memoria_principal'] = $resultados['memoria_principal_leituras'] + $resultados['memoria_principal_escritas'];

    
    //Calculo do tempo médio de acesso
    $tempoMedio = ((($resultados['cache_leituras_acertos_taxa']/100) * $params['tempo_cache']) +
            ((1 - ($resultados['cache_leituras_acertos_taxa']/100)) * ($params['tempo_cache'] + $params['tempo_memoria_principal'])));

    $resultados['tempo_medio'] = $tempoMedio;
    $resultados['params'] = $params;

    return $this->renderer->render($response, 'result.phtml', $resultados);
});
