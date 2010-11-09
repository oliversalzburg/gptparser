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
    * User-supplied callback function to execute for any resulting line.
    *
    * @var mixed
    */
    public $PostProcessor;

    /**
    * The name of the parser class that represents root-level nodes.
    *
    * @var mixed
    */
    private $rootType;

    /**
    * The known, parsed root nodes.
    * These are known, root-level elements that have been parsed and will
    * be called to later generate the output.
    *
    * @var mixed
    */
    private $rootNodes;

    /**
    * Default constructor
    *
    */
    public function  __construct() {
    }

    /**
    * Initialize the parser.
    *
    * @param string $rootType The name of the parser class that represents a root-level node.
    * @param array $parsers An associative array that mapes tokens to parser class names.
    */
    public function init( $rootType, $parsers ) {
      foreach( $parsers as $token => $parser ) {
        GPTParserLibrary::registerParser( $token, $parser );
      }

      $this->rootType = $rootType;
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

      $this->rootNodes = array();

      // Iterate over all lines
      foreach( $lines as $lineNumber => $line ) {
        $this->internalParse( $lineNumber, $line );
      }

      $result = "";

      // Render result
      foreach( $this->rootNodes as $rootNode ) {
        $result .= $rootNode->render( $this->PostProcessor );
      }

      return $result;
    }

    /**
    * Injects an arbitrary markup line into the parser at the current scope.
    *
    * @param int $asLineNumber Claim the inserted line originated from this line number.
    * @param string $line The line to inject.
    */
    public function injectAtCurrentScope( $asLineNumber, $line ) {
      $line = str_repeat( " ", $this->context->Scope->WhitespaceDepth ) . $line;
      $this->internalParse( $asLineNumber, $line );
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
      $isComment = preg_match( "~^\\s*((//)|(#)|(;))~", $line, $matches );
      if( 1 == $isComment ) return;

      // Find indentation
      $lineLength = strlen( $line );
      $found      = preg_match( "/^(\\s)+/", $line, $matches );
      if( 0 == $lineLength ) {
        // Skip empty lines
        if( 0 == $lineLength ) return;

      } else {
        // If we have a valid result from a previous pass,
        // but no scope is stored in the context, store
        // that scope in the context
        if( null == $this->context->Scope && null != $this->result ) {
          $this->context->Scope = $this->result;
        }
        // Adjust scope
        $whiteSpace = ( isset( $matches[ 0 ] ) ) ? $matches[ 0 ] : "";
        if( null != $this->context->Scope && strlen( $whiteSpace ) > $this->context->Scope->WhitespaceDepth ) {
          $this->scopes[] = $this->result;
          $this->result->WhitespaceDepth = strlen( $whiteSpace );
          if( $this->isDebug ) call_user_func( $this->debugLog, "Adjusting scope downwards" );
        }
        while( count( $this->scopes ) > 0 && strlen( $whiteSpace ) < $this->scopes[ count( $this->scopes ) - 1 ]->WhitespaceDepth ) {
          array_pop( $this->scopes );
          if( $this->isDebug ) call_user_func( $this->debugLog, "Adjusting scope upwards" );
        }
      }

      // Set resulting scope in parser context.
      if( 0 == count( $this->scopes ) ) {
        $this->context->Scope = null;

      } else {
        $this->context->Scope = $this->scopes[ count( $this->scopes ) - 1 ];
      }

      if( $this->isDebug ) call_user_func( $this->debugLog, "Current scope type: " . get_class( $this->context->Scope ) );

      // Remove excessive whitespace
      $line = preg_replace( "/\\s+/", " ", trim( $line ) );

      // Split into tokens
      $tokens = explode( " ", $line );
      $token  = $tokens[ 0 ];

      if( $this->isDebug ) call_user_func( $this->debugLog, "Parsing line." );
      // Construct parser and parse tokens
      $parser = GPTParserLibrary::parserFromToken( $token );
      if( $this->isDebug ) call_user_func( $this->debugLog, "Parser: " . $parser );
      $this->result = call_user_func( array( $parser, "parse" ), $tokens );

      if( $this->rootType == get_class( $this->result ) ) {
        $this->rootNodes[] = $this->result;
      }

      return $this->result;
    }
  }
?>