<?php
/*-------------------------------------------------------+
| SYSTOPIA Mailingtools Extension                        |
| Copyright (C) 2020 SYSTOPIA                            |
| Author: B. Endres (endres@systopia.de)               |
+--------------------------------------------------------+
| This program is released as free software under the    |
| Affero GPL license. You can redistribute it and/or     |
| modify it under the terms of this license which you    |
| can read by viewing the included agpl.txt or online    |
| at www.gnu.org/licenses/agpl.html. Removal of this     |
| copyright header is strictly prohibited without        |
| written permission from the original author(s).        |
+--------------------------------------------------------*/


use CRM_Mailingtools_ExtensionUtil as E;

/**
 * Class CRM_Mailingtools_EmailVerifier
 */
class CRM_Mailingtools_RegexToken {

  /**
   * Maximum amount of regex token definitions
   */
  public const MT_REGEX_TOKEN_COUNT  = 5;
  public const REGEX_DELIMITER  = '#';
  public const OPERATOR_API3    = 'api3';     // API3 call
  public const OPERATOR_STATIC  = 'static';   // static function call
  public const OPERATOR_REPLACE = 'replace';  // preg_replace call

  public const VALUE_STATIC_FUNCTION = '/^(?P<class>[a-zA-Z_]+)::(?P<function>[a-zA-Z_]+)$/';
  public const VALUE_API_CALL        = '/^(?P<entity>[a-zA-Z]+)[.](?P<action>[a-zA-Z_]+)$/';

  /**
   * Check if this regex tokens are enabled
   * @return bool true if enabled
   */
  public static function isEnabled() {
    $defs = self::getTokenDefinitions();
    return !empty($defs);
  }

  /**
   * Get the current token definition specs as an array of
   * [
   *  'def' => (string) regular expression without delimiters
   *  'op'  => (string) operator type (api3, static, replace)
   *  'val' => (string) call spec, e.g. "entity.action", or "class::function"
   * ]
   * @return array list of such specs
   */
  public static function getTokenDefinitions() {
    static $token_definitions = NULL;
    if ($token_definitions === NULL) {
      $value = Civi::settings()->get('mailingtools_regex_tokens');
      if (empty($value) || !is_array($value)) {
        $token_definitions = [];
      } else {
        $token_definitions = $value;
      }
    }
    return $token_definitions;
  }

  /**
   * Set the current token definition specs
   * @param $token_definitions array see getTokenDefinitions
   */
  public static function setTokenDefinitions($token_definitions) {
    Civi::settings()->set('mailingtools_regex_tokens', $token_definitions);
  }

  /**
   * Do a replace of all tokens in the given string
   *
   * @param $text    string the source text
   * @param $context array  context information to be passed on to the value functions
   * @return string the input string with all tokens replaced
   */
  public static function tokenReplace($text, $context = []) {
    $token_definitions = self::getTokenDefinitions();
    foreach ($token_definitions as $token_definition) {
      while (preg_match(self::REGEX_DELIMITER . $token_definition['def'] . self::REGEX_DELIMITER, $text, $match)) {

        // token found -> get the replacement value
        $matched_string = $match[0];
        $match_data = array_merge($match, $context);
        $value = self::getTokenValue($matched_string, $token_definition, $match_data);

        // get the offsets and do the replacement
        if ($value != $matched_string) {
          preg_match(self::REGEX_DELIMITER . $token_definition['def'] . self::REGEX_DELIMITER, $text, $offsets, PREG_OFFSET_CAPTURE);
          $text = substr($text, 0, $offsets[0][1]) . $value . substr($text, $offsets[0][1] + strlen($offsets[0][0]));
        }
      }
    }
    return $text;
  }

  /**
   * Calculate the new value for the given token_definition
   * @param $matched_string   string the string matched
   * @param $token_definition array  token definition
   * @param $context          array  context information passed trough to the functions
   * @return string the calculated value
   */
  public static function getTokenValue($matched_string, $token_definition, $context) {
    $params = array_merge(['matched_string' => $matched_string], $token_definition, $context);
    switch ($token_definition['op']) {
      case self::OPERATOR_API3:
        if (preg_match(self::VALUE_API_CALL, $token_definition['val'], $match)) {
          // compile $params
          try {
            $result = civicrm_api3($match['entity'], $match['action'], $params);
            if (is_string($result)) {
              return $result;
            }
            if (is_array($result)) {
              if (isset($result['value'])) {
                return $result['value'];
              }
              if (isset($result['result'])) {
                return $result['result'];
              }
            }
          } catch (Exception $ex) {
            // nothing to do...
          }
        }
        return 'ERROR';

      case self::OPERATOR_STATIC:
        if (preg_match(self::VALUE_STATIC_FUNCTION, $token_definition['val'], $match)) {
          return call_user_func($token_definition['val'], $params);
        } else {
          return 'ERROR';
        }


      case self::OPERATOR_REPLACE:
        try {
          return preg_replace(self::REGEX_DELIMITER . $token_definition['def'] . self::REGEX_DELIMITER, $matched_string, $token_definition['val']);
        } catch (Exception $ex) {
          return 'ERROR';
        }

      default:
        return 'UNDEFINED';
    }

  }

  /**
   * Verify the presented token definition, and return an
   *  error string if not valid
   *
   * @param $token_definition array definition, see getTokenDefinitions
   * @return string|false error or false ("all clear")
   */
  public static function verifyTokenDefinition($token_definition) {
    // test if present
    if (empty($token_definition['def'])) {
      return E::ts("Incomplete definition: definition (regular expression) missing");
    }
    if (empty($token_definition['op'])) {
      return E::ts("Incomplete definition: value type missing");
    }
    if (empty($token_definition['val'])) {
      return E::ts("Incomplete definition: value missing");
    }

    // verify definition (regex)
    try {
      preg_match(self::REGEX_DELIMITER . $token_definition['def'] . self::REGEX_DELIMITER, 'doesntmatter');
    } catch (Exception $ex) {
      return E::ts("Incomplete definition: definition is not a valid regular expression");
    }

    // verify operation
    switch ($token_definition['op']) {
      case self::OPERATOR_API3:
        if (preg_match(self::VALUE_API_CALL, $token_definition['val'], $match)) {
          // verify api entity.action
          try {
            $actions = civicrm_api3($match['entity'], 'getactions');
            if (empty($actions['values'])) {
              return E::ts("API3 action '%1' not found in entity '%2'", [1 => $match['entity'], 2 => $match['action']]);
            }
            $action_found = FALSE;
            $our_action = strtolower($match['action']);
            foreach ($actions['values'] as $known_action) {
              if (strtolower($known_action) == $our_action) {
                $action_found = TRUE;
                break;
              }
            }
            if (!$action_found) {
              return E::ts("API3 action '%1' not found in entity '%2'", [1 => $match['entity'], 2 => $match['action']]);
            }
          } catch (Exception $ex) {
            return E::ts("API3 entity '%1' not found", [1 => $match['entity']]);
          }
        } else {
          return E::ts("API3 action should be defined as 'entity.action'");
        }
        break;


      case self::OPERATOR_STATIC:
        if (preg_match(self::VALUE_STATIC_FUNCTION, $token_definition['val'], $match)) {
          if (!class_exists($match['class'])) {
            return E::ts("Class '%1' not found", [1 => $match['class']]);
          }
          if (!method_exists($match['class'], $match['function'])) {
            return E::ts("Function '%1' not found", [1 => $token_definition['val']]);
          }
        } else {
          return E::ts("Function definition should be 'SomeClass::someFunction'");
        }
        break;


      case self::OPERATOR_REPLACE:
        try {
          preg_replace(self::REGEX_DELIMITER . $token_definition['def'] . self::REGEX_DELIMITER, $token_definition['val'], 'doesntmatter');
        } catch (Exception $ex) {
          return E::ts("Ill-defined replace expression");
        }
        break;

      default:
        return E::ts("Unknown value type/operator '%1'", [1 => $token_definition['op']]);
    }
  }
}