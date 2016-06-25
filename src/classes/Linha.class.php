<?php 

class Linha {
    private $rotulo;
    private $endereco;
    private $lru;

    public function __construct(){
        $this->rotulo = null;
        $this->endereco = null;
    }  

    public function setRotulo($rotulo, $endereco) {
        $this->rotulo = $rotulo;
        $this->endereco = $endereco;
    }

    public function getRotulo(){
        return $this->rotulo;
    }

    public function getEnderecoTotal() {
        return $this->endereco;
    }

    public function setEnderecoTotal($endereco) {
        $this->endereco = $endereco;
    }

    public function setLru($lru){
        $this->lru = $lru;
    }

    public function getLru(){
        return $this->lru;
    }
}
?>