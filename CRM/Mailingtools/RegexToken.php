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
  const MT_REGEX_TOKEN_COUNT  = 10;
  const REGEX_DELIMITER  = '#';
  const OPERATOR_API3    = 'api3';     // API3 call
  const OPERATOR_STATIC  = 'static';   // static function call
  const OPERATOR_REPLACE = 'replace';  // preg_replace call

  const VALUE_STATIC_FUNCTION = '/^(?P<class>[a-zA-Z_]+)::(?P<function>[a-zA-Z_]+)$/';
  const VALUE_API_CALL        = '/^(?P<entity>[a-zA-Z]+)[.](?P<action>[a-zA-Z_]+)$/';


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
    $value = Civi::settings()->get('mailingtools_regex_tokens');
    if (empty($value) || !is_array($value)) {
      return [];
    } else {
      return $value;
    }
  }

  /**
   * Set the current token definition specs
   * @param $token_definitions array see getTokenDefinitions
   */
  public static function setTokenDefinitions($token_definitions) {
    Civi::settings()->set('mailingtools_regex_tokens', $token_definitions);
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
          if (!function_exists($match['class'])) {
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