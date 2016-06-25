<?php 

class Linha {
    private $rotulo;
    private $endereco;
    private $ultima_utilizacao;

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

    public function setUltimaUtilizacao($ultima_utilizacao){
        $this->ultima_utilizacao = $ultima_utilizacao;
    }

    public function getUltimaUtilizacao(){
        return $this->ultima_utilizacao;
    }
}
?>