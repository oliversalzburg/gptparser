<?php
  require_once( dirname( __FILE__ ) . "/../error/GPTParserException.php" );

  class GPTParserLibrary {
    private static $instance;

    private $parsers;

    private static function get() {
      if( !isset( self::$instance ) ) self::$instance = new GPTParserLibrary();
      return self::$instance;
    }

    public static function registerParser( $token, $className ) {
      self::get()->parsers[ $token ] = $className;
    }

    public static function parserFromToken( $token ) {
      if( !isset( self::get()->parsers[ $token ] ) ) throw new GPTParserException( sprintf( "Unknown token '%s' at %s:%s.", $token, GPTParserContext::get()->Filename, GPTParserContext::get()->Line ) );
      $className = self::get()->parsers[ $token ];
      return $className;
    }
  }
?>