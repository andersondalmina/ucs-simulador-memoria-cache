<?php 
/*---------------------------------------------------
    Autor: Ânderson Zorrer Dalmina
*/

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

use Respect\Validation\Validator as validator;
use Respect\Validation\Exceptions\NestedValidationException;

class Waiter {
    public function readFile($file){
    	$buffer = fopen($file, "r") or die("Unable to open file!");
    	$address = [];

		while(!feof($buffer)){
			$line = fgets($buffer);

            if(strlen($line) > 0){
			    $options['address'] = $this->convertHexadecimalToBinary(substr($line, 0, 8));
    			$options['operation'] = substr($line, -2, 1);

                if($options['operation'] == ' '){
                    $options['operation'] = substr($line, -1, 1);                
                }

    			array_push($address, $options);
            }
		}

		fclose($buffer);

		return $address;
    }

    public function convertHexadecimalToBinary($hexadecimal){
        $decimal = hexdec($hexadecimal);
        $binary = $this->convertDecimalToBinary($decimal);

        return $binary;
    }

    public function convertDecimalToBinary($decimal){
    	return decbin($decimal);
    }

    public function validate(ServerRequestInterface $request){
        try {
            validator::intVal()->min(0)->max(1)->setName('Política de Escrita')->assert($request->getParam('politica_escrita'));
            validator::intVal()->min(0)->max(2)->setName('Política de Substituição')->assert($request->getParam('politica_substituicao'));

            validator::intVal()->setName('Número de Linhas')->assert($request->getParam('numero_linhas'));
            validator::intVal()->min(1)->max($request->getParam('numero_linhas'))->setName('Linhas por Conjunto')->assert($request->getParam('linhas_conjunto'));
            validator::intVal()->setName('Tamanho da Linha')->assert($request->getParam('tamanho_linha'));

            validator::intVal()->setName('Tempo de acesso da memória cache')->assert($request->getParam('tempo_cache'));
            validator::intVal()->setName('Tempo de acesso da memória principal')->assert($request->getParam('tempo_memoria_principal'));
        } catch(NestedValidationException $exception){
            return false;
        }

        $bin_linhas = decbin($request->getParam('numero_linhas'));
        $bin_conjuntos = decbin($request->getParam('linhas_conjunto'));
        $bin_tamanho = decbin($request->getParam('tamanho_linha'));
        
        if(preg_match('/^0*10*$/', $bin_linhas) && preg_match('/^0*10*$/', $bin_conjuntos) && preg_match('/^0*10*$/', $bin_tamanho)){
            return true;
        } else {
            return false;
        }
    }

    public function calcularParteEndereco($int){
        $tamanho = 0;
        while($int != 1){
            $int = $int / 2;
            $tamanho++;
        }

        return $tamanho;
    }
}

?>