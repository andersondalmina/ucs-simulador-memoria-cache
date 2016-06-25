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
        for($i = 0; $i < $this->total; $i++){
            if($this->enderecos[$i] == $endereco){
                return true;
            }
        }
        
        return false;
    }
}

?>