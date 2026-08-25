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

declare(strict_types = 1);

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
  // API3 call
  public const OPERATOR_API3 = 'api3';
  // Static method call
  public const OPERATOR_STATIC = 'static';
  // preg_replace call
  public const OPERATOR_REPLACE = 'replace';

  public const VALUE_STATIC_FUNCTION = '/^(?P<class>[a-zA-Z_]+)::(?P<function>[a-zA-Z_]+)$/';
  public const VALUE_API_CALL        = '/^(?P<entity>[a-zA-Z]+)[.](?P<action>[a-zA-Z_]+)$/';

  /**
   * Check if this regex tokens are enabled
   * @return bool true if enabled
   */
  public static function isEnabled() {
    $defs = self::getTokenDefinitions();
    return $defs !== [];
  }

  /**
   * Get the current token definition specs as an array of
   * [
   *  'def' => (string) regular expression without delimiters
   *  'op'  => (string) operator type (api3, static, replace)
   *  'val' => (string) call spec, e.g. "entity.action", or "class::function"
   * ]
   * @return array<int, array{def: string, op: string, val: string}> list of such specs
   */
  public static function getTokenDefinitions() {
    $value = Civi::settings()->get('mailingtools_regex_tokens');
    if (!is_array($value) || $value === []) {
      return [];
    }
    /** @var array<int, array{def: string, op: string, val: string}> $value */
    return $value;
  }

  /**
   * Set the current token definition specs
   * @param array<int, array{def: string, op: string, val: string}> $token_definitions see getTokenDefinitions
   * @return void
   */
  public static function setTokenDefinitions($token_definitions) {
    Civi::settings()->set('mailingtools_regex_tokens', $token_definitions);
  }

  /**
   * Do a replace of all tokens in the given string
   *
   * @param string $text    the source text
   * @param array<string, mixed> $context  context information to be passed on to the value functions
   * @return string the input string with all tokens replaced
   */
  public static function tokenReplace($text, $context = []) {
    $token_definitions = self::getTokenDefinitions();
    foreach ($token_definitions as $token_definition) {
      $regex = self::REGEX_DELIMITER . $token_definition['def'] . self::REGEX_DELIMITER;
      $offset = 0;
      while (preg_match($regex, $text, $match, PREG_OFFSET_CAPTURE, $offset) === 1) {

        // token found -> get the replacement value
        $matched_string = $match[0][0];
        $match_position = $match[0][1];
        $match_groups = [];
        foreach ($match as $group_key => $group) {
          $match_groups[$group_key] = $group[0];
        }
        $match_data = array_merge($match_groups, $context);
        $value = (string) self::getTokenValue($matched_string, $token_definition, $match_data);

        $text = substr($text, 0, $match_position) . $value . substr($text, $match_position + strlen($matched_string));
        $offset = $match_position + strlen($value);
      }
    }
    return $text;
  }

  /**
   * Calculate the new value for the given token_definition
   * @param string $matched_string   the string matched
   * @param array{def: string, op: string, val: string} $token_definition  token definition
   * @param array<string, mixed> $context           context information passed trough to the functions
   * @return string the calculated value
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
  public static function getTokenValue($matched_string, $token_definition, $context) {
    $params = array_merge(['matched_string' => $matched_string], $token_definition, $context);
    switch ($token_definition['op']) {
      case self::OPERATOR_API3:
        if ((bool) preg_match(self::VALUE_API_CALL, $token_definition['val'], $match)) {
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
          }
          catch (Exception $ex) {
            // @ignoreException
          }
        }
        return 'ERROR';

      case self::OPERATOR_STATIC:
        if ((bool) preg_match(self::VALUE_STATIC_FUNCTION, $token_definition['val'], $match)) {
          // @phpstan-ignore argument.type
          return CRM_Mailingtools_Utils::toString(call_user_func($token_definition['val'], $params));
        }
        else {
          return 'ERROR';
        }

      case self::OPERATOR_REPLACE:
        $replaced = @preg_replace(
          self::REGEX_DELIMITER . $token_definition['def'] . self::REGEX_DELIMITER,
          $matched_string,
          $token_definition['val']
        );
        return $replaced ?? 'ERROR';

      default:
        return 'UNDEFINED';
    }

  }

  /**
   * Check if a definition value is missing: not set, an empty string, or
   * the string '0' (same falsy set empty() would use for a string).
   *
   * @param array{def: string, op: string, val: string} $token_definition
   * @param string $key
   */
  private static function isEmptyDefinitionValue($token_definition, $key): bool {
    $value = $token_definition[$key] ?? NULL;
    return $value === NULL || $value === '' || $value === '0';
  }

  /**
   * Verify the presented token definition, and return an
   *  error string if not valid
   *
   * @param array{def: string, op: string, val: string} $token_definition definition, see getTokenDefinitions
   * @return string|false error or false ("all clear")
   */
  // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh, Generic.Metrics.NestingLevel.TooHigh
  public static function verifyTokenDefinition($token_definition) {
    // test if present
    if (self::isEmptyDefinitionValue($token_definition, 'def')) {
      return E::ts('Incomplete definition: definition (regular expression) missing');
    }
    if (self::isEmptyDefinitionValue($token_definition, 'op')) {
      return E::ts('Incomplete definition: value type missing');
    }
    if (self::isEmptyDefinitionValue($token_definition, 'val')) {
      return E::ts('Incomplete definition: value missing');
    }

    // verify definition (regex)
    $regex_check = @preg_match(
      self::REGEX_DELIMITER . $token_definition['def'] . self::REGEX_DELIMITER,
      'doesntmatter'
    );
    if ($regex_check === FALSE) {
      return E::ts('Incomplete definition: definition is not a valid regular expression');
    }

    // verify operation
    switch ($token_definition['op']) {
      case self::OPERATOR_API3:
        if ((bool) preg_match(self::VALUE_API_CALL, $token_definition['val'], $match)) {
          // verify api entity.action
          try {
            $actions = civicrm_api3($match['entity'], 'getactions');
            if ($actions['values'] === []) {
              return E::ts("API3 action '%1' not found in entity '%2'", [1 => $match['entity'], 2 => $match['action']]);
            }
            $action_found = FALSE;
            $our_action = strtolower($match['action']);
            foreach ($actions['values'] as $known_action) {
              if (strtolower($known_action) === $our_action) {
                $action_found = TRUE;
                break;
              }
            }
            if (!$action_found) {
              return E::ts("API3 action '%1' not found in entity '%2'", [1 => $match['entity'], 2 => $match['action']]);
            }
          }
          catch (Exception $ex) {
            // @ignoreException
            return E::ts("API3 entity '%1' not found", [1 => $match['entity']]);
          }
        }
        else {
          return E::ts("API3 action should be defined as 'entity.action'");
        }
        break;

      case self::OPERATOR_STATIC:
        if ((bool) preg_match(self::VALUE_STATIC_FUNCTION, $token_definition['val'], $match)) {
          if (!class_exists($match['class'])) {
            return E::ts("Class '%1' not found", [1 => $match['class']]);
          }
          if (!method_exists($match['class'], $match['function'])) {
            return E::ts("Function '%1' not found", [1 => $token_definition['val']]);
          }
        }
        else {
          return E::ts("Function definition should be 'SomeClass::someFunction'");
        }
        break;

      case self::OPERATOR_REPLACE:
        if (@preg_replace(
          self::REGEX_DELIMITER . $token_definition['def'] . self::REGEX_DELIMITER,
          $token_definition['val'],
          'doesntmatter'
        ) === NULL) {
          return E::ts('Ill-defined replace expression');
        }
        break;

      default:
        return E::ts("Unknown value type/operator '%1'", [1 => $token_definition['op']]);
    }
    return FALSE;
  }

}
