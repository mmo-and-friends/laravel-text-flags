<?php

namespace MmoAndFriends\LaravelTextFlags;

use Exception;
use Illuminate\Support\Arr;

class TextFlags
{
    public $models        = [];
    public $modelKeys     = [];    
    public $textsBag      = [];
    public $regExpModels  = [];    
    public $flagWrap      = ['{','}'];
    public $detectedFlags = [];
    public $output        = [];
    

    /**
     * Set the models where the values will be taken
     * 
     * [ 'provider_name' => array|collection|object]
     */
    public static function fill($models)
    {
        return (new self())->hydrate($models);
    }

    /**
     * Hydrate the models
     */
    public function hydrate($models)
    {
        foreach ($models as $modelName => $value) {
            $this->models[$modelName] = $value;
        }
        
        $this->modelKeys = array_keys($this->models);

        $this->buildRegExp();

        return $this;
    }

    /**
     * Read a text or array and get the flags that are present
     *
     * @param string\array[string] $text
     * @return \MmoAndFriends\LaravelTextFlags\TextFlags
     */
    public function read($text)
    {
        //'/\{.*?\}/
        //preg_match_all('/\{(.*?)\}/',$text, $matches); and without {}

        if (!is_string($text) && !is_array($text)) {
            $this->throwsAnError(__FUNCTION__, 'Expects a string or array of strings, ['.gettype($text).'] obtained.');
        }

        $this->textsBag[] = $text;        
        $this->textsBag   = Arr::flatten($this->textsBag);

        foreach ($this->textsBag as $key => $text) {
            $this->match($text);
        }
        
        return $this;
    }

    /**
     * Build RegExp for each model key
     * 
     * @return void
     */
    private function buildRegExp()
    {
         $this->flagWrap[1];

         // /\{model:(.*?)\}/
         // /\{'.$modelName.':'.'(.*?)\}/;

        foreach ($this->modelKeys as $modelName) {
            $this->regExpModels[$modelName] = "/\\{$this->flagWrap[0]}{$modelName}:(.*?)\\{$this->flagWrap[1]}".'/';
        }
    }


    /**
     * Match all from text
     *
     * @param string $text
     * @return void
     */
    private function match($text)
    {        
        if (empty($this->regExpModels)) {
            $this->throwsAnError(__FUNCTION__,'needs call to buildRegExp() method before');
        }

        foreach ($this->regExpModels as $modelName => $pattern) {

            //
            // If the $modelName has not a value to access, unset all
            //
            if (!in_array($modelName, $this->modelKeys)) {                
                $this->unsetAllOf($modelName);
                continue;
            }

            //
            // Matching
            //
            $matches = [];

            //
            // If model is not dected yet create an array for store the matches
            //
            if (!isset($this->detectedFlags[$modelName])) {
                $this->detectedFlags[$modelName] = [];
            }

            preg_match_all($pattern, $text, $matches);
            
            //
            // If not matches continue 
            //
            if (empty($matches[0])) {
                continue;
            }

            //Delete duplicated values

            //Flag and attribute {model:attr}
            $matches[0] = array_unique($matches[0]);
            //attribute
            $matches[1] = array_unique($matches[1]);

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
     * Replace flags of the input text
     * 
     * @param boolean $reset Reset all attributes except the output
     * @return array|string
     */
    public function apply($reset = true)
    {
        $this->fillDetectedFlags();

        $this->output = $this->textsBag;

        foreach ($this->detectedFlags as $modelName => $matches) {
            foreach (Arr::except($matches,'analyzed') as $matchKey => $match) {
                
                foreach ($match['pattern'] as $pKey => $pattern) {
                    $attribute =  $match['subject'][$pKey];
                    $realValue = $this->detectedFlags[$modelName]['analyzed'][$attribute];                    
                    foreach ($this->output as $outputKey => $text) {
                        $this->output[$outputKey] = preg_replace("/$pattern/", $realValue, $this->output[$outputKey]);
                    }
                } 
            }
        }
        
        if ($reset) {
            $this->reset();
        }

        if (count($this->output) == 1) {
            return $this->output[0];
        }

        return array_values($this->output);
    }


    /**
     *  Unset all array with key
     *
     * @param string|int $key
     */
    private function unsetAllOf($key)
    {
        unset($this->regExpModels[$key]);
        unset($this->detectedFlags[$key]);
    }


    /**
     * Fill the presentFlags with the real value
     * 
     * @return void
     */
    private function fillDetectedFlags()
    {
        // $defaultConfig = config('utils.flags.default');
        // $hiddenAttrForever = $defaultConfig['never_show'];
        foreach ($this->detectedFlags as $modelName => $matches) {

            foreach ($matches as $match) {                
                
                foreach ($match['subject'] as $attr) {
 
                    if (!isset($this->detectedFlags[$modelName]['analyzed'])) {
                        $this->detectedFlags[$modelName]['analyzed'] = [];
                    }

                    $this->detectedFlags[$modelName]['analyzed'][$attr] = $this->mixedGetValue(
                        $this->models[$modelName], $attr
                    );
                }
            }
        }
    }

  


    /**
     * Reset all attributes
     *
     * @return void
     */
    private function reset()
    {
        $this->models        = [];
        $this->modelKeys     = [];
        $this->textsBag      = [];
        $this->regExpModels  = [];
        $this->flagWrap      = ['{','}'];
        $this->detectedFlags = [];
    }
  


    /**
     * Get a value of an object, array, etc..
     * 
     * @return mixed
     */
    public function mixedGetValue($obj, $attribute, $default = null)
    {
        $_value = null;
        if (is_null($attribute)) {
            $_value = $default;
        }elseif (isset($obj[$attribute])) {
            $_value = $obj[$attribute];
        }else if (is_array($obj)) {
            $_value = Arr::get($obj, $attribute);
        }elseif(strpos($attribute, '.') !== false){
            $_value = object_get($obj, $attribute, $default); //array_reduce(explode('.', $attribute), function ($o, $attr) { return $o->$attr; }, $obj);
        }else{
            $_value = $obj->{$attribute};
        }
        return ($_value ?? $default);
    }


    /**
     * Throws an error
     * 
     * @throws \Exeption
     */
    private function throwsAnError($fnName, string $message, int $code = 1)
    {
        throw new Exception("TextFlags[{$fnName}] {$message}", $code);      
    }


}
