<?php

namespace MmoAndFriends\LaravelTextFlags\Compilers;

use MmoAndFriends\LaravelTextFlags\TextFlags;

class EachCompiler implements ParserContract
{
    /**
     * Handler
     * 
     * @var TextFlags
     */
    protected $handler;
    protected $regExpModels     = [];
    protected $detectedFlags    = [];
    public const RESERVED_WORDS = [
        'each',
        'each_k',
        'each_v',
        'end_each'
    ];

    public function __construct(TextFlags &$handler)
    {
        $this->handler = $handler;
    }

    public function input()
    {
        $matchGroups  = [];
        //$matchGroups[0] // patron completo
        //$matchGroups[1] // models array_access
        //$matchGroups[2] // the each content

        preg_match_all("/\{each:(.*?)\}([^`]*?)\{end_each:(.*?)\}/", $this->subject,$matchGroups);

        foreach ($matchGroups[1] as $matchKey => $modelName) {        
            if (!isset($matchGroups[$modelName])) {
                $match[$modelName] = [];
            }

            $matchGroups[$modelName][] = [
                'model_name'   => $matchGroups[1][$matchKey],
                'full_pattern' => $matchGroups[0][$matchKey],
                'each_content' => $matchGroups[2][$matchKey],
                'render'       => '',
            ];
            unset($matchGroups[0][$matchKey]);
            unset($matchGroups[1][$matchKey]);
            unset($matchGroups[2][$matchKey]);
        }

        // 
        // Unset the numeric arrays, preserver the associative keys
        // 
        $matchGroups = array_filter($matchGroups, function($key){return !is_numeric($key);}, ARRAY_FILTER_USE_KEY);

        // In some cases the pattern for an array_access appears many times
        // We need group by array_access and render every group

        foreach ($matchGroups as $matchGroupsKey => $mGroup) {
            foreach ($mGroup as $mGroupKey => &$match) {            
                $eachAttributes = [];
                preg_match_all("/\{each_v:(.*?)\}/", $match['each_content'], $eachAttributes);
    
                // Looping the array_access models

                foreach ($this->models[$match['model_name']] as $modelInstance) {                    
                    
                    // Replacing the {each_v:attr}                    
                    $eachPartOfTheRender = $match['each_content'];
    
                    foreach ($eachAttributes[1] as $eachAttributesKey => $attr) {
                        $pattern      = '/'.$eachAttributes[0][$eachAttributesKey].'/';
                        $realValue    = mixed_get_value($modelInstance, $attr);
                        /**
                         * join to rendered string
                         */
                        $eachPartOfTheRender = preg_replace($pattern, $realValue, $eachPartOfTheRender);
                    }                    
                    // Add the model instance render to the master render
                    $match['render'] .= $eachPartOfTheRender;
                }
                // Replace the each syntax for rendered string;
                $this->subject = preg_replace("/\{each:".$match['model_name']."\}([^`]*?)\{end_each:".$match['model_name']."\}/", $match['render'], $this->subject, 1);       
            }
        }
        return $this->subject;
    }


    /**
     * Build RegExp for each model key
     * 
     * /\{each:(.*?)\}([^`]*?)\{end_each:(.*?)\}/
     * @return void
     */
    public function buildRegExp()
    {
        $leftWrap  = $this->handler->getLeftWrap();
        $rightWrap = $this->handler->getRightWrap();

        foreach ($this->handler->modelKeys as $modelName) {         
            $this->regExpModels[$modelName] = "/\\{$leftWrap}each:{$modelName}\\{$rightWrap}([^`]*?)\\{$leftWrap}end_each:{$modelName}\\{$rightWrap}/";
        }
    }

    /**
     * Match all from subject
     *
     * /\{each:(.*?)\}([^`]*?)\{end_each:(.*?)\}/
     * @param string $subject
     * @return void
     */
    public function match($subject)
    {
        if (empty($this->regExpModels)) {
            $this->handler->throwsAnError(__FUNCTION__,'needs call to buildRegExp() method before');
        }
        
        foreach ($this->regExpModels as $modelName => $pattern) {
            // If the $modelName has not a value to access, unset all

            if (!in_array($modelName, $this->handler->modelKeys)) {                
                $this->unsetAllOf($modelName);
                continue;
            }

            $matchGroups = [];
            preg_match_all($pattern, $subject, $matchGroups);
            
            if (empty($matchGroups[0])) {
                continue;
            }
            
            for ($matchGroupKey=0; $matchGroupKey < count($matchGroups[0]); $matchGroupKey++) {                 
                if (!isset($this->detectedFlags[$modelName])) {
                    $this->detectedFlags[$modelName] = [];
                }

                $this->detectedFlags[$modelName][] = [
                    'pattern'      => $matchGroups[0][$matchGroupKey],
                    'each_content' => $matchGroups[1][$matchGroupKey],
                    'render'       => '',
                ];
            }
        }
    }

    /**
     * Replace flags of the input subject
     * 
     * /\{each_v:(.*?)\}/
     * 
     * @param boolean $reset Reset all attributes except the output
     * @return array|string
     */
    public function apply()
    {
        $leftWrap  = $this->handler->getLeftWrap();
        $rightWrap = $this->handler->getRightWrap();
        
        // Looping the detected flags
        foreach ($this->detectedFlags as $modelName => $matchGroup) {        
            
            //Looping the match group from a model

            foreach ($matchGroup as $matchGroupKey => &$match) {     

                $eachAttributes = [];

                preg_match_all("/\\{$leftWrap}each_v:(.*?)\\{$rightWrap}/", $match['each_content'], $eachAttributes);

                //Delete duplicated values
                $eachAttributes[0] = array_unique($eachAttributes[0]); // pattern
                $eachAttributes[1] = array_unique($eachAttributes[1]); //subject

                // Looping the array_access models
                foreach ($this->handler->models[$modelName] as $data) {                                        
                    // Replacing the {each_v:attr}
                    $eachPartOfTheRender = $match['each_content'];
    
                    foreach ($eachAttributes[1] as $eachAttributesKey => $attr) {
                        $pattern      = '/'.$eachAttributes[0][$eachAttributesKey].'/';
                        $realValue    = $this->handler->mixedGetValue($data, $attr);
                        // join to rendered string
                        $eachPartOfTheRender = preg_replace($pattern, $realValue, $eachPartOfTheRender);
                    }                    
                    // Add the model instance render to the master render                    
                    $match['render'] .= $eachPartOfTheRender;
                }
                
                // Replace the each syntax for rendered string;
                for ($outputKey=0; $outputKey < count($this->handler->output) ; $outputKey++) {                         
                    $this->handler->output[$outputKey] = preg_replace("/\\{$leftWrap}each:".$modelName."\\{$rightWrap}([^`]*?)\\{$leftWrap}end_each:".$modelName."\\{$rightWrap}/", $match['render'], $this->handler->output[$outputKey], 1);
                }
            }
        }
    }

    public function reset()
    {
        $this->regExpModels  = [];
        $this->detectedFlags = [];
    }

    public function unsetAllOf($key)
    {
        unset($this->regExpModels[$key]);
        unset($this->detectedFlags[$key]);
    }

}


