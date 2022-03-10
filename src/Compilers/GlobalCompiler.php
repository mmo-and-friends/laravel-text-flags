<?php

namespace MmoAndFriends\LaravelTextFlags\Compilers;

use Illuminate\Support\Arr;
use MmoAndFriends\LaravelTextFlags\TextFlags;

class GlobalCompiler implements ParserContract
{

    /**
     * Handler
     * 
     * @var TextFlags
     */
    protected $handler;
    protected $regExpModels     = [];
    protected $detectedFlags    = [];
    public const RESERVED_WORDS = [];

    public function __construct(TextFlags &$handler)
    {
        $this->handler = $handler;
    }
   
    /**
     * Build RegExp for each model key
     * 
     * /\{model:(.*?)\}/
     * @return void
     */
    public function buildRegExp()
    {
        foreach ($this->handler->modelKeys as $modelName) {
            $this->regExpModels[$modelName] = "/\\{$this->handler->getFlagWrap(0)}{$modelName}:(.*?)\\{$this->handler->getFlagWrap(1)}".'/';
        }
    }

    /**
     * Match all from subject
     *
     * /\{.*?\}/
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

            // Matching

            $matches = [];

            // If model is not dected yet create an array for store the matches

            if (!isset($this->detectedFlags[$modelName])) {
                $this->detectedFlags[$modelName] = [];
            }

            preg_match_all($pattern, $subject, $matches);
            
            // If not matches continue 

            if (empty($matches[0])) {
                continue;
            }

            //Delete duplicated values

            $matches[0] = array_unique($matches[0]); // pattern
            $matches[1] = array_unique($matches[1]); //subject

            //
            // Adding to the model the detected flags
            //
            $this->detectedFlags[$modelName][] = [
                'pattern' => $matches[0],
                'subject' => $matches[1]
            ];
        }
    }
  
    /**
     * Replace flags of the input subject
     * 
     * /\{attr:(.*?)\}/
     * @param boolean $reset Reset all attributes except the output
     * @return array|string
     */
    public function apply()
    {
        foreach ($this->detectedFlags as $modelName => $matches) {            
            foreach ($matches as $matchKey => $match) {
                foreach ($match['pattern'] as $patternKey => $pattern) {
                    $attribute    = $match['subject'][$patternKey];                    
                    $replaceValue = $this->handler->mixedGetValue($this->handler->models[$modelName], $attribute);
                    for ($outputKey=0; $outputKey < count($this->handler->output) ; $outputKey++) {                         
                        $this->handler->output[$outputKey] = preg_replace("/$pattern/", $replaceValue, $this->handler->output[$outputKey]);
                    }
                }       
            }
        }
    }


    /**
     *  Unset all array with key
     *
     * @param string|int $key
     */
    public function unsetAllOf($key)
    {
        unset($this->regExpModels[$key]);
        unset($this->detectedFlags[$key]);
    }

    /**
     * Reset all attributes
     *
     * @return void
     */
    public function reset()
    {
        $this->regExpModels  = [];
        $this->detectedFlags = [];        
    }
  

}