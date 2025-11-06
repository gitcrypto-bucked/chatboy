<?php



class Dictionary
{
    public function __construct()
    {
    }

    public function findBestMatch($query)
    {
        
        for($i = 0; $i< sizeof($this->dictionary); $i++)
        {    
            similar_text($query,$this->dictionary[$i], $percent);
            if($percent  >= 80)
            {
                return $this->dictionary[$i];	 exit;
            }  
            if($percent  >= 60)
            {
                return $this->dictionary[$i];	 exit;
            }  
        }
    
    }


    public function findSynonyms($query)
    {
        foreach($this->synonyms as $key => $value)
        {
            similar_text($query,$key, $percent);
            if($percent  >= 80)
            {
                return $value;	 exit;
            }  
            if($percent  >= 60)
            {
                return $value;	 exit;
            } 
        }    
    }

    public function toEnglish($query)
    {
        foreach($this->english as $key => $value)
        {
            similar_text($query,$key, $percent);
            if($percent  >= 80)
            {
                return $value;	 exit;
            }  
        }    
    }

    private $dictionary =  ['excel','exportar','importar','alterar','senha','filtros', 'nfe', 'nota fiscal', 'danfe',
                            'filtrar','chamados','abrir chamado', 'abrir', 'editar','alterar','limpar','rastrear','rastreamento' ,'ajuda','manual'];

    private $synonyms  = ['xlsx' =>"excel",
                          'xls' =>"excel",
                          'csv' =>"excel",

                          'nfe'=>'nota fiscal',
                          'danfe'=>'nota fiscal'
                          
                          
                        ];

    private $english = ['rastreamento'=>'tracking',
                         'inventário'=>'inventory',
                         'faturamento'=>'invoice',
                         'chamados'=>'tickets',
                         'dashboard'=>'dashboard'];
}


// function findBestMatch($query)
// {
//     global $pspell;
    
//     for($i = 0; $i< sizeof($pspell); $i++)
//     {    
//          similar_text($query,$pspell[$i], $percent);
//          if($percent  >= 88.888888)
//          {
//             return $pspell[$i];	 exit;
//          }  
//     }
  
// }

// $pspell = ['excel','exportar','importar','alterar','senha','filtros', 'filtrar','chamados','abrir chamado', 'abrir', 'editar','alterar','limpar','rastrear','rastreamento'];