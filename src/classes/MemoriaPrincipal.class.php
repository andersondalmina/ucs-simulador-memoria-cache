<?php 
/*---------------------------------------------------
    Autor: Ânderson Zorrer Dalmina
*/

class MemoriaPrincipal {
    private $enderecos;

    public function __construct(){
        $this->enderecos = array();
    }
    
    public function getEnderecos(){
        return $this->enderecos;
    }

    public function add($endereco){
        array_push($this->enderecos, $endereco);
    }
    
    public function search($endereco){
        foreach($this->enderecos as $cada_endereco){
            if($cada_endereco == $endereco){
                return true;
            }
        }
        
        return false;
    }
}

?>