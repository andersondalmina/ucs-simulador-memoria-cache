<?php 

class MemoriaCache {
    private $conjuntos;
    private $tamanho;
    private $proximo;

    public function __construct($quantidade_conjuntos, $quantidade_linhas){
        $this->conjuntos = array();
        $this->tamanho = $quantidade_conjuntos;

        for($i = 0; $i < $this->tamanho; $i++){
            $this->conjuntos[$i] = new Conjunto($quantidade_linhas);
        }

        $this->proximo = 0;
    }

    public function setConjuntos($conjuntos) {
        $this->conjuntos = $conjuntos;
    }

    public function getConjuntos(){
        return $this->conjuntos;
    }
    
    public function procuraConjunto($endereco){
        for($i = 0; $i < $this->proximo; $i++){
            if($this->conjuntos[$i]->getConjunto() == $endereco){
                return $this->conjuntos[$i];
            }
        }

        return null;
    }
    
    public function gravaConjunto($enderecoConjunto){
        if($this->proximo < $this->tamanho){
            $this->conjuntos[$this->proximo]->setConjunto($enderecoConjunto);
            $this->proximo++;

            return $this->conjuntos[$this->proximo - 1];
        }

        return null;
    }
}
?>