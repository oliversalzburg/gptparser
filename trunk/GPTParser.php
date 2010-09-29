<?php
  require_once( dirname( __FILE__ ) . "/error/GPTParserException.php" );
  require_once( dirname( __FILE__ ) . "/token/GPTParserLibrary.php" );
  require_once( dirname( __FILE__ ) . "/GPTParserContext.php" );

  class GPTParser {
    /**
    * Is the parser initialized?
    *
    * @var bool
    */
    private $isInitialized = false;
    /**
    * Are we in debug mode?
    *
    * @var bool
    */
    public $isDebug = false;

    /**
    * Callback for error logging.
    *
    * @var mixed
    */
    public $errorLog;
    /**
    * Callback for debug logging.
    *
    * @var mixed
    */
    public $debugLog;

    /**
    * The current scope of the parser.
    *
    * @var mixed
    */
    private $scope;
    /**
    * The stack of scopes.
    *
    * @var mixed
    */
    private $scopes;

    /**
    * Last recorded whitespace depth.
    *
    * @var mixed
    */
    private $lastWhitespace;

    /**
    * The GPTParserContext instance for this parser.
    *
    * @var GPTParserContext
    */
    private $context;
    /**
    * The overall result of the parsing operation.
    *
    * @var string
    */
    private $result;
    /**
    * The known authentication ports
    *
    * @var mixed
    */
    private $ports;

    /**
    * Default constructor
    *
    */
    public function  __construct() {
    }

    /**
    * Initialize the parser.
    *
    */
    public function init() {
      // TODO: Register parsers

      $this->isInitialized = true;
    }

    /**
    * Parse a file with authentication definitions.
    *
    * @param string $filename The name of the file to parse.
    */
    public function parseFile( $filename ) {
      if( $this->isInitialized == false ) throw new GPTParserException( "GPT parser not initialized. Call init() first." );

      $lines = file( $filename, FILE_IGNORE_NEW_LINES );

      $this->context = GPTParserContext::get();
      $this->context->Filename  = $filename;
      $this->context->Parser    = $this;

      $this->lastWhitespace = 0;
      $this->scopes = array();

      $this->ports = array();

      // Iterate over all lines
      foreach( $lines as $lineNumber => $line ) {
        $this->internalParse( $lineNumber, $line );
      }

      $result = "";

      // Render result
      foreach( $this->ports as $port ) {
        $result .= $port->render() . "\n";
      }

      return $result;
    }

    /**
    * Parsing core method.
    *
    * @param int $lineNumber The current line number.
    * @param string $line The line to parse.
    */
    public function internalParse( $lineNumber, $line ) {
      $this->context->Line = $lineNumber + 1;

      if( $this->isDebug ) call_user_func( $this->debugLog, $line );

      // Skip comments
      $isComment = preg_match( "~^\s*((//)|(#)|(;))~", $line, $matches );
      if( 1 == $isComment ) return;

      // Find indentation
      $lineLength = strlen( $line );
      $found      = preg_match( "/^(\s)+/", $line, $matches );
      if( 0 == $lineLength ) {
        // Skip empty lines
        if( 0 == $lineLength ) return;

      } else {
        // Adjust scope
        $whiteSpace = ( isset( $matches[ 0 ] ) ) ? $matches[ 0 ] : "";
        if( strlen( $whiteSpace ) > $this->context->Scope->WhitespaceDepth ) {
          $this->scopes[] = $this->result;
          $this->result->WhitespaceDepth = strlen( $whiteSpace );
          //$this->lastWhitespace = strlen( $whiteSpace );
          if( $this->isDebug ) call_user_func( $this->debugLog, "Adjusting scope downwards" );

        } //else if( strlen( $whiteSpace ) < $this->context->Scope->WhitespaceDepth ) {
        while( strlen( $whiteSpace ) < $this->scopes[ count( $this->scopes ) - 1 ]->WhitespaceDepth ) {
          array_pop( $this->scopes );
          //$this->lastWhitespace = strlen( $whiteSpace );
          if( $this->isDebug ) call_user_func( $this->debugLog, "Adjusting scope upwards" );
        }
        //}
      }

      // Set resulting scope in parser context.
      if( 0 == count( $this->scopes ) ) {
        $this->context->Scope = null;

      } else {
        $this->context->Scope = $this->scopes[ count( $this->scopes ) - 1 ];
      }

      if( $this->isDebug ) call_user_func( $this->debugLog, "Current scope type: " . get_class( $this->context->Scope ) );

      // Remove excessive whitespace
      $line = preg_replace( "/\s+/", " ", trim( $line ) );

      // Split into tokens
      $tokens = explode( " ", $line );
      $token  = $tokens[ 0 ];

      if( $this->isDebug ) call_user_func( $this->debugLog, "Parsing line." );
      // Construct parser and parse tokens
      $parser = GPTParserLibrary::parserFromToken( $token );
      if( $this->isDebug ) call_user_func( $this->debugLog, "Parser: " . $parser );
      $this->result = call_user_func( array( $parser, "parse" ), $tokens );

      if( "PortToken" == get_class( $this->result ) ) {
        $this->ports[] = $this->result;
      }

      return $this->result;
    }
  }
?>