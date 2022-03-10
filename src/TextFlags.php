<?php

namespace MmoAndFriends\LaravelTextFlags;

use Exception;
use Illuminate\Support\Arr;
use MmoAndFriends\LaravelTextFlags\Compilers\EachCompiler;
use MmoAndFriends\LaravelTextFlags\Compilers\GlobalCompiler;

class TextFlags
{
    public $models           = [];
    public $modelKeys        = [];    
    public $output           = [];
    protected $flagWrap      = ['{','}'];
    protected $subjectsBag   = [];
    protected $reservedWords = [];
    protected $parsers       = [];

    public function __construct()
    {
        $this->reservedWords = array_merge(EachCompiler::RESERVED_WORDS);
        $this->parsers = [
            'global' => new GlobalCompiler($this),
            'each'   => new EachCompiler($this)
        ];
    }

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
     * Hydrate the models and verify if isn't a reserved word
     * 
     * @throws \Execption
     * @return \MmoAndFriends\LaravelTextFlags\TextFlags
     */
    public function hydrate($models)
    {
        foreach ($models as $modelName => $value) {
            if (in_array($modelName, $this->reservedWords)) {
                $this->throwsAnError(__FUNCTION__, "reserved word \"{$modelName}\"!");
            }
            $this->models[$modelName] = $value;
        }
        
        $this->modelKeys = array_keys($this->models);
        
        $this->foreEachCompiler('buildRegExp');

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
        if (!is_string($text) && !is_array($text)) {
            $this->throwsAnError(__FUNCTION__, 'Expects a string or array of strings, ['.gettype($text).'] obtained.');
        }

        $this->subjectsBag[] = $text;
        $this->subjectsBag   = Arr::flatten($this->subjectsBag);

        foreach ($this->subjectsBag as $subject) {            
            $this->foreEachCompiler('match', $subject);
        }

        $this->output = $this->subjectsBag;

        return $this;
    }
   
    /**
     * Replace flags of the input text
     * 
     * @param boolean $reset Reset all attributes except the output
     * @return array|string
     */
    public function apply($reset = true)
    {
        $this->foreEachCompiler('apply');
        
        if ($reset) {
            $this->foreEachCompiler('reset');            
        }
        
        if (count($this->output) == 1) {
            return $this->output[0];
        }

        return array_values($this->output);
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
            $_value = Arr::get($obj, $attribute, $default ?? []);
        }elseif(strpos($attribute, '.') !== false){
            $_value = function_exists('object_get') ? object_get($obj, $attribute, $default) : array_reduce(explode('.', $attribute), function ($o, $attr) { return $o->$attr; }, $obj);
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
    public function throwsAnError($fnName, string $message, int $code = 1)
    {
        throw new Exception("TextFlags[{$fnName}] {$message}", $code);      
    }

    public function foreEachCompiler($callAcction, ...$args)
    {
        foreach ($this->parsers as $parser) {
            $parser->{$callAcction}(...$args);
        }

        return $this;
    }

    public function getFlagWrap($side)
    {
        return $this->flagWrap[$side];
    }

    public function getLeftWrap()
    {
        return $this->getFlagWrap(0);
    }

    public function getRightWrap()
    {
        return $this->getFlagWrap(1);
    }
    
    public function getModels()
    {
        return $this->models;
    }

    public function getModelKeys()
    {
        return $this->modelKeys;
    }
}
