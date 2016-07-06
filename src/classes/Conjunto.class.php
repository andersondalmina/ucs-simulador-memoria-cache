<?php 
/*---------------------------------------------------
    Autor: Ânderson Zorrer Dalmina
*/

use Carbon\Carbon;

class Conjunto {
	private $linhas;
	private $conjunto;
	private $tamanho;

    public function __construct($quantidade_linhas){
        $this->linhas = array();
        $this->tamanho = $quantidade_linhas;   
    }

    public function setConjunto($conjunto){
        $this->conjunto = $conjunto;
    }

    public function getConjunto(){
        return $this->conjunto;
    }

    public function setLinhas($linhas){
        $this->linhas = $linhas;
    }

    public function getLinhas(){
        return $this->linhas;
    }

	public function procuraRotulo($rotulo){
        foreach($this->linhas as $cada_linha){
            if($cada_linha->getRotulo() == $rotulo){
                $data = Carbon::now()->timestamp;
                $cada_linha->setAcessos($cada_linha->getAcessos() + 1);
                $cada_linha->setUltimaUtilizacao($data);
                
                return true;
            }    
        }

        return false;
    }

    public function gravaRotulo($rotulo, $politica_substituicao, $politica_gravacao, $memoria_principal, $endereco){
        $data = Carbon::now()->timestamp;

        if(count($this->linhas) == $this->tamanho){
            if($politica_substituicao == 0){ //LFU
                $num_linha = $this->getLinhaLFU();
                
            } else if($politica_substituicao == 1){ //LRU
                $num_linha = $this->getLinhaLRU();
            
            } else { //aleatoria
                $num_linha = mt_rand(0, count($this->linhas) - 1);
            }

            $linha = $this->linhas[$num_linha];
            
            if($politica_gravacao == 0){ //Write-Through
                $memoria_principal->add($linha->getEndereco());
            } else {
                if($linha->getDirtyBit() == 1){ //Write-Back
                    $memoria_principal->add($linha->getEndereco());
                }
            }

            $linha->setRotulo($rotulo, $endereco);
            $linha->setAcessos($linha->getAcessos() + 1);
            $linha->setUltimaUtilizacao($data);
            $linha->setDirtyBit(1);

        } else {
            $linha = new Linha();
            $linha->setRotulo($rotulo, $endereco);
            $linha->setAcessos($linha->getAcessos() + 1);
            $linha->setUltimaUtilizacao($data);

            array_push($this->linhas, $linha);
        }

        return $linha->getDirtyBit();
    }
    
    //retorna linha menos acessada recentemente
    public function getLinhaLRU(){
        if(count($this->linhas) > 0){
            $aux = $this->linhas[0]->getUltimaUtilizacao();
            $linha_menos_acessada = 0;

            foreach($this->linhas as $key => $cada_linha){
                if($cada_linha->getUltimaUtilizacao() < $aux){
                    $aux = $cada_linha->getUltimaUtilizacao();
                    $linha_menos_acessada = $key;
                }
            }

            return $linha_menos_acessada;
        } else {
            return false;
        }
    }

    //retorna linha menos acessada frequentemente
    public function getLinhaLFU(){
        if(count($this->linhas) > 0){
            $aux = $this->linhas[0]->getAcessos();
            $linha_menos_acessada = 0;

            foreach($this->linhas as $key => $cada_linha){
                if($cada_linha->getAcessos() < $aux){
                    $aux = $cada_linha->getAcessos();
                    $linha_menos_acessada = $key;
                }
            }

            return $linha_menos_acessada;
        } else {
            return false;
        }
    }
}

?>