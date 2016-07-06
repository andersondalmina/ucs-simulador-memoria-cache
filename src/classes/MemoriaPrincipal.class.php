<?php 

class MemoriaPrincipal {
    private $enderecos;
    private $total;

    public function __construct(){
        $this->enderecos = array();
        $total = 0;
    }
    
    public function add($endereco){
        array_push($this->enderecos, $endereco);
        $this->total++;
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