<?php
  class GPTParserContext {
    /**
    * The name of the file that is currently being processed.
    *
    * @var string
    */
    public $Filename;

    /**
    * The line (number) that is currently being parsed.
    *
    * @var int
    */
    public $Line;

    /**
    * The current scope of the parser.
    *
    * @var mixed
    */
    public $Scope;

    /**
    * The GPTParser instance this context belongs to.
    *
    * @var GPTParser
    */
    public $Parser;

    private static $instance;

    /**
    * Retrieve the singleton GPTParserContext instance.
    *
    */
    public static function get() {
      if( !isset( self::$instance ) ) self::$instance = new GPTParserContext();
      return self::$instance;
    }
  }
?>