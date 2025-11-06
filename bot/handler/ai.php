<?php
header('Content-Type: text/html; charset=utf-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
date_default_timezone_set('America/Sao_Paulo');
ignore_user_abort(true);

include_once  'config.php';
include_once  'db.php';
include_once  'dictionary.php';
session_start();

env();



class Ai
{
    public function __construct()
    {
        global $link;
        $this->arraySaudacao = ['Bom dia', 'Boa tarde', 'Boa Noite', 'Oi', 'Olá','Tudo bem','Oi tudo bem'];
        $this->banned =  ['Bom dia', 'Boa tarde', 'Boa Noite', 'Oi', 'Olá','Tudo bem','Oi tudo bem','Como','como','um','exportar'
                        ,'Por favor','bom dia', 'boa tarde', 'boa noite', 'faço','para', ',','Oi', 'Ola','Tudo bem','Oi tudo bem','?', 'fazer a',
                            '!','como' ,'trocar','alterar','minha','consigo','minha',' do','rastreio', 'acesso','estou', 'não','conseguindo', 'gerar' , 'emitir'
                            ,'rastreamento',
                            'nâo'];

        $this->help = [   'exportar'=> '<img src="../help/exportar.png" alt="exportar"  style="width:90%">',
                           'excel'=> '<img src="../help/exportar.png" alt="exportar"  style="width:90%">',
                           'filtrar'=> '<img src="../help/busca.png" alt="filtrar" style="width:90%">',
                           'busca'=> '<img src="../help/busca.png" alt="filtrar" style="width:90%">',
                           'pesquisa'=> '<img src="../help/busca.png" alt="filtrar" style="width:90%">',
                           'nota fiscal'=> '<img src="../help/nfe.png" alt="filtrar" style="width:90%">',
                           'a nota fiscal'=> '<img src="../help/nfe.png" alt="filtrar" style="width:90%">',
                           'nfe'=> '<img src="../help/nfe.png" alt="filtrar" style="width:90%">',
                           'a nfe'=> '<img src="../help/nfe.png" alt="filtrar" style="width:90%">',
                           'danfe'=> '<img src="../help/nfe.png" alt="filtrar" style="width:90%">',
                           'senha' => '<img src="../help/senha.png" alt="alterar senha"  style="width:90%">',
                           'ajuda' => '<img src="../help/senha.png" alt="ajuda"  style="width:90%">',
                           'abrir chamado' => '<img src="../help/abrir_chamado.png" alt="abrir chamado"  style="width:90%">',
                           'novo chamado' => '<img src="../help/novo_chamado.png" alt="novo chamado"  style="width:90%">',
                            'manual' => '<img src="../help/manual.png" alt="manual"  style="width:90%">',

                     ];
        $this->link = $link;
        $this->handle();
    }

    function tirarAcentos($string){
        return preg_replace(array("/(á|à|ã|â|ä)/","/(Á|À|Ã|Â|Ä)/","/(é|è|ê|ë)/","/(É|È|Ê|Ë)/","/(í|ì|î|ï)/","/(Í|Ì|Î|Ï)/","/(ó|ò|õ|ô|ö)/","/(Ó|Ò|Õ|Ô|Ö)/","/(ú|ù|û|ü)/","/(Ú|Ù|Û|Ü)/","/(ñ)/","/(Ñ)/"),explode(" ","a A e E i I o O u U n N"),$string);
    }

    protected function saudacao() 
    {   
        $time = date("H");
        /* Set the $timezone variable to become the current timezone */
        $timezone = date("e");
        /* If the time is less than 1200 hours, show good morning */
        if ($time < "12") {
            return "Bom dia, ";
        } else
        /* If the time is grater than or equal to 1200 hours, but less than 1700 hours, so good afternoon */
        if ($time >= "12" && $time < "17") {
            return "Boa tarde, ";
        } else
    
        if ($time >= "19") {
            return "Boa noite, ";
        }
    }

    protected function containMessageCheck($string) {
        return $this->saudacao()."deculpe me não consegui encontrar ´{$string}´.";
    }

    protected function loadIntents(): void 
    {
        $file = './intents/intents.json';
        if (!file_exists($file)) {
            static::$intents = [];
            return;
        }
        $json = file_get_contents($file);
        static::$intents = json_decode($json, true);
        if (!is_array(static::$intents)) static::$intents = [];
    }

    protected function matchIntent(string $text): ?string
    {
        $this->loadIntents();

        $normalized = mb_strtolower(trim($text));
        // direct exact and contains matching
        foreach ( static::$intents as $intent) {
            if (empty($intent['examples'])) continue;
            foreach ($intent['examples'] as $ex) {
                $exNorm = mb_strtolower($ex);
                // exact match
                if ($normalized === $exNorm) return $intent['id'];
                // contains word
                if (mb_strpos($normalized, $exNorm) !== false) return $intent['id'];
                // basic regex support if example contains regex delimiter e.g. /when.*open/
                if (strlen($exNorm) > 2 && $exNorm[0] === '/' && substr($exNorm, -1) === '/') {
                    $pattern = $exNorm;
                    if (@preg_match($pattern, $normalized)) return $intent['id'];
                }
            }
        }
        // no match
        return 'fallback';
    }

   

    protected function handleMessage(string $sessionId, string $text)
    {
            // 1. Try rule-based matching
            $match = $this->matchIntent($text);
            if ($match !== null && $match !== 'fallback') {
                $response = $this->chooseResponse($match);
                return $this->buildResponse($response, $match);
            }
            // 3. Fallback intent
            $dataLookup = $this->dataLookup($text);
            return $this->buildResponse($dataLookup, 'greet');
    }

    function dataLookup($text)
    {
        $oldneedle =trim(strtolower(str_ireplace($this->banned, '', $text)));
        if($oldneedle != '' )
        {
            $SQL = "select response from intents where keywords like '%".$oldneedle."%' OR alias like '%".$oldneedle."%'";
            $result = mysqli_query($this->link, $SQL);
            if(mysqli_num_rows($result) > 0)
            {
                $row = mysqli_fetch_assoc($result);
                return $row['response'];
            }
            else return $this->dictionarySearch($text, $oldneedle);
        } 
        else $this->chooseResponse('fallback');
    }


    function dictionarySearch($text, $oldneedle)
    {
        $needle = new Dictionary()->findBestMatch($oldneedle);

        if($needle != NULL)
        {
            $this->setQuery(str_replace($oldneedle, $needle, $this->getQuery()));
            static::$needle = $needle;

            $SQL = "select response from intents where keywords like '%".$needle."%' OR alias like '%".$needle."%'";
            $result = mysqli_query($this->link, $SQL);
            if(mysqli_num_rows($result) > 0)
            {
                $row = mysqli_fetch_assoc($result);
                return $row['response'];
            }
            else
            {
                return $this->searchSinonimos($text, $oldneedle);
            }   
        }
         return $this->searchSinonimos($text, $oldneedle);
    }


    function searchSinonimos($text, $needle)
    {
        $synonyms = new Dictionary()->findSynonyms($needle);
        if($synonyms != NULL)
        {
            $this->setQuery(str_replace($needle, $synonyms, $this->getQuery()));
        
            $SQL = "select response from intents where keywords like '%".$synonyms."%' OR alias like '%".$synonyms."%'";
            $result = mysqli_query($this->link, $SQL);

            if(mysqli_num_rows($result) > 0)
            {
                $row = mysqli_fetch_assoc($result);
                return $row['response'];
            }
            else
            {
                return; #$this->containMessageCheck($needle);
            }   
        }
         return; #$this->containMessageCheck($needle);
    }

     protected function chooseResponse(string $intentId): string {
        $this->loadIntents();
        foreach ( static::$intents as $intent) {
            if ($intent['id'] === $intentId) {
                if (isset($intent['responses']) && count($intent['responses']) > 0) {
                    return $intent['responses'][array_rand($intent['responses'])];
                }
            }
        }
        // default
        return "Sorry, I didn't understand that.";
    }

    function buildResponse( $text , string $intent): array 
    {
        if(!is_null($this->getGreet()))
        {
            $text = $this->getGreet()." ".strtolower($text);
        }   
        $this->session( $this->getQuery() ,$text, $this->session);

        return [
            'session' => $this->session,
            'intent' => $intent,
            'text' =>$text,
        ];
    }


    function session(string $query, $response ,  $sessionId)
    {
        $this->setNeedle(trim(strtolower(str_ireplace($this->banned, '', $query))));  //retorno needle  = key action  

        $SQL = "insert into sessions (sessionId, meta, response,created_at) values ('".$this->session."','".$this->getNeedle()."','".@$response."','".date('Y-m-d H:i:s')."')
                on duplicate key update meta='".$this->getNeedle()."' , response = '".@$response."', updated_at = '".date('Y-m-d H:i:s')."'";
        mysqli_query($this->getLink(), $SQL);
    }

    function attemp( $text)
    {
        $SQL = "insert into attemps (sessionId, query, response,created_at) values ('".$this->session."','".$this->getQuery()."','".$text."','".date('Y-m-d H:i:s')."')";
        mysqli_query($this->getLink(), $SQL);
    }

    protected function setNeedle($needle)
    {
        static::$needle = $needle;
    }

    protected function getNeedle()
    {
        return static::$needle;
    }

    protected function setQuery($query)
    {
        $this->query = $query;
    }

    protected function getQuery()
    {
        return $this->query;
    }

    protected function setResponse($response)
    {
        $this->response = $response;
    }

    protected function getResponse()
    {
        return $this->response;
    }

    protected function setGreet($greet)
    {
        $this->greet = $greet;
    }

    protected function getGreet()
    {
        return $this->greet;
    }

    protected function setLink($link)
    {
        $this->link = $link;
    }

    protected function getLink()
    {
        return $this->link;
    }

    protected function handle()
    {
        $this->setQuery($_GET['query']);
        $this->session = $_GET['sessionId'];

        foreach($this->arraySaudacao as $key =>$value)
        {
            if(str_contains(strtolower($this->getQuery()), (strtolower($value)))) 
            {
                $this->setGreet($value.", ");
                break;
            }
        }

        $response = ($this->handleMessage($this->session,$this->getQuery()));

        #var_dump($this->getNeedle());
        #exit;
        #$this->isSystemModule($this->getNeedle()); 

        if(isset($this->help[$this->getNeedle()]))
        {
            $response['text'].= $this->help[$this->getNeedle()];
        }
        else
        {
            $needle = new Dictionary()->findBestMatch($this->getNeedle());
            if($needle != NULL)
            {
                $this->setNeedle($needle);
                 $response['text'].= $this->help[$needle];
            }
        }     


        if(!is_null( $response['text']))
        {
            if(str_contains($response['text'],'http:'))
            {
                echo json_encode(['status'=>200,'result'=>$response['text'],'href'=>$response['text']]);exit;
            }
            else echo json_encode(['status'=>200,'result'=>$response['text'],'href'=>null]);exit;
        }
        if(is_null( $response['text']) && is_null($this->getGreet()))
        {
           $this->attemp($this->getQuery());
           echo json_encode(['status'=>200,'result'=>'Desculpe me, eu não entendi isso. Você pode reformular?']);exit;
        }    
    }


    //@on dev mode do not call 
    protected function isSystemModule($module)
    {
        global $sgo;
        $systemModules = ['rastreamento','inventário','faturamento','chamados','dashboard'];
        $modulo = "";
        $is = false;
        for($i = 0; $i< sizeof($systemModules); $i++)
        {    
            similar_text($module,$systemModules[$i], $percent);
            if($percent  >= 70)
            {
                $is = true;
                $modulo =  $systemModules[$i];
                break;
            }  
        }

        $SQL = "SELECT * FROM users WHERE id=".$this->session;
        $result = mysqli_query($sgo, $SQL);
        $user = mysqli_fetch_assoc($result);

        if($is)
        {
            if(isset($user[$modulo]) || isset($user[new Dictionary()->toEnglish($modulo)]))
            {
                if(@$user[$modulo] == '1' || $user[new Dictionary()->toEnglish($modulo)] == '1')
                {
                    echo json_encode(['status'=>200,'result'=>$this->getGreet().' consultei o seu cadastro <br> você possui acesso no módulo <br> se persisitr por favor contate o admin.','href'=>null]);exit;
                }
                else
                {
                    echo json_encode(['status'=>200,'result'=>$this->getGreet().' você não tem acesso ao módulo '.$modulo,'href'=>null]);exit;
                }    
            } 
            else
            {
                echo json_encode(['status'=>200,'result'=>$this->getGreet().' desculpe me contate o admim, não encontrei seu cadastro','href'=>null]);exit;
            }
        }         

  
    }

    protected static $intents;
    protected static $needle;
    protected $arraySaudacao;
    protected $greet;
    protected $banned;
    protected $query;
    protected $help;
    protected $link;
    protected $session;

}
return new Ai();