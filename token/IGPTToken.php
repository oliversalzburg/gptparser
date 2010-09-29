<?php
  interface IGPTToken {
    static function parse( $tokens );

    /**
    * Render the content of the token.
    */
    function render();
  }
?>
