<?php 

use Respect\Validation\Validator as validator;
use Respect\Validation\Exceptions\NestedValidationException;

class Waiter {
    public function readFile($file){
    	$buffer = fopen($file, "r") or die("Unable to open file!");
    	$address = [];

		while(!feof($buffer)){
			$line = fgets($buffer);

			$options['address'] = $this->convertHexadecimalToBinary(substr($line, 0, 8));
			$options['operation'] = substr($line, -2, 1);

			array_push($address, $options);
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

    public function validate($request){
        try {
            validator::intVal()->min(0)->max(1)->setName('Política de Escrita')->assert($request->getParam('politica_escrita'));
            validator::intVal()->min(0)->max(1)->setName('Política de Substituição')->assert($request->getParam('politica_substituicao'));

            validator::intVal()->multiple(2)->setName('Número de Linhas')->assert($request->getParam('numero_linhas'));
            validator::intVal()->min(1)->max($request->getParam('numero_linhas'))->multiple(2)->setName('Linhas por Conjunto')->assert($request->getParam('linhas_conjunto'));
            validator::intVal()->multiple(2)->setName('Tamanho da Linha')->assert($request->getParam('tamanho_linha'));

            validator::intVal()->setName('Tempo de acesso da memória cache')->assert($request->getParam('tempo_cache'));
            validator::intVal()->setName('Tempo de acesso da memória principal')->assert($request->getParam('tempo_memoria_principal'));
        } catch(NestedValidationException $exception){
            $errors = $exception->findMessages([
                'intVal' => '{{name}} deve ser um número válido!',
                'min' => '{{name}} deve ser no mínimo 1',
                'max' => '{{name}} deve ser no máximo '.$request->getParam('numero_linhas'),
                'multiple' => '{{name}} deve ser potência de 2',
            ]);

            $this->flash->addMessage('error', $errors);
            return $response->withHeader('Location', '/');
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