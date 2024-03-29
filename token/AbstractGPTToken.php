<?php
  require_once( dirname( __FILE__ ) . "/../error/GPTParserException.php" );

  class AbstractGPTToken {
    public $WhitespaceDepth;

    protected $scope;

    protected static function expectParameters() {
      $params = func_get_args();
      $supplied = $params[ 0 ];
      for( $paramIndex = 1; $paramIndex < count( $params ); ++$paramIndex ) {
        if( !isset( $supplied[ $paramIndex - 1 ] ) ) {
          throw new GPTParserException( sprintf( "Missing parameter '%s' at %s:%s.", $params[ $paramIndex ], GPTParserContext::get()->Filename, GPTParserContext::get()->Line ) );
        }
      }
    }

    /**
    * Invoke an arbitrary amount of resulting output lines on the user supplied post processor.
    */
    protected function callPostProcessCallback() {
      $params = func_get_args();
      if( null == $params ) return;
      $callback = array_shift( $params );
      foreach( $params as $query ) {
        call_user_func( $callback, $query );
      }
    }

    /**
    * Works just like implode() but ignores null values in array.
    *
    * @param mixed $glue
    * @param mixed $array
    */
    protected function implodeEx( $glue, $array ) {
      while( count( $array ) >= 1 && null == $array[ count( $array ) - 1 ] ) {
        array_pop( $array );
      }
      $result = "";
      for( $i = 0; $i < count( $array ) - 1; ++$i ) {
        if( null == $array[ $i ] ) continue;
        $result .= $array[ $i ];
        $result .= $glue;
      }
      if( count( $array ) >= 1 ) {
        $result .= $array[ count( $array ) - 1 ];
      }
      return $result;
    }
  }
?>
