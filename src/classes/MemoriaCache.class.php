<?php 

class MemoriaCache {
    private $conjuntos;
    private $tamanho;
    private $conjunto_quantidade_linhas;

    public function __construct($quantidade_conjuntos, $quantidade_linhas){
        $this->conjuntos = array();
        $this->tamanho = $quantidade_conjuntos;
        $this->conjunto_quantidade_linhas = $quantidade_linhas;
    }

    public function setConjuntos($conjuntos) {
        $this->conjuntos = $conjuntos;
    }

    public function getConjuntos(){
        return $this->conjuntos;
    }
    
    public function procuraConjunto($endereco){
        if(is_array($this->conjuntos)){
            foreach($this->conjuntos as $cada_conjunto){
                if($cada_conjunto->getConjunto() == $endereco){
                    return $cada_conjunto;
                }
            }
        }

        return null;
    }
    
    public function gravaConjunto($enderecoConjunto){
        if(count($this->conjuntos) < $this->tamanho){
            $conjunto = new Conjunto($this->conjunto_quantidade_linhas);
            $conjunto->setConjunto($enderecoConjunto);

            array_push($this->conjuntos, $conjunto);
            
            return $conjunto;
        }

        return null;
    }
}
?>