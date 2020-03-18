
<?php

/*defines
  tag simples= tag que se fecha em uma na mesma que abre. ex: </b>
  tag dupla= precisa de uma segunda tag para fechar. ex: <p></p>

  autor: Carlos Eduardo Coutinho Bernardo

 */
 
  /*$var= readline();
  $parser = new parser(null);
  $variavel= $parser->parse($var,"");
 /* $variavel= $parser->getTag($var,'card');
  foreach($variavel as $v)
  {
    print_r("\xa\xa".$v->atributes['id']."\xa\xa");
  }*/

 
  
 
 //class tag is used to storage a especific tag 
 
 class Tag {
    public $id; //desuso
    public $dad; //desuso
    public $name;
    public $container; //tag's content 
    public $atributes=Array(); //atributes's tag
    public $simple=true;
    //construct the obj tag
    function __construct(){
      
    }
    //function to add atribute to this tag
    public function add_Atribute($name, $value)
    {
      $this->atributes[$name]=$value;
      
    }
    
 }


//parser class to really doe the parser
class parser {
	private $supported_xml_version = Array("2.0");
	private $master;
  private $map = Array();//cartoes com origem, conteudo e destino
  private $variables; //variaveis
	public $promotional_text = "";
	public $short_code = "";
	public $keyword = "";
	public $short_code_description = "";
	public $keyword_description = "";
	public $operation_type = "";
	public $has_menu = 0;
	public $failed = false;


    function __construct($master) {
        $this->master = $master;
    }
  
  
  
  
  //begin main function to parser
	public function parse($scenario, $id_scenario) 
	{
   
	  
	  
	    $xml = simplexml_load_string($scenario);
	    if($xml !== false)
	    {
	      
		    if(in_array($xml['version'], $this->supported_xml_version)) //suported_xml_version tem xml[version]? ou versão=2.0?
	      {
          $isGetIMEI=false;
          // for a simmple xml, without Wib tag
		    	if($xml->content->{'generic-scenario-xml'}) 
		    	{
		    		$isGetIMEI = false;
			    	$s=0;
			    	
			    	foreach($xml->content->{'generic-scenario-xml'}->children() as $type=>$d) 
			    	{

			    		switch($type) 
    			    		{
    			    			case "display-text":
    			    				$this->add("promotional_text", $this->personalized_text($d, $d->text));
    			    				$this->add("operation_type", "display_text");
    			    				$s++;
    			    				break;
    			    			case "inbox-sms":
    			    				$this->add("promotional_text", $this->personalized_text($d, $d->{'inbox-sms-message'}->text));
    			    				$this->add("operation_type", "inbox_sms");
    			    				$s++;
    			    				break;	
    			    			case "get-input":  
    			    				$this->add("promotional_text", $d->{'question-text'}->text);
    			    				$this->add("operation_type", "get_input");
    			    				$s++;
    			    				break;
    			    			case "select-item":
    			    				$this->has_menu = 1;
    			    				$j=1;
    			    				$ret = "";
    
    			    				foreach($d->{'question-text'} as $id=>$v) {
    			    					$ret .= $j.") ".$v->text." ";
    									$j++;
    			    				}
    
    			    				foreach($d->item as $id=>$v) {
    			    					$ret .= $j.") ".$v->text." ";
    									$j++;
    			    				}
    			    				$this->add("promotional_text", $ret);
    			    				$this->add("operation_type", "menu");
    			    				$s++;
    			    				break;
    			    			case "send-sms":
    			    				$this->short_code = $d->{'target-tpda'};
    			    				$this->keyword = $d->message;
    			    				$this->add("short_code_description", "SCREEN ".$s.": ".$d->{'target-tpda'});
    			    				$this->add("keyword_description", "SCREEN ".$s.": ".$d->message);		    				
    			    			case "questionnaire":
    			    				if($d['include-imei-info'] == "true") {
    			    					$isGetIMEI = true;	
    			    				}
    			    				break;
    			    			case "go-to":
    			    				break;
    			    			case "metric":
    			    				break;
    			    		}

			    	}
            // if don't exist a promotional text
			    
			    } else {
			    	$this->log("warn", "Scenario ".$id_scenario." is not a generic-scenario-xml");			    	
			    }
			    	//end rotine to parser a simple xml

          //begin routine to parser a xml with wib tag
			    //em construção
        
				if($xml->content->{'wib-scenario-xml'})
					{
						$tagWib=$scenario;
						$dato=$this->getTag($scenario,'wib-scenario-xml');
						if(stristr($dato[0]->container,'<![CDATA['))
						{
						  $dato=explode('<![CDATA[', $dato[0]->container);
						  $dato=explode(']]',$dato[1])[0];
						}
            if(!is_array($dato))
						$cards=$this->getTag($dato,'card');
            else
            $cards=$this->getTag($dato[0]->container,'card');
						$variables= array();
            $ordercards= array();
            $cardsOrdened=array();
            $caminho=array(); // ou -> ou :
            $nivel=0;
            if(is_array($cards))
						foreach($cards as $card)
						{
              if(array_key_exists('id',$card->atributes))
              {
                //echo('existe');
             // $card->id=$card->atributes['id'];
              if($card->dad==null)
                  $card->dad=$nivel;

						  if(stristr($card->container, '<setvar'))//variaveis
						  {
						    $_definesvars=$this->getTag($card->container,'setvar');
						    
						    foreach($_definesvars as $_var){
						      
						       $variables[$_var->atributes['name']]=$_var->atributes['value'];
						    }
						    
						  }
              //echo("\xa\xa".$card->atributes['id']."\xa\xa");
              /* if(stristr($card->container, '<select'))//variaveis
						  {
						    $_definesvars=$this->getTag($card->container,'select');
						    
						    foreach($_definesvars as $_var){
						      
						       $variables[$_var->atributes['name']]=$_var->atributes['title'];
						    }
						    
						  }*/ //em caso de emegencia, descomentar
						  if(array_key_exists('id',$card->atributes))
						  if($card->atributes['id']!=='END')
						  {
    						  if(stristr($card->container,'href=')==true) //destino pode ser go ou test
    						  {
                   // echo("\xa\xa\xa\xa\xa".$card->atributes['id']."\xa\xa\xa\xa");
    						      $dest=$this->getTag($card->container,'go');
    						      if($dest)//vai pra esta tag
    						          {
    						          
                            if(!in_array($card->atributes['id'],$ordercards) and $card->atributes['id']!=='END')
                            {
                                
                                array_push($caminho,'->');
                                array_push($ordercards,$card->atributes['id']);
                            }
    						          }else
    						              {
                                
    						                $dest=$this->getTag($card->container,'test');

                                if(!in_array($card->atributes['id'],$ordercards)){
    						                        //$caminho.=' '.$card->atributes['id'].' ->';
                                        array_push($caminho,'->');
                                        array_push($ordercards,$card->atributes['id']);
                                }
                                if(is_array($dest)){
    						                foreach($dest as $ref)
                                  if(!in_array(substr($ref->atributes['href'],1),$ordercards) and ' '.substr($ref->atributes['href'],1).' '!==' END '){
    						                    //$caminho.=' '.substr($ref->atributes['href'],1)." :";
                                    array_push($caminho,':');
                                    array_push($ordercards,substr($ref->atributes['href'],1));

                                  }
                                  }
                                  else("\xA o bug tá no card ".$card->atributes['id']);

                              }                              
    						    }else{ //possivel onpick
                          $dest=$this->getTag($card->container,'option');
                          if(array_key_exists('id',$card->atributes))
                          if(!in_array($card->atributes['id'],$ordercards))
                              array_push($ordercards,$card->atributes['id']);
                            if(is_array($dest))
                               foreach($dest as $ref)
                              {
                            //echo("\xA\xA".substr($ref->atributes['onpick'],1)."\xA");
                               if(!in_array(substr($ref->atributes['onpick'],1),$ordercards))                      {
                               array_push($ordercards, substr($ref->atributes['onpick'],1));
                             }
                          }

                    }

						  }	 
						  
						 $nivel++; 
             $flag=false;
            } 
            else
            $flag=true;
						}


           if(!$flag)
           {
               array_push($ordercards, 'END');
           }
            else{
                $cardsOrdened=$cards;
                
            }
            
            
           // foreach($ordercards AS $OC) print_r("->".$OC);
            
                      
            $i=0;
            if($flag)
            while($i!=count($ordercards))
            {
              /*print_r($cards[$i]->atributes['id']."\xa");
              print_r($ordercards[$i]."\xa\xa");
              print_r("cards".count($cards)."\xa");
              print_r("ordercards".count($ordercards)."\xa");*/
              if(is_array($cards))
              foreach($cards as $c)
              {
                if($ordercards[$i]==$c->atributes['id']){
                    array_push($cardsOrdened,$c);
                    break;
                }
                  
              }
             // print_r(count($cardsOrdened));
            $i++;
            
            } 

           /* foreach($cardsOrdened as $co){
                print("\xa".$co->atributes['id']."\xa\xa");
                print($co->container."\xa\xa");
                }*/

            if($this->promotional_text=="hello world")
            {
                  $this->promotional_text="";
                  $this->operation_type="";
            }
             $screen=0; 
            
            //cards ordenados por ordem de chamada
            if(is_array($cardsOrdened))
            foreach($cardsOrdened as $co)
            {

              $ps='';
              $ps=$this->getTag($co->container,'p');

              if($ps!==null)
                  {
                    if(is_array($ps))
                      foreach($ps as $p)
                      {                    
                        
                        if(substr(trim($p->container),0,1)!=='<'){ 
                          if(substr(trim($p->container),0,2)!=='$('){
                              $this->promotional_text.=$p->container.'->';
                              $this->add("operation_type", "display_text");
                              $screen++;
                          }   
                          else //variaveis
                            {
                             
                              //echo("\xa\xa entra aqui \xa\xa");
                              if(stristr($p->container,'<'))
                                $text=explode('<',$p->container)[0];
                              $text=explode('$(',$text);
                              for($i=1;$i<=count($text);$i++)
                                  {
                                    $indice=substr($text[$i],0,-1);
                                    $this->promotional_text.=$variables[$indice].' -> ';
                                    $this->add("operation_type", "display_text");
                                    $screen++;
                                  }
                            }
                            
                        
                            }else
                              {
                                //trata tags dentro d <p>
                                $plugin='';
                                $plugins=$this->getTag($co->container,'plugin');
                                if(is_array($plugins))
                                  foreach($plugins as $plugin)
                                  {
                                    $text=$plugin->atributes['params'];
                                    $text=explode('$(',$text)[2];
                                    $text=substr($text,0,-1);
                                    $text=$variables[$text];
                                    $this->promotional_text.=$text.' -> ';
                                    $this->add("operation_type", "display_text");
                                    $screen++;

                                  }
                                $input='';
                                $inputs=$this->getTag($co->container, 'input');
                                if(is_array($inputs))
                                  foreach($inputs as $input)
                                  {
                                      $text=$input->atributes['title'];
                                      if(substr(trim($text),0,2)=='$(')
                                        {
                                            $text=explode('$(',$text)[1];
                                            $text=substr(trim($text),0,-1);  
                                            if(!stristr($this->promotional_text, $variables[$text].' : '))
                                            {
                                              $this->promotional_text.=$variables[$text].' : ';
                                              $this->add("operation_type", "get_input");    
                                              $screen++;                                   
                                            }
                                        }else{
                                          $this->promotional_text.=$text.' : ';
                                          $this->add("operation_type", "get_input");
                                        }
                                  }
                                $sends=$this->getTag($co->container,'sendsm');
                                if(is_array($sends))
                                  foreach($sends as $send)
                                  {
                                     $dest=$this->getTag($send->container,'destaddress');
                                     if(is_array($dest))
                                     foreach($dest as $d)
                                     {
                                       $text=$d->atributes['value'];
                                       if(!stristr($this->short_code_description, 'Screen '.$screen.' : '.$text))
                                       {
                                          $this->short_code=$text;
                                          $this->short_code_description.='Screen '.$screen.' : '.$text.' -> ';
                                       }
                                        if(substr($this->short_code_description,0,1)=='0')
                                        {
                                          //$this->short_code=substr($this->short_code,1);
                                          $this->short_code_description=substr($this->short_code_description,1);
                                        }
                                     }
                                     $usersdata=$this->getTag($send->container,'userdata');
                                     if(is_array($usersdata))
                                     foreach($usersdata as $ud)
                                     {
                                       $text=$ud->container;
                                       if(stristr($text,'$('))///variavel
                                       {
                                          $text=explode('$(',$text);
                                          $varHex=$text[0];
                                          for($h=1;$h<count($text);$h++)
                                          {
                                            $variavel=$text[$h];
                                            if(stristr($variavel,')'))
                                            {
                                               $aux=explode(')',$variavel);
                                            }  
                                              if(array_key_exists($aux[0] , $variables))       
                                                $varHex.=$variables[$aux[0]];
                                              $varHex.=$aux[1];                            
                                             
                                          }
                                          if($this->keyword!='')
                                            {
                                              $this->keyword.=' -> ';
                                              $this->keyword_description.=' -> ';
                                              
                                            }

                                          $this->keyword.= $varHex;
                                          $this->keyword_description.='Screen '.$screen.' : '.$varHex;

                                       }else
                                            {
                                              if($this->keyword!='')
                                               {
                                                 $this->keyword.=' -> ';
                                                 $this->keyword_description.=' -> ';
                                               }
                                               $this->keyword.=$ud->container;  
                                               $this->keyword_description.='Screen '.$screen.' : '.$ud->container;
                                            }
                                     }


                                  }
                                
                                $selects=$this->getTag($co->container,'select');
                                
                                if(is_array($selects))
                                  foreach($selects as $select)
                                  {
                                    $text=$select->atributes['title'];
                                    if(substr($text,0,2)=='$(')
                                    {
                                      $text=explode('$(',$text)[1];
                                      $text=explode(')',$text)[0];
                                      $text=$variables[$text];
                                    }
                                    $this->promotional_text.= $text.' : ';
                                    $options=$this->getTag($select->container,'option');
                                    $numop=1;
                                    if(is_array($options))
                                      foreach($options as $op)
                                      {
                                        $text=$op->container;
                                        if(substr($op->container,0,2)=='$(')
                                        {
                                          $text=explode('$(',$op->container)[1];
                                          $text=explode(')',$text)[0];
                                          $text=$variables[$text];
                                        }
                                        $this->promotional_text.=$numop.')'.$text.' ';
                                        $numop++;
                                      }
                                    $screen++;
                                    $this->add("operation_type","menu");
                                  }
                              }
                        }
                        else
                          {
                            //não é um vetor
                          }
                      
                   
  
  
                  }
              $send=$this->getTag($co->container,'sendsm');
              if($send!==null)
                {
                   // nunca cai (espero)
                }
              
              

            }
         

            ///limpando lixo
           // $this->promotional_text=substr($this->promotional_text,0,-3);
            //$this->short_code=substr($this->short_code,0,-3);
            $this->short_code_description=substr($this->short_code_description,0,-3);


            
					
					}

          /*/*/	
          	if(strlen($this->promotional_text) == 0 && strlen($this->keyword) == 0) 
  			    	{
  			    		if($isGetIMEI) 
  			    		{
  			    			$this->promotional_text = "Get IMEI Scenario";
  			    		} else {
  				    		$this->log("warn", "Scenario ".$id_scenario." has empty message");
  				    	}
  			    	}
           /* echo("\xa promotional_text=  ".$this->promotional_text."\xa");  
            echo("\xa operation_type=  ".$this->operation_type."\xa");
            echo("\xa short_code=  ".$this->short_code."\xa");
            echo("\xa short_code_description=  ".$this->short_code_description."\xa");
            echo("\xa keyword=  ".$this->keyword."\xa");
            echo("\xa keyword_description= ".$this->keyword_description."\xA");*/

						
						
			//	if($xml->content->{'wib-scenario-xml'}){ //pra outro browser futuro
				  
				//}
		    
	      } else  // if xml version is not compatible
	            {
		    	      $this->log("error", "Scenario ".$id_scenario." with version ".$xml['version']." not supported");
	              }
  	 }else // if this xml not exists
  	      {
	           $this->log("error", "Scenario ".$id_scenario." failed to parser");
	        }
	}
	//end main parser function
	

	public function splitTags($input, $tagname, $number = "none")
	{
	  
	  
	  $vetTags= array();
	  
	  if(!is_array($input))
          $ret=explode('<'.$tagname,$input);
          else
          $ret=$input;
	 
    if(count($ret)==1)
      return null;
      
  
//parte de 1 pq a string 0 eh vazia
		for($cont=1;$cont<count($ret);$cont+=1) //capture all tags input
		{ 
		  
		  $k=$cont;
		 
		  if($number!=="none")
		  {
		    $k=$number+1;
		   
		  }
		  
		   $tagparser= new tag();
		   $tagparser->name=$tagname;
		   
		 if(stristr($ret[$k],'"'))//simples ou dupla (atributos)
		 {      
		        $atributes=true;
            $aux=explode('"',$ret[$k]);
            
    		    for($l=0;$l<count($aux);$l+=2){
    		          if(stristr($aux[$l],'>'))
    		          {
    		            if($l==0)
    		               $atributes=false;
    		            if(explode('>',$aux[$l])[0]!=='/')
    		            {
    		              $tagparser->simple=false;
    		            }
    		             break;
		              } 
		          }
		 }else{ //sem atributos
		   $aux=explode('>',$ret[$k]);
		   if(substr($aux[0],-1)!=='/')
		      $tagparser->simple=false;
		  $atributes=false;
		   
		 }
		 
		 $size=1;
		  //pegar atributos
		  if($atributes)//averiguar
    	 {
            $aux=explode('"',$ret[$k]);
            //$size+=count($aux)-1;
            $name='';
    		    $value='';
             //trata o caso se existir algum '>' que não seja fechamento de tag
            for($l=0;$l<count($aux);$l++)
            {   
              if($l%2==0){//posicao par estao os nome
                  if(!stristr($aux[$l],'>')) //ainda náo acabou
                  {
                      $size+=strlen($aux[$l])+2;
                      $name=explode('=',$aux[$l]);
                      $name=explode(' ',$name[0]);
                      $name=$name[count($name)-1];// no do atributo
                  }else{//fim da tag
                          $size+=strlen(explode('>',$aux[$l])[0])+1;
                          break;
                  }   
                      
              }else{//valores
                    $value=$aux[$l];
                    $tagparser->add_Atribute($name,$value);
                    $size+=strlen($aux[$l]);
              }
              
                   
            }         
         $size-=1;             
    	 }
    	 
    	 
    	 if(!$tagparser->simple)//nao eh simples, logo, tem container
    	 {
    	     $container=substr($ret[$k], $size);
    	     $aux=explode('</'.$tagname,$container);
    	     $container=$aux[0];
    	     $tagparser->container=$container;
 
    	 }

     if($number!=="none"){
       return $tagparser;
     }
        
        
  	$vetTags[$k-1]=$tagparser;	   
		unset($tagparser);
		  
		} // fim do for de pegar cada tag

		return $vetTags;
	}	

	
	//function to get a array or only one especific tag 
	//if the last parameter don't was passed, the function returns a tag's array
	//if input don't has the tag defined, return false
	//if the parameter tagname is "", so is executed the function geting all tags of the first level

	public function getTag($input, $tagname="", $number = "none") {
	   $vetTags= array();
		 $ret = "";
    
    ///if(is_array($input)) exit('claytonnnnn');
	  if($tagname!==""){ //deve pegar só a tag especificada
	    if(!stristr($input,'<'.$tagname))
	        return false;
	        else{
	        	$ret=explode('<'.$tagname,$input);
            $vet= array();
            foreach($ret as $tag)
            {     
                switch(substr($tag,0,1)){
                        case " ": //espaço
                        case "  ": //tab
                        case "/":
                        case ">":
                            array_push($vet,$tag);
                            break;
                        default:
                          $last=''.array_pop($vet).'<'.$tagname.$tag;
                          array_push($vet,$last);
                          break;
                }             
            }
            
            
	        	return $this->splitTags($vet,$tagname,$number);//passando um array
	        }
	    }
	    else //só entra se for pra pegar as tags do mesmo nivel
	    {
	      $text=$input;
	      $i=0; // possivel numero da tag
	      
	   
	     while($text!=='')
	      {
	         $ret=explode('<',$text);
	         if(stristr($ret[1],' ')){
	            $ret=explode(' ',$ret[1]);
	            if(stristr($ret[0],'>')){
	              $ret=explode('>', $ret[0]); 
	            }
	         }
	         else
	         $ret=explode('>',$ret[1]);
	         $ret=$ret[0]; //nome da primeira tag
	         
	         if(stristr($ret,'/')){
	            $ret=explode('/',$ret)[0];
	          }
	         
	         $vetTags[$i]= $this->splitTags($text,$ret,0);
	         
	         if($number!=="none" and $number==$i)
	         {
	           return $vetTags[$i];
	         }
	            
	         
	         if($vetTags[$i]->simple)//ultima tag capturada é simples
	            {
	               $ret=explode('"',$text); //só p/ garantir q n é string
	               $contador=0;
	               
      	        for($j=0;$j<count($ret);$j+=1)//se estiver em posição impar tá entre parenteses
      	         {
      	            if(stristr($ret[$j],'/>') and $j%2==0)
      	            { //fim da tag
      	               $aux=explode('/>',$ret[$j]);
      	               $kuo=$contador+strlen($aux[0])+2;
      	               
      	               $text=substr($text,$kuo);
      	               
      	               break;
      	            }
      	           $contador+=strlen($ret[$j])+1;//add o caracter(")
      	         }
   
	            }
	            else //tag dupla
	            {
	               $ret=explode('"',$text); //só p/ garantir q n é string
	               $contador=0;
	             
      	         for($j=0;$j<count($ret);$j+=1)//se estiver em posição impar tá entre parenteses
      	         {
      	            if(stristr($ret[$j],'</'.$vetTags[$i]->name.'>') and $j%2==0 )
      	            {
      	               $aux=explode('</'.$vetTags[$i]->name,$ret[$j]);
      	               $kuo=$contador+strlen($aux[0])+strlen('</'.$vetTags[$i]->name.'>');
      	               $text=substr($text,$kuo);
      	               $wespace=trim($text);
      	               if(strlen($wespace)==0)
      	                  $text=$wespace;
      	               break;
      	            }else{
      	               $contador+=strlen($ret[$j])+1;
        
      	            }
      	         }
	              
	            }
	         $i++;

	      }

	      return $vetTags;
	    }
	}


  //add a value in string to concatenate in patter
	private function add($var, $value) {
		if(strlen($this->{$var})==0) {
			$this->{$var} = $value;
		} else {
			$this->{$var} .= ' -> '.$value;
		}
	}


	private function personalized_text($d, $obj) {
		$ret = "";
		$j=0;
	    foreach($obj as $id=>$v) {
			$ret .= $v;
			if($d->{'personalized-text'}[$j]) {
				$ret .= "{".$d->{'personalized-text'}[$j]->{'field-name'}.",".$d->{'personalized-text'}[$j]->{'default-value'}."}";
			}
			$j++;
		}
		if(strlen($ret) == 0 && $d->{'personalized-text'}) {
			$ret = "{".$d->{'personalized-text'}->{'field-name'}.",".$d->{'personalized-text'}->{'default-value'}."}";
		}

		return $ret;
	}

 private function hexToStr($hex)
{
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2)
    {
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}

	private function debug($arr) {
		print "<pre>";
	    print_r($arr);
	  	print "</pre>";		
	}

	private function log($type, $msg) {
		if($type == "error") {
			$this->failed = true;
		}
	/*$this->master->connect_db_oracle("stats"); 
		$this->master->log($type, $msg);		*/
	}
}

?>
