<?php 

use Carbon\Carbon;

class Conjunto {
	private $linhas;
	private $conjunto;
	private $tamanho;
	private $prox;

    public function __construct($quantidade_linhas){
        for($i = 0; $i < $quantidade_linhas; $i++){
            $this->linhas[$i] = new Linha;
        }
        
        $this->tamanho = $quantidade_linhas;
        $this->prox = 0;
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
        for($i = 0; $i < $this->prox; $i++){
            if($this->linhas[$i]->getRotulo() == $rotulo){
                $data = Carbon::now()->timestamp;
                $this->linhas[$i]->setUltimaUtilizacao($data);
                
                return true;
            }
        }

        return false;
    }

    public function gravaRotulo($rotulo, $politica_substituicao, $politica_gravacao, $memoria_principal, $endereco){
        $data = Carbon::now()->timestamp;

        if($this->prox == $this->tamanho){
            if($politica_substituicao == 0){ //LRU
                $linhaMenosRecUsada = $this->buscaUltimaUsada();
                
                if($politica_gravacao == 1){
                    $memoria_principal->adicionaNaMP($this->linhas[$linhaMenosRecUsada]->getEnderecoTotal());
                }

                $this->linhas[$linhaMenosRecUsada]->setRotulo($rotulo, $endereco);
                $this->linhas[$linhaMenosRecUsada]->setUltimaUtilizacao($data);
            } else { //Aleatorio
                $linhaAleatoria = rand(0, $this->tamanho);

                if($politica_gravacao == 1){
                    $memoria_principal->adicionaNaMP($this->linhas[$linhaAleatoria]->getEnderecoTotal());
                }

                $this->linhas[$linhaAleatoria]->setRotulo($rotulo, $endereco);
                $this->linhas[$linhaAleatoria]->setUltimaUtilizacao($data);
            }
        } else {
            $this->linhas[$this->prox] = new Linha();
            $this->linhas[$this->prox]->setRotulo($rotulo, $endereco);
            $this->linhas[$this->prox]->setUltimaUtilizacao($data);
            $this->prox++;
        }
    }
    
    public function buscaUltimaUsada(){
        $aux = $this->linhas[0]->getUltimaUtilizacao();
        $menosUsado = 0;
        for($i = 0; $i < $this->tamanho; $i++){
            if($this->linhas[$i]->getUltimaUtilizacao() < $aux){
                $aux = $this->linhas[$i]->getUltimaUtilizacao();
                $menosUsado = $i;
            }
        }
        
        return $menosUsado;
    }
}

?>