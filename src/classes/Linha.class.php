<?php 
/*---------------------------------------------------
    Autor: Ânderson Zorrer Dalmina
*/

class Linha {
    private $rotulo;
    private $endereco;
    private $dirty_bit;
    private $acessos;
    private $ultima_utilizacao;

    public function __construct(){
        $this->rotulo = null;
        $this->endereco = null;
        $this->dirty_bit = 0;
    }  

    public function setRotulo($rotulo, $endereco){
        $this->rotulo = $rotulo;
        $this->endereco = $endereco;
    }

    public function getRotulo(){
        return $this->rotulo;
    }

    public function getEndereco(){
        return $this->endereco;
    }

    public function setEndereco($endereco){
        $this->endereco = $endereco;
    }

    public function setDirtyBit($dirty_bit){
        $this->dirty_bit = $dirty_bit;
    }

    public function getDirtyBit(){
        return $this->dirty_bit;
    }

    public function setAcessos($acessos){
        $this->acessos = $acessos;
    }

    public function getAcessos(){
        return $this->acessos;
    }

    public function setUltimaUtilizacao($ultima_utilizacao){
        $this->ultima_utilizacao = $ultima_utilizacao;
    }

    public function getUltimaUtilizacao(){
        return $this->ultima_utilizacao;
    }
}
?>